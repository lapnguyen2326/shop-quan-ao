<?php
// Lớp truy cập dữ liệu dùng PDO: chạy được cả MySQL và SQL Server.
function driver() { return cfg('driver'); }

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        if (driver() === 'sqlsrv') {
            $c = cfg('sqlsrv');
            $pdo = new PDO("sqlsrv:Server={$c['host']};Database={$c['dbname']};TrustServerCertificate=1",
                           $c['user'] ?: null, $c['pass'] ?: null);
        } else {
            $c = cfg('mysql');
            $pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['dbname']};charset=utf8mb4",
                           $c['user'], $c['pass'], [PDO::ATTR_EMULATE_PREPARES => false]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        exit('<meta charset="utf-8"><h3>Không kết nối được database</h3><p>Kiểm tra lại file <code>config.php</code> (driver, host, user, pass).</p><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

function q($sql, $p = []) { $st = db()->prepare($sql); $st->execute($p); return $st; }
function all($sql, $p = []) { return q($sql, $p)->fetchAll(); }
function one($sql, $p = []) { $r = q($sql, $p)->fetch(); return $r ?: null; }
function val($sql, $p = []) { $r = q($sql, $p)->fetch(PDO::FETCH_NUM); return $r ? $r[0] : null; }
function run($sql, $p = []) { return q($sql, $p)->rowCount(); }
function lastId() { return (int)db()->lastInsertId(); }

// Phân trang: MySQL dùng LIMIT, SQL Server dùng OFFSET/FETCH (câu SQL phải có ORDER BY).
function limitSql($sql, $n, $off = 0) {
    $n = (int)$n; $off = (int)$off;
    return driver() === 'sqlsrv' ? "$sql OFFSET $off ROWS FETCH NEXT $n ROWS ONLY" : "$sql LIMIT $n OFFSET $off";
}
function topN($sql, $n) { return limitSql($sql, $n, 0); }
// Ngày giờ theo định dạng an toàn cho từng loại database (SQL Server dùng yyyymmdd để không phụ thuộc ngôn ngữ).
function dbDate($d = null) { return date(driver() === 'sqlsrv' ? 'Ymd H:i:s' : 'Y-m-d H:i:s', $d ? strtotime($d) : time()); }
function nowStr() { return dbDate(); }

// Sinh mã tiếp theo, ví dụ KH21, HD013.
function nextCode($prefix, $table, $col, $width = 2) {
    $max = 0;
    foreach (all("SELECT $col AS c FROM $table") as $r)
        if (preg_match('/^' . $prefix . '(\d+)$/', $r['c'], $m)) $max = max($max, (int)$m[1]);
    return $prefix . str_pad($max + 1, $width, '0', STR_PAD_LEFT);
}
