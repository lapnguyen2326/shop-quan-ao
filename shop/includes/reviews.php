<?php
function saveProductReview(string $productId, string $userName, int $rating, string $comment): void {
    $existing = one('SELECT MaDG FROM DanhGia WHERE MaSP = ? AND UserName = ?', [$productId, $userName]);
    if ($existing) {
        run('UPDATE DanhGia SET SoSao = ?, NoiDung = ?, NgayTao = ? WHERE MaDG = ?', [$rating, $comment, nowStr(), $existing['MaDG']]);
        return;
    }
    run('INSERT INTO DanhGia (MaSP, UserName, SoSao, NoiDung, NgayTao) VALUES (?, ?, ?, ?, ?)', [$productId, $userName, $rating, $comment, nowStr()]);
}

function productReviewSummary(string $productId): array {
    return one('SELECT COUNT(*) AS SoLuot, COALESCE(AVG(CAST(SoSao AS DECIMAL(4,2))), 0) AS DiemTB FROM DanhGia WHERE MaSP = ?', [$productId]);
}

function productReviewDistribution(string $productId): array {
    $distribution = array_fill(1, 5, 0);
    foreach (all('SELECT SoSao, COUNT(*) AS SoLuot FROM DanhGia WHERE MaSP = ? GROUP BY SoSao', [$productId]) as $row)
        $distribution[(int)$row['SoSao']] = (int)$row['SoLuot'];
    return $distribution;
}

function productReviews(string $productId): array {
    return all(topN('SELECT d.SoSao, d.NoiDung, d.NgayTao, u.HoTen, u.UserName
        FROM DanhGia d JOIN Users u ON u.UserName = d.UserName
        WHERE d.MaSP = ? ORDER BY d.NgayTao DESC, d.MaDG DESC', 10), [$productId]);
}