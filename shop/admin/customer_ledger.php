<?php
$title = 'Sổ khách hàng';
require __DIR__ . '/_header.php';
if (!isAdmin()) { http_response_code(403); exit('Chỉ quản trị viên được xem sổ sách khách hàng.'); }
require __DIR__ . '/_books_data.php';

$search = trim((string)($_GET['q'] ?? ''));
$customerId = trim((string)($_GET['id'] ?? ''));
$customer = $customerId !== '' ? one('SELECT * FROM KhachHang WHERE MaKH = ?', [$customerId]) : null;
$matches = [];
$summary = null;
$orders = [];
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$orderCount = 0;

if ($customerId !== '' && !$customer) {
    flash('Không tìm thấy khách hàng.', 'err');
    redirect('admin/customer_ledger.php?q=' . urlencode($search));
}
if ($search !== '' && !$customer) {
    $like = '%' . $search . '%';
    $matches = all(limitSql(
        'SELECT k.MaKH, k.TenKH, k.DienThoai, k.Email, COUNT(h.MaHD) AS SoDon
         FROM KhachHang k LEFT JOIN HoaDon h ON h.MaKH = k.MaKH
         WHERE k.TenKH LIKE ? OR k.MaKH LIKE ? OR k.DienThoai LIKE ?
         GROUP BY k.MaKH, k.TenKH, k.DienThoai, k.Email
         ORDER BY k.TenKH, k.MaKH',
        25
    ), [$like, $like, $like]);
}
if ($customer) {
    $summary = one(
        'SELECT COUNT(*) AS SoDon,
            COALESCE(SUM(CASE WHEN TrangThai = ? THEN 1 ELSE 0 END), 0) AS SoDonDaHuy,
            COALESCE(SUM(TienHang - GiamVoucher + PhiVanChuyen), 0) AS TongGiaTriDon,
            COALESCE(SUM(CASE WHEN TrangThai <> ? THEN TienHang - GiamVoucher + PhiVanChuyen ELSE 0 END), 0) AS TongDonChuaHuy,
            COALESCE(SUM(CASE WHEN TrangThai = ? THEN TienHang - GiamVoucher + PhiVanChuyen ELSE 0 END), 0) AS TongDonDaHuy,
            COALESCE(SUM(CASE WHEN TrangThaiThanhToan = ? AND PTThanhToan = ? THEN TienHang - GiamVoucher + PhiVanChuyen ELSE 0 END), 0) AS DaThuCOD,
            COALESCE(SUM(CASE WHEN TrangThaiThanhToan = ? AND PTThanhToan = ? THEN TienHang - GiamVoucher + PhiVanChuyen ELSE 0 END), 0) AS DaThuChuyenKhoan,
            COALESCE(SUM(CASE WHEN TrangThaiThanhToan <> ? AND TrangThai <> ? THEN TienHang - GiamVoucher + PhiVanChuyen ELSE 0 END), 0) AS ChuaThanhToan
         FROM (' . orderSelect() . ') AS SoKhach
         WHERE MaKH = ?',
        ['Đã hủy', 'Đã hủy', 'Đã hủy', 'Đã thanh toán', 'COD', 'Đã thanh toán', 'Chuyển khoản', 'Đã thanh toán', 'Đã hủy', $customerId]
    );
    $orderCount = (int)val('SELECT COUNT(*) FROM HoaDon WHERE MaKH = ?', [$customerId]);
    $orders = all(
        limitSql(orderSelect() . ' WHERE h.MaKH = ? ORDER BY h.NgayLapHD DESC, h.MaHD DESC', $perPage, ($page - 1) * $perPage),
        [$customerId]
    );
}
?>
<div class="page-head"><h1>Sổ sách khách hàng</h1></div>
<form class="filters" method="get" action="<?= url('admin/customer_ledger.php') ?>">
  <input type="search" name="q" placeholder="Tìm tên, mã hoặc số điện thoại khách hàng" value="<?= h($search) ?>">
  <button class="btn ghost">Tra cứu</button>
