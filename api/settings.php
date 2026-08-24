<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$action = $_GET['action'] ?? 'get';

if ($action === 'get') {
    $r = $mysqli->query("SELECT * FROM settings");
    $s = [];
    while ($row = $r->fetch_assoc()) $s[$row['setting_key']] = $row['setting_value'];
    respond(['ok' => true, 'data' => $s]);
}

if ($action === 'save') {
    foreach ($_POST as $k => $v) {
        if (strpos($k, 'setting_') === 0) {
            $key = substr($k, 8);
            setSetting($mysqli, $key, $v);
        }
    }
    respond(['ok' => true]);
}

if ($action === 'backup') {
    $tables = ['users','customers','categories','products','bills','bill_items','credit_payments','settings'];
    $sql = "-- BMS Backup " . date('Y-m-d H:i:s') . "\n\n";
    foreach ($tables as $t) {
        $r = $mysqli->query("SELECT * FROM $t");
        while ($row = $r->fetch_assoc()) {
            $cols = array_map(function($v) use ($mysqli) { return $v === null ? 'NULL' : "'" . $mysqli->real_escape_string($v) . "'"; }, array_values($row));
            $sql .= "INSERT INTO $t (" . implode(',', array_keys($row)) . ") VALUES (" . implode(',', $cols) . ");\n";
        }
        $sql .= "\n";
    }
    file_put_contents('../uploads/backup_' . date('Ymd_His') . '.sql', $sql);
    respond(['ok' => true, 'file' => 'backup_' . date('Ymd_His') . '.sql']);
}

if ($action === 'restore') {
    if (empty($_FILES['file'])) respond(['ok' => false, 'msg' => 'No file']);
    $sql = file_get_contents($_FILES['file']['tmp_name']);
    $mysqli->multi_query($sql);
    while ($mysqli->next_result()) {}
    respond(['ok' => true]);
}