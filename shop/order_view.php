<?php
require __DIR__ . '/includes/bootstrap.php';
requireLogin();
$o = one(orderSelect() . ' WHERE h.MaHD = ?', [$_GET['id'] ?? '']);
if (!$o || (!isAdmin() && $o['MaKH'] !== me()['MaKH'])) { flash('Không tìm thấy đơn hàng.', 'err'); redirect('account.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'cancel' && $o['TrangThai'] === 'Mới' && ($o['TrangThaiThanhToan'] ?? '') !== 'Đã thanh toán') {
    $pdo = db();
    $pdo->beginTransaction();
    restock($o['MaHD']);
    releaseVoucherUse($o);
    run('UPDATE HoaDon SET TrangThai = ? WHERE MaHD = ?', ['Đã hủy', $o['MaHD']]);
    $pdo->commit();
    flash('Đã hủy đơn hàng ' . $o['MaHD'] . '.');
    redirect('order_view.php?id=' . urlencode($o['MaHD']));
}
$lines = orderLines($o['MaHD']);
$title = 'Đơn ' . $o['MaHD'];
include __DIR__ . '/includes/header.php';
?>
<nav class="crumbs"><a href="<?= url('account.php') ?>">Tài khoản</a> / Đơn <?= h($o['MaHD']) ?></nav>
<div class="page-head"><h1>Đơn hàng <?= h($o['MaHD']) ?></h1><?= badge($o['TrangThai']) ?></div>
<div class="cart-layout">
  <div class="table-wrap"><table>
    <tr><th>Sản phẩm</th><th>Phân loại</th><th class="r">Đơn giá</th><th class="r">SL</th><th class="r">Thành tiền</th></tr>
    <?php foreach ($lines as $l): $net = $l['DonGia'] * (1 - $l['GiamGia']); ?>
      <tr><td><?= h($l['TenSP'] ?? $l['MaSP']) ?></td><td><?= h(trim($l['KichCo'] . ' ' . $l['MauSac'])) ?></td>
          <td class="r"><?= money($net) ?></td><td class="r"><?= (int)$l['SoLuong'] ?></td><td class="r"><?= money($net * $l['SoLuong']) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
  <aside class="summary">
    <h2>Giao hàng</h2>
    <p><b><?= h($o['TenNguoiNhan']) ?></b><br><?= h($o['DienThoaiGiao']) ?><br><?= h($o['DiaChiGiao']) ?></p>
    <p class="muted">Đặt ngày <?= fdate($o['NgayLapHD']) ?><br>Giao ngày <?= fdate($o['NgayGiaoHang'], 'd/m/Y') ?><br>Thanh toán: <?= h($o['PTThanhToan']) ?><br>Trạng thái thanh toán: <b><?= h($o['TrangThaiThanhToan'] ?? 'Chưa thanh toán') ?></b></p>
    <dl><dt>Tiền hàng</dt><dd><?= money($o['TienHang']) ?></dd><?php if ((float)$o['GiamVoucher'] > 0): ?><dt>Voucher <?= h($o['MaVoucher']) ?></dt><dd>−<?= money($o['GiamVoucher']) ?></dd><?php endif; ?><dt>Vận chuyển</dt><dd><?= money($o['PhiVanChuyen']) ?></dd>
        <dt class="tot">Tổng cộng</dt><dd class="tot"><?= money(orderTotal($o)) ?></dd></dl>
    <?php if ($o['TrangThai'] === 'Mới' && ($o['TrangThaiThanhToan'] ?? '') !== 'Đã thanh toán'): ?>
      <form method="post" onsubmit="return confirm('Hủy đơn hàng này?')"><?= csrf() ?><input type="hidden" name="action" value="cancel"><button class="btn ghost block-btn">Hủy đơn hàng</button></form>
    <?php endif; ?>
  </aside>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
