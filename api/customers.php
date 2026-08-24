<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $r = $mysqli->query("SELECT c.*, COALESCE(SUM(CASE WHEN b.payment_mode='credit' THEN b.total ELSE 0 END) - SUM(COALESCE(cp.amount,0)),0) as credit
        FROM customers c LEFT JOIN bills b ON b.customer_id=c.id AND b.payment_mode='credit'
        LEFT JOIN credit_payments cp ON cp.bill_id=b.id GROUP BY c.id ORDER BY c.name");
    $list = [];
    while ($row = $r->fetch_assoc()) $list[] = $row;
    respond(['ok' => true, 'data' => $list]);
}

if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $addr = trim($_POST['address']);
    $gst = trim($_POST['gst_number']);
    if (!$name) respond(['ok' => false, 'msg' => 'Name required']);
    if ($id) {
        $stmt = $mysqli->prepare("UPDATE customers SET name=?,phone=?,address=?,gst_number=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $phone, $addr, $gst, $id);
    } else {
        $stmt = $mysqli->prepare("INSERT INTO customers (name,phone,address,gst_number) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $name, $phone, $addr, $gst);
    }
    respond(['ok' => $stmt->execute()]);
}

if ($action === 'delete') {
    $id = (int)$_GET['id'];
    $mysqli->query("DELETE FROM customers WHERE id=$id");
    respond(['ok' => true]);
}

if ($action === 'history') {
    $id = (int)$_GET['id'];
    $r = $mysqli->query("SELECT * FROM bills WHERE customer_id=$id ORDER BY created_at DESC");
    $list = [];
    while ($row = $r->fetch_assoc()) $list[] = $row;
    respond(['ok' => true, 'data' => $list]);
}