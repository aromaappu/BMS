<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'create';

if ($action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);
    $customer_id = (int)($data['customer_id'] ?? 0) ?: 'NULL';
    $items = $data['items'] ?? [];
    $discount = (float)($data['discount'] ?? 0);
    $tax = (float)($data['tax'] ?? 0);
    $mode = $data['payment_mode'] ?? 'cash';
    $due_date = $data['due_date'] ?? 'NULL';
    
    if (empty($items)) respond(['ok' => false, 'msg' => 'No items']);
    
    $subtotal = 0;
    $profit = 0;
    foreach ($items as $it) {
        $subtotal += $it['price'] * $it['qty'];
        $profit += ($it['price'] - $it['purchase_price']) * $it['qty'];
    }
    $total = $subtotal - $discount + $tax;
    $bill_no = genBillNo($mysqli);
    
    $mysqli->begin_transaction();
    try {
        $dueSql = $mode === 'credit' && $due_date !== 'NULL' ? "'$due_date'" : 'NULL';
        $custSql = $customer_id ?: 'NULL';
        $stmt = $mysqli->prepare("INSERT INTO bills (bill_no,customer_id,subtotal,discount,tax,total,payment_mode,profit,due_date) VALUES (?,?,?,?,?,?,?,?, $dueSql)");
        $stmt->bind_param("sidddds", $bill_no, $customer_id, $subtotal, $discount, $tax, $total, $mode, $profit);
        // Fix: need to handle NULL customer
        $sql = "INSERT INTO bills (bill_no,customer_id,subtotal,discount,tax,total,payment_mode,profit,due_date) VALUES ('$bill_no', $custSql, $subtotal, $discount, $tax, $total, '$mode', $profit, $dueSql)";
        $mysqli->query($sql);
        $bill_id = $mysqli->insert_id;
        
        foreach ($items as $it) {
            $pid = (int)$it['product_id'];
            $qty = (int)$it['qty'];
            $price = (float)$it['price'];
            $pp = (float)$it['purchase_price'];
            $stmt = $mysqli->prepare("INSERT INTO bill_items (bill_id,product_id,qty,price,purchase_price) VALUES (?,?,?,?,?)");
            $stmt->bind_param("iiidd", $bill_id, $pid, $qty, $price, $pp);
            $stmt->execute();
            $mysqli->query("UPDATE products SET stock = stock - $qty WHERE id=$pid");
        }
        
        $mysqli->commit();
        respond(['ok' => true, 'bill_no' => $bill_no, 'bill_id' => $bill_id]);
    } catch (Exception $e) {
        $mysqli->rollback();
        respond(['ok' => false, 'msg' => $e->getMessage()]);
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