<?php
$title = 'Loại sản phẩm';
require __DIR__ . '/_header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = post('action'); $id = (int)post('id'); $ten = post('ten');
    if ($a === 'delete') {
        if ((int)val('SELECT COUNT(*) FROM SanPham WHERE MaLoaiSP = ?', [$id]) > 0) flash('Loại này còn sản phẩm, hãy chuyển hoặc xóa sản phẩm trước.', 'err');
        else { run('DELETE FROM LoaiSP WHERE MaLoaiSP = ?', [$id]); flash('Đã xóa loại sản phẩm.'); }
    } elseif ($ten === '') flash('Vui lòng nhập tên loại.', 'err');
    elseif ($a === 'add') { run('INSERT INTO LoaiSP (TenLoaiSP) VALUES (?)', [$ten]); flash('Đã thêm loại sản phẩm.'); }
    elseif ($a === 'edit') { run('UPDATE LoaiSP SET TenLoaiSP = ? WHERE MaLoaiSP = ?', [$ten, $id]); flash('Đã cập nhật.'); }
    redirect('admin/categories.php');
}
$rows = all('SELECT l.*, (SELECT COUNT(*) FROM SanPham s WHERE s.MaLoaiSP = l.MaLoaiSP) AS SL FROM LoaiSP l ORDER BY l.MaLoaiSP');
?>
<div class="page-head"><h1>Loại sản phẩm</h1></div>
<form method="post" class="filters"><?= csrf() ?><input type="hidden" name="action" value="add"><input name="ten" placeholder="Tên loại mới, ví dụ: Áo len" required><button class="btn">Thêm loại</button></form>
<div class="table-wrap"><table><tr><th>Mã</th><th>Tên loại</th><th class="r">Số sản phẩm</th><th></th></tr>
<?php foreach ($rows as $r): ?>
  <tr><td><?= (int)$r['MaLoaiSP'] ?></td>
  <td><form method="post" class="inline"><?= csrf() ?><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= (int)$r['MaLoaiSP'] ?>"><input name="ten" value="<?= h($r['TenLoaiSP']) ?>"> <button class="link">Lưu</button></form></td>
  <td class="r"><?= (int)$r['SL'] ?></td>
  <td><form method="post" onsubmit="return confirm('Xóa loại này?')"><?= csrf() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['MaLoaiSP'] ?>"><button class="link danger">Xóa</button></form></td></tr>
<?php endforeach; ?></table></div>
<?php require __DIR__ . '/_footer.php'; ?>
