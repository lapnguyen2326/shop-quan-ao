<?php
$title = 'Tổng quan';
require __DIR__ . '/_header.php';
$count = fn($t, $w = '1=1') => (int)val("SELECT COUNT(*) FROM $t WHERE $w");
$stat = [
  'Đơn mới chờ xử lý' => $count('HoaDon', "TrangThai = 'Mới'"),
  'Tổng đơn hàng' => $count('HoaDon'),
  'Sản phẩm đang bán' => $count('SanPham', 'HienThi = 1'),
  'Khách hàng' => $count('KhachHang'),
];
$orders = all(orderSelect() . ' ORDER BY h.NgayLapHD DESC');
$revenue = 0; $byMonth = [];
foreach ($orders as $o) {
    if ($o['TrangThai'] !== 'Hoàn thành') continue;
    $t = orderTotal($o); $revenue += $t;
    $m = fdate($o['NgayLapHD'], 'Y-m'); $byMonth[$m] = ($byMonth[$m] ?? 0) + $t;
}
ksort($byMonth); $byMonth = array_slice($byMonth, -6, 6, true);
$maxM = max($byMonth ?: [1]);
$top = all(topN("SELECT s.MaSP, s.TenSP, SUM(c.SoLuong) AS SL FROM CTHD c JOIN HoaDon h ON h.MaHD = c.MaHD JOIN SanPham s ON s.MaSP = c.MaSP
        WHERE h.TrangThai <> 'Đã hủy' GROUP BY s.MaSP, s.TenSP ORDER BY SUM(c.SoLuong) DESC", 5));
$low = all(topN('SELECT MaSP, TenSP, SoLuongTon FROM SanPham WHERE HienThi = 1 AND SoLuongTon <= 10 ORDER BY SoLuongTon, MaSP', 6));
?>
<h1>Tổng quan</h1>
<div class="stats">
  <?php foreach ($stat as $k => $v): ?><div class="stat"><span><?= $k ?></span><b><?= $v ?></b></div><?php endforeach; ?>
  <div class="stat wide"><span>Doanh thu (đơn hoàn thành)</span><b><?= money($revenue) ?></b></div>
</div>
<div class="two-col">
  <section class="panel"><h2>Doanh thu theo tháng</h2>
    <?php if (!$byMonth): ?><p class="muted">Chưa có đơn hoàn thành.</p><?php endif; ?>
    <div class="bars"><?php foreach ($byMonth as $m => $v): ?>
      <div class="bar"><span class="fill" style="height:<?= max(4, round($v / $maxM * 100)) ?>%"></span><small><?= h(substr($m, 5) . '/' . substr($m, 0, 4)) ?></small><em><?= number_format($v / 1000, 0, ',', '.') ?>k</em></div>
    <?php endforeach; ?></div>
  </section>
  <section class="panel"><h2>Bán chạy nhất</h2>
    <table><?php foreach ($top as $t): ?><tr><td><?= h($t['TenSP']) ?></td><td class="r"><?= (int)$t['SL'] ?> đã bán</td></tr><?php endforeach; ?></table>
    <h2 class="gap">Sắp hết hàng</h2>
    <table><?php foreach ($low as $t): ?><tr><td><a href="<?= url('admin/product_form.php?id=' . urlencode($t['MaSP'])) ?>"><?= h($t['TenSP']) ?></a></td><td class="r"><?= (int)$t['SoLuongTon'] ?> còn lại</td></tr><?php endforeach; ?>
    <?php if (!$low) echo '<tr><td class="muted">Tồn kho ổn định.</td></tr>'; ?></table>
  </section>
</div>
<section class="panel"><div class="block-head"><h2>Đơn hàng gần đây</h2><a href="<?= url('admin/orders.php') ?>">Xem tất cả</a></div>
  <div class="table-wrap"><table><tr><th>Mã</th><th>Khách</th><th>Ngày</th><th>Trạng thái</th><th class="r">Tổng</th></tr>
  <?php foreach (array_slice($orders, 0, 6) as $o): ?>
    <tr><td><a href="<?= url('admin/order_view.php?id=' . urlencode($o['MaHD'])) ?>"><?= h($o['MaHD']) ?></a></td><td><?= h($o['TenKH']) ?></td><td><?= fdate($o['NgayLapHD'], 'd/m/Y') ?></td><td><?= badge($o['TrangThai']) ?></td><td class="r"><?= money(orderTotal($o)) ?></td></tr>
  <?php endforeach; ?></table></div></section>
<?php require __DIR__ . '/_footer.php'; ?>
