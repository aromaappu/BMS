<?php
/**
 * Central DB credentials loader.
 *
 * Defaults are safe placeholders. For local development create
 * `config.local.php` in this folder (it is git-ignored):
 *
 * <?php
 * return ['host'=>'localhost','user'=>'root','pass'=>'YOUR_PASSWORD','name'=>'bms_db'];
 */

$TM_DB = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'bms_db',
];

if (file_exists(__DIR__ . '/config.local.php')) {
    $TM_DB = array_merge($TM_DB, require __DIR__ . '/config.local.php');
}

define('DB_HOST', $TM_DB['host']);
define('DB_USER', $TM_DB['user']);
define('DB_PASS', $TM_DB['pass']);
define('DB_NAME', $TM_DB['name']);

if (!defined('BMS_SKIP_HEADERS')) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
}
