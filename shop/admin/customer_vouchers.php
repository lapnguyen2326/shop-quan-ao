<?php
$title = 'Voucher khách hàng';
require __DIR__ . '/_header.php';
$customerId = trim((string)($_GET['id'] ?? ''));
$customer = one('SELECT * FROM KhachHang WHERE MaKH = ?', [$customerId]);
if (!$customer) { flash('Không tìm thấy khách hàng.', 'err'); redirect('admin/customers.php'); }
$vouchers = all('SELECT * FROM Voucher WHERE MaKH = ? ORDER BY MaVoucher DESC', [$customerId]);
?>
<nav class="crumbs"><a href="<?= url('admin/customers.php') ?>">Khách hàng</a> / Voucher</nav>
<div class="page-head"><h1><?= h($customer['TenKH']) ?> <small class="muted">(<?= h($customer['MaKH']) ?>)</small></h1></div>
<section class="panel">
  <h2>Thông tin thành viên</h2>
  <p>Điểm tích lũy: <b><?= number_format((int)$customer['DiemTichLuy']) ?></b></p>
  <p class="muted">Số điện thoại: <?= h($customer['DienThoai']) ?> · Email: <?= h($customer['Email']) ?></p>
</section>
<section class="panel">
  <h2>Voucher đã cấp / đổi</h2>
  <div class="table-wrap"><table><tr><th>Mã voucher</th><th>Ưu đãi</th><th>Đơn tối thiểu</th><th>Thời hạn</th><th>Trạng thái</th></tr>
  <?php foreach ($vouchers as $voucher): ?>
    <?php $available = (int)$voucher['KichHoat'] && ($voucher['SoLuong'] === null || (int)$voucher['DaSuDung'] < (int)$voucher['SoLuong']); ?>
    <tr>
      <td><b><?= h($voucher['MaVoucher']) ?></b></td>
      <td><?= $voucher['LoaiGiam'] === 'phantram' ? h($voucher['GiaTri']) . '%' : money($voucher['GiaTri']) ?></td>
      <td><?= (float)$voucher['DonToiThieu'] > 0 ? money($voucher['DonToiThieu']) : 'Mọi đơn' ?></td>
      <td><?= $voucher['NgayBatDau'] ? fdate($voucher['NgayBatDau'], 'd/m/Y') : 'Không giới hạn' ?> – <?= $voucher['NgayKetThuc'] ? fdate($voucher['NgayKetThuc'], 'd/m/Y') : 'Không giới hạn' ?></td>
      <td><?= $available ? 'Có thể sử dụng' : 'Đã sử dụng hoặc đã tắt' ?></td>
    </tr>
  <?php endforeach; if (!$vouchers): ?><tr><td colspan="5" class="muted">Khách hàng chưa có voucher riêng.</td></tr><?php endif; ?>
  </table></div>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
