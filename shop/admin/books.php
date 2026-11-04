<?php
$title = 'Sổ sách';
require __DIR__ . '/_header.php';
require __DIR__ . '/_books_data.php';
$filters = booksFilters();
$summary = booksSummary($filters['where'], $filters['params']);
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 20;
$total = (int)$summary['SoDon'];
$rows = all(limitSql(orderSelect() . $filters['where'] . ' ORDER BY h.NgayLapHD DESC, h.MaHD DESC', $per, ($page - 1) * $per), $filters['params']);
$query = array_filter(['st' => $filters['status'], 'from' => $filters['from'], 'to' => $filters['to'], 'q' => $filters['search']], 'strlen');
?>
<div class="page-head"><h1>Sổ sách <small class="muted">(<?= $total ?> đơn)</small></h1>
  <a class="btn ghost" href="<?= url('admin/books_export.php?' . http_build_query($query)) ?>">Tải sổ sách Excel</a></div>
<div class="stats">
  <div class="stat"><span>Doanh thu đơn hoàn thành</span><b><?= money($summary['DaThu']) ?></b></div>
  <div class="stat"><span>Giá trị đơn đang xử lý</span><b><?= money($summary['ChoXuLy']) ?></b></div>
  <div class="stat"><span>Lợi nhuận đơn hoàn thành</span><b><?= money($summary['LoiNhuan']) ?></b></div>
  <div class="stat"><span>Tổng đơn theo bộ lọc</span><b><?= $total ?></b></div>
</div>
<?php if ((int)$summary['DonChuaCoGiaVon'] > 0): ?><p class="muted">Chưa tính được lợi nhuận của <?= (int)$summary['DonChuaCoGiaVon'] ?> đơn hoàn thành vì giá vốn chưa được lưu tại thời điểm bán.</p><?php endif; ?>
<form class="filters" method="get">
  <input type="search" name="q" placeholder="Mã đơn hoặc tên khách" value="<?= h($filters['search']) ?>">
  <input type="date" name="from" aria-label="Từ ngày" value="<?= h($filters['from']) ?>">
  <input type="date" name="to" aria-label="Đến ngày" value="<?= h($filters['to']) ?>">
  <select name="st"><option value="">Mọi trạng thái</option><?php foreach (STATUSES as $status): ?><option value="<?= h($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= h($status) ?></option><?php endforeach; ?></select>
  <button class="btn ghost">Lọc sổ</button>
</form>
<div class="table-wrap"><table><tr><th>Mã đơn</th><th>Ngày lập</th><th>Khách hàng</th><th>Thanh toán</th><th>Trạng thái</th><th class="r">Tiền hàng</th><th class="r">Giá vốn</th><th class="r">Lợi nhuận</th><th>Voucher</th><th class="r">Phí ship</th><th class="r">Tổng cộng</th></tr>
<?php foreach ($rows as $order): ?>
  <tr><td><a href="<?= url('admin/order_view.php?id=' . urlencode($order['MaHD'])) ?>"><?= h($order['MaHD']) ?></a></td><td><?= fdate($order['NgayLapHD'], 'd/m/Y') ?></td><td><?= h($order['TenKH'] ?: $order['TenNguoiNhan']) ?></td><td><?= h($order['PTThanhToan']) ?></td><td><?= badge($order['TrangThai']) ?></td><td class="r"><?= money($order['TienHang']) ?></td><td class="r"><?= $order['TienVon'] === null ? 'Chưa có' : money($order['TienVon']) ?></td><td class="r"><?= $order['TrangThai'] !== 'Hoàn thành' || $order['TienVon'] === null ? '—' : money((float)$order['TienHang'] - (float)$order['GiamVoucher'] - (float)$order['TienVon']) ?></td><td><?= $order['GiamVoucher'] > 0 ? h($order['MaVoucher']) . ' −' . money($order['GiamVoucher']) : '—' ?></td><td class="r"><?= money($order['PhiVanChuyen']) ?></td><td class="r"><b><?= money(orderTotal($order)) ?></b></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="11" class="muted">Không có giao dịch phù hợp.</td></tr><?php endif; ?></table></div>
<?= pager($total, $per, $page, $query) ?>
<?php require __DIR__ . '/_footer.php'; ?>
