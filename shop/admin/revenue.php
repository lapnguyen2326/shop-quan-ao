<?php
$title = 'Doanh thu';
require __DIR__ . '/_header.php';

$todayStart = new DateTimeImmutable('today');
$currentYear = (int)$todayStart->format('Y');
$currentMonth = (int)$todayStart->format('n');
$firstOrderDate = val('SELECT MIN(NgayLapHD) FROM HoaDon WHERE TrangThai = ?', ['Hoàn thành']);
$firstYear = $firstOrderDate ? (int)(new DateTimeImmutable((string)$firstOrderDate))->format('Y') : $currentYear;
$firstYear = min($firstYear, $currentYear);
$year = filter_var($_GET['year'] ?? $currentYear, FILTER_VALIDATE_INT);
$year = $year !== false && $year >= $firstYear && $year <= $currentYear ? $year : $currentYear;
$monthNumber = filter_var($_GET['month'] ?? $currentMonth, FILTER_VALIDATE_INT);
$monthNumber = $monthNumber !== false && $monthNumber >= 1 && $monthNumber <= 12 ? $monthNumber : $currentMonth;
$yearStart = new DateTimeImmutable(sprintf('%04d-01-01', $year));
$nextYearStart = $yearStart->modify('+1 year');

$monthlyRevenue = array_fill(1, 12, ['amount' => 0, 'count' => 0]);
$yearOrders = all(
    orderSelect() . ' WHERE h.TrangThai = ? AND h.NgayLapHD >= ? AND h.NgayLapHD < ?',
    ['Hoàn thành', dbDate($yearStart->format('Y-m-d H:i:s')), dbDate($nextYearStart->format('Y-m-d H:i:s'))]
);
foreach ($yearOrders as $order) {
    $orderMonth = (int)fdate($order['NgayLapHD'], 'n');
    $monthlyRevenue[$orderMonth]['amount'] += orderTotal($order);
    $monthlyRevenue[$orderMonth]['count']++;
}
$selectedMonth = $monthlyRevenue[$monthNumber];
?>
<div class="page-head"><h1>Doanh thu</h1></div>
<p class="muted">Doanh thu toàn cửa hàng từ các đơn hoàn thành, tính theo ngày đặt đơn; tiền voucher đã được trừ và phí vận chuyển được tính vào tổng.</p>
<section class="panel">
  <h2>Tra cứu doanh thu theo lịch</h2>
  <form class="filters revenue-date-filters" method="get">
    <label>Năm
      <select name="year"><?php for ($optionYear = $currentYear; $optionYear >= $firstYear; $optionYear--): ?><option value="<?= $optionYear ?>" <?= $year === $optionYear ? 'selected' : '' ?>><?= $optionYear ?></option><?php endfor; ?></select>
    </label>
    <label>Tháng
      <select name="month"><?php for ($optionMonth = 1; $optionMonth <= 12; $optionMonth++): ?><option value="<?= $optionMonth ?>" <?= $monthNumber === $optionMonth ? 'selected' : '' ?>>Tháng <?= $optionMonth ?></option><?php endfor; ?></select>
    </label>
    <button class="btn">Xem doanh thu</button>
  </form>
  <h3>Doanh thu 12 tháng năm <?= $year ?></h3>
  <div class="revenue-month-grid">
    <?php foreach ($monthlyRevenue as $displayMonth => $summary): ?>
      <a class="revenue-month<?= $displayMonth === $monthNumber ? ' current' : '' ?>" href="<?= url('admin/revenue.php?' . http_build_query(['year' => $year, 'month' => $displayMonth])) ?>">
        <span>Tháng <?= $displayMonth ?></span>
        <b><?= money($summary['amount']) ?></b>
        <small><?= $summary['count'] ?> đơn hoàn thành</small>
      </a>
    <?php endforeach; ?>
  </div>
  <p class="revenue-month-total">Tổng tháng <?= $monthNumber ?>/<?= $year ?>: <b><?= money($selectedMonth['amount']) ?></b> · <?= $selectedMonth['count'] ?> đơn hoàn thành</p>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
