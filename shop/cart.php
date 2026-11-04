<?php
require __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    if ($a === 'add') {
        $sp = one('SELECT * FROM SanPham WHERE MaSP = ? AND HienThi = 1', [post('ma')]);
        $qty = max(1, (int)post('qty', 1));
        if (!$sp) flash('Sản phẩm không tồn tại.', 'err');
        elseif ((int)$sp['SoLuongTon'] < $qty) flash('Không đủ hàng trong kho.', 'err');
        else {
            $size = in_array(post('size'), csv($sp['KichCo'])) ? post('size') : '';
            $mau = in_array(post('mau'), csv($sp['MauSac'])) ? post('mau') : '';
            cartAdd($sp['MaSP'], $size, $mau, $qty);
            flash('Đã thêm “' . $sp['TenSP'] . '” vào giỏ hàng.');
            if (post('go') === '1') redirect('checkout.php');
        }
        redirect($sp ? 'cart.php' : 'products.php');
    }
    if ($a === 'update') {
        foreach ($_POST['qty'] ?? [] as $k => $n) {
            if (!isset($_SESSION['cart'][$k])) continue;
            $n = (int)$n;
            if ($n <= 0) { unset($_SESSION['cart'][$k]); continue; }
            $stock = (int)val('SELECT SoLuongTon FROM SanPham WHERE MaSP = ?', [$_SESSION['cart'][$k]['ma']]);
            $_SESSION['cart'][$k]['qty'] = min($n, max($stock, 1));
        }
        flash('Đã cập nhật giỏ hàng.');
    }
    if ($a === 'remove') unset($_SESSION['cart'][post('key')]);
    if ($a === 'clear') $_SESSION['cart'] = [];
    redirect('cart.php');
}
[$items, $sub] = cartItems();
$fee = shipFee($sub);
$title = 'Giỏ hàng';
include __DIR__ . '/includes/header.php';
?>
<h1>Giỏ hàng</h1>
<?php if (!$items): ?>
  <div class="empty"><p>Giỏ hàng đang trống.</p><a class="btn" href="<?= url('products.php') ?>">Tiếp tục mua sắm</a></div>
<?php else: ?>
<div class="cart-layout">
  <form method="post" class="cart-list">
    <?= csrf() ?><input type="hidden" name="action" value="update">
    <?php foreach ($items as $it): ?>
      <div class="cart-row">
        <img src="<?= h(img($it['sp'])) ?>" alt="">
        <div class="cart-name">
          <a href="<?= url('product.php?id=' . urlencode($it['ma'])) ?>"><b><?= h($it['sp']['TenSP']) ?></b></a>
          <span class="muted"><?= h(trim(($it['size'] ? 'Size ' . $it['size'] : '') . ($it['mau'] ? ' · ' . $it['mau'] : ''), ' ·')) ?></span>
          <span><?= money($it['price']) ?></span>
        </div>
        <input type="number" name="qty[<?= h($it['key']) ?>]" value="<?= (int)$it['qty'] ?>" min="0" aria-label="Số lượng">
        <b class="line"><?= money($it['line']) ?></b>
        <button class="x" type="submit" form="rm" name="key" value="<?= h($it['key']) ?>" aria-label="Xóa khỏi giỏ">Xóa</button>
      </div>
    <?php endforeach; ?>
    <div class="cart-actions"><button class="btn ghost">Cập nhật số lượng</button></div>
  </form>
  <form id="rm" method="post"><?= csrf() ?><input type="hidden" name="action" value="remove"></form>
  <aside class="summary">
    <h2>Tóm tắt đơn hàng</h2>
    <dl><dt>Tạm tính</dt><dd><?= money($sub) ?></dd>
        <dt>Phí vận chuyển</dt><dd><?= $fee ? money($fee) : 'Miễn phí' ?></dd>
        <dt class="tot">Tổng cộng</dt><dd class="tot"><?= money($sub + $fee) ?></dd></dl>
    <?php if ($fee): ?><p class="muted">Mua thêm <?= money(cfg('free_ship_from') - $sub) ?> để được miễn phí giao hàng.</p><?php endif; ?>
    <a class="btn block-btn" href="<?= url('checkout.php') ?>">Tiến hành đặt hàng</a>
    <form method="post"><?= csrf() ?><input type="hidden" name="action" value="clear"><button class="link">Xóa toàn bộ giỏ hàng</button></form>
  </aside>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
