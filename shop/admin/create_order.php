<?php
$title = 'Tạo đơn cho khách';
require __DIR__ . '/_header.php';

$customers = all('SELECT MaKH, TenKH, DienThoai, DiaChi FROM KhachHang ORDER BY TenKH, MaKH');
$products = all('SELECT MaSP, TenSP, DonGia, GiaVon, PhanTramGiam, SoLuongTon, KichCo, MauSac FROM SanPham WHERE HienThi = 1 AND SoLuongTon > 0 ORDER BY TenSP');
$activeVouchers = all("SELECT MaVoucher FROM Voucher WHERE KichHoat = 1 AND (NgayBatDau IS NULL OR NgayBatDau <= ?) AND (NgayKetThuc IS NULL OR NgayKetThuc >= ?) AND (SoLuong IS NULL OR DaSuDung < SoLuong) ORDER BY MaVoucher", [nowStr(), nowStr()]);
$form = [
    'customer' => post('customer'),
    'recipient' => post('recipient'),
    'phone' => post('phone'),
    'address' => post('address'),
    'note' => post('note'),
    'payment' => post('payment', 'COD'),
    'voucher' => strtoupper(post('voucher')),
];
$postedItems = is_array($_POST['items'] ?? null) ? $_POST['items'] : [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer = $form['customer'] !== '' ? one('SELECT MaKH, TenKH FROM KhachHang WHERE MaKH = ?', [$form['customer']]) : null;
    if ($form['customer'] !== '' && !$customer) $error = 'Khách hàng được chọn không tồn tại.';
    elseif ($form['recipient'] === '') $error = 'Vui lòng nhập tên người nhận.';
    elseif (!preg_match('/^[0-9 .+-]{8,15}$/', $form['phone'])) $error = 'Số điện thoại không hợp lệ.';
    elseif ($form['address'] === '') $error = 'Vui lòng nhập địa chỉ giao hàng.';
    elseif (!in_array($form['payment'], ['COD', 'Chuyển khoản'], true)) $error = 'Phương thức thanh toán không hợp lệ.';

    $itemsByKey = [];
    if (!$error) {
        foreach ($postedItems as $row) {
            if (!is_array($row)) { $error = 'Thông tin sản phẩm không hợp lệ.'; break; }
            $productId = trim((string)($row['product'] ?? ''));
            $quantity = filter_var($row['quantity'] ?? null, FILTER_VALIDATE_INT);
            if ($productId === '' || $quantity === false || $quantity < 1) { $error = 'Vui lòng chọn sản phẩm và nhập số lượng hợp lệ.'; break; }
            $size = trim((string)($row['size'] ?? ''));
            $color = trim((string)($row['color'] ?? ''));
            if (strlen($size) > 10 || strlen($color) > 30) { $error = 'Kích cỡ hoặc màu sắc quá dài.'; break; }
            $key = json_encode([$productId, $size, $color], JSON_UNESCAPED_UNICODE);
            if (isset($itemsByKey[$key])) $itemsByKey[$key]['quantity'] += $quantity;
            else $itemsByKey[$key] = ['product' => $productId, 'quantity' => $quantity, 'size' => $size, 'color' => $color];
        }
        if (!$error && !$itemsByKey) $error = 'Vui lòng thêm ít nhất một sản phẩm.';
    }

    $subtotal = 0;
    $pricedItems = [];
    if (!$error) {
        foreach ($itemsByKey as $item) {
            $product = one('SELECT * FROM SanPham WHERE MaSP = ? AND HienThi = 1', [$item['product']]);
            if (!$product) { $error = 'Có sản phẩm không tồn tại hoặc đã ngừng bán.'; break; }
            $sizes = array_values(array_filter(array_map('trim', explode(',', (string)$product['KichCo'])), 'strlen'));
            $colors = array_values(array_filter(array_map('trim', explode(',', (string)$product['MauSac'])), 'strlen'));
            if (($sizes && !in_array($item['size'], $sizes, true)) || (!$sizes && $item['size'] !== '')) { $error = 'Kích cỡ không hợp lệ cho sản phẩm ' . $product['TenSP'] . '.'; break; }
            if (($colors && !in_array($item['color'], $colors, true)) || (!$colors && $item['color'] !== '')) { $error = 'Màu sắc không hợp lệ cho sản phẩm ' . $product['TenSP'] . '.'; break; }
            if ((int)$product['SoLuongTon'] < $item['quantity']) { $error = 'Sản phẩm ' . $product['TenSP'] . ' không đủ tồn kho.'; break; }
            $unitPrice = effPrice($product);
            $subtotal += $unitPrice * $item['quantity'];
            $pricedItems[] = ['product' => $product, 'quantity' => $item['quantity'], 'size' => $item['size'], 'color' => $item['color']];
        }
    }

    $voucher = null;
    $discount = 0;
    if (!$error && $form['voucher'] !== '') {
        [$voucher, $discount, $voucherError] = findVoucher($form['voucher'], $subtotal, $customer['MaKH'] ?? null, true);
        if ($voucherError) $error = $voucherError;
    }

    if (!$error) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $freshCustomer = $form['customer'] !== '' ? one('SELECT MaKH FROM KhachHang WHERE MaKH = ?', [$form['customer']]) : null;
            if ($form['customer'] !== '' && !$freshCustomer) throw new RuntimeException('Khách hàng không còn tồn tại.');
            if ($form['voucher'] !== '') {
                [$voucher, $discount, $voucherError] = findVoucher($form['voucher'], $subtotal, $freshCustomer['MaKH'] ?? null, true);
                if ($voucherError) throw new RuntimeException($voucherError);
            }
            $orderId = nextCode('HD', 'HoaDon', 'MaHD', 3);
            $shippingFee = shipFee($subtotal);
            run('INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen, MaVoucher, GiamVoucher) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $orderId, $freshCustomer['MaKH'] ?? null, isEmployee() ? (int)me()['MaNV'] : null, nowStr(), null, 'Mới',
                $form['recipient'], $form['address'], $form['phone'], $form['note'], $form['payment'], $shippingFee,
                $voucher['MaVoucher'] ?? null, $discount,
            ]);
            if ($voucher) {
                $used = run('UPDATE Voucher SET DaSuDung = DaSuDung + 1 WHERE MaVoucher = ? AND KichHoat = 1 AND (NgayBatDau IS NULL OR NgayBatDau <= ?) AND (NgayKetThuc IS NULL OR NgayKetThuc >= ?) AND (SoLuong IS NULL OR DaSuDung < SoLuong)', [$voucher['MaVoucher'], nowStr(), nowStr()]);
                if ($used < 1) throw new RuntimeException('Voucher vừa hết lượt hoặc không còn hiệu lực, vui lòng thử lại.');
            }
            foreach ($pricedItems as $item) {
                $product = $item['product'];
                $stockUpdated = run('UPDATE SanPham SET SoLuongTon = SoLuongTon - ? WHERE MaSP = ? AND HienThi = 1 AND SoLuongTon >= ?', [$item['quantity'], $product['MaSP'], $item['quantity']]);
                if ($stockUpdated < 1) throw new RuntimeException('Sản phẩm ' . $product['TenSP'] . ' vừa hết hoặc không đủ tồn kho.');
                $monthlyCost = one('SELECT GiaVon FROM GiaVonThang WHERE MaSP = ? AND Thang = ?', [$product['MaSP'], date('Y-m')]);
                $unitCost = $monthlyCost ? (float)$monthlyCost['GiaVon'] : ((float)$product['GiaVon'] > 0 ? (float)$product['GiaVon'] : null);
                run('INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia, GiaVon) VALUES (?,?,?,?,?,?,?,?)', [
                    $orderId, $product['MaSP'], $item['size'], $item['color'], $item['quantity'],
                    (int)$product['PhanTramGiam'] / 100, $product['DonGia'], $unitCost,
                ]);
            }
            $pdo->commit();
            flash('Đã tạo đơn hàng ' . $orderId . ' cho khách.');
            redirect('admin/order_view.php?id=' . urlencode($orderId));
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $e->getMessage();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Không thể tạo đơn hàng do lỗi dữ liệu. Vui lòng kiểm tra thông tin và thử lại.';
        }
    }
}

