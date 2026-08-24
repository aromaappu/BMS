<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $r = $mysqli->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.name");
    $list = [];
    while ($row = $r->fetch_assoc()) $list[] = $row;
    respond(['ok' => true, 'data' => $list]);
}

if ($action === 'categories') {
    $r = $mysqli->query("SELECT * FROM categories ORDER BY name");
    $list = [];
    while ($row = $r->fetch_assoc()) $list[] = $row;
    respond(['ok' => true, 'data' => $list]);
}

if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name']);
    $cat = (int)($_POST['category_id'] ?? 0);
    $pp = (float)$_POST['purchase_price'];
    $sp = (float)$_POST['selling_price'];
    $stock = (int)$_POST['stock'];
    $threshold = (int)($_POST['low_stock_threshold'] ?? 5);
    
    if ($id) {
        $stmt = $mysqli->prepare("UPDATE products SET name=?,category_id=?,purchase_price=?,selling_price=?,stock=?,low_stock_threshold=? WHERE id=?");
        $stmt->bind_param("siddiii", $name, $cat, $pp, $sp, $stock, $threshold, $id);
    } else {
        $stmt = $mysqli->prepare("INSERT INTO products (name,category_id,purchase_price,selling_price,stock,low_stock_threshold) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("sidddi", $name, $cat, $pp, $sp, $stock, $threshold);
    }
    respond(['ok' => $stmt->execute()]);
}

if ($action === 'adjust_stock') {
    $id = (int)$_POST['id'];
    $qty = (int)$_POST['qty'];
    $mysqli->query("UPDATE products SET stock = stock + $qty WHERE id=$id");
    respond(['ok' => true]);
}

if ($action === 'delete') {
    $id = (int)$_GET['id'];
    $mysqli->query("DELETE FROM products WHERE id=$id");
    respond(['ok' => true]);
}