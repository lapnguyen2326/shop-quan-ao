<?php
const STATUSES = ['Mới', 'Đang xử lý', 'Đang giao', 'Hoàn thành', 'Đã hủy'];

function cfg($k) { global $CFG; return $CFG[$k]; }
function h($s) { return is_scalar($s) || $s === null ? htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8') : ''; }
function url($p = '') { return BASE . '/' . ltrim($p, '/'); }
function redirect($p) { header('Location: ' . ((str_starts_with($p, '/') || str_starts_with($p, 'http')) ? $p : url($p))); exit; }
function money($n) { return number_format((float)$n, 0, ',', '.') . ' ₫'; }
function fdate($d, $f = 'd/m/Y H:i') {
    if (!$d) return '—';
    return $d instanceof DateTimeInterface ? $d->format($f) : date($f, strtotime($d));
}
function post($k, $d = '') { return trim((string)($_POST[$k] ?? $d)); }

// ---------- Thông báo & CSRF ----------
function flash($msg = null, $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'][] = [$type, $msg]; return; }
    $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f;
}
function csrf() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return '<input type="hidden" name="_csrf" value="' . $_SESSION['csrf'] . '">';
}
function checkCsrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')) {
        http_response_code(400);
        exit('<meta charset="utf-8">Phiên làm việc đã hết hạn. Hãy quay lại và tải lại trang.');
    }
}

function createLoginCaptcha() {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    $_SESSION['login_captcha'] = $code;
    $_SESSION['login_captcha_expires'] = time() + 600;
}

// ---------- Đăng nhập ----------
function me() { return $_SESSION['user'] ?? null; }
function isAdmin() { return (me()['Quyen'] ?? '') === 'admin'; }
function isManager() { return (me()['Quyen'] ?? '') === 'quanly' && !empty(me()['MaNV']); }
function isEmployee() { return (me()['Quyen'] ?? '') === 'nhanvien' && !empty(me()['MaNV']); }
function isStaff() { return isManager() || isEmployee(); }
function isCustomerAccount() { return (me()['Quyen'] ?? '') === 'khach'; }
function customerMaKH() { return isCustomerAccount() ? (me()['MaKH'] ?: null) : null; }
function requireLogin() { if (!me()) { $_SESSION['back'] = $_SERVER['REQUEST_URI']; redirect('login.php'); } }
function refreshStaffSession() {
    if (!me()) return false;
    $u = one('SELECT UserName, HoTen, Quyen, MaKH, MaNV FROM Users WHERE UserName = ?', [me()['UserName']]);
    if (!$u) { unset($_SESSION['user']); return false; }
    $_SESSION['user'] = ['UserName' => $u['UserName'], 'HoTen' => $u['HoTen'] ?: $u['UserName'], 'Quyen' => $u['Quyen'], 'MaKH' => $u['MaKH'], 'MaNV' => $u['MaNV']];
    return true;
}
function requireAdmin() {
    if (!refreshStaffSession() || !isAdmin()) {
        $_SESSION['back'] = $_SERVER['REQUEST_URI'];
        flash('Bạn cần đăng nhập bằng tài khoản quản trị.', 'err');
        redirect('login.php');
    }
}
function requireAdminPage() {
    if (!refreshStaffSession()) {
        $_SESSION['back'] = $_SERVER['REQUEST_URI'];
        flash('Vui lòng đăng nhập để tiếp tục.', 'err');
        redirect('login.php');
    }
    if (isAdmin()) return;
    $managerPages = ['employees.php', 'users.php', 'orders.php', 'order_view.php', 'products.php', 'inventory.php'];
    if (isManager() && in_array(basename($_SERVER['SCRIPT_NAME']), $managerPages, true)) return;
    $employeePages = ['orders.php', 'order_view.php', 'products.php'];
    if (isEmployee() && in_array(basename($_SERVER['SCRIPT_NAME']), $employeePages, true)) return;
    http_response_code(403);
    exit('Bạn không có quyền truy cập trang này.');
}
function loginUser($u) {
    session_regenerate_id(true);
    $_SESSION['user'] = ['UserName' => $u['UserName'], 'HoTen' => $u['HoTen'] ?: $u['UserName'], 'Quyen' => $u['Quyen'], 'MaKH' => $u['MaKH'], 'MaNV' => $u['MaNV'] ?? null];
}

