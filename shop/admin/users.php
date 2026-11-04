<?php
$title = 'Tài khoản';
require __DIR__ . '/_header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = post('user');
    $action = post('action');
    $managerActions = ['create_all_employees', 'reset_all_employee_passwords', 'create_employee', 'delete', 'reset'];
    if (isManager() && !in_array($action, $managerActions, true)) {
        http_response_code(403);
        exit('Quản lý không có quyền thay đổi chức vụ tài khoản.');
    }
    if ($action === 'create_all_employees') {
        $pdo = db();
        $credentials = [];
        $pdo->beginTransaction();
        try {
            $unlinkedEmployees = all('SELECT n.MaNV, n.HoNV, n.Ten FROM Nhanvien n LEFT JOIN Users u ON u.MaNV = n.MaNV WHERE u.UserName IS NULL ORDER BY n.MaNV');
            foreach ($unlinkedEmployees as $employee) {
                $baseUsername = 'nv' . str_pad((string)$employee['MaNV'], 3, '0', STR_PAD_LEFT);
                $username = $baseUsername;
                $suffix = 1;
                while (one('SELECT 1 AS x FROM Users WHERE UserName = ?', [$username])) {
                    $username = $baseUsername . '_' . $suffix++;
                }
                $password = bin2hex(random_bytes(8));
                run('INSERT INTO Users (UserName, Password, HoTen, Quyen, MaKH, MaNV) VALUES (?,?,?,?,?,?)',
                    [$username, password_hash($password, PASSWORD_DEFAULT), trim($employee['HoNV'] . ' ' . $employee['Ten']), 'nhanvien', null, $employee['MaNV']]);
                $credentials[] = [
                    'MaNV' => $employee['MaNV'],
                    'HoTen' => trim($employee['HoNV'] . ' ' . $employee['Ten']),
                    'UserName' => $username,
                    'Password' => $password,
                ];
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        if ($credentials) {
            $_SESSION['employee_credentials_once'] = $credentials;
            flash('Đã tạo ' . count($credentials) . ' tài khoản nhân viên. Hãy lưu thông tin đăng nhập bên dưới ngay.');
        } else {
            flash('Tất cả nhân viên hiện đã có tài khoản.');
        }
    } elseif ($action === 'reset_all_employee_passwords') {
        $password = (string)($_POST['employee_pass'] ?? '');
        if (strlen($password) < 6) {
            flash('Mật khẩu cần tối thiểu 6 ký tự.', 'err');
        } else {
            $count = run("UPDATE Users SET Password = ? WHERE Quyen = 'nhanvien' AND MaNV IS NOT NULL", [password_hash($password, PASSWORD_DEFAULT)]);
            flash('Đã đổi mật khẩu cho ' . $count . ' tài khoản nhân viên.');
        }
    } elseif ($action === 'create_employee') {
        $username = post('new_user');
        $password = (string)($_POST['new_pass'] ?? '');
        $maNV = (int)post('ma_nv');
        if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username)) flash('Tên đăng nhập gồm 3–30 ký tự chữ, số, dấu chấm hoặc gạch dưới.', 'err');
        elseif (strlen($password) < 6) flash('Mật khẩu cần tối thiểu 6 ký tự.', 'err');
        elseif (!one('SELECT MaNV FROM Nhanvien WHERE MaNV = ?', [$maNV])) flash('Vui lòng chọn nhân viên hợp lệ.', 'err');
        elseif (one('SELECT 1 AS x FROM Users WHERE UserName = ?', [$username])) flash('Tên đăng nhập đã được sử dụng.', 'err');
        elseif (one('SELECT 1 AS x FROM Users WHERE MaNV = ?', [$maNV])) flash('Nhân viên này đã có tài khoản.', 'err');
        else {
            $staff = one('SELECT HoNV, Ten FROM Nhanvien WHERE MaNV = ?', [$maNV]);
            run('INSERT INTO Users (UserName, Password, HoTen, Quyen, MaKH, MaNV) VALUES (?,?,?,?,?,?)',
                [$username, password_hash($password, PASSWORD_DEFAULT), trim($staff['HoNV'] . ' ' . $staff['Ten']), 'nhanvien', null, $maNV]);
            flash('Đã tạo tài khoản đăng nhập cho nhân viên.');
        }
    } elseif ($action === 'delete') {
        if ($u === me()['UserName']) flash('Không thể xóa tài khoản đang đăng nhập.', 'err');
        elseif (isManager() && !one("SELECT UserName FROM Users WHERE UserName = ? AND Quyen = 'nhanvien' AND MaNV IS NOT NULL", [$u])) {
            flash('Quản lý chỉ được xóa tài khoản của nhân viên.', 'err');
        } else {
            run('DELETE FROM Users WHERE UserName = ?', [$u]);
            flash('Đã xóa tài khoản.');
        }
    } elseif ($action === 'role') {
        $adm = (int)val("SELECT COUNT(*) FROM Users WHERE Quyen = 'admin'");
        $role = post('quyen');
        $maNV = (int)post('ma_nv');
        if (!isAdmin()) { http_response_code(403); exit('Chỉ quản trị viên được thay đổi chức vụ tài khoản.'); }
        if (!in_array($role, ['admin', 'quanly', 'nhanvien', 'khach'], true)) flash('Quyền không hợp lệ.', 'err');
        elseif ($u === me()['UserName'] && $role !== 'admin' && $adm <= 1) flash('Phải còn ít nhất một quản trị viên.', 'err');
        elseif (in_array($role, ['nhanvien', 'quanly'], true) && !one('SELECT MaNV FROM Nhanvien WHERE MaNV = ?', [$maNV])) flash('Vui lòng chọn nhân viên hợp lệ.', 'err');
        elseif (in_array($role, ['nhanvien', 'quanly'], true) && one('SELECT UserName FROM Users WHERE MaNV = ? AND UserName <> ?', [$maNV, $u])) flash('Nhân viên này đã được liên kết với tài khoản khác.', 'err');
        else {
            run('UPDATE Users SET Quyen = ?, MaNV = ? WHERE UserName = ?', [$role, in_array($role, ['nhanvien', 'quanly'], true) ? $maNV : null, $u]);
            flash('Đã đổi quyền.');
        }
    } elseif ($action === 'reset') {
        if (strlen($_POST['pass'] ?? '') < 6) flash('Mật khẩu mới cần tối thiểu 6 ký tự.', 'err');
        elseif (isManager() && !one("SELECT UserName FROM Users WHERE UserName = ? AND Quyen = 'nhanvien' AND MaNV IS NOT NULL", [$u])) {
            flash('Quản lý chỉ được đặt lại mật khẩu cho tài khoản nhân viên.', 'err');
        } else {
            run('UPDATE Users SET Password = ? WHERE UserName = ?', [password_hash($_POST['pass'], PASSWORD_DEFAULT), $u]);
            flash('Đã đặt lại mật khẩu cho ' . $u . '.');
        }
    }
    redirect('admin/users.php');
}
$rows = all('SELECT u.*, k.TenKH, n.HoNV, n.Ten AS TenNV FROM Users u LEFT JOIN KhachHang k ON k.MaKH = u.MaKH LEFT JOIN Nhanvien n ON n.MaNV = u.MaNV' . (isManager() ? " WHERE u.Quyen = 'nhanvien' AND u.MaNV IS NOT NULL" : '') . ' ORDER BY u.Quyen, u.UserName');
$employees = all('SELECT MaNV, HoNV, Ten FROM Nhanvien ORDER BY Ten, HoNV');
$employeesWithoutAccounts = all('SELECT n.MaNV, n.HoNV, n.Ten FROM Nhanvien n LEFT JOIN Users u ON u.MaNV = n.MaNV WHERE u.UserName IS NULL ORDER BY n.MaNV');
$newEmployeeCredentials = $_SESSION['employee_credentials_once'] ?? [];
unset($_SESSION['employee_credentials_once']);
?>
<div class="page-head"><h1><?= isManager() ? 'Tài khoản nhân viên' : 'Tài khoản đăng nhập' ?></h1></div>
<?php if (isManager()): ?><p class="muted">Quản lý có thể tạo và hỗ trợ tài khoản nhân viên. Chỉ quản trị viên mới được bổ nhiệm hoặc thu hồi chức vụ quản lý.</p><?php endif; ?>
<form method="post" class="panel form-card"><?= csrf() ?><input type="hidden" name="action" value="create_all_employees">
  <h2>Tạo tài khoản cho tất cả nhân viên</h2>
  <p class="muted">Còn <?= count($employeesWithoutAccounts) ?> nhân viên chưa có tài khoản. Mỗi nhân viên nhận tên đăng nhập theo mã nhân viên và mật khẩu ngẫu nhiên riêng.</p>
  <button class="btn" <?= $employeesWithoutAccounts ? '' : 'disabled' ?> onclick="return confirm('Tạo tài khoản cho tất cả nhân viên chưa có tài khoản?')">Tạo <?= count($employeesWithoutAccounts) ?> tài khoản</button>
