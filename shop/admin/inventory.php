<?php
$title = 'Nhập hàng & giá vốn';
require __DIR__ . '/_header.php';
ensureProductCostColumn();
ensureInventoryTables();

$maxStock = 2147483647;
$maxMoney = 999999999999999999;
$maxUnitCost = 999999999999;
$error = '';
$noteValue = $_POST['note'] ?? '';
$note = is_string($noteValue) ? trim($noteValue) : '';
$postedItems = is_array($_POST['items'] ?? null) ? array_values($_POST['items']) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? null) === 'receive') {
    $items = [];
    $seenProducts = [];
    foreach ($postedItems as $row) {
        if (!is_array($row)) {
            $error = 'Thông tin sản phẩm nhập không hợp lệ.';
            break;
        }
        $productValue = $row['product'] ?? '';
        $quantityValue = $row['quantity'] ?? '';
        $costValue = $row['unit_cost'] ?? '';
        $openingCostValue = $row['opening_cost'] ?? '';
        if (!is_scalar($productValue) || !is_scalar($quantityValue) || !is_scalar($costValue) || !is_scalar($openingCostValue)) {
            $error = 'Thông tin sản phẩm nhập không hợp lệ.';
            break;
        }
        $productId = trim((string)$productValue);
        $rawQuantity = trim((string)$quantityValue);
        $rawCost = trim((string)$costValue);
        $rawOpeningCost = trim((string)$openingCostValue);
        if ($productId === '' || !preg_match('/^\d+$/', $rawQuantity) || (int)$rawQuantity < 1 || (int)$rawQuantity > 1000000) {
            $error = 'Vui lòng chọn sản phẩm và nhập số lượng từ 1 đến 1.000.000.';
            break;
        }
        if (!preg_match('/^\d{1,12}$/', $rawCost) || (int)$rawCost < 1 || (int)$rawCost > $maxUnitCost) {
            $error = 'Đơn giá nhập phải là số nguyên dương, tối đa 999.999.999.999đ.';
            break;
        }
        if ($rawOpeningCost !== '' && (!preg_match('/^\d{1,12}$/', $rawOpeningCost) || (int)$rawOpeningCost < 1 || (int)$rawOpeningCost > $maxUnitCost)) {
            $error = 'Giá vốn tồn trước nhập phải là số nguyên dương, tối đa 999.999.999.999đ.';
            break;
        }
        if (isset($seenProducts[$productId])) {
            $error = 'Mỗi sản phẩm chỉ được xuất hiện một lần trong một phiếu nhập.';
            break;
        }
        $seenProducts[$productId] = true;
        $items[] = [
            'product' => $productId,
            'quantity' => (int)$rawQuantity,
            'unit_cost' => (int)$rawCost,
            'opening_cost' => $rawOpeningCost === '' ? null : (int)$rawOpeningCost,
        ];
    }
    if (!$error && !$items) $error = 'Vui lòng thêm ít nhất một sản phẩm vào phiếu nhập.';
    if (!$error && !is_string($noteValue)) $error = 'Ghi chú phiếu nhập không hợp lệ.';
    $noteLength = preg_match_all('/./us', $note);
    if (!$error && ($noteLength === false || $noteLength > 255)) $error = 'Ghi chú phiếu nhập không được vượt quá 255 ký tự.';

    if (!$error) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $receiptLines = [];
            $totalQuantity = 0;
            $totalAmount = 0;
            foreach ($items as $item) {
                $product = one('SELECT MaSP, TenSP, SoLuongTon, GiaVon FROM SanPham WHERE MaSP = ?', [$item['product']]);
                if (!$product) throw new RuntimeException('Có sản phẩm không còn tồn tại, vui lòng tải lại trang.');

                $oldStock = (int)$product['SoLuongTon'];
                $storedCost = (int)$product['GiaVon'];
                if ($oldStock < 0 || $storedCost < 0) throw new RuntimeException('Tồn kho hoặc giá vốn của sản phẩm ' . $product['TenSP'] . ' không hợp lệ.');
                if ($item['quantity'] > $maxStock - $oldStock) throw new RuntimeException('Số lượng tồn sau nhập của sản phẩm ' . $product['TenSP'] . ' vượt giới hạn.');

                if ($oldStock > 0 && $storedCost <= 0 && $item['opening_cost'] === null) {
                    throw new RuntimeException('Sản phẩm ' . $product['TenSP'] . ' đang có tồn nhưng chưa có giá vốn. Vui lòng nhập giá vốn tồn trước nhập để hệ thống tính bình quân chính xác.');
                }
                $openingCost = $oldStock > 0 ? ($storedCost > 0 ? $storedCost : $item['opening_cost']) : 0;
                if ($oldStock > 0 && $openingCost > intdiv($maxMoney, $oldStock)) {
                    throw new RuntimeException('Giá trị tồn hiện tại của sản phẩm ' . $product['TenSP'] . ' vượt giới hạn tính toán.');
                }
                if ($item['unit_cost'] > intdiv($maxMoney, $item['quantity'])) {
                    throw new RuntimeException('Thành tiền nhập của sản phẩm ' . $product['TenSP'] . ' vượt giới hạn tính toán.');
                }

                $oldValue = $oldStock * $openingCost;
                $lineAmount = $item['quantity'] * $item['unit_cost'];
                if ($oldValue > $maxMoney - $lineAmount || $totalAmount > $maxMoney - $lineAmount) {
                    throw new RuntimeException('Tổng giá trị phiếu nhập vượt giới hạn lưu trữ.');
                }
                $newStock = $oldStock + $item['quantity'];
                $weightedValue = $oldValue + $lineAmount;
                $newCost = intdiv($weightedValue, $newStock);
                if ($weightedValue % $newStock >= intdiv($newStock, 2) + ($newStock % 2)) $newCost++;

                $updated = run(
                    'UPDATE SanPham SET SoLuongTon = ?, GiaVon = ? WHERE MaSP = ? AND SoLuongTon = ? AND GiaVon = ?',
                    [$newStock, $newCost, $product['MaSP'], $oldStock, $product['GiaVon']]
                );
                if ($updated !== 1) throw new RuntimeException('Tồn kho sản phẩm ' . $product['TenSP'] . ' vừa thay đổi. Vui lòng tải lại trang và thử lại.');

                $receiptLines[] = [
                    'product' => $product['MaSP'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'old_stock' => $oldStock,
                    'old_cost' => $openingCost,
                    'new_stock' => $newStock,
                    'new_cost' => $newCost,
                    'amount' => $lineAmount,
                ];
                $totalQuantity += $item['quantity'];
                if ($totalQuantity > $maxStock) throw new RuntimeException('Tổng số lượng phiếu nhập vượt giới hạn lưu trữ.');
                $totalAmount += $lineAmount;
            }

            $headerValues = [nowStr(), $note !== '' ? $note : null, me()['UserName'], $totalQuantity, $totalAmount];
            if (driver() === 'sqlsrv') {
                $receiptId = (int)q(
                    'INSERT INTO PhieuNhapKho (NgayNhap, GhiChu, NguoiNhap, TongSoLuong, TongTien) OUTPUT INSERTED.MaPN VALUES (?,?,?,?,?)',
                    $headerValues
                )->fetchColumn();
            } else {
                run('INSERT INTO PhieuNhapKho (NgayNhap, GhiChu, NguoiNhap, TongSoLuong, TongTien) VALUES (?,?,?,?,?)', $headerValues);
                $receiptId = lastId();
            }
            if ($receiptId < 1) throw new RuntimeException('Không tạo được mã phiếu nhập.');

            foreach ($receiptLines as $line) {
                run(
                    'INSERT INTO ChiTietPhieuNhap (MaPN, MaSP, SoLuong, DonGiaNhap, TonTruocNhap, GiaVonTruocNhap, TonSauNhap, GiaVonSauNhap, ThanhTien) VALUES (?,?,?,?,?,?,?,?,?)',
                    [
                        $receiptId, $line['product'], $line['quantity'], $line['unit_cost'],
                        $line['old_stock'], $line['old_cost'], $line['new_stock'], $line['new_cost'], $line['amount'],
                    ]
                );
            }

            $pdo->commit();
            flash('Đã xác nhận phiếu nhập #' . $receiptId . ', tổng ' . number_format($totalQuantity, 0, ',', '.') . ' sản phẩm.');
            redirect('admin/inventory.php');
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $e->getMessage();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Không thể lưu phiếu nhập do lỗi dữ liệu. Vui lòng kiểm tra và thử lại.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}

$products = all('SELECT p.MaSP, p.TenSP, p.SoLuongTon, p.GiaVon, p.HienThi, l.TenLoaiSP FROM SanPham p LEFT JOIN LoaiSP l ON l.MaLoaiSP = p.MaLoaiSP ORDER BY l.TenLoaiSP, p.TenSP');
$recentReceipts = all(limitSql(
    'SELECT pn.MaPN, pn.NgayNhap, pn.GhiChu, pn.NguoiNhap, pn.TongSoLuong, pn.TongTien, u.HoTen FROM PhieuNhapKho pn LEFT JOIN Users u ON u.UserName = pn.NguoiNhap ORDER BY pn.MaPN DESC',
    20
));
$receiptDetails = [];
if ($recentReceipts) {
    $receiptIds = array_map(static fn($receipt) => (int)$receipt['MaPN'], $recentReceipts);
    $details = all(
        'SELECT d.MaPN, d.MaSP, p.TenSP, d.SoLuong, d.DonGiaNhap, d.TonTruocNhap, d.GiaVonTruocNhap, d.TonSauNhap, d.GiaVonSauNhap, d.ThanhTien FROM ChiTietPhieuNhap d JOIN SanPham p ON p.MaSP = d.MaSP WHERE d.MaPN IN (' . implode(',', array_fill(0, count($receiptIds), '?')) . ') ORDER BY d.MaPN DESC, d.MaSP',
        $receiptIds
    );
    foreach ($details as $detail) $receiptDetails[$detail['MaPN']][] = $detail;
}

$formatNumber = static fn($value) => number_format((float)$value, 0, ',', '.');
?>
<div class="page-head"><h1>Nhập hàng & giá vốn</h1></div>
<p class="muted">Giá vốn được tính theo bình quân gia quyền sau mỗi phiếu nhập. Đơn đã tạo giữ nguyên giá vốn đã chụp tại thời điểm đặt hàng.</p>
<?php if ($error): ?><div class="flash err"><?= h($error) ?></div><?php endif; ?>

<section class="panel">
  <h2>Tạo phiếu nhập hàng</h2>
  <p class="muted">Nhập số lượng và đơn giá cho từng sản phẩm. Tồn kho và giá vốn mới chỉ được cập nhật sau khi xác nhận phiếu. Nếu sản phẩm đang có tồn nhưng chưa có giá vốn, hệ thống sẽ yêu cầu nhập giá vốn tồn hiện tại.</p>
  <form method="post" id="receipt-form">
    <?= csrf() ?>
    <input type="hidden" name="action" value="receive">
    <div id="receipt-lines">
      <?php
      $formRows = $postedItems ?: [['product' => '', 'quantity' => '', 'unit_cost' => '', 'opening_cost' => '']];
      foreach ($formRows as $index => $row):
          if (!is_array($row)) $row = [];
          $selectedProduct = is_scalar($row['product'] ?? null) ? (string)$row['product'] : '';
      ?>
      <div class="receipt-line">
        <label>Sản phẩm
          <select class="receipt-product" name="items[<?= (int)$index ?>][product]" required>
            <option value="">Chọn sản phẩm</option>
            <?php foreach ($products as $product): ?>
              <option value="<?= h($product['MaSP']) ?>"
                data-stock="<?= (int)$product['SoLuongTon'] ?>"
                data-cost="<?= (int)$product['GiaVon'] ?>"
                <?= $selectedProduct === (string)$product['MaSP'] ? 'selected' : '' ?>>
                <?= h($product['TenSP']) ?> (<?= h($product['MaSP']) ?><?= !(int)$product['HienThi'] ? ' · Đang ẩn' : '' ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Số lượng nhập
          <input class="receipt-quantity" type="number" name="items[<?= (int)$index ?>][quantity]" min="1" max="1000000" step="1" value="<?= h($row['quantity'] ?? '') ?>" required>
        </label>
        <label>Giá nhập / sản phẩm (₫)
          <input class="receipt-unit-cost" type="number" name="items[<?= (int)$index ?>][unit_cost]" min="1" max="<?= $maxUnitCost ?>" step="1" value="<?= h($row['unit_cost'] ?? '') ?>" required>
        </label>
        <label class="opening-cost-field" hidden>Giá vốn tồn hiện tại / sản phẩm (₫)
          <input class="receipt-opening-cost" type="number" name="items[<?= (int)$index ?>][opening_cost]" min="1" max="<?= $maxUnitCost ?>" step="1" value="<?= h($row['opening_cost'] ?? '') ?>">
        </label>
        <button type="button" class="link danger remove-receipt-line" aria-label="Xóa sản phẩm">Xóa</button>
        <div class="receipt-line-preview">
          <span>Tồn trước: <b class="preview-old-stock">—</b></span>
          <span>Giá vốn trước: <b class="preview-old-cost">—</b></span>
          <span>Giá trị tồn trước: <b class="preview-old-value">—</b></span>
          <span>Tồn sau: <b class="preview-new-stock">—</b></span>
          <span>Giá vốn sau: <b class="preview-new-cost">—</b></span>
          <span>Giá trị tồn sau: <b class="preview-new-value">—</b></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <template id="receipt-line-template">
      <div class="receipt-line">
        <label>Sản phẩm
          <select class="receipt-product" required>
            <option value="">Chọn sản phẩm</option>
            <?php foreach ($products as $product): ?>
              <option value="<?= h($product['MaSP']) ?>" data-stock="<?= (int)$product['SoLuongTon'] ?>" data-cost="<?= (int)$product['GiaVon'] ?>">
                <?= h($product['TenSP']) ?> (<?= h($product['MaSP']) ?><?= !(int)$product['HienThi'] ? ' · Đang ẩn' : '' ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Số lượng nhập<input class="receipt-quantity" type="number" min="1" max="1000000" step="1" required></label>
        <label>Giá nhập / sản phẩm (₫)<input class="receipt-unit-cost" type="number" min="1" max="<?= $maxUnitCost ?>" step="1" required></label>
        <label class="opening-cost-field" hidden>Giá vốn tồn hiện tại / sản phẩm (₫)<input class="receipt-opening-cost" type="number" min="1" max="<?= $maxUnitCost ?>" step="1"></label>
        <button type="button" class="link danger remove-receipt-line" aria-label="Xóa sản phẩm">Xóa</button>
        <div class="receipt-line-preview">
          <span>Tồn trước: <b class="preview-old-stock">—</b></span>
          <span>Giá vốn trước: <b class="preview-old-cost">—</b></span>
          <span>Giá trị tồn trước: <b class="preview-old-value">—</b></span>
          <span>Tồn sau: <b class="preview-new-stock">—</b></span>
          <span>Giá vốn sau: <b class="preview-new-cost">—</b></span>
          <span>Giá trị tồn sau: <b class="preview-new-value">—</b></span>
        </div>
      </div>
    </template>
    <button type="button" class="btn ghost" id="add-receipt-line">+ Thêm sản phẩm</button>
    <div class="receipt-summary">
      <div>Tổng số lượng nhập <b id="receipt-total-quantity">0 sản phẩm</b></div>
      <div>Tổng tiền nhập dự kiến <b id="receipt-total-amount">0 ₫</b></div>
    </div>
    <label class="receipt-note">Ghi chú phiếu nhập
      <input type="text" name="note" maxlength="255" value="<?= h($note) ?>" placeholder="Nhà cung cấp, số hóa đơn nhập...">
    </label>
    <button type="submit" class="btn">Xác nhận phiếu nhập</button>
  </form>
</section>

<section class="panel">
  <h2>Lịch sử phiếu nhập</h2>
  <div class="table-wrap"><table>
    <tr><th>Mã phiếu</th><th>Ngày nhập</th><th>Ghi chú</th><th>Người nhập</th><th class="r">Số lượng</th><th class="r">Tổng tiền</th><th>Chi tiết</th></tr>
    <?php foreach ($recentReceipts as $receipt): ?>
      <tr>
        <td>#<?= (int)$receipt['MaPN'] ?></td>
        <td><?= h(fdate($receipt['NgayNhap'])) ?></td>
        <td><?= h($receipt['GhiChu'] ?: '—') ?></td>
        <td><?= h($receipt['HoTen'] ?: $receipt['NguoiNhap'] ?: '—') ?></td>
        <td class="r"><?= $formatNumber($receipt['TongSoLuong']) ?></td>
        <td class="r"><?= money($receipt['TongTien']) ?></td>
        <td><details><summary>Xem</summary>
          <div class="table-wrap receipt-details"><table>
            <tr><th>Sản phẩm</th><th class="r">SL</th><th class="r">Đơn giá nhập</th><th class="r">Tồn trước</th><th class="r">Giá vốn trước</th><th class="r">Tồn sau</th><th class="r">Giá vốn sau</th><th class="r">Thành tiền</th></tr>
            <?php foreach ($receiptDetails[$receipt['MaPN']] ?? [] as $detail): ?>
              <tr>
                <td><?= h($detail['TenSP']) ?> (<?= h($detail['MaSP']) ?>)</td>
                <td class="r"><?= $formatNumber($detail['SoLuong']) ?></td>
                <td class="r"><?= money($detail['DonGiaNhap']) ?></td>
                <td class="r"><?= $formatNumber($detail['TonTruocNhap']) ?></td>
                <td class="r"><?= money($detail['GiaVonTruocNhap']) ?></td>
                <td class="r"><?= $formatNumber($detail['TonSauNhap']) ?></td>
                <td class="r"><?= money($detail['GiaVonSauNhap']) ?></td>
                <td class="r"><?= money($detail['ThanhTien']) ?></td>
              </tr>
            <?php endforeach; ?>
          </table></div>
        </details></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$recentReceipts): ?><tr><td colspan="7" class="muted">Chưa có phiếu nhập hàng.</td></tr><?php endif; ?>
  </table></div>
</section>

<script>
(() => {
  const lines = document.getElementById('receipt-lines');
  const template = document.getElementById('receipt-line-template');
  const money = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 });

  function updateNames() {
    lines.querySelectorAll('.receipt-line').forEach((line, index) => {
      line.querySelector('.receipt-product').name = `items[${index}][product]`;
      line.querySelector('.receipt-quantity').name = `items[${index}][quantity]`;
      line.querySelector('.receipt-unit-cost').name = `items[${index}][unit_cost]`;
      line.querySelector('.receipt-opening-cost').name = `items[${index}][opening_cost]`;
    });
  }

  function updatePreview() {
    let totalQuantity = 0;
    let totalAmount = 0;
    lines.querySelectorAll('.receipt-line').forEach(line => {
      const option = line.querySelector('.receipt-product').selectedOptions[0];
      const quantity = Number(line.querySelector('.receipt-quantity').value);
      const unitCost = Number(line.querySelector('.receipt-unit-cost').value);
      const oldStock = option && option.value ? Number(option.dataset.stock) : 0;
      const storedCost = option && option.value ? Number(option.dataset.cost) : 0;
      const openingField = line.querySelector('.opening-cost-field');
      const openingInput = line.querySelector('.receipt-opening-cost');
      const needsOpeningCost = oldStock > 0 && storedCost <= 0;
      openingField.hidden = !needsOpeningCost;
      openingInput.required = needsOpeningCost;
      const openingCost = needsOpeningCost ? Number(openingInput.value) : storedCost;
      const valid = option && option.value && Number.isInteger(quantity) && quantity > 0 && Number.isInteger(unitCost) && unitCost > 0 && (!needsOpeningCost || (Number.isInteger(openingCost) && openingCost > 0));
      line.querySelector('.preview-old-stock').textContent = option && option.value ? money.format(oldStock) : '—';
      line.querySelector('.preview-old-cost').textContent = oldStock > 0 && openingCost > 0 ? money.format(openingCost) + ' ₫' : '—';
      line.querySelector('.preview-old-value').textContent = option && option.value
        ? (oldStock === 0 ? money.format(0) + ' ₫' : (openingCost > 0 ? money.format(oldStock * openingCost) + ' ₫' : 'Cần giá vốn'))
        : '—';
      if (!valid) {
        line.querySelector('.preview-new-stock').textContent = needsOpeningCost && !openingCost ? 'Cần giá vốn tồn' : '—';
        line.querySelector('.preview-new-cost').textContent = '—';
        line.querySelector('.preview-new-value').textContent = '—';
        return;
      }
      const newStock = oldStock + quantity;
      const newCost = Math.floor((oldStock * openingCost + quantity * unitCost) / newStock + 0.5);
      line.querySelector('.preview-new-stock').textContent = money.format(newStock);
      line.querySelector('.preview-new-cost').textContent = money.format(newCost) + ' ₫';
      line.querySelector('.preview-new-value').textContent = money.format(newStock * newCost) + ' ₫';
      totalQuantity += quantity;
      totalAmount += quantity * unitCost;
    });
    document.getElementById('receipt-total-quantity').textContent = money.format(totalQuantity) + ' sản phẩm';
    document.getElementById('receipt-total-amount').textContent = money.format(totalAmount) + ' ₫';
  }

  document.getElementById('add-receipt-line').addEventListener('click', () => {
    lines.append(template.content.cloneNode(true));
    updateNames();
    updatePreview();
  });
  lines.addEventListener('click', event => {
    if (!event.target.classList.contains('remove-receipt-line')) return;
    if (lines.querySelectorAll('.receipt-line').length > 1) event.target.closest('.receipt-line').remove();
    updateNames();
    updatePreview();
  });
  lines.addEventListener('input', updatePreview);
  lines.addEventListener('change', event => {
    if (event.target.classList.contains('receipt-product')) {
      event.target.closest('.receipt-line').querySelector('.receipt-opening-cost').value = '';
    }
    updatePreview();
  });
  updateNames();
  updatePreview();
})();
</script>
<?php require __DIR__ . '/_footer.php'; ?>
