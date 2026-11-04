<?php
$title = 'Bán hàng sản phẩm';
require __DIR__ . '/_header.php';

$month = (string)($_GET['month'] ?? date('Y-m'));
$parsedMonth = DateTimeImmutable::createFromFormat('!Y-m', $month);
if (!$parsedMonth || $parsedMonth->format('Y-m') !== $month) $month = date('Y-m');
$monthStart = $month . '-01';
$nextMonthStart = (new DateTimeImmutable($monthStart))->modify('+1 month')->format('Y-m-d');
$monthEnd = $nextMonthStart;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedMonth = (string)($_POST['month'] ?? '');
    $parsedPostedMonth = DateTimeImmutable::createFromFormat('!Y-m', $postedMonth);
    if (!$parsedPostedMonth || $parsedPostedMonth->format('Y-m') !== $postedMonth) {
        $error = 'Tháng không hợp lệ.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $products = all('SELECT MaSP FROM SanPham ORDER BY MaSP');
            foreach ($products as $product) {
                $productId = $product['MaSP'];
                if (!isset($_POST['cost'][$productId])) continue;
                $rawCost = trim((string)$_POST['cost'][$productId]);
                if ($rawCost === '') {
                    run('DELETE FROM GiaVonThang WHERE MaSP = ? AND Thang = ?', [$productId, $postedMonth]);
                    continue;
                }
                if (!preg_match('/^\d{1,12}$/', $rawCost)) throw new RuntimeException('Giá vốn phải là số nguyên không âm.');
                $existing = one('SELECT MaSP FROM GiaVonThang WHERE MaSP = ? AND Thang = ?', [$productId, $postedMonth]);
                if ($existing) {
                    run('UPDATE GiaVonThang SET GiaVon = ? WHERE MaSP = ? AND Thang = ?', [(int)$rawCost, $productId, $postedMonth]);
                } else {
                    run('INSERT INTO GiaVonThang (MaSP, Thang, GiaVon) VALUES (?,?,?)', [$productId, $postedMonth, (int)$rawCost]);
                }
            }
            $pdo->commit();
            flash('Đã lưu giá vốn tháng ' . $postedMonth . '.');
            redirect('admin/product_sales.php?month=' . urlencode($postedMonth));
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}