</form>
<form method="post" class="panel form-card" onsubmit="return confirm('Đổi mật khẩu của tất cả tài khoản nhân viên?')"><?= csrf() ?><input type="hidden" name="action" value="reset_all_employee_passwords">
  <h2>Đặt mật khẩu chung cho nhân viên</h2>
  <div class="two"><label>Mật khẩu mới<input type="password" name="employee_pass" minlength="6" required></label><div><button class="btn">Đổi mật khẩu tất cả nhân viên</button></div></div>
</form>
<?php if ($newEmployeeCredentials): ?>
<section class="panel">
  <h2>Thông tin đăng nhập mới</h2>
  <p class="muted">Mật khẩu chỉ hiển thị lần này. Hãy sao chép và gửi riêng cho từng nhân viên trước khi rời trang.</p>
  <div class="table-wrap"><table><tr><th>Mã NV</th><th>Họ tên</th><th>Tên đăng nhập</th><th>Mật khẩu tạm</th></tr>
  <?php foreach ($newEmployeeCredentials as $credential): ?>
    <tr><td><?= (int)$credential['MaNV'] ?></td><td><?= h($credential['HoTen']) ?></td><td><b><?= h($credential['UserName']) ?></b></td><td><code><?= h($credential['Password']) ?></code></td></tr>
  <?php endforeach; ?></table></div>
