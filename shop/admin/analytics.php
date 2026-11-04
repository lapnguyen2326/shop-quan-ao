<?php
$title = 'Phân tích';
require __DIR__ . '/_header.php';

$period = (int)($_GET['period'] ?? 30);
if (!in_array($period, [7, 30, 90, 365], true)) $period = 30;
$today = new DateTimeImmutable('today');
$start = $today->sub(new DateInterval('P' . ($period - 1) . 'D'));
$previousStart = $start->sub(new DateInterval('P' . $period . 'D'));
$orders = all(orderSelect() . ' ORDER BY h.NgayLapHD');
$currentOrders = $previousOrders = [];
$revenue = $previousRevenue = 0;
$customerIds = $previousCustomerIds = [];
$statusCounts = array_fill_keys(STATUSES, 0);
$dailyRevenue = [];
$bucketCount = $period <= 30 ? $period : ($period === 90 ? 13 : 12);
for ($i = 0; $i < $bucketCount; $i++) $dailyRevenue[$i] = 0;
$bucketForDate = static function (DateTimeImmutable $date) use ($start, $period, $bucketCount): int {
  $offset = (int)$start->diff($date)->format('%a');
  return min($bucketCount - 1, (int)floor($offset / $period * $bucketCount));
};

foreach ($orders as $order) {
    $date = new DateTimeImmutable(fdate($order['NgayLapHD'], 'Y-m-d'));
    $total = orderTotal($order);
    if ($date >= $start && $date <= $today) {
        $currentOrders[] = $order;
        $statusCounts[$order['TrangThai']] = ($statusCounts[$order['TrangThai']] ?? 0) + 1;
        if ($order['TrangThai'] === 'Hoàn thành') $revenue += $total;
        $customerKey = $order['MaKH'] ?: ($order['DienThoaiGiao'] ?: ($order['TenNguoiNhan'] ?: $order['MaHD']));
        $customerIds[$customerKey] = true;
        $bucket = $bucketForDate($date);
        if (isset($dailyRevenue[$bucket]) && $order['TrangThai'] === 'Hoàn thành') $dailyRevenue[$bucket] += $total;
    } elseif ($date >= $previousStart && $date < $start) {
        $previousOrders[] = $order;
        if ($order['TrangThai'] === 'Hoàn thành') $previousRevenue += $total;
        $customerKey = $order['MaKH'] ?: ($order['DienThoaiGiao'] ?: ($order['TenNguoiNhan'] ?: $order['MaHD']));
        $previousCustomerIds[$customerKey] = true;
    }
}

