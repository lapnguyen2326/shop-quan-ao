<?php
require __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
require __DIR__ . '/_books_data.php';

$filters = booksFilters();
$summary = booksSummary($filters['where'], $filters['params']);
$orders = booksRows($filters['where'], $filters['params']);
$filterDescription = [];
if ($filters['search'] !== '') $filterDescription[] = 'Tìm kiếm: ' . $filters['search'];
if ($filters['from'] !== '') $filterDescription[] = 'Từ ngày: ' . $filters['from'];
if ($filters['to'] !== '') $filterDescription[] = 'Đến ngày: ' . $filters['to'];
if ($filters['status'] !== '') $filterDescription[] = 'Trạng thái: ' . $filters['status'];

$rows = [
    [['Sổ sách cửa hàng', 'string', 1]],
    [['Bộ lọc', 'string', 3], [implode(' · ', $filterDescription) ?: 'Tất cả giao dịch', 'string', 0]],
    [['Doanh thu đơn hoàn thành', 'string', 0], [(float)$summary['DaThu'], 'number', 2]],
    [['Giá trị đơn đang xử lý', 'string', 0], [(float)$summary['ChoXuLy'], 'number', 2]],
    [['Lợi nhuận đơn hoàn thành', 'string', 0], [(float)$summary['LoiNhuan'], 'number', 2]],
    [['Tổng số đơn theo bộ lọc', 'string', 0], [(int)$summary['SoDon'], 'number', 0]],
];
if ((int)$summary['DonChuaCoGiaVon'] > 0) {
    $rows[] = [['Lưu ý', 'string', 3], ['Chưa tính được lợi nhuận của ' . (int)$summary['DonChuaCoGiaVon'] . ' đơn hoàn thành vì thiếu giá vốn tại thời điểm bán.', 'string', 0]];
}
$rows[] = [];
$headerRow = count($rows) + 1;
$rows[] = array_map(
    static fn($heading) => [$heading, 'string', 3],
    ['Mã đơn', 'Ngày lập', 'Khách hàng', 'Thanh toán', 'Trạng thái', 'Tiền hàng', 'Giá vốn', 'Lợi nhuận', 'Voucher', 'Giảm voucher', 'Phí ship', 'Tổng cộng']
);

foreach ($orders as $order) {
    $profit = $order['TrangThai'] !== 'Hoàn thành' || $order['TienVon'] === null
        ? null
        : (float)$order['TienHang'] - (float)$order['GiamVoucher'] - (float)$order['TienVon'];
    $rows[] = [
        [$order['MaHD'], 'string', 0],
        [fdate($order['NgayLapHD'], 'Y-m-d H:i:s'), 'string', 0],
        [$order['TenKH'] ?: $order['TenNguoiNhan'], 'string', 0],
        [$order['PTThanhToan'], 'string', 0],
        [$order['TrangThai'], 'string', 0],
        [(float)$order['TienHang'], 'number', 2],
        [$order['TienVon'] === null ? 'Chưa có' : (float)$order['TienVon'], $order['TienVon'] === null ? 'string' : 'number', $order['TienVon'] === null ? 0 : 2],
        [$profit === null ? '—' : $profit, $profit === null ? 'string' : 'number', $profit === null ? 0 : 2],
        [$order['MaVoucher'] ?? '—', 'string', 0],
        [(float)$order['GiamVoucher'], 'number', 2],
        [(float)$order['PhiVanChuyen'], 'number', 2],
        [orderTotal($order), 'number', 2],
    ];
}

$xml = static fn($value) => htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
$columnName = static function (int $column): string {
    $name = '';
    while ($column > 0) {
        $column--;
        $name = chr(65 + ($column % 26)) . $name;
        $column = intdiv($column, 26);
    }
    return $name;
};
$sheetRows = '';
foreach ($rows as $rowIndex => $row) {
    $sheetRows .= '<row r="' . ($rowIndex + 1) . '">';
    foreach ($row as $columnIndex => $cell) {
        [$value, $type, $style] = array_pad($cell, 3, 0);
        $reference = $columnName($columnIndex + 1) . ($rowIndex + 1);
        $styleAttribute = $style ? ' s="' . (int)$style . '"' : '';
        if ($type === 'number') {
            $sheetRows .= '<c r="' . $reference . '"' . $styleAttribute . '><v>' . (float)$value . '</v></c>';
        } else {
            $sheetRows .= '<c r="' . $reference . '" t="inlineStr"' . $styleAttribute . '><is><t xml:space="preserve">' . $xml($value) . '</t></is></c>';
        }
    }
    $sheetRows .= '</row>';
}

$lastRow = count($rows);
$worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $headerRow . '" topLeftCell="A' . ($headerRow + 1) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
    . '<cols><col min="1" max="1" width="16" customWidth="1"/><col min="2" max="2" width="22" customWidth="1"/><col min="3" max="3" width="30" customWidth="1"/><col min="4" max="5" width="22" customWidth="1"/><col min="6" max="8" width="20" customWidth="1"/><col min="9" max="9" width="20" customWidth="1"/><col min="10" max="12" width="20" customWidth="1"/></cols>'
    . '<sheetData>' . $sheetRows . '</sheetData>'
    . '<autoFilter ref="A' . $headerRow . ':L' . max($headerRow, $lastRow) . '"/>'
    . '</worksheet>';
$workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheets><sheet name="So sach" sheetId="1" r:id="rId1"/></sheets></workbook>';
$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<fonts count="3"><font><sz val="11"/><name val="Times New Roman"/><family val="2"/></font><font><b/><sz val="16"/><name val="Times New Roman"/><family val="2"/></font><font><b/><sz val="11"/><name val="Times New Roman"/><family val="2"/></font></fonts>'
    . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9EAF7"/><bgColor indexed="64"/></patternFill></fill></fills>'
    . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
    . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0&quot; ₫&quot;"/></numFmts>'
    . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
    . '<cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/><xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
    . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
$files = [
    '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '</Types>',
    '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>',
    'xl/workbook.xml' => $workbook,
    'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>',
    'xl/worksheets/sheet1.xml' => $worksheet,
    'xl/styles.xml' => $styles,
];
$zipData = '';
$centralDirectory = '';
foreach ($files as $path => $content) {
    $compressed = gzdeflate($content, 6);
    $checksum = crc32($content);
    $pathLength = strlen($path);
    $compressedLength = strlen($compressed);
    $contentLength = strlen($content);
    $offset = strlen($zipData);
    $zipData .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 8, 0, 0, $checksum, $compressedLength, $contentLength, $pathLength, 0)
        . $path . $compressed;
    $centralDirectory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 8, 0, 0, $checksum, $compressedLength, $contentLength, $pathLength, 0, 0, 0, 0, 0, $offset)
        . $path;
}
$centralOffset = strlen($zipData);
$centralLength = strlen($centralDirectory);
$zipData .= $centralDirectory
    . pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), $centralLength, $centralOffset, 0);

$filename = 'so-sach-' . date('Ymd-His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($zipData));
header('X-Content-Type-Options: nosniff');
echo $zipData;
