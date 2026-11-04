<?php
$title = 'Voucher';
require __DIR__ . '/_header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isAdmin()) { http_response_code(403); exit('Bạn không có quyền chỉnh sửa voucher.'); }
    if (post('action') === 'toggle') {
        run('UPDATE Voucher SET KichHoat = ? WHERE MaVoucher = ?', [(int)post('active'), strtoupper(post('code'))]);
        flash('Đã cập nhật trạng thái voucher.');
    } elseif (post('action') === 'create') {
        $code = strtoupper(post('code'));
        $type = post('type');
        $value = (float)post('value');
        $minimum = (float)post('minimum', '0');
        $cap = post('cap') === '' ? null : (float)post('cap');
        $quantity = post('quantity') === '' ? null : (int)post('quantity');
        $start = post('start'); $end = post('end');
        if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $code)) flash('Mã voucher gồm 3–30 ký tự chữ, số, gạch ngang hoặc gạch dưới.', 'err');
        elseif (!in_array($type, ['phantram', 'tienmat'], true) || $value <= 0 || ($type === 'phantram' && $value > 100)) flash('Mức giảm không hợp lệ.', 'err');
        elseif ($minimum < 0 || ($cap !== null && $cap <= 0) || ($quantity !== null && $quantity <= 0)) flash('Điều kiện giá trị, giảm tối đa hoặc số lượt không hợp lệ.', 'err');
        elseif ($start !== '' && $end !== '' && $start > $end) flash('Ngày kết thúc phải sau ngày bắt đầu.', 'err');
        elseif (one('SELECT 1 AS x FROM Voucher WHERE MaVoucher = ?', [$code])) flash('Mã voucher đã tồn tại.', 'err');
        else {
            run('INSERT INTO Voucher (MaVoucher, LoaiGiam, GiaTri, DonToiThieu, GiamToiDa, SoLuong, DaSuDung, NgayBatDau, NgayKetThuc, KichHoat) VALUES (?,?,?,?,?,?,?,?,?,?)', [
                $code, $type, $value, $minimum, $cap, $quantity, 0,
                $start !== '' ? dbDate($start . ' 00:00:00') : null,
                $end !== '' ? dbDate($end . ' 23:59:59') : null,
                1,
            ]);
            flash('Đã tạo voucher ' . $code . '.');
        }
    }
    redirect('admin/vouchers.php');
}
$vouchers = all('SELECT v.*, k.TenKH AS TenChuSoHuu, v.MaKH AS MaKHChuSoHuu FROM Voucher v LEFT JOIN KhachHang k ON k.MaKH = v.MaKH ORDER BY v.KichHoat DESC, v.MaVoucher');
?>
<div class="page-head"><h1>Voucher</h1></div>
<?php if (isAdmin()): ?>
<form method="post" class="form-card">
  <h2>Tạo mã mới</h2>
  <?= csrf() ?><input type="hidden" name="action" value="create">
    <div class="three"><label>Mã voucher<input name="code" maxlength="30" required></label>
      <label>Kiểu giảm<select name="type"><option value="phantram">Phần trăm</option><option value="tienmat">Số tiền</option></select></label>
      <label>Mức giảm<input name="value" type="number" min="1" step="1" required></label></div>
    <div class="three"><label>Đơn tối thiểu (₫)<input name="minimum" type="number" min="0" value="0"></label>
      <label>Giảm tối đa (₫, tùy chọn)<input name="cap" type="number" min="1"></label>
      <label>Số lượt (để trống = không giới hạn)<input name="quantity" type="number" min="1"></label></div>
    <div class="two"><label>Bắt đầu<input name="start" type="date"></label><label>Kết thúc<input name="end" type="date"></label></div>
    <button class="btn">Tạo voucher</button>
</form>
<?php else: ?>
<p class="muted">Bạn đang xem voucher ở chế độ chỉ đọc.</p>
<?php endif; ?>
<div class="table-wrap"><table><tr><th>Mã</th><th>Khách sở hữu</th><th>Ưu đãi</th><th>Điều kiện</th><th>Lượt dùng</th><th>Thời hạn</th><th>Trạng thái</th><th></th></tr>
<?php foreach ($vouchers as $voucher): ?>
  <tr><td><b><?= h($voucher['MaVoucher']) ?></b></td><td><?php if ($voucher['MaKHChuSoHuu']): ?><a href="<?= url('admin/customer_vouchers.php?id=' . urlencode($voucher['MaKHChuSoHuu'])) ?>"><?= h($voucher['TenChuSoHuu'] ?: $voucher['MaKHChuSoHuu']) ?></a><?php else: ?>Dùng chung<?php endif; ?></td>
    <td><?= $voucher['LoaiGiam'] === 'phantram' ? h($voucher['GiaTri']) . '%' : money($voucher['GiaTri']) ?><?php if ($voucher['GiamToiDa'] !== null): ?><br><small class="muted">Tối đa <?= money($voucher['GiamToiDa']) ?></small><?php endif; ?></td>
    <td><?= $voucher['DonToiThieu'] > 0 ? 'Từ ' . money($voucher['DonToiThieu']) : 'Mọi đơn' ?></td>
    <td><?= (int)$voucher['DaSuDung'] ?> / <?= $voucher['SoLuong'] === null ? 'Không giới hạn' : (int)$voucher['SoLuong'] ?></td>
    <td><?= $voucher['NgayBatDau'] ? fdate($voucher['NgayBatDau'], 'd/m/Y') : 'Không giới hạn' ?> – <?= $voucher['NgayKetThuc'] ? fdate($voucher['NgayKetThuc'], 'd/m/Y') : 'Không giới hạn' ?></td>
    <td><?= (int)$voucher['KichHoat'] ? 'Đang bật' : 'Đã tắt' ?></td>
    <td><?php if (isAdmin()): ?><form method="post" class="inline"><?= csrf() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="code" value="<?= h($voucher['MaVoucher']) ?>"><input type="hidden" name="active" value="<?= (int)!$voucher['KichHoat'] ?>"><button class="link"><?= (int)$voucher['KichHoat'] ? 'Tắt' : 'Bật' ?></button></form><?php else: ?>—<?php endif; ?></td>
  </tr>
<?php endforeach; if (!$vouchers) echo '<tr><td colspan="8" class="muted">Chưa có voucher.</td></tr>'; ?></table></div>
<?php require __DIR__ . '/_footer.php'; ?>