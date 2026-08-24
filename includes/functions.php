<?php
function money($n) { return '₹' . number_format((float)$n, 2); }
function today() { return date('Y-m-d'); }

function genBillNo($mysqli) {
    $prefix = 'BILL-' . date('Ymd') . '-';
    $r = $mysqli->query("SELECT bill_no FROM bills WHERE bill_no LIKE '$prefix%' ORDER BY id DESC LIMIT 1");
    $last = $r->fetch_row();
    $num = $last ? (int)substr($last[0], -4) + 1 : 1;
    return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
}

function getSetting($mysqli, $key, $default = '') {
    $stmt = $mysqli->prepare("SELECT setting_value FROM settings WHERE setting_key=?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? $row['setting_value'] : $default;
}

function setSetting($mysqli, $key, $val) {
    $stmt = $mysqli->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) 
                              ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $stmt->bind_param("ss", $key, $val);
    return $stmt->execute();
}

function getNotifications($mysqli) {
    $notes = [];
    $r = $mysqli->query("SELECT name, stock, low_stock_threshold FROM products WHERE stock <= low_stock_threshold");
    while ($row = $r->fetch_assoc()) {
        $notes[] = ['type' => 'warning', 'icon' => '📦', 'msg' => "Low Stock: {$row['name']} ({$row['stock']} left)"];
    }
    $today = today();
    $r = $mysqli->query("SELECT b.id, b.bill_no, c.name, c.phone, b.total - COALESCE(SUM(cp.amount),0) as due, b.due_date
                         FROM bills b LEFT JOIN customers c ON c.id=b.customer_id
                         LEFT JOIN credit_payments cp ON cp.bill_id=b.id
                         WHERE b.payment_mode='credit' AND b.due_date='$today'
                         GROUP BY b.id HAVING due>0");
    while ($row = $r->fetch_assoc()) {
        $notes[] = ['type' => 'info', 'icon' => '📅', 'msg' => "Due Today: {$row['name']} - {$row['bill_no']} (" . money($row['due']) . ")", 'data' => $row];
    }
    $r = $mysqli->query("SELECT b.id, b.bill_no, c.name, c.phone, b.total - COALESCE(SUM(cp.amount),0) as due, b.due_date
                         FROM bills b LEFT JOIN customers c ON c.id=b.customer_id
                         LEFT JOIN credit_payments cp ON cp.bill_id=b.id
                         WHERE b.payment_mode='credit' AND b.due_date < '$today'
                         GROUP BY b.id HAVING due>0");
    while ($row = $r->fetch_assoc()) {
        $notes[] = ['type' => 'danger', 'icon' => '⚠️', 'msg' => "Overdue: {$row['name']} - {$row['bill_no']} (" . money($row['due']) . ")", 'data' => $row];
    }
    return $notes;
}