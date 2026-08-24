<?php
// ============================================
// 🚀 BMS ONE-CLICK DATABASE SETUP
// ============================================

require_once __DIR__ . '/config/credentials.php';
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbName = DB_NAME;

$message = '';
$status = '';
$installed = false;

// Check if already installed
try {
    $check = new mysqli($host, $user, $pass, $dbName);
    if (!$check->connect_error) {
        $r = $check->query("SELECT COUNT(*) as c FROM users");
        if ($r && $r->fetch_assoc()['c'] > 0) {
            $installed = true;
        }
    }
    @$check->close();
} catch (Exception $e) {}

// Handle setup button click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    try {
        // Step 1: Connect without database
        $mysqli = new mysqli($host, $user, $pass);
        if ($mysqli->connect_error) {
            throw new Exception("Cannot connect to MySQL: " . $mysqli->connect_error);
        }

        // Step 2: Create database
        $mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $mysqli->select_db($dbName);

        // Step 3: Create tables
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                pin_hash VARCHAR(255) NOT NULL,
                role ENUM('admin','staff') DEFAULT 'admin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS customers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                phone VARCHAR(20),
                address TEXT,
                gst_number VARCHAR(30),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) UNIQUE NOT NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                category_id INT,
                purchase_price DECIMAL(12,2) NOT NULL DEFAULT 0,
                selling_price DECIMAL(12,2) NOT NULL DEFAULT 0,
                stock INT NOT NULL DEFAULT 0,
                low_stock_threshold INT DEFAULT 5,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS bills (
                id INT AUTO_INCREMENT PRIMARY KEY,
                bill_no VARCHAR(30) UNIQUE NOT NULL,
                customer_id INT,
                subtotal DECIMAL(12,2) DEFAULT 0,
                discount DECIMAL(12,2) DEFAULT 0,
                tax DECIMAL(12,2) DEFAULT 0,
                total DECIMAL(12,2) NOT NULL,
                payment_mode ENUM('cash','upi','credit') NOT NULL,
                profit DECIMAL(12,2) DEFAULT 0,
                due_date DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS bill_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                bill_id INT NOT NULL,
                product_id INT NOT NULL,
                qty INT NOT NULL,
                price DECIMAL(12,2) NOT NULL,
                purchase_price DECIMAL(12,2) NOT NULL,
                FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS credit_payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                bill_id INT NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                note VARCHAR(255),
                paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(50) PRIMARY KEY,
                setting_value TEXT
            )"
        ];

        foreach ($tables as $sql) {
            if (!$mysqli->query($sql)) {
                throw new Exception("Table error: " . $mysqli->error);
            }
        }

        // Step 4: Insert default admin
        $pwd = password_hash('admin123', PASSWORD_DEFAULT);
        $pin = password_hash('1234', PASSWORD_DEFAULT);
        $mysqli->query("DELETE FROM users WHERE username='admin'");
        $mysqli->query("INSERT INTO users (username, password_hash, pin_hash, role) VALUES ('admin', '$pwd', '$pin', 'admin')");

        // Step 5: Insert default settings
        $settings = [
            ['business_name', 'My Business'],
            ['business_address', ''],
            ['business_phone', ''],
            ['gst_enabled', '0'],
            ['default_gst', '18'],
            ['currency', '₹'],
            ['theme', 'light']
        ];
        foreach ($settings as $s) {
            $mysqli->query("INSERT INTO settings (setting_key, setting_value) VALUES ('{$s[0]}', '{$s[1]}') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        }

        // Step 6: Insert default categories
        $cats = ['General', 'Electronics', 'Grocery', 'Clothing'];
        foreach ($cats as $c) {
            $mysqli->query("INSERT INTO categories (name) VALUES ('$c') ON DUPLICATE KEY UPDATE name=VALUES(name)");
        }

        $mysqli->close();
        
        $status = 'success';
        $message = '✅ Database created successfully!';
        $installed = true;
        
    } catch (Exception $e) {
        $status = 'error';
        $message = '❌ Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BMS - Setup</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
}
.setup-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    max-width: 550px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    text-align: center;
}
.logo { font-size: 70px; margin-bottom: 10px; }
h1 { color: #6366f1; margin-bottom: 10px; font-size: 28px; }
.subtitle { color: #6b7280; margin-bottom: 30px; }

.info-box {
    background: #f3f4f6;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    text-align: left;
}
.info-box h3 { color: #1f2937; margin-bottom: 12px; font-size: 16px; }
.info-box ul { list-style: none; }
.info-box li {
    padding: 8px 0;
    color: #4b5563;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid #e5e7eb;
}
.info-box li:last-child { border-bottom: none; }
.info-box li::before { content: '✓'; color: #10b981; font-weight: bold; }

.credentials {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border-left: 4px solid #f59e0b;
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
    text-align: left;
}
.credentials h4 { color: #92400e; margin-bottom: 8px; font-size: 14px; }
.credentials p { color: #78350f; font-size: 13px; margin: 4px 0; }
.credentials strong { color: #1f2937; }

.btn {
    padding: 15px 30px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}
.btn-primary {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    width: 100%;
    justify-content: center;
    font-size: 18px;
    padding: 18px;
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(99,102,241,0.4); }
.btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    width: 100%;
    justify-content: center;
    font-size: 18px;
    padding: 18px;
}
.btn-success:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(16,185,129,0.4); }

.alert {
    padding: 15px;
    border-radius: 10px;
    margin: 20px 0;
    font-weight: 600;
}
.alert.success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
.alert.error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
.alert.info { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }

.steps {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin: 20px 0;
}
.step {
    background: #f9fafb;
    padding: 12px 8px;
    border-radius: 8px;
    font-size: 12px;
    color: #4b5563;
}
.step .num {
    display: block;
    width: 25px;
    height: 25px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    border-radius: 50%;
    margin: 0 auto 5px;
    line-height: 25px;
    font-weight: bold;
}

.warning {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 12px;
    border-radius: 8px;
    margin-top: 20px;
    font-size: 13px;
    color: #92400e;
    text-align: left;
}

@media (max-width: 500px) {
    .setup-card { padding: 25px 20px; }
    .steps { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>
<div class="setup-card">
    <div class="logo">🏢</div>
    <h1>Business Management System</h1>
    <p class="subtitle">One-Click Database Setup</p>

    <?php if ($message): ?>
        <div class="alert <?= $status ?>"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($installed && $status !== 'success'): ?>
        <div class="alert info">
            ✅ Database is already installed and ready to use!
        </div>
        <a href="public/index.html" class="btn btn-success">🚀 Go to Login Page</a>
        
    <?php elseif ($installed && $status === 'success'): ?>
        <div class="credentials">
            <h4>🔐 Your Login Credentials</h4>
            <p>Username: <strong>admin</strong></p>
            <p>Password: <strong>admin123</strong></p>
            <p>PIN: <strong>1234</strong></p>
        </div>
        <div class="warning">
            ⚠️ <strong>Important:</strong> Change these credentials after first login in Settings!
        </div>
        <br>
        <a href="public/index.html" class="btn btn-success">🚀 Start Using BMS</a>
        
    <?php else: ?>
        <div class="info-box">
            <h3>📋 What this will do:</h3>
            <ul>
                <li>Create database: <strong>bms_db</strong></li>
                <li>Create 8 tables (users, customers, products, bills, etc.)</li>
                <li>Insert default admin account</li>
                <li>Insert default settings & categories</li>
                <li>Setup takes only 2 seconds!</li>
            </ul>
        </div>

        <div class="steps">
            <div class="step"><span class="num">1</span>Database</div>
            <div class="step"><span class="num">2</span>Tables</div>
            <div class="step"><span class="num">3</span>Admin</div>
            <div class="step"><span class="num">4</span>Ready!</div>
        </div>

        <form method="post">
            <button type="submit" name="setup" class="btn btn-primary">
                ⚡ Create Database Now
            </button>
        </form>

        <div class="warning">
            💡 <strong>Note:</strong> Make sure XAMPP MySQL is running before clicking the button.
        </div>
    <?php endif; ?>
</div>
</body>
</html>