$productOptions = '';
$productsById = [];
foreach ($products as $product) {
    $productsById[$product['MaSP']] = $product;
    $productOptions .= '<option value="' . h($product['MaSP']) . '" data-sizes="' . h($product['KichCo']) . '" data-colors="' . h($product['MauSac']) . '">' .
        h($product['TenSP']) . ' (' . h($product['MaSP']) . ') · ' . money(effPrice($product)) . ' · Tồn ' . (int)$product['SoLuongTon'] . '</option>';
}
$customerOptions = '<option value="">Khách mua lẻ / không liên kết tài khoản</option>';
foreach ($customers as $customer) {
    $customerOptions .= '<option value="' . h($customer['MaKH']) . '" data-name="' . h($customer['TenKH'] ?: $customer['MaKH']) . '" data-phone="' . h($customer['DienThoai']) . '" data-address="' . h($customer['DiaChi']) . '"' . ($form['customer'] === $customer['MaKH'] ? ' selected' : '') . '>' . h($customer['TenKH'] ?: $customer['MaKH']) . ' · ' . h($customer['DienThoai']) . '</option>';
}
?>
<div class="page-head"><h1>Tạo đơn cho khách</h1></div>
<?php if ($error): ?><div class="flash err"><?= h($error) ?></div><?php endif; ?>
<?php if (!$products): ?><div class="flash err">Hiện không có sản phẩm đang bán còn hàng để tạo đơn.</div><?php else: ?>
<form method="post" class="form-card wide-form">
  <?= csrf() ?>
  <h2>Thông tin khách hàng</h2>
  <div class="two">
    <label>Liên kết khách hàng (cần chọn để dùng voucher riêng)
      <select name="customer" id="order-customer"><?= $customerOptions ?></select>
    </label>
    <label>Tên người nhận<input name="recipient" value="<?= h($form['recipient']) ?>" maxlength="60" required></label>
  </div>
  <div class="two">
    <label>Số điện thoại<input name="phone" value="<?= h($form['phone']) ?>" maxlength="20" required></label>
    <label>Địa chỉ giao hàng<input name="address" value="<?= h($form['address']) ?>" maxlength="200" required></label>
  </div>
  <h2>Sản phẩm</h2>
  <div id="order-items">
    <?php foreach ($postedItems ?: [[]] as $index => $item): $itemProduct = $productsById[$item['product'] ?? ''] ?? null; ?>
      <div class="three order-item">
        <label>Sản phẩm<select name="items[<?= (int)$index ?>][product]" class="order-product"><option value="">Chọn sản phẩm</option><?php foreach ($products as $product): ?><option value="<?= h($product['MaSP']) ?>" data-sizes="<?= h($product['KichCo']) ?>" data-colors="<?= h($product['MauSac']) ?>" <?= ($item['product'] ?? '') === $product['MaSP'] ? 'selected' : '' ?>><?= h($product['TenSP']) ?> (<?= h($product['MaSP']) ?>) · <?= money(effPrice($product)) ?> · Tồn <?= (int)$product['SoLuongTon'] ?></option><?php endforeach; ?></select></label>
        <label>Kích cỡ<select name="items[<?= (int)$index ?>][size]" class="order-size"><option value="">Chọn kích cỡ</option><?php foreach (csv($itemProduct['KichCo'] ?? '') as $size): ?><option value="<?= h($size) ?>" <?= ($item['size'] ?? '') === $size ? 'selected' : '' ?>><?= h($size) ?></option><?php endforeach; ?></select></label>
        <label>Màu sắc<select name="items[<?= (int)$index ?>][color]" class="order-color"><option value="">Chọn màu</option><?php foreach (csv($itemProduct['MauSac'] ?? '') as $color): ?><option value="<?= h($color) ?>" <?= ($item['color'] ?? '') === $color ? 'selected' : '' ?>><?= h($color) ?></option><?php endforeach; ?></select></label>
        <label>Số lượng<input type="number" name="items[<?= (int)$index ?>][quantity]" class="order-quantity" value="<?= h($item['quantity'] ?? 1) ?>" min="1" step="1" required></label>
        <button class="link danger remove-order-item" type="button">Bỏ sản phẩm</button>
      </div>
    <?php endforeach; ?>
  </div>
  <button class="btn ghost" type="button" id="add-order-item">Thêm sản phẩm</button>
  <h2>Ưu đãi và thanh toán</h2>
  <div class="two">
    <label>Mã voucher (<?= count($activeVouchers) ?> mã đang hoạt động)
      <input name="voucher" value="<?= h($form['voucher']) ?>" list="available-vouchers" maxlength="30">
      <datalist id="available-vouchers"><?php foreach ($activeVouchers as $item): ?><option value="<?= h($item['MaVoucher']) ?>"><?php endforeach; ?></datalist>
    </label>
    <label>Phương thức thanh toán
      <select name="payment"><option value="COD" <?= $form['payment'] === 'COD' ? 'selected' : '' ?>>COD</option><option value="Chuyển khoản" <?= $form['payment'] === 'Chuyển khoản' ? 'selected' : '' ?>>Chuyển khoản</option></select>
    </label>
  </div>
  <label>Ghi chú<input name="note" value="<?= h($form['note']) ?>" maxlength="255"></label>
  <p class="muted">Voucher dùng chung có thể áp dụng cho khách mua lẻ; voucher riêng cần chọn đúng khách hàng. Mã phải còn hiệu lực, lượt dùng và đạt giá trị đơn tối thiểu. Đơn mới ở trạng thái “Mới / Chưa thanh toán”.</p>
  <button class="btn" type="submit">Tạo đơn hàng</button>