</form>
<?php if ($search !== '' && !$customer): ?>
  <div class="table-wrap"><table><tr><th>Mã khách</th><th>Họ tên</th><th>Điện thoại</th><th>Email</th><th class="r">Số đơn</th><th></th></tr>
    <?php foreach ($matches as $match): ?>
      <tr><td><?= h($match['MaKH']) ?></td><td><?= h($match['TenKH']) ?></td><td><?= h($match['DienThoai']) ?></td><td><?= h($match['Email']) ?></td><td class="r"><?= (int)$match['SoDon'] ?></td><td><a class="btn ghost" href="<?= url('admin/customer_ledger.php?id=' . urlencode($match['MaKH']) . '&q=' . urlencode($search)) ?>">Xem sổ</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$matches): ?><tr><td colspan="6" class="muted">Không tìm thấy khách hàng phù hợp.</td></tr><?php endif; ?>
  </table></div>
<?php elseif ($customer && $summary): ?>
  <div class="page-head">
    <h2><?= h($customer['TenKH']) ?> <small class="muted">(<?= h($customer['MaKH']) ?>)</small></h2>
    <a class="btn ghost" href="<?= url('admin/customer_ledger_export.php?id=' . urlencode($customer['MaKH'])) ?>">Tải sổ khách hàng Excel</a>
  </div>
  <p class="muted">Điện thoại: <?= h($customer['DienThoai']) ?> · Email: <?= h($customer['Email']) ?></p>
  <div class="stats">
    <div class="stat"><span>Đã thanh toán COD</span><b><?= money($summary['DaThuCOD']) ?></b></div>
    <div class="stat"><span>Đã thanh toán chuyển khoản</span><b><?= money($summary['DaThuChuyenKhoan']) ?></b></div>
    <div class="stat"><span>Tổng giá trị tất cả đơn đã đặt</span><b><?= money($summary['TongGiaTriDon']) ?></b></div>
    <div class="stat"><span>Đơn chưa hủy</span><b><?= money($summary['TongDonChuaHuy']) ?></b></div>
    <div class="stat"><span>Chưa thanh toán (đơn chưa hủy)</span><b><?= money($summary['ChuaThanhToan']) ?></b></div>
    <div class="stat"><span>Tổng số đơn đã đặt</span><b><?= (int)$summary['SoDon'] ?></b></div>
    <div class="stat"><span>Số đơn đã hủy</span><b><?= (int)$summary['SoDonDaHuy'] ?></b></div>
    <div class="stat"><span>Tổng tiền đơn đã hủy</span><b><?= money($summary['TongDonDaHuy']) ?></b></div>
  </div>
  <div class="table-wrap"><table>
    <tr><th>Mã đơn</th><th>Ngày lập</th><th>Phương thức</th><th>Thanh toán</th><th>Trạng thái đơn</th><th class="r">Tiền hàng</th><th class="r">Voucher</th><th class="r">Phí ship</th><th class="r">Tổng tiền</th></tr>
    <?php foreach ($orders as $order): ?>
      <tr>
        <td><a href="<?= url('admin/order_view.php?id=' . urlencode($order['MaHD'])) ?>"><?= h($order['MaHD']) ?></a></td>
        <td><?= fdate($order['NgayLapHD'], 'd/m/Y H:i') ?></td>
        <td><?= h($order['PTThanhToan']) ?></td>
        <td><?= h($order['TrangThaiThanhToan'] ?? 'Chưa thanh toán') ?></td>
        <td><?= badge($order['TrangThai']) ?></td>
        <td class="r"><?= money($order['TienHang']) ?></td>
        <td class="r"><?= (float)$order['GiamVoucher'] > 0 ? '−' . money($order['GiamVoucher']) : '—' ?></td>
        <td class="r"><?= money($order['PhiVanChuyen']) ?></td>
        <td class="r"><b><?= money(orderTotal($order)) ?></b></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$orders): ?><tr><td colspan="9" class="muted">Khách hàng chưa có đơn hàng.</td></tr><?php endif; ?>
  </table></div>
  <?= pager($orderCount, $perPage, $page, ['id' => $customer['MaKH'], 'q' => $search]) ?>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
