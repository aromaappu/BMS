<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $r = $mysqli->query("SELECT b.id, b.bill_no, b.total, b.due_date, b.created_at, c.name, c.phone,
        COALESCE(SUM(cp.amount),0) as paid, b.total - COALESCE(SUM(cp.amount),0) as outstanding
        FROM bills b LEFT JOIN customers c ON c.id=b.customer_id
        LEFT JOIN credit_payments cp ON cp.bill_id=b.id
        WHERE b.payment_mode='credit' GROUP BY b.id ORDER BY b.due_date ASC");
    $list = [];
    while ($row = $r->fetch_assoc()) $list[] = $row;
    respond(['ok' => true, 'data' => $list]);
}

if ($action === 'pay') {
    $bill_id = (int)$_POST['bill_id'];
    $amount = (float)$_POST['amount'];
    $note = trim($_POST['note'] ?? '');
    $stmt = $mysqli->prepare("INSERT INTO credit_payments (bill_id,amount,note) VALUES (?,?,?)");
    $stmt->bind_param("ids", $bill_id, $amount, $note);
    respond(['ok' => $stmt->execute()]);
}

if ($action === 'history') {
    $bill_id = (int)$_GET['bill_id'];
    $r = $mysqli->query("SELECT * FROM credit_payments WHERE bill_id=$bill_id ORDER BY paid_at DESC");
    $list = [];
    while ($row = $r->fetch_assoc()) $list[] = $row;
    respond(['ok' => true, 'data' => $list]);
}