$products = all('SELECT p.*, m.GiaVon AS GiaVonThang FROM SanPham p LEFT JOIN GiaVonThang m ON m.MaSP = p.MaSP AND m.Thang = ? ORDER BY p.MaSP', [$month]);
$lineRevenue = '(c.SoLuong * c.DonGia * (1 - COALESCE(c.GiamGia, 0)))';
$netLineRevenue = "$lineRevenue - CASE WHEN orderTotals.TienHang > 0 THEN COALESCE(h.GiamVoucher, 0) * $lineRevenue / orderTotals.TienHang ELSE 0 END";
$unitCost = 'COALESCE(c.GiaVon, m.GiaVon, NULLIF(p.GiaVon, 0))';
$monthSales = all("SELECT c.MaSP,
    SUM(c.SoLuong) AS SoLuongBan,
    SUM($netLineRevenue) AS DoanhThu,
    SUM(CASE WHEN $unitCost IS NULL THEN 0 ELSE c.SoLuong * $unitCost END) AS TienVon,
    SUM(CASE WHEN $unitCost IS NULL THEN 0 ELSE c.SoLuong END) AS SoLuongCoGiaVon,
    SUM(CASE WHEN $unitCost IS NULL THEN c.SoLuong ELSE 0 END) AS SoLuongThieuGiaVon
    FROM CTHD c
    JOIN HoaDon h ON h.MaHD = c.MaHD
    JOIN SanPham p ON p.MaSP = c.MaSP
    LEFT JOIN GiaVonThang m ON m.MaSP = c.MaSP AND m.Thang = ?
    LEFT JOIN (SELECT MaHD, SUM(SoLuong * DonGia * (1 - COALESCE(GiamGia, 0))) AS TienHang FROM CTHD GROUP BY MaHD) orderTotals ON orderTotals.MaHD = h.MaHD
    WHERE h.TrangThai = ? AND h.NgayLapHD >= ? AND h.NgayLapHD < ?
    GROUP BY c.MaSP", [$month, 'Hoàn thành', $monthStart, $monthEnd]);
$allTimeSales = all("SELECT c.MaSP,
    SUM(c.SoLuong) AS SoLuongBan,
    SUM($netLineRevenue) AS DoanhThu
    FROM CTHD c
    JOIN HoaDon h ON h.MaHD = c.MaHD
    LEFT JOIN (SELECT MaHD, SUM(SoLuong * DonGia * (1 - COALESCE(GiamGia, 0))) AS TienHang FROM CTHD GROUP BY MaHD) orderTotals ON orderTotals.MaHD = h.MaHD
    WHERE h.TrangThai = ?
    GROUP BY c.MaSP", ['Hoàn thành']);
$monthByProduct = [];
foreach ($monthSales as $sale) $monthByProduct[$sale['MaSP']] = $sale;
$allTimeByProduct = [];
foreach ($allTimeSales as $sale) $allTimeByProduct[$sale['MaSP']] = $sale;
?>
<div class="page-head"><h1>Bán hàng theo sản phẩm</h1></div>
<?php if ($error): ?><div class="flash err"><?= h($error) ?></div><?php endif; ?>
<form class="filters" method="get">
  <label>Tháng <input type="month" name="month" value="<?= h($month) ?>"></label>
  <button class="btn ghost">Xem tháng</button>
</form>
<section class="panel">
  <h2>Giá vốn theo tháng · <?= h($month) ?></h2>
  <p class="muted">Doanh thu, số bán và lợi nhuận chỉ tính các đơn đã hoàn thành. Nhập giá vốn mỗi sản phẩm cho tháng này. Nếu để trống, hệ thống dùng giá vốn chung của sản phẩm (nếu đã nhập). Giá vốn được chụp vào đơn khi khách đặt hàng.</p>
  <form method="post"><?= csrf() ?><input type="hidden" name="month" value="<?= h($month) ?>">
    <div class="table-wrap"><table>
      <tr><th>Mã</th><th>Sản phẩm</th><th class="r">Giá vốn chung</th><th>Giá vốn tháng (₫)</th><th class="r">Số bán tháng</th><th class="r">Doanh thu ròng tháng</th><th class="r">Giá vốn hàng bán</th><th class="r">Giá vốn bình quân/chiếc</th><th class="r">Lợi nhuận</th><th class="r">Đã bán tổng</th><th class="r">Doanh thu tổng</th></tr>
      <?php foreach ($products as $product):
          $monthly = $monthByProduct[$product['MaSP']] ?? null;
          $total = $allTimeByProduct[$product['MaSP']] ?? null;
          $monthlyCost = $product['GiaVonThang'] !== null ? (float)$product['GiaVonThang'] : ((float)$product['GiaVon'] > 0 ? (float)$product['GiaVon'] : null);
          $knownCost = $monthly ? (int)$monthly['SoLuongCoGiaVon'] : 0;
          $profit = $monthly && (int)$monthly['SoLuongThieuGiaVon'] === 0 ? (float)$monthly['DoanhThu'] - (float)$monthly['TienVon'] : null;
      ?>
        <tr>
          <td><?= h($product['MaSP']) ?></td>
          <td><?= h($product['TenSP']) ?></td>
          <td class="r"><?= (float)$product['GiaVon'] > 0 ? money($product['GiaVon']) : '—' ?></td>
          <td><input type="number" name="cost[<?= h($product['MaSP']) ?>]" min="0" step="1000" value="<?= $product['GiaVonThang'] !== null ? h($product['GiaVonThang']) : '' ?>" placeholder="<?= $monthlyCost === null ? 'Chưa nhập' : 'Mặc định ' . number_format($monthlyCost, 0, ',', '.') ?>" aria-label="Giá vốn tháng cho <?= h($product['TenSP']) ?>"></td>
          <td class="r"><?= $monthly ? number_format((int)$monthly['SoLuongBan']) : '0' ?></td>
          <td class="r"><?= $monthly ? money($monthly['DoanhThu']) : money(0) ?></td>
          <td class="r"><?= $monthly && $knownCost > 0 ? money($monthly['TienVon']) : '—' ?></td>
          <td class="r"><?= $monthly && $knownCost > 0 ? money((float)$monthly['TienVon'] / $knownCost) : '—' ?></td>
          <td class="r"><?= $profit === null ? 'Thiếu giá vốn' : money($profit) ?></td>
          <td class="r"><?= $total ? number_format((int)$total['SoLuongBan']) : '0' ?></td>
          <td class="r"><?= $total ? money($total['DoanhThu']) : money(0) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$products): ?><tr><td colspan="11" class="muted">Chưa có sản phẩm.</td></tr><?php endif; ?>
    </table></div>
    <p><button class="btn">Lưu giá vốn tháng <?= h($month) ?></button></p>
  </form>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
