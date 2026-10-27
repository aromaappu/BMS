<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$type = $_GET['type'] ?? 'daily';
$allowed = ['daily', 'monthly', 'yearly', 'product', 'category', 'customer', 'payment', 'credit'];
if (!in_array($type, $allowed, true)) respond(['ok' => false, 'msg' => 'Invalid report type']);

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$validDate = function ($date) {
    if (!is_string($date)) return false;
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
};
if (!$validDate($from) || !$validDate($to) || $from > $to) {
    respond(['ok' => false, 'msg' => 'Invalid date range']);
}

// Validated ISO dates cannot inject SQL. Use a half-open range to keep indexes usable.
$where = "b.created_at >= '$from 00:00:00' AND b.created_at < DATE_ADD('$to', INTERVAL 1 DAY)";
$productId = max(0, (int)($_GET['product_id'] ?? 0));
$categoryId = max(0, (int)($_GET['category_id'] ?? 0));
if ($productId || $categoryId) {
    $filter = ['f.bill_id = b.id'];
    if ($productId) $filter[] = "f.product_id = $productId";
    if ($categoryId) $filter[] = "fp.category_id = $categoryId";
    $where .= " AND EXISTS (SELECT 1 FROM bill_items f JOIN products fp ON fp.id=f.product_id WHERE " . implode(' AND ', $filter) . ")";
}

$labels = [];
$values = [];
$profits = [];
if ($type === 'daily') {
    // Aggregate bills only once – joining bill_items would multiply revenue/profit.
    $sql = "SELECT DATE(b.created_at) label, SUM(b.total) amount, SUM(b.profit) profit
            FROM bills b WHERE $where GROUP BY DATE(b.created_at) ORDER BY label";
} elseif ($type === 'monthly') {
    $sql = "SELECT DATE_FORMAT(b.created_at,'%Y-%m') label, SUM(b.total) amount, SUM(b.profit) profit
            FROM bills b WHERE $where GROUP BY DATE_FORMAT(b.created_at,'%Y-%m') ORDER BY label";
} elseif ($type === 'yearly') {
    $sql = "SELECT YEAR(b.created_at) label, SUM(b.total) amount, SUM(b.profit) profit
            FROM bills b WHERE $where GROUP BY YEAR(b.created_at) ORDER BY label";
} elseif ($type === 'product') {
    $sql = "SELECT p.name label, SUM(bi.qty * bi.price) amount
            FROM bill_items bi JOIN products p ON p.id=bi.product_id
            JOIN bills b ON b.id=bi.bill_id WHERE $where
            GROUP BY bi.product_id, p.name ORDER BY amount DESC";
} elseif ($type === 'category') {
    $sql = "SELECT COALESCE(c.name, 'Uncategorized') label, SUM(bi.qty * bi.price) amount
            FROM bill_items bi JOIN products p ON p.id=bi.product_id
            LEFT JOIN categories c ON c.id=p.category_id
            JOIN bills b ON b.id=bi.bill_id WHERE $where
            GROUP BY p.category_id, c.name ORDER BY amount DESC";
} elseif ($type === 'customer') {
    $sql = "SELECT COALESCE(c.name, 'Walk-in') label, SUM(b.total) amount
            FROM bills b LEFT JOIN customers c ON c.id=b.customer_id
            WHERE $where GROUP BY b.customer_id, c.name ORDER BY amount DESC";
} elseif ($type === 'payment') {
    $sql = "SELECT UPPER(b.payment_mode) label, SUM(b.total) amount
            FROM bills b WHERE $where GROUP BY b.payment_mode ORDER BY amount DESC";
} else {
    $sql = "SELECT DATE(b.due_date) label, SUM(b.total) amount
            FROM bills b WHERE b.payment_mode='credit' AND $where
            GROUP BY DATE(b.due_date) ORDER BY label";
}

$result = $mysqli->query($sql);
if (!$result) respond(['ok' => false, 'msg' => 'Unable to generate report']);
while ($row = $result->fetch_assoc()) {
    $labels[] = (string)($row['label'] ?? 'Unknown');
    $values[] = (float)$row['amount'];
    if (array_key_exists('profit', $row)) $profits[] = (float)$row['profit'];
}
respond(['ok' => true, 'labels' => $labels, 'values' => $values, 'profits' => $profits, 'total' => array_sum($values)]);
