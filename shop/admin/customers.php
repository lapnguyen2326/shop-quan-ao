<?php
$title = 'Khách hàng';
require __DIR__ . '/_header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isAdmin()) { http_response_code(403); exit('Bạn không có quyền chỉnh sửa khách hàng.'); }
    $ma = post('ma');
    if (post('action') === 'delete') {
        if ((int)val('SELECT COUNT(*) FROM HoaDon WHERE MaKH = ?', [$ma]) > 0) flash('Khách hàng đã có đơn hàng nên không thể xóa.', 'err');
        else { run('DELETE FROM KhachHang WHERE MaKH = ?', [$ma]); flash('Đã xóa khách hàng.'); }
    } elseif (post('action') === 'save') {
        if (post('ten') === '') flash('Vui lòng nhập tên khách hàng.', 'err');
        elseif ($ma !== '' && one('SELECT 1 AS x FROM KhachHang WHERE MaKH = ?', [$ma])) {
            run('UPDATE KhachHang SET TenKH=?, DiaChi=?, DienThoai=?, Fax=?, Email=? WHERE MaKH=?', [post('ten'), post('dc'), post('sdt'), post('fax'), post('email'), $ma]); flash('Đã cập nhật khách hàng.');
        } else { run('INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (?,?,?,?,?,?)', [nextCode('KH', 'KhachHang', 'MaKH'), post('ten'), post('dc'), post('sdt'), post('fax'), post('email')]); flash('Đã thêm khách hàng.'); }
    }
    redirect('admin/customers.php');
}
$qs = trim($_GET['q'] ?? ''); $page = max(1, (int)($_GET['page'] ?? 1)); $per = 15;
$w = '1=1'; $p = [];
if ($qs !== '') { $w = '(k.TenKH LIKE ? OR k.MaKH LIKE ? OR k.DienThoai LIKE ?)'; $p = ["%$qs%", "%$qs%", "%$qs%"]; }
$total = (int)val("SELECT COUNT(*) FROM KhachHang k WHERE $w", $p);
$rows = all(limitSql("SELECT k.*, (SELECT COUNT(*) FROM HoaDon h WHERE h.MaKH = k.MaKH) AS SoDon FROM KhachHang k WHERE $w ORDER BY k.MaKH", $per, ($page - 1) * $per), $p);
$customerAccounts = [];
foreach (all('SELECT UserName, MaKH FROM Users WHERE MaKH IS NOT NULL ORDER BY UserName') as $account) {
    $customerAccounts[$account['MaKH']][] = $account['UserName'];
}
$e = isAdmin() && isset($_GET['edit']) ? one('SELECT * FROM KhachHang WHERE MaKH = ?', [$_GET['edit']]) : null;
?>
<div class="page-head"><h1>Khách hàng <small class="muted">(<?= $total ?>)</small></h1></div>
<?php if (isAdmin()): ?>
<form method="post" class="panel form-card"><?= csrf() ?><input type="hidden" name="action" value="save"><input type="hidden" name="ma" value="<?= h($e['MaKH'] ?? '') ?>">
  <h2><?= $e ? 'Sửa khách hàng ' . h($e['MaKH']) : 'Thêm khách hàng' ?></h2>
  <div class="three"><label>Họ tên<input name="ten" value="<?= h($e['TenKH'] ?? '') ?>" required></label><label>Điện thoại<input name="sdt" value="<?= h($e['DienThoai'] ?? '') ?>"></label><label>Fax<input name="fax" value="<?= h($e['Fax'] ?? '') ?>"></label></div>
  <div class="two"><label>Địa chỉ<input name="dc" value="<?= h($e['DiaChi'] ?? '') ?>"></label><label>Email<input name="email" value="<?= h($e['Email'] ?? '') ?>"></label></div>
  <div><button class="btn">Lưu</button> <?php if ($e): ?><a class="btn ghost" href="<?= url('admin/customers.php') ?>">Hủy sửa</a><?php endif; ?></div>
</form>
<?php endif; ?>
<form class="filters" method="get"><input name="q" placeholder="Tìm tên, mã hoặc số điện thoại" value="<?= h($qs) ?>"><button class="btn ghost">Tìm</button></form>
<div class="table-wrap"><table><tr><th>Mã</th><th>Họ tên</th><th>Điện thoại</th><th>Địa chỉ</th><th>Email</th><th>Tài khoản đăng nhập</th><th class="r">Điểm</th><th class="r">Số đơn</th><th></th></tr>
<?php foreach ($rows as $k): ?>
  <tr><td><?= h($k['MaKH']) ?></td><td><?= h($k['TenKH']) ?></td><td><?= h($k['DienThoai']) ?></td><td><?= h($k['DiaChi']) ?></td><td><?= h($k['Email']) ?></td>
  <td><?= h(implode(', ', $customerAccounts[$k['MaKH']] ?? [])) ?></td><td class="r"><?= number_format((int)$k['DiemTichLuy']) ?></td><td class="r"><?= (int)$k['SoDon'] ?></td>
  <td class="actions"><a href="<?= url('admin/customer_vouchers.php?id=' . urlencode($k['MaKH'])) ?>">Voucher</a><?php if (isAdmin()): ?> · <a href="<?= url('admin/customer_ledger.php?id=' . urlencode($k['MaKH'])) ?>">Sổ sách</a> · <a href="?edit=<?= urlencode($k['MaKH']) ?>">Sửa</a>
  <form method="post" onsubmit="return confirm('Xóa khách hàng này?')"><?= csrf() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="ma" value="<?= h($k['MaKH']) ?>"><button class="link danger">Xóa</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></table></div>
<?= pager($total, $per, $page, ['q' => $qs]) ?>
<?php require __DIR__ . '/_footer.php'; ?>
