<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$today = today();
$month = date('Y-m');

$tSales = $mysqli->query("SELECT COALESCE(SUM(total),0) FROM bills WHERE DATE(created_at)='$today'")->fetch_row()[0];
$tProfit = $mysqli->query("SELECT COALESCE(SUM(profit),0) FROM bills WHERE DATE(created_at)='$today'")->fetch_row()[0];
$customers = $mysqli->query("SELECT COUNT(*) FROM customers")->fetch_row()[0];
$products = $mysqli->query("SELECT COUNT(*) FROM products")->fetch_row()[0];

$credit = $mysqli->query("SELECT COALESCE(SUM(outstanding),0) FROM (
    SELECT b.total - COALESCE(SUM(cp.amount),0) as outstanding
    FROM bills b LEFT JOIN credit_payments cp ON cp.bill_id=b.id
    WHERE b.payment_mode='credit' GROUP BY b.id HAVING outstanding>0
) t")->fetch_row()[0];

$lowStock = $mysqli->query("SELECT COUNT(*) FROM products WHERE stock<=low_stock_threshold")->fetch_row()[0];

$monthly = $mysqli->query("SELECT DATE_FORMAT(created_at,'%Y-%m') m, SUM(total) t, SUM(profit) p
              FROM bills WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
              GROUP BY m ORDER BY m");
$months = []; $salesData = []; $profitData = [];
while ($r = $monthly->fetch_assoc()) { $months[] = $r['m']; $salesData[] = (float)$r['t']; $profitData[] = (float)$r['p']; }

$best = [];
$r = $mysqli->query("SELECT p.name, SUM(bi.qty) q FROM bill_items bi JOIN products p ON p.id=bi.product_id GROUP BY bi.product_id ORDER BY q DESC LIMIT 5");
while ($row = $r->fetch_assoc()) $best[] = $row;

$slow = [];
$r = $mysqli->query("SELECT p.name, COALESCE(SUM(bi.qty),0) q FROM products p LEFT JOIN bill_items bi ON bi.product_id=p.id GROUP BY p.id ORDER BY q ASC LIMIT 5");
while ($row = $r->fetch_assoc()) $slow[] = $row;

respond([
    'ok' => true,
    'today_sales' => (float)$tSales,
    'today_profit' => (float)$tProfit,
    'customers' => (int)$customers,
    'products' => (int)$products,
    'credit' => (float)$credit,
    'low_stock' => (int)$lowStock,
    'months' => $months,
    'sales_data' => $salesData,
    'profit_data' => $profitData,
    'best' => $best,
    'slow' => $slow
]);