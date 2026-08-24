<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'login') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row && password_verify($p, $row['password_hash'])) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];
        respond(['ok' => true, 'user' => $row['username']]);
    }
    respond(['ok' => false, 'msg' => 'Invalid credentials'], 401);
}

if ($action === 'verify_pin') {
    requireLogin();
    $pin = $_POST['pin'] ?? '';
    $stmt = $mysqli->prepare("SELECT pin_hash FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (password_verify($pin, $row['pin_hash'])) {
        $_SESSION['pin_verified'] = true;
        respond(['ok' => true]);
    }
    respond(['ok' => false, 'msg' => 'Invalid PIN'], 401);
}

if ($action === 'check') {
    if (empty($_SESSION['user_id'])) respond(['ok' => false, 'logged_in' => false]);
    respond(['ok' => true, 'logged_in' => true, 'pin_verified' => !empty($_SESSION['pin_verified']), 'user' => $_SESSION['username']]);
}

if ($action === 'change_password') {
    requireLogin();
    $old = $_POST['old'] ?? ''; $new = $_POST['new'] ?? '';
    $stmt = $mysqli->prepare("SELECT password_hash FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!password_verify($old, $row['password_hash'])) respond(['ok' => false, 'msg' => 'Old password wrong']);
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $mysqli->query("UPDATE users SET password_hash='$hash' WHERE id=" . $_SESSION['user_id']);
    respond(['ok' => true]);
}

if ($action === 'change_pin') {
    requireLogin();
    $old = $_POST['old'] ?? ''; $new = $_POST['new'] ?? '';
    $stmt = $mysqli->prepare("SELECT pin_hash FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!password_verify($old, $row['pin_hash'])) respond(['ok' => false, 'msg' => 'Old PIN wrong']);
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $mysqli->query("UPDATE users SET pin_hash='$hash' WHERE id=" . $_SESSION['user_id']);
    respond(['ok' => true]);
}

if ($action === 'logout') {
    session_destroy();
    respond(['ok' => true]);
}