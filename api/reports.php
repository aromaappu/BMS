<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$type = $_GET['type'] ?? 'daily';
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$product_id = (int)($_GET['product_id'] ?? 0);
$category_id = (int)($_GET['category_id'] ?? 0);

$where = "DATE(b.created_at) BETWEEN '$from' AND '$to'";
if ($product_id) $where .= " AND bi.product_id=$product_id";
if ($category_id) $where .= " AND p.category_id=$category_id";

$data = [];
$labels = [];
$values = [];
$profits = [];

if ($type === 'daily') {
    $r = $mysqli->query("SELECT DATE(b.created_at) d, SUM(b.total) t, SUM(b.profit) p
        FROM bills b LEFT JOIN bill_items bi ON bi.bill_id=b.id LEFT JOIN products p ON p.id=bi.product_id
        WHERE $where GROUP BY d ORDER BY d");
    while ($row = $r->fetch_assoc()) { $labels[] = $row['d']; $values[] = (float)$row['t']; $profits[] = (float)$row['p']; }
} elseif ($type === 'monthly') {
    $r = $mysqli->query("SELECT DATE_FORMAT(b.created_at,'%Y-%m') m, SUM(b.total) t, SUM(b.profit) p
        FROM bills b LEFT JOIN bill_items bi ON bi.bill_id=b.id LEFT JOIN products p ON p.id=bi.product_id
        WHERE $where GROUP BY m ORDER BY m");
    while ($row = $r->fetch_assoc()) { $labels[] = $row['m']; $values[] = (float)$row['t']; $profits[] = (float)$row['p']; }
} elseif ($type === 'yearly') {
    $r = $mysqli->query("SELECT YEAR(b.created_at) y, SUM(b.total) t, SUM(b.profit) p
        FROM bills b LEFT JOIN bill_items bi ON bi.bill_id=b.id LEFT JOIN products p ON p.id=bi.product_id
        WHERE $where GROUP BY y ORDER BY y");
    while ($row = $r->fetch_assoc()) { $labels[] = $row['y']; $values[] = (float)$row['t']; $profits[] = (float)$row['p']; }
} elseif ($type === 'product') {
    $r = $mysqli->query("SELECT p.name, SUM(bi.qty) q, SUM(bi.qty*bi.price) t
        FROM bill_items bi JOIN products p ON p.id=bi.product_id JOIN bills b ON b.id=bi.bill_id
        WHERE $where GROUP BY bi.product_id ORDER BY t DESC");
    while ($row = $r->fetch_assoc()) { $labels[] = $row['name']; $values[] = (float)$row['t']; }
} elseif ($type === 'category') {
    $r = $mysqli->query("SELECT c.name, SUM(bi.qty*bi.price) t
        FROM bill_items bi JOIN products p ON p.id=bi.product_id LEFT JOIN categories c ON c.id=p.category_id
        JOIN bills b ON b.id=bi.bill_id WHERE $where GROUP BY p.category_id ORDER BY t DESC");
    while ($row = $r->fetch_assoc()) { $labels[] = $row['name'] ?? 'Uncategorized'; $values[] = (float)$row['t']; }
} elseif ($type === 'customer') {
    $r = $mysqli->query("SELECT c.name, SUM(b.total) t
        FROM bills b JOIN customers c ON c.id=b.customer_id
        LEFT JOIN bill_items bi ON bi.bill_id=b.id LEFT JOIN products p ON p.id=bi.product_id
        WHERE $where GROUP BY b.customer_id ORDER BY t DESC");
    while ($row = $r->fetch_assoc()) { $labels[] = $row['name']; $values[] = (float)$row['t']; }
} elseif ($type === 'payment') {
    $r = $mysqli->query("SELECT b.payment_mode, SUM(b.total) t
        FROM bills b LEFT JOIN bill_items bi ON bi.bill_id=b.id LEFT JOIN products p ON p.id=bi.product_id
        WHERE $where GROUP BY b.payment_mode");
    while ($row = $r->fetch_assoc()) { $labels[] = strtoupper($row['payment_mode']); $values[] = (float)$row['t']; }
} elseif ($type === 'credit') {
    $r = $mysqli->query("SELECT DATE(b.due_date) d, SUM(b.total) t
        FROM bills b LEFT JOIN bill_items bi ON bi.bill_id=b.id LEFT JOIN products p ON p.id=bi.product_id
        WHERE b.payment_mode='credit' AND $where GROUP BY d ORDER BY d");
    while ($row = $r->fetch_assoc()) { $labels[] = $row['d']; $values[] = (float)$row['t']; }
}

respond(['ok' => true, 'labels' => $labels, 'values' => $values, 'profits' => $profits, 'total' => array_sum($values)]);