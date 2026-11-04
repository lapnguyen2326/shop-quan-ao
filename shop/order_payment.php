<?php
require __DIR__ . '/includes/bootstrap.php';
$id = trim((string)($_GET['id'] ?? ''));
$o = $id !== '' ? one(orderSelect() . ' WHERE h.MaHD = ?', [$id]) : null;
$mine = $o && (in_array($id, $_SESSION['my_orders'] ?? [], true) || (me() && me()['MaKH'] && me()['MaKH'] === $o['MaKH']));
if (!$mine) redirect('index.php');
if ($o['PTThanhToan'] !== 'Chuyển khoản') redirect('order_success.php?id=' . urlencode($o['MaHD']));

$amount = (int)round(orderTotal($o));
$canPay = !in_array($o['TrangThai'], ['Đã hủy', 'Hoàn thành'], true);
$transferContent = 'Thanh toan don ' . $o['MaHD'];
$qrBase = 'https://img.vietqr.io/image/' . rawurlencode(cfg('bank_bin')) . '-' . rawurlencode(cfg('bank_account')) . '-compact2.png';
$qrUrl = $qrBase . '?' . http_build_query([
    'amount' => $amount,
    'addInfo' => $transferContent,
    'accountName' => cfg('bank_account_name'),
], '', '&', PHP_QUERY_RFC3986);
$title = 'Thanh toán chuyển khoản';
include __DIR__ . '/includes/header.php';
?>
<nav class="crumbs"><a href="<?= url('products.php') ?>">Mua sắm</a> / Thanh toán đơn <?= h($o['MaHD']) ?></nav>
<div class="page-head"><h1>Thanh toán đơn hàng</h1><?= badge($o['TrangThai']) ?></div>
<?php if (($o['TrangThaiThanhToan'] ?? '') === 'Đã thanh toán'): ?>
  <div class="flash ok">Thanh toán thành công. Shop đã xác nhận nhận được tiền cho đơn hàng này.</div>
  <p class="muted">Mã đơn: <b><?= h($o['MaHD']) ?></b> · Số tiền đã xác nhận: <b><?= money($amount) ?></b></p>
<?php elseif (!$canPay): ?>
  <div class="flash err">Đơn hàng này đã <?= $o['TrangThai'] === 'Đã hủy' ? 'bị hủy' : 'được hoàn thành' ?>, không thể thanh toán bằng mã QR.</div>
<?php else: ?>
<div class="flash">Đang chờ shop xác nhận giao dịch. Vui lòng tải lại trang sau khi chuyển khoản.</div>
<div class="payment-layout">
  <section class="payment-qr">
    <h2>Quét mã QR để chuyển khoản</h2>
    <p class="muted">Mở ứng dụng ngân hàng và quét mã. Số tiền cùng nội dung chuyển khoản đã được điền sẵn.</p>
    <img src="<?= h($qrUrl) ?>" alt="Mã QR chuyển khoản đơn <?= h($o['MaHD']) ?>" width="360" height="360">
    <p class="muted">Đơn hàng <b><?= h($o['MaHD']) ?></b></p>
  </section>
  <aside class="summary payment-summary">
    <h2>Thông tin chuyển khoản</h2>
    <dl>
      <dt>Ngân hàng</dt><dd>VietinBank</dd>
      <dt>Số tài khoản</dt><dd><b><?= h(cfg('bank_account')) ?></b></dd>
      <dt>Chủ tài khoản</dt><dd><?= h(cfg('bank_account_name')) ?></dd>
      <dt>Nội dung</dt><dd><b><?= h($transferContent) ?></b></dd>
      <dt class="tot">Số tiền</dt><dd class="tot"><?= money($amount) ?></dd>
    </dl>
    <p class="muted">Đơn hàng sẽ được xác nhận sau khi cửa hàng kiểm tra giao dịch.</p>
    <a class="btn ghost block-btn" href="<?= url('order_success.php?id=' . urlencode($o['MaHD'])) ?>">Quay lại thông tin đơn</a>
  </aside>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>