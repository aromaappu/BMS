<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();
respond(['ok' => true, 'data' => getNotifications($mysqli)]);