<?php
require_once __DIR__ . '/credentials.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    http_response_code(500);
    die(json_encode(['ok' => false, 'msg' => 'DB Error: ' . $mysqli->connect_error]));
}
$mysqli->set_charset("utf8mb4");

session_start();

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        respond(['ok' => false, 'msg' => 'Not logged in'], 401);
    }
}