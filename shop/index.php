<?php
require __DIR__ . '/includes/bootstrap.php';
$new  = all(topN('SELECT * FROM SanPham WHERE HienThi = 1 AND SPMoi = 1 ORDER BY NgayTao DESC, MaSP', 8));
$sale = all(topN('SELECT * FROM SanPham WHERE HienThi = 1 AND PhanTramGiam > 0 ORDER BY PhanTramGiam DESC, MaSP', 4));
$catList = all('SELECT l.MaLoaiSP, l.TenLoaiSP, (SELECT COUNT(*) FROM SanPham s WHERE s.MaLoaiSP = l.MaLoaiSP AND s.HienThi = 1) AS SL FROM LoaiSP l ORDER BY l.MaLoaiSP');
$hero = all(topN('SELECT * FROM SanPham WHERE HienThi = 1 ORDER BY SPMoi DESC, MaSP', 3));
$title = '';
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="hero-text">
    <h1>Mặc gọn, mặc đẹp, mặc cả tuần không lo trùng đồ.</h1>
    <p>Áo thun, sơ mi, khoác, quần và váy chọn lọc theo chất vải và đường may. Giá ghi rõ, đổi size trong 7 ngày.</p>
    <a class="btn" href="<?= url('products.php') ?>">Xem tất cả sản phẩm</a>
    <a class="btn ghost" href="<?= url('products.php?sale=1') ?>">Đang giảm giá</a>
  </div>
  <div class="hero-pics">
    <?php foreach ($hero as $i => $sp): ?>
      <a href="<?= url('product.php?id=' . urlencode($sp['MaSP'])) ?>" class="hp hp<?= $i ?>"><img src="<?= h(img($sp)) ?>" alt="<?= h($sp['TenSP']) ?>"></a>
    <?php endforeach; ?>
  </div>
</section>

<section class="block">
  <h2>Chọn theo nhóm</h2>
  <div class="cat-row">
    <?php foreach ($catList as $c): ?>
      <a href="<?= url('products.php?c=' . $c['MaLoaiSP']) ?>"><b><?= h($c['TenLoaiSP']) ?></b><span><?= (int)$c['SL'] ?> sản phẩm</span></a>
    <?php endforeach; ?>
  </div>
</section>

<section class="block">
  <div class="block-head"><h2>Hàng mới về</h2><a href="<?= url('products.php') ?>">Xem tất cả</a></div>
  <div class="grid"><?php foreach ($new as $sp) echo productCard($sp); ?></div>
</section>

<?php if ($sale): ?>
<section class="block">
  <div class="block-head"><h2>Đang giảm giá</h2><a href="<?= url('products.php?sale=1') ?>">Xem tất cả</a></div>
  <div class="grid"><?php foreach ($sale as $sp) echo productCard($sp); ?></div>
</section>
<?php endif; ?>

<section class="block perks">
  <div><b>Giao hàng toàn quốc</b><p>Miễn phí từ <?= money(cfg('free_ship_from')) ?>.</p></div>
  <div><b>Đổi trả 7 ngày</b><p>Đổi size hoặc màu nếu chưa mặc, còn nguyên tem.</p></div>
  <div><b>Thanh toán linh hoạt</b><p>Trả tiền khi nhận hàng hoặc chuyển khoản.</p></div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
