<?php
$title = 'Đơn hàng';
require __DIR__ . '/_header.php';
$st = $_GET['st'] ?? ''; $qs = trim($_GET['q'] ?? ''); $page = max(1, (int)($_GET['page'] ?? 1)); $per = 12;
$w = ['1=1']; $p = [];
if (in_array($st, STATUSES)) { $w[] = 'h.TrangThai = ?'; $p[] = $st; }
if ($qs !== '') { $w[] = '(h.MaHD LIKE ? OR k.TenKH LIKE ? OR h.DienThoaiGiao LIKE ?)'; array_push($p, "%$qs%", "%$qs%", "%$qs%"); }
$W = implode(' AND ', $w);
$total = (int)val("SELECT COUNT(*) FROM HoaDon h LEFT JOIN KhachHang k ON k.MaKH = h.MaKH WHERE $W", $p);
$rows = all(limitSql(orderSelect() . " WHERE $W ORDER BY h.NgayLapHD DESC, h.MaHD DESC", $per, ($page - 1) * $per), $p);
?>
<div class="page-head"><h1>Đơn hàng <small class="muted">(<?= $total ?>)</small></h1></div>
<form class="filters" method="get">
  <input name="q" placeholder="Mã đơn, tên khách hoặc số điện thoại" value="<?= h($qs) ?>">
  <select name="st"><option value="">Mọi trạng thái</option><?php foreach (STATUSES as $s): ?><option <?= $st === $s ? 'selected' : '' ?>><?= h($s) ?></option><?php endforeach; ?></select>
  <button class="btn ghost">Lọc</button>
</form>
<div class="table-wrap"><table><tr><th>Mã</th><th>Khách hàng</th><th>Ngày đặt</th><th>Trạng thái</th><th>Người xử lý</th><th>Phương thức</th><th>Thanh toán</th><th class="r">Tổng tiền</th><th></th></tr>
<?php foreach ($rows as $o): ?>
  <tr><td><b><?= h($o['MaHD']) ?></b></td><td><?= h($o['TenKH']) ?></td><td><?= fdate($o['NgayLapHD']) ?></td><td><?= badge($o['TrangThai']) ?></td><td><?= h(orderProcessorLabel($o)) ?></td><td><?= h($o['PTThanhToan']) ?></td><td><?= h($o['TrangThaiThanhToan'] ?? 'Chưa thanh toán') ?></td>
  <td class="r"><?= money(orderTotal($o)) ?></td><td><a href="<?= url('admin/order_view.php?id=' . urlencode($o['MaHD'])) ?>">Chi tiết</a></td></tr>
<?php endforeach; if (!$rows) echo '<tr><td colspan="9" class="muted">Không có đơn hàng.</td></tr>'; ?></table></div>
<?= pager($total, $per, $page, ['q' => $qs, 'st' => $st]) ?>
<?php require __DIR__ . '/_footer.php'; ?>
