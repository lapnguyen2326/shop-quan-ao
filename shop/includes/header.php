<?php
$navCats = all('SELECT l.MaLoaiSP, l.TenLoaiSP FROM LoaiSP l ORDER BY l.MaLoaiSP');
$title = ($title ?? '') ? $title . ' | ' . cfg('shop_name') : cfg('shop_name') . ' – Thời trang mỗi ngày';
?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= (int)filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<div class="topbar">Miễn phí giao hàng cho đơn từ <?= money(cfg('free_ship_from')) ?> · Đổi trả trong 7 ngày</div>
<header class="site-head">
  <div class="wrap head-row">
    <a class="logo" href="<?= url() ?>"><?= h(cfg('shop_name')) ?></a>
    <nav class="main-nav" id="nav">
      <a href="<?= url('products.php') ?>">Tất cả</a>
      <?php foreach ($navCats as $c): ?><a href="<?= url('products.php?c=' . $c['MaLoaiSP']) ?>"><?= h($c['TenLoaiSP']) ?></a><?php endforeach; ?>
      <a class="sale-link" href="<?= url('products.php?sale=1') ?>">Đang giảm giá</a>
    </nav>
    <form class="search" action="<?= url('products.php') ?>" method="get">
      <input type="search" name="q" placeholder="Tìm sản phẩm…" value="<?= h($_GET['q'] ?? '') ?>" aria-label="Tìm sản phẩm">
    </form>
    <div class="head-actions">
      <?php if (me()): ?>
        <a href="<?= url(isAdmin() ? 'admin/' : 'account.php') ?>">Xin chào, <?= h(me()['HoTen'] ?: me()['UserName']) ?></a>
        <a href="<?= url('logout.php') ?>">Thoát</a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>">Đăng nhập</a>
      <?php endif; ?>
      <?php if (!isAdmin()): ?>
        <a class="cart-link" href="<?= url('cart.php') ?>">Giỏ hàng<?php if ($n = cartCount()): ?><span class="count"><?= $n ?></span><?php endif; ?></a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main class="wrap">
<?= flashHtml() ?>
