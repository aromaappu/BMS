<?php
require_once __DIR__ . '/config/credentials.php';
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_error) {
    die("❌ Connection failed: " . $mysqli->connect_error);
}

echo "✅ Connected to bms_db<br><br>";

// Insert test customer
$mysqli->query("INSERT INTO customers (name, phone, address) VALUES ('Test User', '9999999999', 'Test Address')");

if ($mysqli->affected_rows > 0) {
    echo "✅ Customer inserted! ID: " . $mysqli->insert_id . "<br><br>";
} else {
    echo "❌ Insert failed: " . $mysqli->error . "<br><br>";
}

// Show all customers
$result = $mysqli->query("SELECT * FROM customers");
echo "<h3>All Customers in Database:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Phone</th><th>Address</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['phone']}</td><td>{$row['address']}</td></tr>";
}
echo "</table>";

$mysqli->close();
?>