</section>
<?php endif; ?>
<form method="post" class="panel form-card"><?= csrf() ?><input type="hidden" name="action" value="create_employee">
  <h2>Tạo riêng một tài khoản nhân viên</h2>
  <div class="two"><label>Nhân viên<select name="ma_nv" required><option value="">Chọn nhân viên</option><?php foreach ($employeesWithoutAccounts as $employee): ?><option value="<?= (int)$employee['MaNV'] ?>"><?= h(trim($employee['HoNV'] . ' ' . $employee['Ten'])) ?> (<?= (int)$employee['MaNV'] ?>)</option><?php endforeach; ?></select></label>
  <label>Tên đăng nhập<input name="new_user" required pattern="[A-Za-z0-9_.]{3,30}"></label></div>
  <div class="two"><label>Mật khẩu<input type="password" name="new_pass" minlength="6" required></label><div><button class="btn">Tạo tài khoản</button></div></div>
</form>
<div class="table-wrap"><table><tr><th>Tên đăng nhập</th><th>Họ tên</th><th>Liên kết</th><th>Quyền</th><th>Đặt lại mật khẩu</th><th></th></tr>
<?php foreach ($rows as $r): ?>
  <tr><td><b><?= h($r['UserName']) ?></b></td><td><?= h($r['HoTen']) ?></td><td><?= h($r['MaNV'] ? trim($r['HoNV'] . ' ' . $r['TenNV']) . ' (NV ' . $r['MaNV'] . ')' : ($r['MaKH'] ? $r['MaKH'] . ' – ' . $r['TenKH'] : '')) ?></td>
  <td><?php if (isAdmin()): ?><form method="post" class="inline"><?= csrf() ?><input type="hidden" name="action" value="role"><input type="hidden" name="user" value="<?= h($r['UserName']) ?>">
    <select name="quyen"><option value="khach" <?= $r['Quyen'] === 'khach' ? 'selected' : '' ?>>Khách hàng</option><option value="nhanvien" <?= $r['Quyen'] === 'nhanvien' ? 'selected' : '' ?>>Nhân viên</option><option value="quanly" <?= $r['Quyen'] === 'quanly' ? 'selected' : '' ?>>Quản lý</option><option value="admin" <?= $r['Quyen'] === 'admin' ? 'selected' : '' ?>>Quản trị</option></select>
    <select name="ma_nv"><option value="0">Chọn nhân viên</option><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['MaNV'] ?>" <?= (int)$r['MaNV'] === (int)$employee['MaNV'] ? 'selected' : '' ?>><?= h(trim($employee['HoNV'] . ' ' . $employee['Ten'])) ?></option><?php endforeach; ?></select>
    <button class="link">Lưu quyền</button></form><?php else: ?>Nhân viên<?php endif; ?></td>
  <td><form method="post" class="inline"><?= csrf() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="user" value="<?= h($r['UserName']) ?>"><input type="password" name="pass" placeholder="Mật khẩu mới"> <button class="link">Đặt lại</button></form></td>
  <td><form method="post" onsubmit="return confirm('Xóa tài khoản này?')"><?= csrf() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="user" value="<?= h($r['UserName']) ?>"><button class="link danger">Xóa</button></form></td></tr>
<?php endforeach; ?></table></div>
<?php require __DIR__ . '/_footer.php'; ?>
