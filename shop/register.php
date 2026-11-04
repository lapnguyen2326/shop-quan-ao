<?php
require __DIR__ . '/includes/bootstrap.php';
$f = ['user' => '', 'ten' => '', 'sdt' => '', 'dc' => '', 'email' => ''];
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $_) $f[$k] = post($k);
    $pw = (string)($_POST['pass'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $f['user'])) $err = 'Tên đăng nhập gồm 3–30 ký tự chữ, số, dấu chấm hoặc gạch dưới.';
    elseif ($f['ten'] === '') $err = 'Vui lòng nhập họ tên.';
    elseif (strlen($pw) < 6) $err = 'Mật khẩu cần tối thiểu 6 ký tự.';
    elseif ($pw !== ($_POST['pass2'] ?? '')) $err = 'Hai mật khẩu không khớp.';
    elseif ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $err = 'Email không hợp lệ.';
    elseif (one('SELECT 1 AS x FROM Users WHERE UserName = ?', [$f['user']])) $err = 'Tên đăng nhập đã được sử dụng.';
    if (!$err) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $ma = nextCode('KH', 'KhachHang', 'MaKH');
            run('INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (?,?,?,?,?,?)', [$ma, $f['ten'], $f['dc'], $f['sdt'], '', $f['email']]);
            run('INSERT INTO Users (UserName, Password, HoTen, Quyen, MaKH) VALUES (?,?,?,?,?)', [$f['user'], password_hash($pw, PASSWORD_DEFAULT), $f['ten'], 'khach', $ma]);
            $pdo->commit();
            loginUser(['UserName' => $f['user'], 'HoTen' => $f['ten'], 'Quyen' => 'khach', 'MaKH' => $ma]);
            flash('Đăng ký thành công. Chào mừng bạn!');
            redirect('account.php');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = 'Không thể đăng ký lúc này: ' . $e->getMessage();
        }
    }
}
$title = 'Đăng ký';
include __DIR__ . '/includes/header.php';
?>
<div class="auth">
  <form method="post" class="form-card">
    <h1>Tạo tài khoản</h1>
    <?php if ($err): ?><div class="flash err"><?= h($err) ?></div><?php endif; ?>
    <?= csrf() ?>
    <label>Tên đăng nhập<input name="user" value="<?= h($f['user']) ?>" required></label>
    <label>Họ và tên<input name="ten" value="<?= h($f['ten']) ?>" required></label>
    <div class="two"><label>Số điện thoại<input name="sdt" value="<?= h($f['sdt']) ?>"></label>
    <label>Email<input type="email" name="email" value="<?= h($f['email']) ?>"></label></div>
    <label>Địa chỉ<input name="dc" value="<?= h($f['dc']) ?>"></label>
    <div class="two"><label>Mật khẩu<input type="password" name="pass" required></label>
    <label>Nhập lại mật khẩu<input type="password" name="pass2" required></label></div>
    <button class="btn">Đăng ký</button>
    <p class="muted">Đã có tài khoản? <a href="<?= url('login.php') ?>">Đăng nhập</a></p>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
