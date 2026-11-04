<?php
require __DIR__ . '/../includes/bootstrap.php';
requireAdminPage();

$orderId = trim((string)($_GET['id'] ?? ''));
$order = one(orderSelect() . ' WHERE h.MaHD = ?', [$orderId]);
if (!$order) {
    http_response_code(404);
    exit('Không tìm thấy đơn hàng.');
}
$lines = orderLines($order['MaHD']);
$recipientName = $order['TenNguoiNhan'] ?: $order['TenKH'];
?><!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hóa đơn <?= h($order['MaHD']) ?> | <?= h(cfg('shop_name')) ?></title>
  <style>
    *{box-sizing:border-box}
    body{margin:0;background:#f3f4f6;color:#172033;font:15px/1.5 Arial,sans-serif}
    .toolbar{max-width:850px;margin:24px auto 12px;display:flex;justify-content:flex-end;gap:8px}
    .toolbar a,.toolbar button{padding:10px 16px;border:0;border-radius:6px;background:#e5e7eb;color:#172033;text-decoration:none;font:inherit;cursor:pointer}
    .toolbar button{background:#1f3b5b;color:#fff}
    .invoice{max-width:850px;margin:0 auto 32px;padding:42px;background:#fff;box-shadow:0 2px 12px #00000012}
    .store{text-align:center;border-bottom:2px solid #1f3b5b;padding-bottom:18px}
    h1{margin:0 0 8px;font-size:28px;letter-spacing:1px}
    .store p,.meta p{margin:3px 0}
    .meta{display:flex;justify-content:space-between;gap:24px;margin:24px 0}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px 8px;border-bottom:1px solid #d9dee7;text-align:left}
    th{background:#f3f6fa}
    .r{text-align:right;white-space:nowrap}
    .summary{width:min(100%,360px);margin:18px 0 0 auto}
    .summary td{border:0;padding:5px 8px}
    .summary .total td{border-top:2px solid #1f3b5b;padding-top:12px;font-size:18px;font-weight:bold}
    .note{margin-top:36px;text-align:center;color:#586174}
    @media print{body{background:#fff}.toolbar{display:none}.invoice{max-width:none;margin:0;padding:0;box-shadow:none}@page{margin:16mm}}
    @media(max-width:600px){.invoice{padding:22px}.meta{display:block}.meta>div+div{margin-top:16px}.toolbar{margin:12px;padding:0 12px}}
  </style>
</head>
<body>
  <div class="toolbar">
    <a href="<?= url('admin/order_view.php?id=' . urlencode($order['MaHD'])) ?>">Quay lại đơn hàng</a>
    <button type="button" onclick="window.print()">In / Lưu PDF</button>
  </div>
  <main class="invoice">
    <header class="store">
      <h1><?= h(cfg('shop_name')) ?></h1>
      <p><?= h(cfg('shop_address')) ?></p>
      <p>Điện thoại: <?= h(cfg('shop_phone')) ?> · Email: <?= h(cfg('shop_email')) ?></p>
    </header>
    <section class="meta">
      <div>
        <h2>HÓA ĐƠN BÁN HÀNG</h2>
        <p><b>Mã hóa đơn:</b> <?= h($order['MaHD']) ?></p>
        <p><b>Ngày lập:</b> <?= h(fdate($order['NgayLapHD'])) ?></p>
        <p><b>Trạng thái:</b> <?= h($order['TrangThai']) ?></p>
        <p><b>Người xử lý:</b> <?= h(orderProcessorLabel($order)) ?></p>
      </div>
      <div>
        <h3>Thông tin khách hàng</h3>
        <p><b>Người nhận:</b> <?= h($recipientName) ?></p>
        <p><b>Điện thoại:</b> <?= h($order['DienThoaiGiao']) ?></p>
        <p><b>Địa chỉ:</b> <?= h($order['DiaChiGiao']) ?></p>
        <p><b>Thanh toán:</b> <?= h($order['PTThanhToan']) ?> — <?= h($order['TrangThaiThanhToan'] ?? 'Chưa thanh toán') ?></p>
      </div>
    </section>
    <table>
      <thead><tr><th>STT</th><th>Sản phẩm</th><th>Phân loại</th><th class="r">Đơn giá</th><th class="r">SL</th><th class="r">Thành tiền</th></tr></thead>
      <tbody>
        <?php foreach ($lines as $index => $line): $unitPrice = (float)$line['DonGia'] * (1 - (float)$line['GiamGia']); ?>
          <tr>
            <td><?= $index + 1 ?></td>
            <td><?= h($line['TenSP'] ?? $line['MaSP']) ?><br><small><?= h($line['MaSP']) ?></small></td>
            <td><?= h(trim(($line['KichCo'] ?? '') . ' ' . ($line['MauSac'] ?? ''))) ?></td>
            <td class="r"><?= money($unitPrice) ?></td>
            <td class="r"><?= (int)$line['SoLuong'] ?></td>
            <td class="r"><?= money($unitPrice * (int)$line['SoLuong']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <table class="summary">
      <tbody>
        <tr><td>Tiền hàng</td><td class="r"><?= money($order['TienHang']) ?></td></tr>
        <?php if ((float)$order['GiamVoucher'] > 0): ?><tr><td>Voucher <?= h($order['MaVoucher']) ?></td><td class="r">−<?= money($order['GiamVoucher']) ?></td></tr><?php endif; ?>
        <tr><td>Phí vận chuyển</td><td class="r"><?= money($order['PhiVanChuyen']) ?></td></tr>
        <tr class="total"><td>Tổng thanh toán</td><td class="r"><?= money(orderTotal($order)) ?></td></tr>
      </tbody>
    </table>
    <?php if (!empty($order['GhiChu'])): ?><p><b>Ghi chú:</b> <?= h($order['GhiChu']) ?></p><?php endif; ?>
    <p class="note">Cảm ơn quý khách đã mua hàng!</p>
  </main>
</body>
</html>