</form>
<template id="order-item-template">
  <div class="three order-item">
    <label>Sản phẩm<select class="order-product"><option value="">Chọn sản phẩm</option><?= $productOptions ?></select></label>
    <label>Kích cỡ<select class="order-size"><option value="">Chọn kích cỡ</option></select></label>
    <label>Màu sắc<select class="order-color"><option value="">Chọn màu</option></select></label>
    <label>Số lượng<input type="number" class="order-quantity" value="1" min="1" step="1" required></label>
    <button class="link danger remove-order-item" type="button">Bỏ sản phẩm</button>
  </div>
</template>
<script>
(() => {
  const container = document.getElementById('order-items');
  const template = document.getElementById('order-item-template');
  const syncFields = (row, index) => {
    row.querySelector('.order-product').name = `items[${index}][product]`;
    row.querySelector('.order-size').name = `items[${index}][size]`;
    row.querySelector('.order-color').name = `items[${index}][color]`;
    row.querySelector('.order-quantity').name = `items[${index}][quantity]`;
  };
  const updateVariants = (row, selection, kind, selectedValue = '') => {
    const field = row.querySelector(kind === 'sizes' ? '.order-size' : '.order-color');
    const values = (selection.dataset[kind] || '').split(',').map(value => value.trim()).filter(Boolean);
    field.replaceChildren(new Option(kind === 'sizes' ? 'Chọn kích cỡ' : 'Chọn màu', ''));
    values.forEach(value => field.add(new Option(value, value, false, value === selectedValue)));
  };
  container.querySelectorAll('.order-item').forEach(row => {
    const product = row.querySelector('.order-product');
    const selected = product.selectedOptions[0];
    if (product.value && selected) {
      updateVariants(row, selected, 'sizes', row.querySelector('.order-size').value);
      updateVariants(row, selected, 'colors', row.querySelector('.order-color').value);
    }
    product.addEventListener('change', () => {
      updateVariants(row, product.selectedOptions[0], 'sizes');
      updateVariants(row, product.selectedOptions[0], 'colors');
    });
  });
  const refreshNames = () => [...container.querySelectorAll('.order-item')].forEach(syncFields);
  const addRow = () => {
    const row = template.content.firstElementChild.cloneNode(true);
    container.appendChild(row);
    row.querySelector('.order-product').addEventListener('change', event => {
      updateVariants(row, event.target.selectedOptions[0], 'sizes');
      updateVariants(row, event.target.selectedOptions[0], 'colors');
    });
    refreshNames();
  };
  document.getElementById('add-order-item').addEventListener('click', addRow);
  container.addEventListener('click', event => {
    if (!event.target.classList.contains('remove-order-item')) return;
    const rows = container.querySelectorAll('.order-item');
    if (rows.length > 1) event.target.closest('.order-item').remove();
    refreshNames();
  });
  document.getElementById('order-customer').addEventListener('change', event => {
    const selected = event.target.selectedOptions[0];
    if (!selected.value) return;
    document.querySelector('[name="recipient"]').value = selected.dataset.name || '';
    document.querySelector('[name="phone"]').value = selected.dataset.phone || '';
    document.querySelector('[name="address"]').value = selected.dataset.address || '';
  });
  refreshNames();
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
