<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'create';

if ($action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) respond(['ok' => false, 'msg' => 'Invalid bill']);
    $items = $data['items'] ?? [];
    if (!is_array($items) || !$items) respond(['ok' => false, 'msg' => 'Add at least one product']);

    $customerId = (int)($data['customer_id'] ?? 0);
    $customerId = $customerId > 0 ? $customerId : null;
    $mode = (string)($data['payment_mode'] ?? 'cash');
    if (!in_array($mode, ['cash', 'upi', 'credit'], true)) respond(['ok' => false, 'msg' => 'Invalid payment method']);
    if ($mode === 'credit' && !$customerId) respond(['ok' => false, 'msg' => 'Choose a customer for credit bills']);

    $dueDate = $mode === 'credit' ? ($data['due_date'] ?? null) : null;
    if ($dueDate !== null && $dueDate !== '') {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);
        if (!$date || $date->format('Y-m-d') !== $dueDate) respond(['ok' => false, 'msg' => 'Invalid due date']);
    } else $dueDate = null;

    $qtyById = [];
    foreach ($items as $item) {
        $id = (int)($item['product_id'] ?? 0);
        $qty = (int)($item['qty'] ?? 0);
        if ($id < 1 || $qty < 1) respond(['ok' => false, 'msg' => 'Invalid product or quantity']);
        $qtyById[$id] = ($qtyById[$id] ?? 0) + $qty;
    }

    $discount = (float)($data['discount'] ?? 0);
    if (!is_finite($discount) || $discount < 0) respond(['ok' => false, 'msg' => 'Invalid discount']);
    $gst = getSetting($mysqli, 'gst_enabled', '0') === '1'
        ? max(0, min(100, (float)getSetting($mysqli, 'default_gst', '0'))) : 0;

    $mysqli->begin_transaction();
    try {
        $lookup = $mysqli->prepare('SELECT name, selling_price, purchase_price, stock FROM products WHERE id=? FOR UPDATE');
        $lines = [];
        $subtotal = 0;
        $profit = 0;

        foreach ($qtyById as $id => $qty) {
            $lookup->bind_param('i', $id);
            $lookup->execute();
            $product = $lookup->get_result()->fetch_assoc();
            if (!$product) throw new RuntimeException('One of the products no longer exists');
            if ($qty > (int)$product['stock']) throw new RuntimeException('Insufficient stock for ' . $product['name']);
            $price = (float)$product['selling_price'];
            $purchase = (float)$product['purchase_price'];
            $lines[] = [$id, $qty, $price, $purchase];
            $subtotal += $price * $qty;
            $profit += ($price - $purchase) * $qty;
        }

        if ($discount > $subtotal) throw new RuntimeException('Discount cannot exceed subtotal');
        $tax = round(($subtotal - $discount) * $gst / 100, 2);
        $total = round($subtotal - $discount + $tax, 2);
        $profit = round($profit - $discount, 2);
        $billNo = genBillNo($mysqli);

        $bill = $mysqli->prepare('INSERT INTO bills
            (bill_no, customer_id, subtotal, discount, tax, total, payment_mode, profit, due_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $bill->bind_param('siddddsds', $billNo, $customerId, $subtotal, $discount, $tax, $total, $mode, $profit, $dueDate);
        $bill->execute();
        $billId = $mysqli->insert_id;

        $insertItem = $mysqli->prepare('INSERT INTO bill_items (bill_id, product_id, qty, price, purchase_price) VALUES (?,?,?,?,?)');
        $reduceStock = $mysqli->prepare('UPDATE products SET stock=stock-? WHERE id=?');
        foreach ($lines as [$id, $qty, $price, $purchase]) {
            $insertItem->bind_param('iiidd', $billId, $id, $qty, $price, $purchase);
            $insertItem->execute();
            $reduceStock->bind_param('ii', $qty, $id);
            $reduceStock->execute();
        }
        $mysqli->commit();
        respond(['ok' => true, 'bill_no' => $billNo, 'bill_id' => $billId]);
    } catch (Throwable $e) {
        $mysqli->rollback();
        respond(['ok' => false, 'msg' => $e instanceof RuntimeException ? $e->getMessage() : 'Could not save bill. Please retry.']);
    }
}

if ($action === 'get') {
    $id = (int)$_GET['id'];
    $bill = $mysqli->query("SELECT b.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address, c.gst_number as customer_gst
        FROM bills b LEFT JOIN customers c ON c.id=b.customer_id WHERE b.id=$id")->fetch_assoc();
    $items = [];
    $r = $mysqli->query("SELECT bi.*, p.name as product_name FROM bill_items bi JOIN products p ON p.id=bi.product_id WHERE bi.bill_id=$id");
    while ($row = $r->fetch_assoc()) $items[] = $row;
    $bill['items'] = $items;
    respond(['ok' => true, 'bill' => $bill]);
}

if ($action === 'list') {
    $r = $mysqli->query("SELECT b.*, c.name as customer_name FROM bills b LEFT JOIN customers c ON c.id=b.customer_id ORDER BY b.id DESC LIMIT 100");
    $list = [];
    while ($row = $r->fetch_assoc()) $list[] = $row;
    respond(['ok' => true, 'data' => $list]);
}