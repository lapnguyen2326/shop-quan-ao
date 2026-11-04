<?php
require __DIR__ . '/includes/bootstrap.php';
unset($_SESSION['user'], $_SESSION['my_orders'], $_SESSION['cart']);
session_regenerate_id(true);
flash('Bạn đã đăng xuất.');
redirect('index.php');
