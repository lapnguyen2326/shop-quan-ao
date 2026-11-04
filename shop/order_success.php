<?php
require __DIR__ . '/includes/bootstrap.php';
$id = $_GET['id'] ?? '';
$o = one(orderSelect() . ' WHERE h.MaHD = ?', [$id]);
$mine = $o && (in_array($id, $_SESSION['my_orders'] ?? []) || (me() && me()['MaKH'] && me()['MaKH'] === $o['MaKH']));
if (!$mine) redirect('index.php');
$title = 'Đặt hàng thành công';
include __DIR__ . '/includes/header.php';
?>
<div class="empty ok-box">
  <h1>Cảm ơn bạn, đơn hàng đã được ghi nhận</h1>
  <p>Mã đơn hàng: <b><?= h($o['MaHD']) ?></b> · Tổng thanh toán: <b><?= money(orderTotal($o)) ?></b></p>
  <p class="muted">Shop sẽ liên hệ qua số <?= h($o['DienThoaiGiao']) ?> để xác nhận đơn trong thời gian sớm nhất.
  <?php if ($o['PTThanhToan'] === 'Chuyển khoản'): ?><br>Bạn chọn chuyển khoản. Nội dung chuyển tiền: “Thanh toan don <?= h($o['MaHD']) ?>”.<?php endif; ?></p>
  <?php if ($o['PTThanhToan'] === 'Chuyển khoản' && !in_array($o['TrangThai'], ['Đã hủy', 'Hoàn thành'], true)): ?><a class="btn" href="<?= url('order_payment.php?id=' . urlencode($o['MaHD'])) ?>">Mở mã QR thanh toán</a><?php endif; ?>
  <a class="btn" href="<?= url('products.php') ?>">Tiếp tục mua sắm</a>
  <?php if (me()): ?><a class="btn ghost" href="<?= url('order_view.php?id=' . urlencode($o['MaHD'])) ?>">Xem chi tiết đơn</a><?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
