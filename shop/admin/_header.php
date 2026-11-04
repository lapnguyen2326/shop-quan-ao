<?php
require __DIR__ . '/../includes/bootstrap.php';
requireAdminPage();
$cur = basename($_SERVER['SCRIPT_NAME']);
$unreadMessages = (int)val("SELECT COUNT(*) FROM TinNhan WHERE NguoiGui = 'khach' AND DaDoc = 0");
$menu = ['index.php' => 'Tổng quan', 'analytics.php' => 'Phân tích', 'orders.php' => 'Đơn hàng', 'vouchers.php' => 'Voucher', 'books.php' => 'Sổ sách', 'products.php' => 'Sản phẩm', 'inventory.php' => 'Nhập hàng & giá vốn', 'categories.php' => 'Loại sản phẩm',
         'customers.php' => 'Khách hàng', 'employees.php' => 'Nhân viên', 'users.php' => 'Tài khoản'];
if (isAdmin()) $menu['product_sales.php'] = 'Bán hàng sản phẩm';
if (isAdmin()) $menu['customer_ledger.php'] = 'Sổ khách hàng';
if (isManager()) $menu = ['orders.php' => 'Đơn hàng', 'products.php' => 'Tồn kho sản phẩm', 'inventory.php' => 'Nhập hàng & giá vốn', 'employees.php' => 'Quản lý nhân viên', 'users.php' => 'Tài khoản nhân viên'];
if (isEmployee()) $menu = ['orders.php' => 'Đơn hàng', 'products.php' => 'Tồn kho sản phẩm'];
$sub = ['product_form.php' => 'products.php', 'order_view.php' => 'orders.php'];
$active = $sub[$cur] ?? $cur;
?><!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h(($title ?? 'Quản trị') . ' | ' . cfg('shop_name')) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= (int)filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head><body class="admin">
<aside class="side">
  <a class="logo" href="<?= url(isStaff() ? 'admin/orders.php' : 'admin/') ?>"><?= h(cfg('shop_name')) ?></a>
  <nav><?php foreach ($menu as $f => $n): ?><a class="<?= $f === $active ? 'on' : '' ?>" href="<?= url('admin/' . $f) ?>"><?= $n ?></a><?php endforeach; ?></nav>
  <div class="side-foot"><a href="<?= url() ?>">Xem cửa hàng</a><a href="<?= url('logout.php') ?>">Đăng xuất (<?= h(me()['UserName']) ?>)</a></div>
</aside>
<main class="admin-main">
<?= flashHtml() ?>
