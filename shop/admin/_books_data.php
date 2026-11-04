<?php
function booksFilters(): array {
    $status = $_GET['st'] ?? '';
    if (!in_array($status, STATUSES, true)) $status = '';

    $from = validBookDate($_GET['from'] ?? '');
    $to = validBookDate($_GET['to'] ?? '');
    if ($from && $to && $from > $to) [$from, $to] = [$to, $from];

    $search = trim($_GET['q'] ?? '');
    $where = [];
    $params = [];
    if ($status !== '') { $where[] = 'TrangThai = ?'; $params[] = $status; }
    if ($from !== '') { $where[] = 'NgayLapHD >= ?'; $params[] = $from . ' 00:00:00'; }
    if ($to !== '') {
        $until = (new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d');
        $where[] = 'NgayLapHD < ?'; $params[] = $until . ' 00:00:00';
    }
    if ($search !== '') {
        $where[] = '(MaHD LIKE ? OR TenKH LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    return [
        'status' => $status,
        'from' => $from,
        'to' => $to,
        'search' => $search,
        'where' => $where ? ' WHERE ' . implode(' AND ', $where) : '',
        'params' => $params,
    ];
}

function validBookDate($date): string {
    if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return '';
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date ? $date : '';
}

function booksSummary(string $where, array $params): array {
    return one("SELECT COUNT(*) AS SoDon,
        COALESCE(SUM(CASE WHEN TrangThai = 'Hoàn thành' THEN TienHang - GiamVoucher + PhiVanChuyen ELSE 0 END), 0) AS DaThu,
        COALESCE(SUM(CASE WHEN TrangThai IN ('Mới', 'Đang xử lý', 'Đang giao') THEN TienHang - GiamVoucher + PhiVanChuyen ELSE 0 END), 0) AS ChoXuLy,
        COALESCE(SUM(CASE WHEN TrangThai = 'Hoàn thành' AND TienVon IS NOT NULL THEN TienHang - GiamVoucher - TienVon ELSE 0 END), 0) AS LoiNhuan,
        SUM(CASE WHEN TrangThai = 'Hoàn thành' AND TienVon IS NULL THEN 1 ELSE 0 END) AS DonChuaCoGiaVon
        FROM (" . orderSelect() . ") AS SoSach" . $where, $params);
}

function booksRows(string $where, array $params): array {
    return all(orderSelect() . $where . ' ORDER BY h.NgayLapHD DESC, h.MaHD DESC', $params);
}
