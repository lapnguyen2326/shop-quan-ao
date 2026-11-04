<?php
require __DIR__ . '/includes/bootstrap.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expectedCaptcha = (string)($_SESSION['login_captcha'] ?? '');
    $captchaExpires = (int)($_SESSION['login_captcha_expires'] ?? 0);
    unset($_SESSION['login_captcha'], $_SESSION['login_captcha_expires']);
    $providedCaptcha = strtoupper(post('captcha'));
    if ($expectedCaptcha === '' || $captchaExpires < time() || !hash_equals($expectedCaptcha, $providedCaptcha)) {
        $err = 'Mã CAPTCHA không đúng hoặc đã hết hạn. Vui lòng nhập mã mới.';
    } else {
        $u = one('SELECT * FROM Users WHERE UserName = ?', [post('user')]);
        $pw = (string)($_POST['pass'] ?? '');
        $ok = false;
        if ($u) {
            if (password_verify($pw, (string)$u['Password'])) $ok = true;
            elseif ($u['Password'] !== '' && hash_equals((string)$u['Password'], $pw)) { // mật khẩu cũ chưa mã hóa -> nâng cấp
                $ok = true;
                run('UPDATE Users SET Password = ? WHERE UserName = ?', [password_hash($pw, PASSWORD_DEFAULT), $u['UserName']]);
            }
        }
        if ($ok && in_array($u['Quyen'], ['nhanvien', 'quanly'], true) && empty($u['MaNV'])) $ok = false;
        if ($ok) {
            unset($_SESSION['login_captcha'], $_SESSION['login_captcha_expires']);
            $_SESSION['cart'] = [];
            loginUser($u);
            $back = $_SESSION['back'] ?? '';
            unset($_SESSION['back']);
            if (in_array($u['Quyen'], ['admin', 'quanly', 'nhanvien'], true)) {
                $fallback = match ($u['Quyen']) {
                    'admin' => 'admin/',
                    'quanly' => 'admin/employees.php',
                    default => 'admin/orders.php',
                };
                $allowedEmployeeBack = ['admin/orders.php', 'admin/order_view.php', 'admin/products.php'];
                $employeeBackAllowed = $u['Quyen'] === 'nhanvien'
                    && array_filter($allowedEmployeeBack, static fn($path) => str_contains($back, $path));
                $allowedManagerBack = ['admin/orders.php', 'admin/order_view.php', 'admin/products.php', 'admin/employees.php', 'admin/users.php'];
                $managerBackAllowed = $u['Quyen'] === 'quanly'
                    && array_filter($allowedManagerBack, static fn($path) => str_contains($back, $path));
                redirect($back && str_contains($back, '/admin') && ($u['Quyen'] === 'admin' || $employeeBackAllowed || $managerBackAllowed) ? $back : $fallback);
            }
            redirect($back && !str_contains($back, '/admin') ? $back : 'account.php');
        }
        $err = 'Tên đăng nhập hoặc mật khẩu không đúng.';
    }
    createLoginCaptcha();
} elseif (empty($_SESSION['login_captcha']) || (int)($_SESSION['login_captcha_expires'] ?? 0) < time()) {
    createLoginCaptcha();
}
$title = 'Đăng nhập';
include __DIR__ . '/includes/header.php';
?>
<div class="auth">
  <form method="post" class="form-card">
    <h1>Đăng nhập</h1>
    <?php if ($err): ?><div class="flash err"><?= h($err) ?></div><?php endif; ?>
    <?= csrf() ?>
    <label>Tên đăng nhập<input name="user" value="<?= h($_POST['user'] ?? '') ?>" required autofocus></label>
    <label>Mật khẩu<input type="password" name="pass" required></label>
    <div class="captcha-control">
      <label for="login-captcha-answer">Mã xác nhận</label>
      <div class="captcha-row">
        <img src="<?= url('login_captcha.php') ?>" alt="Mã CAPTCHA" width="190" height="56">
        <button class="btn ghost" type="button" onclick="const image=this.parentElement.querySelector('img'); image.src='<?= h(url('login_captcha.php')) ?>?refresh='+Date.now();">Đổi mã</button>
      </div>
      <input id="login-captcha-answer" name="captcha" autocomplete="off" autocapitalize="characters" maxlength="6" required aria-describedby="captcha-help">
      <small id="captcha-help" class="muted">Nhập 6 ký tự trong hình. Mã có hiệu lực 10 phút.</small>
    </div>
    <button class="btn">Đăng nhập</button>
    <p class="muted">Chưa có tài khoản? <a href="<?= url('register.php') ?>">Đăng ký</a></p>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
