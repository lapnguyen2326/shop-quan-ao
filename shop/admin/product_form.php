<?php
$title = 'Sản phẩm';
require __DIR__ . '/_header.php';
ensureProductCostColumn();
$old = one('SELECT * FROM SanPham WHERE MaSP = ?', [$_GET['id'] ?? '']);
$edit = (bool)$old;
$cats = all('SELECT * FROM LoaiSP ORDER BY MaLoaiSP');
$f = $old ?: ['MaSP' => '', 'TenSP' => '', 'DonViTinh' => 'Cái', 'DonGia' => '', 'GiaVon' => '', 'MaLoaiSP' => $cats[0]['MaLoaiSP'] ?? '', 'HinhSP' => '', 'MoTa' => '', 'KichCo' => 'S,M,L,XL', 'MauSac' => '', 'SoLuongTon' => 0, 'PhanTramGiam' => 0, 'SPMoi' => 0, 'HienThi' => 1];
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['MaSP', 'TenSP', 'DonViTinh', 'MoTa', 'KichCo', 'MauSac'] as $k) $f[$k] = post($k);
    foreach (['DonGia', 'GiaVon', 'MaLoaiSP', 'SoLuongTon', 'PhanTramGiam'] as $k) $f[$k] = (int)post($k);
    $f['SPMoi'] = isset($_POST['SPMoi']) ? 1 : 0; $f['HienThi'] = isset($_POST['HienThi']) ? 1 : 0;
    if ($edit) $f['MaSP'] = $old['MaSP'];
    if (!preg_match('/^[A-Za-z0-9]{2,10}$/', $f['MaSP'])) $err = 'Mã sản phẩm gồm 2–10 ký tự chữ hoặc số.';
    elseif ($f['TenSP'] === '') $err = 'Vui lòng nhập tên sản phẩm.';
    elseif ($f['GiaVon'] < 0) $err = 'Giá vốn không được âm.';
    elseif ($f['DonGia'] <= 0) $err = 'Giá bán phải lớn hơn 0.';
    elseif ($f['GiaVon'] > $f['DonGia']) $err = 'Giá vốn không được lớn hơn giá bán.';
    elseif ($f['PhanTramGiam'] < 0 || $f['PhanTramGiam'] > 90) $err = 'Phần trăm giảm giá từ 0 đến 90.';
    elseif ($f['SoLuongTon'] < 0) $err = 'Số lượng tồn không được âm.';
    elseif (!$edit && one('SELECT 1 AS x FROM SanPham WHERE MaSP = ?', [$f['MaSP']])) $err = 'Mã sản phẩm đã tồn tại.';
    if (!$err && !empty($_FILES['hinh']['name'])) {
        $up = $_FILES['hinh'];
        $ext = strtolower(pathinfo($up['name'], PATHINFO_EXTENSION));
        if ($up['error'] !== UPLOAD_ERR_OK || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']) || !@getimagesize($up['tmp_name'])) $err = 'Ảnh không hợp lệ (chỉ nhận jpg, png, webp, gif).';
        else {
            $name = $f['MaSP'] . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
            if (move_uploaded_file($up['tmp_name'], __DIR__ . '/../images/' . $name)) $f['HinhSP'] = 'images/' . $name; else $err = 'Không lưu được ảnh, kiểm tra quyền ghi thư mục images/.';
        }
    }
    if (!$err && !$edit && $f['HinhSP'] === '') $f['HinhSP'] = 'images/' . $f['MaSP'] . '.jpg';
    if (!$err) {
        $v = [$f['TenSP'], $f['DonViTinh'], $f['GiaVon'], $f['DonGia'], $f['MaLoaiSP'], $f['HinhSP'], $f['MoTa'], $f['KichCo'], $f['MauSac'], $f['SoLuongTon'], $f['PhanTramGiam'], $f['SPMoi'], $f['HienThi']];
        if ($edit) run('UPDATE SanPham SET TenSP=?, DonViTinh=?, GiaVon=?, DonGia=?, MaLoaiSP=?, HinhSP=?, MoTa=?, KichCo=?, MauSac=?, SoLuongTon=?, PhanTramGiam=?, SPMoi=?, HienThi=? WHERE MaSP=?', array_merge($v, [$f['MaSP']]));
        else run('INSERT INTO SanPham (TenSP, DonViTinh, GiaVon, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, MaSP, NgayTao) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', array_merge($v, [$f['MaSP'], nowStr()]));
        flash($edit ? 'Đã cập nhật sản phẩm.' : 'Đã thêm sản phẩm mới.');
        redirect('admin/products.php');
    }
}
?>
<div class="page-head"><h1><?= $edit ? 'Sửa sản phẩm ' . h($f['MaSP']) : 'Thêm sản phẩm' ?></h1></div>
<?php if ($err): ?><div class="flash err"><?= h($err) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="form-card wide-form"><?= csrf() ?>
  <div class="two"><label>Mã sản phẩm<input name="MaSP" value="<?= h($f['MaSP']) ?>" <?= $edit ? 'readonly' : 'required' ?>></label>
  <label>Tên sản phẩm<input name="TenSP" value="<?= h($f['TenSP']) ?>" required></label></div>
  <div class="three"><label>Loại<select name="MaLoaiSP"><?php foreach ($cats as $k): ?><option value="<?= $k['MaLoaiSP'] ?>" <?= $f['MaLoaiSP'] == $k['MaLoaiSP'] ? 'selected' : '' ?>><?= h($k['TenLoaiSP']) ?></option><?php endforeach; ?></select></label>
  <label>Đơn vị tính<input name="DonViTinh" value="<?= h($f['DonViTinh']) ?>"></label>
  <label>Giá vốn/đơn vị (₫)<input type="number" name="GiaVon" min="0" step="1000" value="<?= h($f['GiaVon'] ?? 0) ?>"></label></div>
  <div class="three"><label>Giá bán (₫)<input type="number" name="DonGia" min="0" step="1000" value="<?= h($f['DonGia'] !== '' ? (int)$f['DonGia'] : '') ?>" required></label>
  <label>Giảm giá (%)<input type="number" name="PhanTramGiam" min="0" max="90" value="<?= (int)$f['PhanTramGiam'] ?>"></label>
  <label>Tồn kho<input type="number" name="SoLuongTon" min="0" value="<?= (int)$f['SoLuongTon'] ?>"></label></div>
  <div class="three"><label>Ảnh sản phẩm<input type="file" name="hinh" accept="image/*"></label>
  <label>Lợi nhuận/đơn vị<?php $profit = productProfitAmount($f); ?><input value="<?= $profit === null ? 'Nhập giá vốn để tính' : money($profit) ?>" readonly></label>
  <label>Giá sau giảm<input value="<?= money(effPrice($f)) ?>" readonly></label></div>
  <div class="two"><label>Kích cỡ (cách nhau dấu phẩy)<input name="KichCo" value="<?= h($f['KichCo']) ?>"></label>
  <label>Màu sắc (cách nhau dấu phẩy)<input name="MauSac" value="<?= h($f['MauSac']) ?>"></label></div>
  <label>Mô tả<textarea name="MoTa" rows="4"><?= h($f['MoTa']) ?></textarea></label>
  <p class="muted">Ảnh hiện tại: <code><?= h($f['HinhSP'] ?: 'images/' . ($f['MaSP'] ?: 'MaSP') . '.jpg') ?></code> · Bạn cũng có thể chép ảnh thẳng vào thư mục <code>images/</code> với đúng tên này.</p>
  <div class="checks"><label><input type="checkbox" name="SPMoi" <?= (int)$f['SPMoi'] ? 'checked' : '' ?>> Đánh dấu hàng mới</label>
  <label><input type="checkbox" name="HienThi" <?= (int)$f['HienThi'] ? 'checked' : '' ?>> Hiển thị trên cửa hàng</label></div>
  <div><button class="btn">Lưu sản phẩm</button> <a class="btn ghost" href="<?= url('admin/products.php') ?>">Quay lại</a></div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
