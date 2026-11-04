<?php
$title = 'Sản phẩm';
require __DIR__ . '/_header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isAdmin()) {
        http_response_code(403);
        exit('Nhân viên chỉ được xem danh sách sản phẩm.');
    }
    $ma = post('ma');
    if (post('action') === 'delete') {
        try { run('DELETE FROM SanPham WHERE MaSP = ?', [$ma]); flash('Đã xóa sản phẩm ' . $ma . '.'); }
        catch (PDOException $e) { run('UPDATE SanPham SET HienThi = 0 WHERE MaSP = ?', [$ma]); flash('Sản phẩm đã có trong đơn hàng nên không xóa được, hệ thống đã chuyển sang trạng thái ẩn.', 'err'); }
    } elseif (post('action') === 'toggle') run('UPDATE SanPham SET HienThi = ? WHERE MaSP = ?', [post('to') === '1' ? 1 : 0, $ma]);
    redirect('admin/products.php');
}
$qs = trim($_GET['q'] ?? ''); $c = (int)($_GET['c'] ?? 0); $page = max(1, (int)($_GET['page'] ?? 1)); $per = 12;
$w = ['1=1']; $p = [];
if ($qs !== '') { $w[] = '(s.TenSP LIKE ? OR s.MaSP LIKE ?)'; $p[] = "%$qs%"; $p[] = "%$qs%"; }
if ($c) { $w[] = 's.MaLoaiSP = ?'; $p[] = $c; }
$W = implode(' AND ', $w);
$total = (int)val("SELECT COUNT(*) FROM SanPham s WHERE $W", $p);
$rows = all(limitSql("SELECT s.*, l.TenLoaiSP FROM SanPham s LEFT JOIN LoaiSP l ON l.MaLoaiSP = s.MaLoaiSP WHERE $W ORDER BY s.MaLoaiSP, s.MaSP", $per, ($page - 1) * $per), $p);
$cats = all('SELECT * FROM LoaiSP ORDER BY MaLoaiSP');
?>
<div class="page-head"><h1><?= isStaff() ? 'Tồn kho sản phẩm' : 'Sản phẩm' ?> <small class="muted">(<?= $total ?>)</small></h1><?php if (isAdmin()): ?><a class="btn" href="<?= url('admin/product_form.php') ?>">Thêm sản phẩm</a><?php endif; ?></div>
<form class="filters" method="get">
  <input name="q" placeholder="Tìm theo tên hoặc mã" value="<?= h($qs) ?>">
  <select name="c"><option value="0">Tất cả loại</option><?php foreach ($cats as $k): ?><option value="<?= $k['MaLoaiSP'] ?>" <?= $c == $k['MaLoaiSP'] ? 'selected' : '' ?>><?= h($k['TenLoaiSP']) ?></option><?php endforeach; ?></select>
  <button class="btn ghost">Lọc</button>
</form>
<div class="table-wrap"><table>
  <tr><th></th><th>Mã</th><th>Tên sản phẩm</th><th>Loại</th><?php if (!isStaff()): ?><th class="r">Giá vốn</th><?php endif; ?><th class="r">Giá bán</th><?php if (!isStaff()): ?><th class="r">Lợi nhuận</th><?php endif; ?><th class="r">Tồn</th><th>Hiển thị</th><?php if (isAdmin()): ?><th></th><?php endif; ?></tr>
  <?php foreach ($rows as $s): ?>
  <tr class="<?= (int)$s['HienThi'] ? '' : 'dim' ?>">
    <td><img class="tiny" src="<?= h(img($s)) ?>" alt=""></td><td><?= h($s['MaSP']) ?></td><td><?= h($s['TenSP']) ?></td><td><?= h($s['TenLoaiSP']) ?></td>
    <?php if (!isStaff()): ?><td class="r"><?= money($s['GiaVon'] ?? 0) ?></td><?php endif; ?><td class="r"><?= money($s['DonGia']) ?></td><?php if (!isStaff()): ?><td class="r"><?= ($profit = productProfitAmount($s)) === null ? 'Chưa nhập giá vốn' : money($profit) ?></td><?php endif; ?><td class="r"><?= (int)$s['SoLuongTon'] ?></td>
    <td><?php if (isAdmin()): ?><form method="post"><?= csrf() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="ma" value="<?= h($s['MaSP']) ?>"><input type="hidden" name="to" value="<?= (int)$s['HienThi'] ? 0 : 1 ?>"><button class="link"><?= (int)$s['HienThi'] ? 'Đang bán' : 'Đang ẩn' ?></button></form><?php else: ?><?= (int)$s['HienThi'] ? 'Đang bán' : 'Đang ẩn' ?><?php endif; ?></td>
    <?php if (isAdmin()): ?><td class="actions"><a href="<?= url('admin/product_form.php?id=' . urlencode($s['MaSP'])) ?>">Sửa</a>
      <form method="post" onsubmit="return confirm('Xóa sản phẩm này?')"><?= csrf() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="ma" value="<?= h($s['MaSP']) ?>"><button class="link danger">Xóa</button></form></td>
    <?php endif; ?>
  </tr>
  <?php endforeach; if (!$rows) echo '<tr><td colspan="' . (isStaff() ? 7 : (isAdmin() ? 10 : 9)) . '" class="muted">Không có sản phẩm.</td></tr>'; ?>
</table></div>
<?= pager($total, $per, $page, ['q' => $qs, 'c' => $c ?: '']) ?>
<?php require __DIR__ . '/_footer.php'; ?>
