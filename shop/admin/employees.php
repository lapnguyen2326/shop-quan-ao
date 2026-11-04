<?php
$title = 'Quản lý nhân viên';
require __DIR__ . '/_header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)post('id');
    if (post('action') === 'delete') {
        $linkedAccount = one('SELECT Quyen FROM Users WHERE MaNV = ?', [$id]);
        if (($linkedAccount['Quyen'] ?? '') === 'quanly') {
            flash('Hãy hạ quyền quản lý trong trang tài khoản trước khi xóa nhân viên này.', 'err');
        } else {
            run("UPDATE Users SET Quyen = 'khach', MaNV = NULL WHERE MaNV = ?", [$id]);
            run('DELETE FROM Nhanvien WHERE MaNV = ?', [$id]);
            flash('Đã xóa nhân viên (các đơn của nhân viên này chuyển về chưa phân công).');
        }
    }
    elseif (post('ten') === '') flash('Vui lòng nhập tên nhân viên.', 'err');
    elseif ($id) { run('UPDATE Nhanvien SET HoNV=?, Ten=?, DiaChi=?, DienThoai=? WHERE MaNV=?', [post('ho'), post('ten'), post('dc'), post('sdt'), $id]); flash('Đã cập nhật nhân viên.'); }
    else { run('INSERT INTO Nhanvien (HoNV, Ten, DiaChi, DienThoai) VALUES (?,?,?,?)', [post('ho'), post('ten'), post('dc'), post('sdt')]); flash('Đã thêm nhân viên.'); }
    redirect('admin/employees.php');
}
$rows = all('SELECT n.*, (SELECT COUNT(*) FROM HoaDon h WHERE h.MaNV = n.MaNV) AS SoDon FROM Nhanvien n ORDER BY n.MaNV');
$e = isset($_GET['edit']) ? one('SELECT * FROM Nhanvien WHERE MaNV = ?', [(int)$_GET['edit']]) : null;
?>
<div class="page-head"><h1>Quản lý nhân viên</h1></div>
<form method="post" class="panel form-card"><?= csrf() ?><input type="hidden" name="id" value="<?= (int)($e['MaNV'] ?? 0) ?>">
  <h2><?= $e ? 'Sửa nhân viên #' . (int)$e['MaNV'] : 'Thêm nhân viên' ?></h2>
  <div class="two"><label>Họ đệm<input name="ho" value="<?= h($e['HoNV'] ?? '') ?>"></label><label>Tên<input name="ten" value="<?= h($e['Ten'] ?? '') ?>" required></label></div>
  <div class="two"><label>Địa chỉ<input name="dc" value="<?= h($e['DiaChi'] ?? '') ?>"></label><label>Điện thoại<input name="sdt" value="<?= h($e['DienThoai'] ?? '') ?>"></label></div>
  <div><button class="btn">Lưu</button> <?php if ($e): ?><a class="btn ghost" href="<?= url('admin/employees.php') ?>">Hủy sửa</a><?php endif; ?></div>
</form>
<div class="table-wrap"><table><tr><th>Mã</th><th>Họ tên</th><th>Địa chỉ</th><th>Điện thoại</th><th class="r">Đơn phụ trách</th><th></th></tr>
<?php foreach ($rows as $n): ?>
  <tr><td><?= (int)$n['MaNV'] ?></td><td><?= h(trim($n['HoNV'] . ' ' . $n['Ten'])) ?></td><td><?= h($n['DiaChi']) ?></td><td><?= h($n['DienThoai']) ?></td><td class="r"><?= (int)$n['SoDon'] ?></td>
  <td class="actions"><a href="?edit=<?= (int)$n['MaNV'] ?>">Sửa</a>
  <form method="post" onsubmit="return confirm('Xóa nhân viên này?')"><?= csrf() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$n['MaNV'] ?>"><button class="link danger">Xóa</button></form></td></tr>
<?php endforeach; ?></table></div>
<?php require __DIR__ . '/_footer.php'; ?>
