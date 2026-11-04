<?php
$title = 'Chi tiết đơn hàng';
require __DIR__ . '/_header.php';
$o = one(orderSelect() . ' WHERE h.MaHD = ?', [$_GET['id'] ?? '']);
if (!$o) { flash('Không tìm thấy đơn hàng.', 'err'); redirect('admin/orders.php'); }
$employeeOwnsOrder = isStaff() && (int)($o['MaNV'] ?? 0) === (int)me()['MaNV'];
$employeeCanClaimOrder = isStaff() && empty($o['MaNV']) && !in_array($o['TrangThai'], ['Hoàn thành', 'Đã hủy'], true);
$employeeReadOnly = isStaff() && !$employeeOwnsOrder && !$employeeCanClaimOrder;
$nextStaffStatus = ['Mới' => 'Đang xử lý', 'Đang xử lý' => 'Đang giao', 'Đang giao' => 'Hoàn thành'];
$staffCanCancel = isStaff() && !in_array($o['TrangThai'], ['Hoàn thành', 'Đã hủy'], true) && ($o['TrangThaiThanhToan'] ?? '') !== 'Đã thanh toán';
$statusOptions = isAdmin()
  ? STATUSES
  : array_values(array_unique(array_filter(
      [$o['TrangThai'], $nextStaffStatus[$o['TrangThai']] ?? null, $staffCanCancel ? 'Đã hủy' : null],
      static fn($status) => $status !== null
  )));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (post('action') === 'claim_order') {
    if (!isStaff() || !$employeeCanClaimOrder) { http_response_code(403); exit('Đơn này đã được nhân viên khác nhận hoặc không thể nhận xử lý.'); }
    $pdo = db();
    $pdo->beginTransaction();
    $claimedStatus = $o['TrangThai'] === 'Mới' ? 'Đang xử lý' : $o['TrangThai'];
    $claimed = run('UPDATE HoaDon SET MaNV = ?, NguoiXuLy = ?, TrangThai = ? WHERE MaHD = ? AND MaNV IS NULL AND TrangThai = ?', [(int)me()['MaNV'], 'nhanvien', $claimedStatus, $o['MaHD'], $o['TrangThai']]);
    if ($claimed < 1) {
      $pdo->rollBack();
      flash('Đơn vừa được người khác nhận xử lý. Hãy tải lại danh sách.', 'err');
    } else {
      $pdo->commit();
      flash('Bạn đã nhận xử lý đơn ' . $o['MaHD'] . ($o['TrangThai'] === 'Mới' ? ' và duyệt đơn.' : '.'));
    }
    redirect('admin/order_view.php?id=' . urlencode($o['MaHD']));
  }
  if (post('action') === 'mark_paid') {
    if (!isAdmin()) { http_response_code(403); exit('Chỉ quản trị viên được xác nhận thanh toán.'); }
    if ($o['TrangThai'] === 'Đã hủy') flash('Không thể xác nhận thanh toán cho đơn đã hủy.', 'err');
    else {
      run('UPDATE HoaDon SET TrangThaiThanhToan = ? WHERE MaHD = ?', ['Đã thanh toán', $o['MaHD']]);
      flash('Đã xác nhận nhận tiền cho đơn ' . $o['MaHD'] . '.');
    }
    redirect('admin/order_view.php?id=' . urlencode($o['MaHD']));
  }
    if (isStaff() && !$employeeOwnsOrder) { http_response_code(403); exit('Bạn chỉ được cập nhật đơn mình đã nhận xử lý.'); }
    $new = post('status'); $ngay = post('ngay');
    if (!in_array($new, $statusOptions, true)) { flash('Trạng thái không hợp lệ hoặc bạn không có quyền thay đổi trạng thái này.', 'err'); redirect('admin/order_view.php?id=' . urlencode($o['MaHD'])); }
    if ($new === 'Đã hủy' && isStaff() && !$staffCanCancel) { flash('Nhân viên chỉ được hủy đơn chưa thanh toán và chưa hoàn thành.', 'err'); redirect('admin/order_view.php?id=' . urlencode($o['MaHD'])); }
    if ($o['TrangThai'] === 'Đã hủy' && $new !== 'Đã hủy') { flash('Đơn đã hủy không thể mở lại.', 'err'); redirect('admin/order_view.php?id=' . urlencode($o['MaHD'])); }
  if ($new === 'Đã hủy' && ($o['TrangThaiThanhToan'] ?? '') === 'Đã thanh toán') { flash('Đơn đã thanh toán. Hãy xử lý hoàn tiền trước khi hủy đơn.', 'err'); redirect('admin/order_view.php?id=' . urlencode($o['MaHD'])); }
    $giao = $ngay !== '' ? dbDate($ngay) : (($new === 'Hoàn thành' && !$o['NgayGiaoHang']) ? nowStr() : ($o['NgayGiaoHang'] ? dbDate(fdate($o['NgayGiaoHang'], 'Y-m-d H:i:s')) : null));
    $pdo = db(); $pdo->beginTransaction();
    if ($new === 'Đã hủy' && $o['TrangThai'] !== 'Đã hủy') { restock($o['MaHD']); releaseVoucherUse($o); }
    syncLoyaltyPoints($o, $new);
    $updated = isStaff()
      ? run('UPDATE HoaDon SET TrangThai = ?, NgayGiaoHang = ?, NguoiXuLy = ? WHERE MaHD = ? AND MaNV = ? AND TrangThai = ?', [$new, $giao, 'nhanvien', $o['MaHD'], (int)me()['MaNV'], $o['TrangThai']])
      : run('UPDATE HoaDon SET TrangThai = ?, NgayGiaoHang = ?, NguoiXuLy = ? WHERE MaHD = ? AND TrangThai = ?', [$new, $giao, 'admin', $o['MaHD'], $o['TrangThai']]);
    if ($updated < 1 && $new !== $o['TrangThai']) {
      $pdo->rollBack();
      flash('Đơn hàng vừa được cập nhật ở nơi khác. Hãy tải lại trang.', 'err');
      redirect('admin/order_view.php?id=' . urlencode($o['MaHD']));
    }
    $pdo->commit();
    flash('Đã cập nhật đơn hàng ' . $o['MaHD'] . '.');
    redirect('admin/order_view.php?id=' . urlencode($o['MaHD']));
}
$lines = orderLines($o['MaHD']);
$kh = one('SELECT * FROM KhachHang WHERE MaKH = ?', [$o['MaKH']]);
?>
<nav class="crumbs"><a href="<?= url('admin/orders.php') ?>">Đơn hàng</a> / <?= h($o['MaHD']) ?></nav>
<div class="page-head"><h1>Đơn <?= h($o['MaHD']) ?></h1><div><?= badge($o['TrangThai']) ?><?php if (!isStaff()): ?> <a class="btn ghost" target="_blank" rel="noopener" href="<?= url('admin/order_invoice.php?id=' . urlencode($o['MaHD'])) ?>">Tạo / in hóa đơn</a><?php endif; ?></div></div>
<div class="two-col">
  <section class="panel"><h2>Sản phẩm</h2><div class="table-wrap"><table>
    <tr><th>Sản phẩm</th><th>Phân loại</th><th class="r">Giá</th><?php if (isAdmin()): ?><th class="r">Giá vốn</th><th class="r">Lợi nhuận</th><?php endif; ?><th class="r">SL</th><th class="r">Thành tiền</th></tr>
    <?php foreach ($lines as $l): $net = $l['DonGia'] * (1 - $l['GiamGia']); ?>
      <tr><td><?= h($l['TenSP'] ?? $l['MaSP']) ?> <small class="muted"><?= h($l['MaSP']) ?></small></td><td><?= h(trim($l['KichCo'] . ' ' . $l['MauSac'])) ?></td><td class="r"><?= money($net) ?><?= (float)$l['GiamGia'] > 0 ? ' <small class="muted">(-' . round($l['GiamGia'] * 100) . '%)</small>' : '' ?></td><?php if (isAdmin()): ?><td class="r"><?= $l['GiaVon'] === null ? 'Chưa có' : money($l['GiaVon']) ?></td><td class="r"><?= $l['GiaVon'] === null ? '—' : money(($net - (float)$l['GiaVon']) * (int)$l['SoLuong']) ?></td><?php endif; ?><td class="r"><?= (int)$l['SoLuong'] ?></td><td class="r"><?= money($net * $l['SoLuong']) ?></td></tr>
    <?php endforeach; ?>
    <tr><td colspan="<?= isAdmin() ? 6 : 4 ?>" class="r">Tiền hàng</td><td class="r"><?= money($o['TienHang']) ?></td></tr>
    <?php if ((float)$o['GiamVoucher'] > 0): ?><tr><td colspan="<?= isAdmin() ? 6 : 4 ?>" class="r">Voucher <?= h($o['MaVoucher']) ?></td><td class="r">−<?= money($o['GiamVoucher']) ?></td></tr><?php endif; ?>
    <tr><td colspan="<?= isAdmin() ? 6 : 4 ?>" class="r">Vận chuyển</td><td class="r"><?= money($o['PhiVanChuyen']) ?></td></tr>
    <tr class="tot"><td colspan="<?= isAdmin() ? 6 : 4 ?>" class="r"><b>Tổng cộng</b></td><td class="r"><b><?= money(orderTotal($o)) ?></b></td></tr>
  </table></div></section>
  <section>
    <div class="panel"><h2>Giao hàng</h2>
      <p><b><?= h($o['TenNguoiNhan']) ?></b><br><?= h($o['DienThoaiGiao']) ?><br><?= h($o['DiaChiGiao']) ?></p>
      <p class="muted">Khách: <?= h($kh['TenKH'] ?? '') ?> (<?= h($o['MaKH']) ?>)<br>Đặt: <?= fdate($o['NgayLapHD']) ?><br>Thanh toán: <?= h($o['PTThanhToan']) ?><?= $o['GhiChu'] ? '<br>Ghi chú: ' . h($o['GhiChu']) : '' ?></p></div>
    <div class="panel"><h2>Người xử lý</h2><p><?= h(orderProcessorLabel($o)) ?></p></div>
    <div class="panel"><h2>Trạng thái thanh toán</h2>
      <p><?= h($o['TrangThaiThanhToan'] ?? 'Chưa thanh toán') ?></p>
      <?php if (isAdmin() && ($o['TrangThaiThanhToan'] ?? '') !== 'Đã thanh toán' && $o['TrangThai'] !== 'Đã hủy'): ?>
        <form method="post"><?= csrf() ?><input type="hidden" name="action" value="mark_paid"><button class="btn">Xác nhận đã nhận tiền</button></form>
      <?php endif; ?>
      <?php if ($o['PTThanhToan'] === 'Chuyển khoản'): ?><p class="muted">Chỉ xác nhận sau khi đã kiểm tra giao dịch vào tài khoản.</p><?php endif; ?>
    </div>
    <?php if ($employeeCanClaimOrder): ?>
      <form method="post" class="panel form-card"><?= csrf() ?><h2>Nhận xử lý đơn</h2>
        <p>Đơn chưa có nhân viên phụ trách. Bấm duyệt để nhận đơn; tên bạn sẽ được lưu để quản lý theo dõi.</p>
        <button class="btn" name="action" value="claim_order">Duyệt và nhận xử lý</button>
      </form>
    <?php elseif ($employeeReadOnly): ?>
      <div class="panel"><h2>Chỉ xem</h2><p>Đơn đã được nhân viên khác nhận xử lý. Bạn có thể xem chi tiết nhưng không thể thay đổi.</p></div>
    <?php else: ?>
      <form method="post" class="panel form-card"><h2>Xử lý đơn</h2><?= csrf() ?>
        <label>Trạng thái<select name="status"><?php foreach ($statusOptions as $s): ?><option <?= $o['TrangThai'] === $s ? 'selected' : '' ?>><?= h($s) ?></option><?php endforeach; ?></select></label>
        <label>Ngày giao hàng<input type="date" name="ngay" value="<?= $o['NgayGiaoHang'] ? h(fdate($o['NgayGiaoHang'], 'Y-m-d')) : '' ?>"></label>
        <button class="btn">Lưu thay đổi</button>
        <p class="muted"><?= isAdmin() ? 'Quản trị viên xem và quản lý trạng thái; nhân viên xử lý được hiển thị riêng ở trên.' : 'Bạn là nhân viên phụ trách đơn này và có thể cập nhật quá trình giao hàng hoặc hủy đơn chưa thanh toán.' ?><?php if (isAdmin() || isStaff()): ?> Hủy đơn sẽ tự cộng lại số lượng vào kho.<?php endif; ?></p>
      </form>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