$orderIds = array_column($currentOrders, 'MaHD');
$topProducts = $categorySales = [];
if ($orderIds) {
    $lines = all('SELECT c.MaHD, c.SoLuong, c.DonGia, c.GiamGia, s.MaSP, s.TenSP, l.TenLoaiSP
        FROM CTHD c JOIN SanPham s ON s.MaSP = c.MaSP
        LEFT JOIN LoaiSP l ON l.MaLoaiSP = s.MaLoaiSP');
    $currentIds = array_fill_keys($orderIds, true);
    $orderStatus = [];
    foreach ($currentOrders as $order) $orderStatus[$order['MaHD']] = $order['TrangThai'];
    foreach ($lines as $line) {
        if (!isset($currentIds[$line['MaHD']]) || $orderStatus[$line['MaHD']] === 'Đã hủy') continue;
        $name = $line['TenSP'] ?: $line['MaSP'];
        $topProducts[$name] = ($topProducts[$name] ?? 0) + (int)$line['SoLuong'];
        $category = $line['TenLoaiSP'] ?: 'Chưa phân loại';
        $categorySales[$category] = ($categorySales[$category] ?? 0) + (int)$line['SoLuong'];
    }
}
arsort($topProducts);
arsort($categorySales);
$topProducts = array_slice($topProducts, 0, 6, true);
$revenueChange = $previousRevenue > 0 ? (($revenue - $previousRevenue) / $previousRevenue) * 100 : null;
$orderChange = count($previousOrders) > 0 ? ((count($currentOrders) - count($previousOrders)) / count($previousOrders)) * 100 : null;
$customerChange = count($previousCustomerIds) > 0 ? ((count($customerIds) - count($previousCustomerIds)) / count($previousCustomerIds)) * 100 : null;
$orderCount = count($currentOrders);
$completionRate = $orderCount ? ($statusCounts['Hoàn thành'] / $orderCount) * 100 : 0;
$periodLabels = [7 => '7 ngày', 30 => '30 ngày', 90 => '3 tháng', 365 => '12 tháng'];
$growthByBucket = array_fill(0, $bucketCount, 0);
$growthCustomers = [];
foreach ($currentOrders as $order) {
  $date = new DateTimeImmutable(fdate($order['NgayLapHD'], 'Y-m-d'));
  $customerKey = $order['MaKH'] ?: ($order['DienThoaiGiao'] ?: ($order['TenNguoiNhan'] ?: $order['MaHD']));
  $growthCustomers[$customerKey] = true;
  $growthByBucket[$bucketForDate($date)] = count($growthCustomers);
}
for ($i = 1; $i < $bucketCount; $i++) if ($growthByBucket[$i] === 0) $growthByBucket[$i] = $growthByBucket[$i - 1];
$chartLabels = [];
for ($i = 0; $i < $bucketCount; $i++) {
  $chartLabels[] = $period <= 30 ? 'Ngày ' . ($i + 1) : ($period === 90 ? 'Tuần ' . ($i + 1) : $start->modify('+' . (int)round($i * $period / $bucketCount) . ' days')->format('m/Y'));
}
$chartData = [
  'labels' => $chartLabels,
  'revenue' => array_values($dailyRevenue),
  'customers' => $growthByBucket,
  'categoryLabels' => array_keys($categorySales),
  'categoryValues' => array_values($categorySales),
  'statusLabels' => array_keys($statusCounts),
  'statusValues' => array_values($statusCounts),
  'productLabels' => array_keys($topProducts),
  'productValues' => array_values($topProducts),
];
?>
<div class="page-head"><h1>Phân tích</h1>
  <nav class="period-tabs" aria-label="Khoảng thời gian">
    <?php foreach ($periodLabels as $days => $label): ?><a class="<?= $period === $days ? 'on' : '' ?>" href="?period=<?= $days ?>"><?= $label ?></a><?php endforeach; ?>
  </nav>
</div>
<p class="muted analytics-period"><?= $start->format('d/m/Y') ?> – <?= $today->format('d/m/Y') ?> · So với kỳ trước</p>
<div class="analytics-stats">
  <article class="analytics-stat"><span class="analytics-stat-icon">₫</span><span>Doanh thu hoàn thành</span><b><?= money($revenue) ?></b><small class="<?= $revenueChange !== null && $revenueChange < 0 ? 'negative' : 'positive' ?>"><?= $revenueChange === null ? 'Chưa có dữ liệu kỳ trước' : sprintf('%+.1f%% so với kỳ trước', $revenueChange) ?></small></article>
  <article class="analytics-stat"><span class="analytics-stat-icon">#</span><span>Đơn hàng</span><b><?= number_format($orderCount) ?></b><small class="<?= $orderChange !== null && $orderChange < 0 ? 'negative' : '' ?>"><?= $orderChange === null ? 'Chưa có dữ liệu kỳ trước' : sprintf('%+.1f%% so với kỳ trước', $orderChange) ?></small></article>
  <article class="analytics-stat"><span class="analytics-stat-icon">KH</span><span>Khách đã đặt hàng</span><b><?= number_format(count($customerIds)) ?></b><small class="<?= $customerChange !== null && $customerChange < 0 ? 'negative' : '' ?>"><?= $customerChange === null ? 'Chưa có dữ liệu kỳ trước' : sprintf('%+.1f%% so với kỳ trước', $customerChange) ?></small></article>
  <article class="analytics-stat"><span class="analytics-stat-icon">%</span><span>Tỉ lệ hoàn thành</span><b><?= number_format($completionRate, 1) ?>%</b><small><?= (int)($statusCounts['Hoàn thành'] ?? 0) ?> / <?= $orderCount ?> đơn</small></article>
</div>
<div class="analytics-grid">
  <section class="panel analytics-chart"><h2>Doanh thu</h2><div class="chart-frame"><canvas id="revenue-chart" aria-label="Biểu đồ doanh thu" role="img"></canvas></div>
  </section>
  <section class="panel analytics-chart"><h2>Tăng trưởng khách hàng</h2><div class="chart-frame"><canvas id="customers-chart" aria-label="Biểu đồ tăng trưởng khách hàng" role="img"></canvas></div>
  </section>
  <section class="panel analytics-chart"><h2>Số lượng bán theo danh mục</h2><div class="chart-frame chart-frame-donut"><canvas id="category-chart" aria-label="Biểu đồ số lượng bán theo danh mục" role="img"></canvas></div>
  </section>
  <section class="panel analytics-chart"><h2>Trạng thái đơn hàng</h2><div class="chart-frame chart-frame-donut"><canvas id="status-chart" aria-label="Biểu đồ trạng thái đơn hàng" role="img"></canvas></div>
  </section>
  <section class="panel analytics-chart analytics-wide"><h2>Sản phẩm bán chạy</h2><div class="chart-frame chart-frame-products"><canvas id="products-chart" aria-label="Biểu đồ sản phẩm bán chạy" role="img"></canvas></div>
  </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const analyticsData = <?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const chartColors = ['#6262ed', '#20bf67', '#ff9f0a', '#f04448', '#139fda', '#aa61c6'];
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { displayColors: false, padding: 10 } },
  interaction: { intersect: false, mode: 'index' },
};
new Chart(document.getElementById('revenue-chart'), {
  type: 'line',
  data: { labels: analyticsData.labels, datasets: [{ data: analyticsData.revenue, borderColor: '#6564ed', backgroundColor: 'rgba(101,100,237,.14)', fill: true, tension: .38, pointRadius: 0, pointHoverRadius: 4, borderWidth: 2.5 }] },
  options: { ...chartOptions, scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 8, maxRotation: 0, color: '#8792a8' } }, y: { beginAtZero: true, border: { display: false }, grid: { color: '#e7ebf3', borderDash: [3, 4] }, ticks: { color: '#8792a8', callback: value => new Intl.NumberFormat('vi-VN').format(value) } } } },
});
new Chart(document.getElementById('customers-chart'), {
  type: 'line',
  data: { labels: analyticsData.labels, datasets: [{ data: analyticsData.customers, borderColor: '#20bf67', backgroundColor: 'rgba(32,191,103,.15)', fill: true, tension: .3, pointRadius: 0, pointHoverRadius: 4, borderWidth: 2.5 }] },
  options: { ...chartOptions, scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 8, maxRotation: 0, color: '#8792a8' } }, y: { beginAtZero: true, border: { display: false }, grid: { color: '#e7ebf3', borderDash: [3, 4] }, ticks: { precision: 0, color: '#8792a8' } } } },
});
const doughnutOptions = { ...chartOptions, cutout: '60%', plugins: { ...chartOptions.plugins, legend: { display: true, position: 'bottom', labels: { usePointStyle: true, pointStyle: 'rect', boxWidth: 10, padding: 14, color: '#667085' } } } };
new Chart(document.getElementById('category-chart'), { type: 'doughnut', data: { labels: analyticsData.categoryLabels, datasets: [{ data: analyticsData.categoryValues, backgroundColor: chartColors, borderColor: '#fff', borderWidth: 2, hoverOffset: 5 }] }, options: doughnutOptions });
new Chart(document.getElementById('status-chart'), { type: 'doughnut', data: { labels: analyticsData.statusLabels, datasets: [{ data: analyticsData.statusValues, backgroundColor: chartColors, borderColor: '#fff', borderWidth: 2, hoverOffset: 5 }] }, options: doughnutOptions });
new Chart(document.getElementById('products-chart'), {
  type: 'bar',
  data: { labels: analyticsData.productLabels, datasets: [{ data: analyticsData.productValues, backgroundColor: '#6564ed', borderRadius: 6, borderSkipped: false, barPercentage: .72, categoryPercentage: .82 }] },
  options: { ...chartOptions, indexAxis: 'y', plugins: { ...chartOptions.plugins, legend: { display: false } }, scales: { x: { beginAtZero: true, border: { display: false }, grid: { color: '#e7ebf3', borderDash: [3, 4] }, ticks: { precision: 0, color: '#8792a8' } }, y: { border: { display: false }, grid: { display: false }, ticks: { color: '#667085' } } } },
});
</script>
<?php require __DIR__ . '/_footer.php'; ?>