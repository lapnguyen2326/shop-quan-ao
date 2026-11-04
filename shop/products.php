<?php
require __DIR__ . '/includes/bootstrap.php';
$c = (int)($_GET['c'] ?? 0);
$qs = trim($_GET['q'] ?? '');
$sale = !empty($_GET['sale']);
$sort = $_GET['s'] ?? 'new';
$page = max(1, (int)($_GET['page'] ?? 1));
$per = cfg('per_page');

$where = ['s.HienThi = 1']; $p = [];
if ($c) { $where[] = 's.MaLoaiSP = ?'; $p[] = $c; }
if ($qs !== '') { $where[] = '(s.TenSP LIKE ? OR s.MoTa LIKE ?)'; $p[] = "%$qs%"; $p[] = "%$qs%"; }
if ($sale) $where[] = 's.PhanTramGiam > 0';
$W = implode(' AND ', $where);
$orders = [
  'new' => 's.NgayTao DESC, s.MaSP', 'price_asc' => 's.DonGia * (100 - s.PhanTramGiam) ASC, s.MaSP',
  'price_desc' => 's.DonGia * (100 - s.PhanTramGiam) DESC, s.MaSP', 'name' => 's.TenSP ASC, s.MaSP',
];
$order = $orders[$sort] ?? $orders['new'];
$total = (int)val("SELECT COUNT(*) FROM SanPham s WHERE $W", $p);
$items = all(limitSql("SELECT s.* FROM SanPham s WHERE $W ORDER BY $order", $per, ($page - 1) * $per), $p);
$cats = all('SELECT MaLoaiSP, TenLoaiSP FROM LoaiSP ORDER BY MaLoaiSP');
$curCat = $c ? one('SELECT TenLoaiSP FROM LoaiSP WHERE MaLoaiSP = ?', [$c]) : null;
$heading = $sale ? 'Đang giảm giá' : ($curCat['TenLoaiSP'] ?? ($qs !== '' ? 'Kết quả cho “' . $qs . '”' : 'Tất cả sản phẩm'));
$title = $heading;
$extra = ['c' => $c ?: '', 'q' => $qs, 'sale' => $sale ? 1 : '', 's' => $sort === 'new' ? '' : $sort];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <h1><?= h($heading) ?></h1>
  <form method="get" class="sorter">
    <?php foreach (['c' => $c ?: '', 'q' => $qs, 'sale' => $sale ? 1 : ''] as $k => $v) if ($v !== '') echo '<input type="hidden" name="' . $k . '" value="' . h($v) . '">'; ?>
    <label>Sắp xếp
      <select name="s" onchange="this.form.submit()">
        <?php foreach (['new' => 'Mới nhất', 'price_asc' => 'Giá tăng dần', 'price_desc' => 'Giá giảm dần', 'name' => 'Tên A–Z'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>
</div>
<div class="chips">
  <a class="<?= !$c && !$sale ? 'on' : '' ?>" href="<?= url('products.php') ?>">Tất cả</a>
  <?php foreach ($cats as $k): ?><a class="<?= $c == $k['MaLoaiSP'] ? 'on' : '' ?>" href="<?= url('products.php?c=' . $k['MaLoaiSP']) ?>"><?= h($k['TenLoaiSP']) ?></a><?php endforeach; ?>
</div>
<p class="muted"><?= $total ?> sản phẩm</p>
<?php if ($items): ?>
  <div class="grid"><?php foreach ($items as $sp) echo productCard($sp); ?></div>
  <?= pager($total, $per, $page, $extra) ?>
<?php else: ?>
  <div class="empty"><p>Không tìm thấy sản phẩm phù hợp.</p><a class="btn" href="<?= url('products.php') ?>">Xem tất cả sản phẩm</a></div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
