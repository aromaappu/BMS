<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$bill_id = (int)$_GET['id'];
$bill = $mysqli->query("SELECT b.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address, c.gst_number as customer_gst
    FROM bills b LEFT JOIN customers c ON c.id=b.customer_id WHERE b.id=$bill_id")->fetch_assoc();
if (!$bill) die('Bill not found');

$items = [];
$r = $mysqli->query("SELECT bi.*, p.name as product_name FROM bill_items bi JOIN products p ON p.id=bi.product_id WHERE bi.bill_id=$bill_id");
while ($row = $r->fetch_assoc()) $items[] = $row;

$biz = getSetting($mysqli, 'business_name', 'My Business');
$addr = getSetting($mysqli, 'business_address', '');
$phone = getSetting($mysqli, 'business_phone', '');

// Simple HTML to PDF using browser print
?>
<!DOCTYPE html>
<html>
<head>
<title>Bill <?=$bill['bill_no']?></title>
<style>
body{font-family:Arial;padding:20px;max-width:800px;margin:auto}
.header{text-align:center;border-bottom:2px solid #6366f1;padding-bottom:15px;margin-bottom:20px}
.header h1{color:#6366f1;margin:0}
.info{display:flex;justify-content:space-between;margin-bottom:20px}
.info div{background:#f3f4f6;padding:10px;border-radius:8px;flex:1;margin:0 5px}
table{width:100%;border-collapse:collapse;margin:15px 0}
th{background:#6366f1;color:white;padding:10px;text-align:left}
td{padding:10px;border-bottom:1px solid #e5e7eb}
.total{text-align:right;margin-top:20px;font-size:18px}
.total div{margin:5px 0}
.grand{color:#6366f1;font-size:24px;font-weight:bold}
.footer{text-align:center;margin-top:30px;color:#6b7280;font-size:12px}
@media print { .no-print { display: none; } }
</style>
</head>
<body>
<button class="no-print" onclick="window.print()" style="padding:10px 20px;background:#6366f1;color:white;border:none;border-radius:8px;cursor:pointer">🖨️ Print / Save PDF</button>
<div class="header">
  <h1><?=htmlspecialchars($biz)?></h1>
  <p><?=htmlspecialchars($addr)?> <?=htmlspecialchars($phone)?></p>
</div>
<div class="info">
  <div><strong>Bill No:</strong><br><?=$bill['bill_no']?></div>
  <div><strong>Date:</strong><br><?=date('d M Y, h:i A', strtotime($bill['created_at']))?></div>
  <div><strong>Customer:</strong><br><?=htmlspecialchars($bill['customer_name'] ?? 'Walk-in')?></div>
</div>
<table>
  <tr><th>#</th><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr>
  <?php $i = 1; foreach ($items as $it): ?>
  <tr>
    <td><?=$i++?></td>
    <td><?=htmlspecialchars($it['product_name'])?></td>
    <td><?=$it['qty']?></td>
    <td>₹<?=number_format($it['price'],2)?></td>
    <td>₹<?=number_format($it['price']*$it['qty'],2)?></td>
  </tr>
  <?php endforeach; ?>
</table>
<div class="total">
  <div>Subtotal: ₹<?=number_format($bill['subtotal'],2)?></div>
  <?php if ($bill['discount'] > 0): ?><div>Discount: -₹<?=number_format($bill['discount'],2)?></div><?php endif; ?>
  <?php if ($bill['tax'] > 0): ?><div>Tax: +₹<?=number_format($bill['tax'],2)?></div><?php endif; ?>
  <div class="grand">Total: ₹<?=number_format($bill['total'],2)?></div>
  <div>Payment: <strong><?=strtoupper($bill['payment_mode'])?></strong></div>
</div>
<div class="footer">Thank you for your business! 🙏</div>
</body>
</html>