// ---------- Sản phẩm ----------
function effPrice($sp) { return round((float)$sp['DonGia'] * (1 - (int)$sp['PhanTramGiam'] / 100)); }
function productProfitAmount($sp) {
    $cost = (float)($sp['GiaVon'] ?? 0);
    if ($cost <= 0) return null;
    return effPrice($sp) - $cost;
}
function ensureProductCostColumn() {
    try {
        $sql = driver() === 'sqlsrv' ? 'SELECT TOP 1 GiaVon FROM SanPham' : 'SELECT GiaVon FROM SanPham LIMIT 1';
        one($sql);
    } catch (PDOException) {
        $sql = driver() === 'sqlsrv'
            ? 'ALTER TABLE SanPham ADD GiaVon DECIMAL(12,0) NOT NULL DEFAULT 0;'
            : 'ALTER TABLE SanPham ADD COLUMN GiaVon DECIMAL(12,0) NOT NULL DEFAULT 0;';
        try {
            run($sql);
        } catch (PDOException $alterError) {
            try {
                one(driver() === 'sqlsrv' ? 'SELECT TOP 1 GiaVon FROM SanPham' : 'SELECT GiaVon FROM SanPham LIMIT 1');
            } catch (PDOException $checkError) {
                throw $alterError;
            }
        }
    }
}
function ensureInventoryTables() {
    if (driver() === 'sqlsrv') {
        run("IF OBJECT_ID(N'dbo.PhieuNhapKho', N'U') IS NULL
            CREATE TABLE dbo.PhieuNhapKho (
                MaPN INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                NgayNhap DATETIME NOT NULL,
                GhiChu NVARCHAR(255) NULL,
                NguoiNhap NVARCHAR(50) NULL,
                TongSoLuong INT NOT NULL,
                TongTien DECIMAL(18,0) NOT NULL,
                CONSTRAINT FK_PhieuNhapKho_Users FOREIGN KEY (NguoiNhap) REFERENCES dbo.Users(UserName) ON DELETE SET NULL
            )");
        run("IF OBJECT_ID(N'dbo.ChiTietPhieuNhap', N'U') IS NULL
            CREATE TABLE dbo.ChiTietPhieuNhap (
                MaPN INT NOT NULL,
                MaSP NVARCHAR(10) NOT NULL,
                SoLuong INT NOT NULL,
                DonGiaNhap DECIMAL(12,0) NOT NULL,
                TonTruocNhap INT NOT NULL,
                GiaVonTruocNhap DECIMAL(12,0) NOT NULL,
                TonSauNhap INT NOT NULL,
                GiaVonSauNhap DECIMAL(12,0) NOT NULL,
                ThanhTien DECIMAL(18,0) NOT NULL,
                CONSTRAINT PK_ChiTietPhieuNhap PRIMARY KEY (MaPN, MaSP),
                CONSTRAINT FK_ChiTietPhieuNhap_PhieuNhapKho FOREIGN KEY (MaPN) REFERENCES dbo.PhieuNhapKho(MaPN) ON DELETE CASCADE,
                CONSTRAINT FK_ChiTietPhieuNhap_SanPham FOREIGN KEY (MaSP) REFERENCES dbo.SanPham(MaSP)
            )");
        run("IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(N'dbo.PhieuNhapKho') AND name = N'IX_PhieuNhapKho_NgayNhap')
            CREATE INDEX IX_PhieuNhapKho_NgayNhap ON dbo.PhieuNhapKho (NgayNhap)");
        return;
    }

    run("CREATE TABLE IF NOT EXISTS PhieuNhapKho (
        MaPN INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        NgayNhap DATETIME NOT NULL,
        GhiChu VARCHAR(255) NULL,
        NguoiNhap VARCHAR(50) NULL,
        TongSoLuong INT NOT NULL,
        TongTien DECIMAL(18,0) NOT NULL,
        INDEX IX_PhieuNhapKho_NgayNhap (NgayNhap),
        CONSTRAINT FK_PhieuNhapKho_Users FOREIGN KEY (NguoiNhap) REFERENCES Users(UserName) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    run("CREATE TABLE IF NOT EXISTS ChiTietPhieuNhap (
        MaPN INT NOT NULL,
        MaSP VARCHAR(10) NOT NULL,
        SoLuong INT NOT NULL,
        DonGiaNhap DECIMAL(12,0) NOT NULL,
        TonTruocNhap INT NOT NULL,
        GiaVonTruocNhap DECIMAL(12,0) NOT NULL,
        TonSauNhap INT NOT NULL,
        GiaVonSauNhap DECIMAL(12,0) NOT NULL,
        ThanhTien DECIMAL(18,0) NOT NULL,
        PRIMARY KEY (MaPN, MaSP),
        CONSTRAINT FK_ChiTietPhieuNhap_PhieuNhapKho FOREIGN KEY (MaPN) REFERENCES PhieuNhapKho(MaPN) ON DELETE CASCADE,
        CONSTRAINT FK_ChiTietPhieuNhap_SanPham FOREIGN KEY (MaSP) REFERENCES SanPham(MaSP)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function img($sp) {
    $p = ltrim(str_replace('~/', '', (string)($sp['HinhSP'] ?? '')), '/');
    if ($p !== '' && is_file(__DIR__ . '/../' . $p)) return url($p);
    $fallbacks = [
        'AK03' => 'images/1.webp',
        'AK01' => 'images/11.jpg',
        'AK02' => 'images/9.jpg',
        'AT01' => 'images/2.jpg',
        'AT02' => 'images/3.jpg',
        'AT03' => 'images/4.jpg',
        'QS01' => 'images/5.jpg',
        'SM03' => 'images/6.jpg',
        'SM02' => 'images/10.jpg',
        'VD01' => 'images/7.jpg',
        'VD02' => 'images/8.jpg',
        'PK01' => 'images/12.jpg',
        'PK02' => 'images/13.jpg',
        'QJ01' => 'images/14.jpg',
        'QT01' => 'images/15.jpg',
        'SM01' => 'images/16.jpg',
        'Áo hoodie nỉ' => 'images/1.webp',
        'Áo khoác jean' => 'images/11.jpg',
        'Áo khoác bomber' => 'images/9.jpg',
        'Áo thun basic cổ tròn' => 'images/2.jpg',
        'Áo polo pique' => 'images/3.jpg',
        'Áo thun oversize in hình' => 'images/4.jpg',
        'Quần short kaki' => 'images/5.jpg',
        'Áo sơ mi linen' => 'images/6.jpg',
        'Áo sơ mi kẻ sọc' => 'images/10.jpg',
        'Váy midi hoa nhí' => 'images/7.jpg',
        'Đầm dự tiệc' => 'images/8.jpg',
        'Nón lưỡi trai' => 'images/12.jpg',
        'Thắt lưng da' => 'images/13.jpg',
        'Quần jeans slim fit' => 'images/14.jpg',
        'Quần tây âu' => 'images/15.jpg',
        'Áo sơ mi trắng công sở' => 'images/16.jpg',
    ];
    $fallback = $fallbacks[$sp['MaSP'] ?? ''] ?? $fallbacks[$sp['TenSP'] ?? ''] ?? '';
    if ($fallback !== '' && is_file(__DIR__ . '/../' . $fallback)) return url($fallback);
    return url('images/placeholder.php') . '?t=' . urlencode($sp['TenSP'] ?? '');
}
function csv($s) { return array_values(array_filter(array_map('trim', explode(',', (string)$s)), 'strlen')); }

function productCard($sp) {
    $sale = (int)$sp['PhanTramGiam'];
    $o = '<a class="card" href="' . url('product.php?id=' . urlencode($sp['MaSP'])) . '">';
    $o .= '<div class="thumb"><img loading="lazy" src="' . h(img($sp)) . '" alt="' . h($sp['TenSP']) . '">';
    if ($sale > 0) $o .= '<span class="tag sale">-' . $sale . '%</span>';
    elseif ((int)$sp['SPMoi']) $o .= '<span class="tag new">Mới</span>';
    if ((int)$sp['SoLuongTon'] <= 0) $o .= '<span class="tag out">Hết hàng</span>';
    $o .= '</div><div class="info"><h3>' . h($sp['TenSP']) . '</h3><p class="price">';
    $o .= '<b>' . money(effPrice($sp)) . '</b>';
    if ($sale > 0) $o .= ' <s>' . money($sp['DonGia']) . '</s>';
    return $o . '</p></div></a>';
}

// ---------- Giỏ hàng (lưu trong session) ----------
function cartAdd($ma, $size, $mau, $qty) {
    $k = $ma . '|' . $size . '|' . $mau;
    $_SESSION['cart'][$k] = ['ma' => $ma, 'size' => $size, 'mau' => $mau, 'qty' => ($_SESSION['cart'][$k]['qty'] ?? 0) + $qty];
}
function cartCount() { return array_sum(array_column($_SESSION['cart'] ?? [], 'qty')); }
function cartItems() {
    $out = []; $sum = 0;
    foreach ($_SESSION['cart'] ?? [] as $k => $it) {
        $sp = one('SELECT * FROM SanPham WHERE MaSP = ? AND HienThi = 1', [$it['ma']]);
        if (!$sp) { unset($_SESSION['cart'][$k]); continue; }
        $price = effPrice($sp);
        $out[] = $it + ['key' => $k, 'sp' => $sp, 'price' => $price, 'line' => $price * $it['qty']];
        $sum += $price * $it['qty'];
    }
    return [$out, $sum];
}
function shipFee($sub) { return ($sub <= 0 || $sub >= cfg('free_ship_from')) ? 0 : cfg('ship_fee'); }

// ---------- Đơn hàng ----------
function orderSelect() {
    return "SELECT h.*, COALESCE(k.TenKH, h.TenNguoiNhan) AS TenKH,
        n.HoNV AS NhanVienHo, n.Ten AS NhanVienTen,
        (SELECT COALESCE(SUM(c.SoLuong * c.DonGia * (1 - c.GiamGia)), 0) FROM CTHD c WHERE c.MaHD = h.MaHD) AS TienHang,
        (SELECT CASE WHEN COUNT(*) = COUNT(c.GiaVon) THEN COALESCE(SUM(c.SoLuong * c.GiaVon), 0) ELSE NULL END FROM CTHD c WHERE c.MaHD = h.MaHD) AS TienVon
        FROM HoaDon h LEFT JOIN KhachHang k ON k.MaKH = h.MaKH
        LEFT JOIN Nhanvien n ON n.MaNV = h.MaNV";
}
function orderTotal($o) { return max(0, (float)$o['TienHang'] - (float)($o['GiamVoucher'] ?? 0)) + (float)$o['PhiVanChuyen']; }
function orderProcessorLabel($order) {
    if (($order['NguoiXuLy'] ?? '') === 'admin') return 'Admin đã xử lý';
    if (!empty($order['MaNV'])) {
        $name = trim(($order['NhanVienHo'] ?? '') . ' ' . ($order['NhanVienTen'] ?? ''));
        return ($name !== '' ? $name : 'Nhân viên') . ' đã xử lý';
    }
    return 'Chưa nhận xử lý';
}
function findVoucher($code, $subtotal, $maKH = null, $allowPublicWithoutCustomer = false) {
    if (!$maKH && !$allowPublicWithoutCustomer) return [null, 0, 'Bạn cần đăng nhập tài khoản khách hàng để sử dụng voucher.'];
    $voucher = one('SELECT * FROM Voucher WHERE MaVoucher = ?', [strtoupper(trim($code))]);
    if (!$voucher || !(int)$voucher['KichHoat']) return [null, 0, 'Mã voucher không hợp lệ hoặc đã ngừng áp dụng.'];
    if (!empty($voucher['MaKH']) && (!$maKH || (string)$voucher['MaKH'] !== (string)$maKH))
        return [null, 0, 'Voucher này thuộc về tài khoản khác.'];
    $now = time();
    if (($voucher['NgayBatDau'] && strtotime($voucher['NgayBatDau']) > $now) || ($voucher['NgayKetThuc'] && strtotime($voucher['NgayKetThuc']) < $now))
        return [null, 0, 'Voucher chưa đến thời gian áp dụng hoặc đã hết hạn.'];
    if ($voucher['SoLuong'] !== null && (int)$voucher['DaSuDung'] >= (int)$voucher['SoLuong'])
        return [null, 0, 'Voucher đã hết lượt sử dụng.'];
    if ((float)$subtotal < (float)$voucher['DonToiThieu'])
        return [null, 0, 'Đơn hàng chưa đạt giá trị tối thiểu của voucher.'];
    $discount = $voucher['LoaiGiam'] === 'phantram'
        ? round((float)$subtotal * (float)$voucher['GiaTri'] / 100)
        : (float)$voucher['GiaTri'];
    if ($voucher['GiamToiDa'] !== null) $discount = min($discount, (float)$voucher['GiamToiDa']);
    $discount = min((float)$subtotal, max(0, $discount));
    if ($discount <= 0) return [null, 0, 'Voucher không tạo ra mức giảm hợp lệ cho đơn hàng này.'];
    return [$voucher, $discount, ''];
}
function redeemLoyaltyReward($maKH, $tier) {
    $rewards = [100 => 50000, 300 => 120000, 500 => 200000];
    if (!$maKH || !isset($rewards[$tier])) throw new RuntimeException('Mức đổi điểm không hợp lệ.');
    if (!one('SELECT 1 AS x FROM Users WHERE MaKH = ? AND Quyen = ?', [$maKH, 'khach']))
        throw new RuntimeException('Chỉ tài khoản khách hàng mới được đổi điểm.');

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $updated = run('UPDATE KhachHang SET DiemTichLuy = DiemTichLuy - ? WHERE MaKH = ? AND DiemTichLuy >= ?', [$tier, $maKH, $tier]);
        if ($updated < 1) throw new RuntimeException('Bạn chưa đủ điểm để đổi voucher này.');
        do {
            $code = 'LOY' . strtoupper(bin2hex(random_bytes(5)));
        } while (one('SELECT 1 AS x FROM Voucher WHERE MaVoucher = ?', [$code]));
        run('INSERT INTO Voucher (MaVoucher, MaKH, LoaiGiam, GiaTri, DonToiThieu, GiamToiDa, SoLuong, DaSuDung, NgayBatDau, NgayKetThuc, KichHoat) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [
            $code, $maKH, 'tienmat', $rewards[$tier], 0, null, 1, 0, null, null, 1,
        ]);
        $pdo->commit();
        return ['code' => $code, 'discount' => $rewards[$tier]];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
function releaseVoucherUse($order) {
    if (!empty($order['MaVoucher']))
        run('UPDATE Voucher SET DaSuDung = CASE WHEN DaSuDung > 0 THEN DaSuDung - 1 ELSE 0 END WHERE MaVoucher = ?', [$order['MaVoucher']]);
}
function syncLoyaltyPoints($order, $newStatus) {
    $awarded = (int)($order['DiemDaCong'] ?? 0);
    $wasComplete = $order['TrangThai'] === 'Hoàn thành';
    $willComplete = $newStatus === 'Hoàn thành';
    if (!$wasComplete && $willComplete && !empty($order['MaKH']) && one('SELECT 1 AS x FROM Users WHERE MaKH = ? AND Quyen = ?', [$order['MaKH'], 'khach'])) {
        $points = (int)floor(max(0, (float)$order['TienHang'] - (float)($order['GiamVoucher'] ?? 0)) / 10000);
        if ($points > 0 && run('UPDATE HoaDon SET DiemDaCong = ? WHERE MaHD = ? AND DiemDaCong = 0 AND TrangThai <> ?', [$points, $order['MaHD'], 'Hoàn thành']) > 0) {
            run('UPDATE KhachHang SET DiemTichLuy = DiemTichLuy + ? WHERE MaKH = ?', [$points, $order['MaKH']]);
        }
    } elseif ($wasComplete && !$willComplete && $awarded > 0 && !empty($order['MaKH'])) {
        if (run('UPDATE HoaDon SET DiemDaCong = 0 WHERE MaHD = ? AND DiemDaCong = ?', [$order['MaHD'], $awarded]) > 0)
            run('UPDATE KhachHang SET DiemTichLuy = CASE WHEN DiemTichLuy >= ? THEN DiemTichLuy - ? ELSE 0 END WHERE MaKH = ?', [$awarded, $awarded, $order['MaKH']]);
    }
}
function orderLines($ma) {
    return all("SELECT c.*, s.TenSP, s.HinhSP FROM CTHD c LEFT JOIN SanPham s ON s.MaSP = c.MaSP WHERE c.MaHD = ? ORDER BY c.MaSP", [$ma]);
}
function restock($ma, $sign = 1) {
    foreach (orderLines($ma) as $l)
        run('UPDATE SanPham SET SoLuongTon = SoLuongTon + ? WHERE MaSP = ?', [$sign * (int)$l['SoLuong'], $l['MaSP']]);
}
function badge($s) {
    $c = ['Mới' => 'b-new', 'Đang xử lý' => 'b-proc', 'Đang giao' => 'b-ship', 'Hoàn thành' => 'b-done', 'Đã hủy' => 'b-cancel'][$s] ?? '';
    return '<span class="badge ' . $c . '">' . h($s) . '</span>';
}

function pager($total, $per, $page, $extra = []) {
    $pages = (int)ceil($total / $per);
    if ($pages <= 1) return '';
    $extra = array_filter($extra, 'strlen');
    $o = '<nav class="pager" aria-label="Phân trang">';
    for ($i = 1; $i <= $pages; $i++)
        $o .= '<a class="' . ($i == $page ? 'on' : '') . '" href="?' . h(http_build_query($extra + ['page' => $i])) . '">' . $i . '</a>';
    return $o . '</nav>';
}
function flashHtml() {
    $o = '';
    foreach (flash() as [$t, $m]) $o .= '<div class="flash ' . $t . '">' . h($m) . '</div>';
    return $o;
}
