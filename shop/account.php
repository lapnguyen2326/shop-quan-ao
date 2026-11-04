<?php
require __DIR__ . '/includes/bootstrap.php';
requireLogin();
$u = me();
$kh = $u['MaKH'] ? one('SELECT * FROM KhachHang WHERE MaKH = ?', [$u['MaKH']]) : null;
$canUseLoyalty = isCustomerAccount() && $kh;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (post('action') === 'redeem') {
        try {
            if (!$canUseLoyalty) throw new RuntimeException('Chỉ tài khoản khách hàng mới được đổi điểm.');
            $reward = redeemLoyaltyReward($kh['MaKH'], (int)post('tier'));
            flash('Đổi thành công voucher ' . $reward['code'] . ' giảm ' . money($reward['discount']) . '.');
        } catch (RuntimeException $e) { flash($e->getMessage(), 'err'); }
    } elseif (post('action') === 'profile') {
        $ten = post('ten');
        if ($ten === '') flash('Họ tên không được để trống.', 'err');
        elseif (post('email') !== '' && !filter_var(post('email'), FILTER_VALIDATE_EMAIL)) flash('Email không hợp lệ.', 'err');
        else {
            if (!$kh) {
                $ma = nextCode('KH', 'KhachHang', 'MaKH');
                run('INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (?,?,?,?,?,?)', [$ma, $ten, post('dc'), post('sdt'), '', post('email')]);
                run('UPDATE Users SET MaKH = ? WHERE UserName = ?', [$ma, $u['UserName']]);
                $_SESSION['user']['MaKH'] = $ma;
            } else run('UPDATE KhachHang SET TenKH = ?, DiaChi = ?, DienThoai = ?, Email = ? WHERE MaKH = ?', [$ten, post('dc'), post('sdt'), post('email'), $kh['MaKH']]);
            run('UPDATE Users SET HoTen = ? WHERE UserName = ?', [$ten, $u['UserName']]);
            $_SESSION['user']['HoTen'] = $ten;
            flash('Đã lưu thông tin.');
        }
    } elseif (post('action') === 'password') {
        $row = one('SELECT Password FROM Users WHERE UserName = ?', [$u['UserName']]);
        if (!password_verify((string)$_POST['old'], (string)$row['Password'])) flash('Mật khẩu hiện tại không đúng.', 'err');
        elseif (strlen((string)$_POST['new']) < 6) flash('Mật khẩu mới cần tối thiểu 6 ký tự.', 'err');
        else { run('UPDATE Users SET Password = ? WHERE UserName = ?', [password_hash($_POST['new'], PASSWORD_DEFAULT), $u['UserName']]); flash('Đã đổi mật khẩu.'); }
    }
    redirect('account.php');
}
$orders = $u['MaKH'] ? all(orderSelect() . ' WHERE h.MaKH = ? ORDER BY h.NgayLapHD DESC', [$u['MaKH']]) : [];
$myVouchers = $canUseLoyalty ? all('SELECT * FROM Voucher WHERE MaKH = ? ORDER BY MaVoucher DESC', [$kh['MaKH']]) : [];
$title = 'Tài khoản';
include __DIR__ . '/includes/header.php';
?>
<h1>Xin chào, <?= h($u['HoTen']) ?></h1>
<?php if ($canUseLoyalty): ?><p class="member-points">Thành viên · <b><?= number_format((int)$kh['DiemTichLuy']) ?> điểm</b> <span class="muted">(1 điểm / 10.000 ₫, cộng khi đơn hoàn thành)</span></p><?php endif; ?>
<div class="account">
  <section>
    <?php if ($canUseLoyalty): ?>
    <h2>Đổi điểm lấy voucher</h2>
    <div class="table-wrap"><table><tr><th>Điểm cần đổi</th><th>Ưu đãi</th><th></th></tr>
      <?php foreach ([100 => 50000, 300 => 120000, 500 => 200000] as $tier => $discount): ?>
        <tr><td><?= number_format($tier) ?> điểm</td><td>Giảm <?= money($discount) ?></td><td><form method="post" class="inline"><?= csrf() ?><input type="hidden" name="action" value="redeem"><input type="hidden" name="tier" value="<?= $tier ?>"><button class="btn" <?= (int)$kh['DiemTichLuy'] < $tier ? 'disabled' : '' ?>>Đổi</button></form></td></tr>
      <?php endforeach; ?>
    </table></div>
    <h2>Voucher của tôi</h2>
    <div class="table-wrap"><table><tr><th>Mã voucher</th><th>Giá trị</th><th>Trạng thái</th></tr>
      <?php foreach ($myVouchers as $ownedVoucher): ?>
        <tr><td><b><?= h($ownedVoucher['MaVoucher']) ?></b></td><td><?= money($ownedVoucher['GiaTri']) ?></td><td><?= (int)$ownedVoucher['KichHoat'] && ($ownedVoucher['SoLuong'] === null || (int)$ownedVoucher['DaSuDung'] < (int)$ownedVoucher['SoLuong']) ? 'Có thể sử dụng' : 'Đã sử dụng' ?></td></tr>
      <?php endforeach; if (!$myVouchers): ?><tr><td colspan="3" class="muted">Bạn chưa đổi voucher nào.</td></tr><?php endif; ?>
    </table></div>
    <?php endif; ?>
    <h2>Đơn hàng của tôi</h2>
    <?php if (!$orders): ?><div class="empty"><p>Bạn chưa có đơn hàng nào.</p><a class="btn" href="<?= url('products.php') ?>">Mua sắm ngay</a></div>
    <?php else: ?>
    <div class="table-wrap"><table>
      <tr><th>Mã đơn</th><th>Ngày đặt</th><th>Trạng thái</th><th class="r">Tổng tiền</th><th></th></tr>
      <?php foreach ($orders as $o): ?>
        <tr><td><?= h($o['MaHD']) ?></td><td><?= fdate($o['NgayLapHD'], 'd/m/Y') ?></td><td><?= badge($o['TrangThai']) ?></td>
            <td class="r"><?= money(orderTotal($o)) ?></td><td><a href="<?= url('order_view.php?id=' . urlencode($o['MaHD'])) ?>">Chi tiết</a></td></tr>
      <?php endforeach; ?>
    </table></div>
    <?php endif; ?>
  </section>
  <section>
    <form method="post" class="form-card"><h2>Thông tin cá nhân</h2><?= csrf() ?><input type="hidden" name="action" value="profile">
      <label>Họ và tên<input name="ten" value="<?= h($kh['TenKH'] ?? $u['HoTen']) ?>" required></label>
      <label>Số điện thoại<input name="sdt" value="<?= h($kh['DienThoai'] ?? '') ?>"></label>
      <label>Email<input type="email" name="email" value="<?= h($kh['Email'] ?? '') ?>"></label>
      <label>Địa chỉ<input name="dc" value="<?= h($kh['DiaChi'] ?? '') ?>"></label>
      <button class="btn">Lưu thông tin</button></form>
    <form method="post" class="form-card"><h2>Đổi mật khẩu</h2><?= csrf() ?><input type="hidden" name="action" value="password">
      <label>Mật khẩu hiện tại<input type="password" name="old" required></label>
      <label>Mật khẩu mới<input type="password" name="new" required></label>
      <button class="btn ghost">Đổi mật khẩu</button></form>
  </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
