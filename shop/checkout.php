<?php
require __DIR__ . '/includes/bootstrap.php';
[$items, $sub] = cartItems();
if (!$items) { flash('Giỏ hàng đang trống.', 'err'); redirect('cart.php'); }
$fee = shipFee($sub);
$voucherCode = $_SESSION['voucher_code'] ?? '';
$voucherError = '';
$voucher = null;
$voucherDiscount = 0;
if ($voucherCode !== '') {
  [$voucher, $voucherDiscount, $voucherError] = findVoucher($voucherCode, $sub, customerMaKH());
  if ($voucherError) { unset($_SESSION['voucher_code']); $voucherCode = ''; }
}
$customerId = customerMaKH();
$kh = $customerId ? one('SELECT * FROM KhachHang WHERE MaKH = ?', [$customerId]) : null;
$addressOptions = [
  'Hà Nội' => [
    'Quận Ba Đình' => ['Phường Cống Vị', 'Phường Giảng Võ', 'Phường Kim Mã', 'Phường Liễu Giai', 'Phường Ngọc Hà'],
    'Quận Hoàn Kiếm' => ['Phường Hàng Bạc', 'Phường Hàng Trống', 'Phường Phan Chu Trinh', 'Phường Tràng Tiền', 'Phường Lý Thái Tổ'],
    'Quận Hai Bà Trưng' => ['Phường Bạch Đằng', 'Phường Minh Khai', 'Phường Nguyễn Du', 'Phường Phố Huế', 'Phường Thanh Lương'],
    'Quận Đống Đa' => ['Phường Cát Linh', 'Phường Khâm Thiên', 'Phường Nam Đồng', 'Phường Trung Liệt', 'Phường Văn Miếu'],
    'Quận Cầu Giấy' => ['Phường Dịch Vọng', 'Phường Mai Dịch', 'Phường Nghĩa Đô', 'Phường Ninh Hiệp', 'Phường Trung Hoà'],
    'Quận Thanh Xuân' => ['Phường Khương Đình', 'Phường Nhân Chính', 'Phường Thanh Xuân Bắc', 'Phường Thanh Xuân Nam', 'Phường Thượng Đình'],
    'Quận Bắc Từ Liêm' => ['Phường Cổ Nhuế 1', 'Phường Đông Ngạc', 'Phường Minh Khai', 'Phường Phú Diễn', 'Phường Thụy Phương'],
    'Quận Nam Từ Liêm' => ['Phường Mỹ Đình 1', 'Phường Mỹ Đình 2', 'Phường Phú Đô', 'Phường Từ Liêm', 'Phường Đại Mỗ'],
    'Huyện Gia Lâm' => ['Xã Đa Tốn', 'Xã Đông Dư', 'Xã Kim Lan', 'Xã Trâu Quỳ', 'Xã Yên Viên'],
    'Huyện Đông Anh' => ['Xã Đông Hội', 'Xã Hải Bối', 'Xã Uy Nỗ', 'Xã Vân Hà', 'Xã Xuân Canh'],
    'Huyện Sóc Sơn' => ['Xã Bắc Phú', 'Xã Hồng Kỳ', 'Xã Minh Phú', 'Xã Thanh Xuân', 'Xã Xuân Giang'],
    'Quận Hà Đông' => ['Phường Dương Nội', 'Phường Mỗ Lao', 'Phường Phú La', 'Phường Văn Quán', 'Phường Yết Kiêu'],
  ],
  'Hồ Chí Minh' => [
    'Quận 1' => ['Phường Bến Nghé', 'Phường Đa Kao', 'Phường Nguyễn Thái Bình', 'Phường Tân Định', 'Phường Cô Giang'],
    'Quận 3' => ['Phường 1', 'Phường 2', 'Phường 5', 'Phường Võ Thị Sáu', 'Phường Phạm Ngũ Lão'],
    'Quận 7' => ['Phường Bình Thuận', 'Phường Phú Mỹ', 'Phường Tân Hưng', 'Phường Tân Kiểng', 'Phường Tân Quy'],
    'Quận 10' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 8', 'Phường 12'],
    'Quận Bình Thạnh' => ['Phường 1', 'Phường 2', 'Phường 5', 'Phường 11', 'Phường 25'],
    'Quận Tân Bình' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 7'],
    'Quận Gò Vấp' => ['Phường 1', 'Phường 3', 'Phường 5', 'Phường 7', 'Phường 12'],
    'Quận Phú Nhuận' => ['Phường 1', 'Phường 2', 'Phường 5', 'Phường 7', 'Phường 15'],
    'Quận Thủ Đức' => ['Phường Bình Thọ', 'Phường Hiệp Bình Phước', 'Phường Linh Chiểu', 'Phường Tam Bình', 'Phường Tăng Nhơn Phú'],
    'Huyện Nhà Bè' => ['Xã Phước Kiển', 'Xã Long Thới', 'Xã Nhơn Đức', 'Xã Phước Lộc', 'Xã Hiệp Phước'],
    'Huyện Cần Giờ' => ['Xã An Thới Đông', 'Xã Long Hòa', 'Xã Lý Nhơn', 'Xã Thạnh An', 'Xã Tam Thôn Hiệp'],
    'Huyện Bình Chánh' => ['Xã An Phú Tây', 'Xã Bình Hưng', 'Xã Phạm Văn Hai', 'Xã Tân Nhựt', 'Xã Vĩnh Lộc A'],
  ],
  'Đà Nẵng' => [
    'Quận Hải Châu' => ['Phường Bình Hiên', 'Phường Hòa Cường Bắc', 'Phường Nam Dương', 'Phường Thanh Bình', 'Phường Thuận Phước'],
    'Quận Cẩm Lệ' => ['Phường Hòa An', 'Phường Hòa Phát', 'Phường Khuê Trung', 'Phường Tân An', 'Phường Thanh Khê'],
    'Quận Liên Chiểu' => ['Phường Hòa Hiệp Bắc', 'Phường Hòa Hiệp Nam', 'Phường Hòa Khánh Bắc', 'Phường Hòa Khánh Nam', 'Phường Hòa Minh'],
    'Quận Ngũ Hành Sơn' => ['Phường Hòa Hải', 'Phường Hòa Quý', 'Phường Khuê Mỹ', 'Phường Mỹ An', 'Phường Phước Mỹ'],
    'Quận Sơn Trà' => ['Phường An Hải Bắc', 'Phường An Hải Tây', 'Phường Mân Thái', 'Phường Nại Hiên Đông', 'Phường Phước Mỹ'],
    'Huyện Hòa Vang' => ['Xã Hòa Bắc', 'Xã Hòa Liên', 'Xã Hòa Ninh', 'Xã Hòa Phú', 'Xã Hòa Vân'],
  ],
  'Hải Phòng' => [
    'Quận Hồng Bàng' => ['Phường Gia Viên', 'Phường Hạ Lý', 'Phường Quán Toan', 'Phường Sở Dầu', 'Phường Thượng Lý'],
    'Quận Lê Chân' => ['Phường An Biên', 'Phường An Dương', 'Phường Dư Hàng Kênh', 'Phường Kênh Dương', 'Phường Trại Cau'],
    'Quận Ngô Quyền' => ['Phường Đằng Giang', 'Phường Lạch Tray', 'Phường Máy Chai', 'Phường Vĩnh Bảo', 'Phường Cầu Đất'],
    'Quận Kiến An' => ['Phường Đằng Lâm', 'Phường Nam Sơn', 'Phường Phúc Tân', 'Phường Tràng Cát', 'Phường Vĩnh Niệm'],
    'Huyện Thủy Nguyên' => ['Xã Bích Hoa', 'Xã Đa Phúc', 'Xã Gia Viên', 'Xã Núi Đèo', 'Xã Tân Tiến'],
    'Huyện An Dương' => ['Xã An Lạc', 'Xã Hồng Phong', 'Xã Lê Thiện', 'Xã Vân Du', 'Xã Xuân Đồng'],
  ],
  'Cần Thơ' => [
    'Quận Ninh Kiều' => ['Phường An Cư', 'Phường An Hòa', 'Phường Cái Khế', 'Phường Tân An', 'Phường Xuân Khánh'],
    'Quận Bình Thủy' => ['Phường Bình Thủy', 'Phường Long Hòa', 'Phường Long Tuyền', 'Phường Thới An Đông', 'Phường Trà Nóc'],
    'Quận Cái Răng' => ['Phường Hưng Phú', 'Phường Lê Bình', 'Phường Phú Thứ', 'Phường Tân Phú', 'Phường Thường Thạnh'],
    'Quận Ô Môn' => ['Phường Châu Văn Liêm', 'Phường Long Hưng', 'Phường Phước Thới', 'Phường Thới Hoà', 'Phường Thới Long'],
    'Huyện Phong Điền' => ['Xã Giai Xuân', 'Xã Mỹ Khánh', 'Xã Phong Điền', 'Xã Trường Long', 'Xã Tân Thới'],
    'Huyện Thốt Nốt' => ['Xã Thốt Nốt', 'Xã Thạnh Hoà', 'Xã Trung Nhứt', 'Xã Trường Long', 'Xã Vĩnh Trinh'],
  ],
  'Đà Lạt' => [
    'Phường 1' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Phường 2' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Huyện Lạc Dương' => ['Xã Đạ Chais', 'Xã Đạ Huoai', 'Xã Lạc Dương', 'Xã Lát', 'Xã Thôn 4'],
  ],
  'Huế' => [
    'Quận Phú Xuân' => ['Phường Hương An', 'Phường Hương Hồ', 'Phường Hương Sơ', 'Phường Thuận Hóa', 'Phường Vĩ Dạ'],
    'Quận Thuận Hóa' => ['Phường An Cựu', 'Phường Dương Nỗ', 'Phường Hương Văn', 'Phường Phú Hậu', 'Phường Tân Mỹ'],
    'Quận Hải Châu' => ['Phường Hòa Khánh', 'Phường Hòa Thuận', 'Phường Thuận An', 'Phường Xuân Đà', 'Phường Xuân Phú'],
    'Huyện Phong Điền' => ['Xã Phong Điền', 'Xã Quảng Thọ', 'Xã Sịa', 'Xã Tứ Hạ', 'Xã Vĩnh Hòa'],
  ],
  'Bà Rịa - Vũng Tàu' => [
    'Thành phố Vũng Tàu' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 5', 'Phường Thắng Nhất'],
    'Thị xã Bà Rịa' => ['Phường Phước Hiệp', 'Phường Phước Hưng', 'Phường Tân Hưng', 'Phường Long Hương', 'Phường Tân Thành'],
    'Huyện Xuyên Mộc' => ['Xã Bưng Riềng', 'Xã Hòa Hiệp', 'Xã Phước Bửu', 'Xã Xuyên Mộc', 'Xã Xuân Hiệp'],
  ],
  'Bắc Giang' => [
    'Thành phố Bắc Giang' => ['Phường Dĩnh Kế', 'Phường Hoàng Văn Thụ', 'Phường Lê Lợi', 'Phường Ngô Quyền', 'Phường Tân Tiến'],
    'Huyện Lạng Giang' => ['Xã Đông Sơn', 'Xã Hương Giang', 'Xã Kép', 'Xã Tân Tiến', 'Xã Xuân Lập'],
    'Huyện Yên Dũng' => ['Xã Hồng Giang', 'Xã Hoàng Văn Thụ', 'Xã Lâm Động', 'Xã Tân Hương', 'Xã Yên Thế'],
  ],
  'Bắc Kạn' => [
    'Thành phố Bắc Kạn' => ['Phường Bắc Kạn', 'Phường Cửa Ông', 'Phường Đà Vị', 'Phường Hữu Sản', 'Phường Ngọc Phái'],
    'Huyện Ba Bể' => ['Xã Chu Hương', 'Xã Nam Mẫu', 'Xã Quang Khải', 'Xã Yên Thượng', 'Xã Yên Trung'],
    'Huyện Chợ Mới' => ['Xã Bằng Hành', 'Xã Bướng', 'Xã Cốc Đán', 'Xã Hòa Mục', 'Xã Yên Cư'],
  ],
  'Bạc Liêu' => [
    'Thành phố Bạc Liêu' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 5', 'Phường 7'],
    'Huyện Hòa Bình' => ['Xã Hòa Bình', 'Xã Hưng Hòa', 'Xã Lương Tâm', 'Xã Phước Long', 'Xã Vĩnh Hậu'],
    'Huyện Vĩnh Lợi' => ['Xã Long Điền', 'Xã Phúc Hòa', 'Xã Tân Hưng', 'Xã Vĩnh Lợi', 'Xã Vĩnh Thạnh'],
  ],
  'Bắc Ninh' => [
    'Thành phố Bắc Ninh' => ['Phường Đại Phúc', 'Phường Hòa Long', 'Phường Kinh Bắc', 'Phường Nguyên Xá', 'Phường Tân Tiến'],
    'Huyện Gia Bình' => ['Xã Gia Bình', 'Xã Lãng Ngâm', 'Xã Ninh Xá', 'Xã Quế Tân', 'Xã Vân Dương'],
    'Huyện Tiên Du' => ['Xã Cổ Nhuế', 'Xã Khắc Niệm', 'Xã Liên Bạc', 'Xã Phù Khê', 'Xã Tân Chi'],
  ],
  'Bến Tre' => [
    'Thành phố Bến Tre' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Huyện Châu Thành' => ['Xã An Hóa', 'Xã Bình Phú', 'Xã Hưng Khánh', 'Xã Long Định', 'Xã Thạnh Phú'],
    'Huyện Mỏ Cày' => ['Xã An Thạnh', 'Xã Hòa Lợi', 'Xã Mỏ Cày', 'Xã Tân Phú', 'Xã Thanh Tân'],
  ],
  'Bình Định' => [
    'Thành phố Quy Nhơn' => ['Phường Lê Hồng Phong', 'Phường Ngô Mây', 'Phường Quang Trung', 'Phường Trần Hưng Đạo', 'Phường Trần Quang Diệu'],
    'Huyện An Lão' => ['Xã An Lão', 'Xã Hòa Nam', 'Xã Hưng Thịnh', 'Xã Vĩnh Lợi', 'Xã Xuân Phương'],
    'Huyện Phù Cát' => ['Xã Cát Hanh', 'Xã Cát Khánh', 'Xã Cát Tài', 'Xã Cát Tiến', 'Xã Cát Lợi'],
  ],
  'Bình Dương' => [
    'Thành phố Thủ Dầu Một' => ['Phường Hiệp An', 'Phường Phú Cường', 'Phường Phú Hòa', 'Phường Tân An', 'Phường Thủ Dầu Một'],
    'Thị xã Dĩ An' => ['Phường An Bình', 'Phường Dĩ An', 'Phường Đông Hòa', 'Phường Tân Bình', 'Phường Tân Đông Hiệp'],
    'Huyện Bến Cát' => ['Xã An Điền', 'Xã Chánh Nghĩa', 'Xã Hòa Lợi', 'Xã Phú Chánh', 'Xã Tân Định'],
  ],
  'Bình Phước' => [
    'Thị xã Đồng Xoài' => ['Phường Tân Bình', 'Phường Tiến Hưng', 'Phường Tân Phú', 'Phường Long Bình', 'Phường Bình Phước'],
    'Huyện Bù Đăng' => ['Xã Bù Đăng', 'Xã Hưng Long', 'Xã Phú Trung', 'Xã Thống Nhất', 'Xã Tân Lợi'],
    'Huyện Lộc Ninh' => ['Xã Lộc Ninh', 'Xã Phước Bình', 'Xã Tân Khai', 'Xã Thống Nhất', 'Xã Trà Lồng'],
  ],
  'Bình Thuận' => [
    'Thành phố Phan Thiết' => ['Phường Bắc Bình', 'Phường Đức Long', 'Phường Hưng Long', 'Phường Phú Tài', 'Phường Trần Hưng Đạo'],
    'Huyện La Gi' => ['Xã Hòa Thắng', 'Xã Lương Sơn', 'Xã Mũi Né', 'Xã Tân Phú', 'Xã Xuân Thọ'],
    'Huyện Tuy Phong' => ['Xã Hòa Thắng', 'Xã Phan Ri', 'Xã Tân Hải', 'Xã Thuận Nam', 'Xã Vĩnh Hảo'],
  ],
  'Cà Mau' => [
    'Thành phố Cà Mau' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 5', 'Phường 7'],
    'Huyện Cái Nước' => ['Xã Hưng Yên', 'Xã Lương Tâm', 'Xã Năm Căn', 'Xã Tân Hưng', 'Xã Trần Hợi'],
    'Huyện Thới Bình' => ['Xã Hòa Tân', 'Xã Long Định', 'Xã Tân Lợi', 'Xã Thới Bình', 'Xã Vĩnh Hòa'],
  ],
  'Cao Bằng' => [
    'Thành phố Cao Bằng' => ['Phường Hợp Giang', 'Phường Húc Nghi', 'Phường Ngọc Xuân', 'Phường Tân Giang', 'Phường Thạch Đà'],
    'Huyện Bảo Lạc' => ['Xã Bảo Lạc', 'Xã Hồng Việt', 'Xã Khánh Xuân', 'Xã Quang Trung', 'Xã Thanh Hòa'],
    'Huyện Trùng Khánh' => ['Xã Đàm Thủy', 'Xã Huy Giả', 'Xã Lý Bôn', 'Xã Tân Cương', 'Xã Vĩnh Tuy'],
  ],
  'Đắk Lắk' => [
    'Thành phố Buôn Ma Thuột' => ['Phường Buôn Ma Thuột', 'Phường Thống Nhất', 'Phường Tân Lợi', 'Phường Tân Thành', 'Phường Thành Nhất'],
    'Huyện Krông Pắc' => ['Xã Ea Đar', 'Xã Ea Hiao', 'Xã Ea Kar', 'Xã Phú Túc', 'Xã Tân Hòa'],
    'Huyện Ea Súp' => ['Xã Cư Mgar', 'Xã Dray Sáp', 'Xã Ea Bông', 'Xã Hòa An', 'Xã Ya Quân'],
  ],
  'Đắk Nông' => [
    'Thành phố Gia Nghĩa' => ['Phường Nghĩa Thành', 'Phường Nghĩa Tân', 'Phường Tân Hòa', 'Phường Trường Xuân', 'Phường Tân Lập'],
    'Huyện Cư Jút' => ['Xã Cư Jút', 'Xã Đắk R’Lấp', 'Xã Ea Tling', 'Xã Tân Lợi', 'Xã Xuân Thịnh'],
    'Huyện Krông Nô' => ['Xã Đắk Nia', 'Xã Đắk Sắk', 'Xã Nâm Nung', 'Xã Quảng Hòa', 'Xã Tân Hòa'],
  ],
  'Điện Biên' => [
    'Thành phố Điện Biên Phủ' => ['Phường Mường Nhà', 'Phường Nam Thanh', 'Phường Pa Tần', 'Phường Tân Thanh', 'Phường Tân Xuân'],
    'Huyện Mường Ảng' => ['Xã Mường Ảng', 'Xã Nặm Ban', 'Xã Pa Thơm', 'Xã Tân Hồng', 'Xã Tủa Chùa'],
    'Huyện Tủa Chùa' => ['Xã Chà Cang', 'Xã Núa Ngam', 'Xã Sính Lủng', 'Xã Tả Sìn', 'Xã Xín Mần'],
  ],
  'Đồng Nai' => [
    'Thành phố Biên Hòa' => ['Phường An Bình', 'Phường Long Bình', 'Phường Long Hưng', 'Phường Quang Vinh', 'Phường Tân Phong'],
    'Thị xã Long Khánh' => ['Phường Bảo Vinh', 'Phường Bình Trưng', 'Phường Cầu Đất', 'Phường Lê Hồng Phong', 'Phường Xuân An'],
    'Huyện Nhơn Trạch' => ['Xã Bửu Long', 'Xã Long Tân', 'Xã Phú Hòa', 'Xã Thạnh Phú', 'Xã Vĩnh Thanh'],
  ],
  'Đồng Tháp' => [
    'Thành phố Cao Lãnh' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Thị xã Sa Đéc' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Huyện Lai Vung' => ['Xã Hòa Long', 'Xã Long Khánh', 'Xã Lương Hòa', 'Xã Mỹ Hòa', 'Xã Tân Huyền'],
  ],
  'Gia Lai' => [
    'Thành phố Pleiku' => ['Phường Biển Hồ', 'Phường Hòa Bình', 'Phường Thống Nhất', 'Phường Yên Đỗ', 'Phường Trần Hưng Đạo'],
    'Huyện Chư Păh' => ['Xã Chư Păh', 'Xã Ia Mơ', 'Xã Ia Quý', 'Xã Pờ Tó', 'Xã Tân Thành'],
    'Huyện Ia Grai' => ['Xã Chư Sê', 'Xã Ia Grai', 'Xã Ia Krêl', 'Xã Ia Tô', 'Xã Tân Hòa'],
  ],
  'Hà Giang' => [
    'Thành phố Hà Giang' => ['Phường Nguyễn Trãi', 'Phường Quang Trung', 'Phường Thạch An', 'Phường Trần Phú', 'Phường Yên Hoà'],
    'Huyện Đồng Văn' => ['Xã Lũng Cú', 'Xã Mèo Vạc', 'Xã Phó Bảng', 'Xã Sủng Là', 'Xã Tòng Đậu'],
    'Huyện Xín Mần' => ['Xã Cán Tỷ', 'Xã Nà Chì', 'Xã Quản Bạ', 'Xã Vĩnh Quỳnh', 'Xã Xín Mần'],
  ],
  'Hà Nam' => [
    'Thành phố Phủ Lý' => ['Phường Hồng Châu', 'Phường Lê Hồng Phong', 'Phường Quang Trung', 'Phường Trần Hưng Đạo', 'Phường Viên An'],
    'Huyện Bình Lục' => ['Xã Bình Lục', 'Xã Đình Bảng', 'Xã Hòa Hậu', 'Xã Kim Bảng', 'Xã Tiên Tân'],
    'Huyện Duy Tiên' => ['Xã Duy Tiên', 'Xã Hồng Châu', 'Xã Minh Tân', 'Xã Quảng Phú', 'Xã Yên Sơn'],
  ],
  'Hà Tĩnh' => [
    'Thành phố Hà Tĩnh' => ['Phường Hà Tĩnh', 'Phường Hồng Lĩnh', 'Phường Nam Hồng', 'Phường Nghi Xuân', 'Phường Trần Phú'],
    'Huyện Hương Sơn' => ['Xã Hương Sơn', 'Xã Kỳ Anh', 'Xã Lộc Hà', 'Xã Tân Hồng', 'Xã Vĩnh Hòa'],
    'Huyện Nghi Xuân' => ['Xã Cẩm Xuyên', 'Xã Kỳ Lợi', 'Xã Nghi Xuân', 'Xã Phú Cường', 'Xã Xuân Hòa'],
  ],
  'Hải Dương' => [
    'Thành phố Hải Dương' => ['Phường Bình Hàn', 'Phường Chí Linh', 'Phường Lê Thanh Nghị', 'Phường Ngọc Châu', 'Phường Tân Bình'],
    'Huyện Chí Linh' => ['Xã An Lạc', 'Xã Cổ Bi', 'Xã Đồng Tâm', 'Xã Lê Hồng', 'Xã Mạnh Cường'],
    'Huyện Nam Sách' => ['Xã Bình Hòa', 'Xã Cẩm Chế', 'Xã Hợp Tiến', 'Xã Minh Đức', 'Xã Thái Hòa'],
  ],
  'Hậu Giang' => [
    'Thành phố Vị Thanh' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Huyện Long Mỹ' => ['Xã Long Mỹ', 'Xã Phú Tân', 'Xã Tân Hòa', 'Xã Viễn Kiến', 'Xã Vĩnh Tường'],
    'Huyện Châu Thành' => ['Xã Châu Thành', 'Xã Long Hòa', 'Xã Tân Hưng', 'Xã Thạnh Xuân', 'Xã Vĩnh Hậu'],
  ],
  'Hòa Bình' => [
    'Thành phố Hòa Bình' => ['Phường Hòa Bình', 'Phường Mường Bi', 'Phường Pù Nhi', 'Phường Tân Hòa', 'Phường Yên Mạc'],
    'Huyện Lạc Sơn' => ['Xã Lạc Sơn', 'Xã Mộc Châu', 'Xã Tân Minh', 'Xã Thái Bình', 'Xã Yên Mạc'],
    'Huyện Kỳ Sơn' => ['Xã Kỳ Sơn', 'Xã Mường Khến', 'Xã Tân Lập', 'Xã Thắng', 'Xã Vụ Bản'],
  ],
  'Hưng Yên' => [
    'Thành phố Hưng Yên' => ['Phường Hưng Yên', 'Phường Lê Lợi', 'Phường Mỹ Hào', 'Phường Quang Trung', 'Phường Tân Hưng'],
    'Huyện Mỹ Hào' => ['Xã Bình Dân', 'Xã Hòa Bình', 'Xã Liên Hòa', 'Xã Minh Khai', 'Xã Tân Phong'],
    'Huyện Văn Giang' => ['Xã Cầu Cụt', 'Xã Hồng Tiến', 'Xã Lê Lợi', 'Xã Thắng Lợi', 'Xã Vân Giang'],
  ],
  'Khánh Hòa' => [
    'Thành phố Nha Trang' => ['Phường Bảo An', 'Phường Lộc Thọ', 'Phường Phương Sài', 'Phường Vĩnh Hòa', 'Phường Xương Huân'],
    'Thành phố Cam Ranh' => ['Phường Cam Linh', 'Phường Cam Lợi', 'Phường Hòa Hiệp', 'Phường Vĩnh Nguyên', 'Phường Xuân Sơn'],
    'Huyện Vạn Ninh' => ['Xã Bảo Ninh', 'Xã Dương Tơ', 'Xã Ninh Hòa', 'Xã Ninh Vân', 'Xã Vạn Thắng'],
  ],
  'Kiên Giang' => [
    'Thành phố Rạch Giá' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 5', 'Phường Vĩnh Lạc'],
    'Thị xã Hà Tiên' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Huyện Giồng Riềng' => ['Xã An Bình', 'Xã Dương Đông', 'Xã Hòa An', 'Xã Mình Hà', 'Xã Vĩnh Hòa'],
  ],
  'Kon Tum' => [
    'Thành phố Kon Tum' => ['Phường Chư Don', 'Phường Duy Tân', 'Phường Quyết Thắng', 'Phường Thống Nhất', 'Phường Trần Hưng Đạo'],
    'Huyện Đăk Glei' => ['Xã Đăk Glei', 'Xã Đăk Rơ Wa', 'Xã Hòa Bắc', 'Xã Măng Cành', 'Xã Tân Lập'],
    'Huyện Sa Thầy' => ['Xã Đăk Tô', 'Xã Phú Hòa', 'Xã Sa Thầy', 'Xã Tân Hộ', 'Xã Tân Lập'],
  ],
  'Lai Châu' => [
    'Thành phố Lai Châu' => ['Phường Tân Phong', 'Phường Yên Phú', 'Phường Đoàn Kết', 'Phường Thống Nhất', 'Phường Tân Hòa'],
    'Huyện Mường Tè' => ['Xã Mường Tè', 'Xã Phong Thổ', 'Xã Tà Mung', 'Xã Tân Thành', 'Xã Vân Hồ'],
    'Huyện Sìn Hồ' => ['Xã Nậm Mười', 'Xã Sìn Hồ', 'Xã Tả Phìn', 'Xã Tủa Chùa', 'Xã Xăm Măng'],
  ],
  'Lâm Đồng' => [
    'Thành phố Đà Lạt' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 5', 'Phường 7'],
    'Thành phố Bảo Lộc' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Huyện Đam Rông' => ['Xã Đam Rông', 'Xã Hòa Thắng', 'Xã Lạc Dương', 'Xã Tân Lạc', 'Xã Xuân Thọ'],
  ],
  'Lạng Sơn' => [
    'Thành phố Lạng Sơn' => ['Phường Chi Lăng', 'Phường Hoàng Văn Thụ', 'Phường Mạo Khê', 'Phường Quan Lạn', 'Phường Vân Nham'],
    'Huyện Bắc Sơn' => ['Xã Quỳnh Sơn', 'Xã Tân Tri', 'Xã Vĩnh Lợi', 'Xã Xuân Hòa', 'Xã Yên Trạch'],
    'Huyện Hữu Lũng' => ['Xã Bình Gia', 'Xã Đồng Đăng', 'Xã Hữu Lũng', 'Xã Lộc Bình', 'Xã Trấn Yên'],
  ],
  'Lào Cai' => [
    'Thành phố Lào Cai' => ['Phường Bắc Cường', 'Phường Cốc Lếu', 'Phường Kim Tân', 'Phường Nam Cường', 'Phường Vĩnh Hòa'],
    'Huyện Bát Xát' => ['Xã Bát Xát', 'Xã Bản Cái', 'Xã Mường Hum', 'Xã Tả Lèng', 'Xã Xín Mần'],
    'Huyện Sa Pa' => ['Xã Bản Hồ', 'Xã Lao Chải', 'Xã Sa Pa', 'Xã Tả Van', 'Xã Vinh Phúc'],
  ],
  'Long An' => [
    'Thành phố Tân An' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 5', 'Phường 7'],
    'Huyện Cần Đước' => ['Xã Châu Hòa', 'Xã Long Hậu', 'Xã Tân Chánh', 'Xã Tân Đông', 'Xã Tân Tập'],
    'Huyện Tân Thạnh' => ['Xã Hòa Bình', 'Xã Long Định', 'Xã Phú Vang', 'Xã Tân Chánh', 'Xã Tân Hưng'],
  ],
  'Nam Định' => [
    'Thành phố Nam Định' => ['Phường Bạch Đằng', 'Phường Lê Hồng Phong', 'Phường Nghĩa Lộ', 'Phường Trần Hưng Đạo', 'Phường Vị Xuyên'],
    'Huyện Hải Hậu' => ['Xã Hải Hậu', 'Xã Hưng Yên', 'Xã Nam Châu', 'Xã Quang Lợi', 'Xã Thịnh Long'],
    'Huyện Mỹ Lộc' => ['Xã Hòa Tiến', 'Xã Mỹ Thuận', 'Xã Ngọc Lặc', 'Xã Xuân Tiến', 'Xã Yên Lộc'],
  ],
  'Nghệ An' => [
    'Thành phố Vinh' => ['Phường Hưng Bình', 'Phường Lê Mao', 'Phường Quán Bàu', 'Phường Trung Đô', 'Phường Vinh Tân'],
    'Huyện Cửa Lò' => ['Xã Cửa Lò', 'Xã Nghi Hương', 'Xã Tân Hồng', 'Xã Thanh Chương', 'Xã Xuân Hòa'],
    'Huyện Diễn Châu' => ['Xã Diễn Châu', 'Xã Hưng Nguyên', 'Xã Lý Sơn', 'Xã Tân Lập', 'Xã Vinh Hùng'],
  ],
  'Ninh Bình' => [
    'Thành phố Ninh Bình' => ['Phường Nam Bình', 'Phường Phúc Tân', 'Phường Tân Bình', 'Phường Vân Giang', 'Phường Yên Ninh'],
    'Huyện Gia Viễn' => ['Xã Gia Hưng', 'Xã Gia Tân', 'Xã Kim Sơn', 'Xã Ninh Vân', 'Xã Yên Hòa'],
    'Huyện Tam Điệp' => ['Xã Đồng Phong', 'Xã Hòa Bình', 'Xã Hưng Long', 'Xã Ninh Hải', 'Xã Yên Đồng'],
  ],
  'Ninh Thuận' => [
    'Thành phố Phan Rang - Tháp Chàm' => ['Phường Đài Sơn', 'Phường Hộ Phòng', 'Phường Kinh Dương', 'Phường Tân Bình', 'Phường Thanh Sơn'],
    'Huyện Bác Ái' => ['Xã Hòa Tiến', 'Xã Ninh Hải', 'Xã Phước Dân', 'Xã Tân Hồng', 'Xã Vĩnh Hảo'],
    'Huyện Ninh Hải' => ['Xã Hòa Thành', 'Xã Long Hải', 'Xã Tân Bình', 'Xã Thái Bình', 'Xã Xuân Hải'],
  ],
  'Phú Thọ' => [
    'Thành phố Việt Trì' => ['Phường Bến Gót', 'Phường Dân Chủ', 'Phường Đuống', 'Phường Hùng Vương', 'Phường Tân Dân'],
    'Huyện Đoan Hùng' => ['Xã Đoan Hùng', 'Xã Hồng Thuận', 'Xã Phú Thọ', 'Xã Tân Sơn', 'Xã Yên Lập'],
    'Huyện Lâm Thao' => ['Xã Hợp Hòa', 'Xã Lâm Thao', 'Xã Mỹ Hòa', 'Xã Tân Hợp', 'Xã Xuân Lập'],
  ],
  'Phú Yên' => [
    'Thành phố Tuy Hòa' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Huyện Đồng Xuân' => ['Xã Hòa Hải', 'Xã Hòa Hiệp', 'Xã Phú Yên', 'Xã Tân Hòa', 'Xã Vĩnh Hòa'],
    'Huyện Sông Hinh' => ['Xã Cà Ná', 'Xã Hòa Phú', 'Xã Sông Hinh', 'Xã Tân Lập', 'Xã Xuân Hòa'],
  ],
  'Quảng Bình' => [
    'Thành phố Đồng Hới' => ['Phường Bắc Lý', 'Phường Đồng Hới', 'Phường Hải Đình', 'Phường Quang Trung', 'Phường Tân Hưng'],
    'Huyện Bố Trạch' => ['Xã Bố Trạch', 'Xã Cự Nẫm', 'Xã Lý Trạch', 'Xã Thượng Hóa', 'Xã Vĩnh Ninh'],
    'Huyện Quảng Ninh' => ['Xã Cảnh Dương', 'Xã Lương Ninh', 'Xã Vĩnh Ninh', 'Xã Xuân Hòa', 'Xã Yên Ninh'],
  ],
  'Quảng Nam' => [
    'Thành phố Tam Kỳ' => ['Phường An Mỹ', 'Phường Hòa Hương', 'Phường Phú Ninh', 'Phường Tân An', 'Phường Trường Xuân'],
    'Thành phố Hội An' => ['Phường Cẩm Châu', 'Phường Minh An', 'Phường Tân An', 'Phường Thanh Hà', 'Phường Tân Hiệp'],
    'Huyện Núi Thành' => ['Xã Bình Minh', 'Xã Duy Nghĩa', 'Xã Tân An', 'Xã Tam Xuân', 'Xã Thăng Bình'],
  ],
  'Quảng Ngãi' => [
    'Thành phố Quảng Ngãi' => ['Phường Chánh Nghĩa', 'Phường Lê Hồng Phong', 'Phường Ngọc Hồi', 'Phường Tân Bình', 'Phường Trần Hưng Đạo'],
    'Huyện Bình Sơn' => ['Xã Bình Sơn', 'Xã Nghĩa Phú', 'Xã Tân Thành', 'Xã Thạnh Mỹ', 'Xã Xuân Phước'],
    'Huyện Sơn Tịnh' => ['Xã Hương An', 'Xã Phù Mỹ', 'Xã Sơn Tịnh', 'Xã Tân An', 'Xã Tây Trà'],
  ],
  'Quảng Ninh' => [
    'Thành phố Hạ Long' => ['Phường Bãi Cháy', 'Phường Hà Khánh', 'Phường Hà Phong', 'Phường Hồng Gai', 'Phường Mông Dương'],
    'Thành phố Cẩm Phả' => ['Phường Cẩm Phả', 'Phường Hưng Đạo', 'Phường Mông Dương', 'Phường Phong Cốc', 'Phường Trần Phú'],
    'Huyện Ba Chẽ' => ['Xã Bãi Cháy', 'Xã Bình Liêu', 'Xã Cửa Ông', 'Xã Hạ Long', 'Xã Quảng Hà'],
  ],
  'Quảng Trị' => [
    'Thành phố Đông Hà' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Huyện Gio Linh' => ['Xã Gio Linh', 'Xã Hải Lăng', 'Xã Hướng Hóa', 'Xã Mai Lão', 'Xã Vĩnh Hoà'],
    'Huyện Triệu Phong' => ['Xã Cồn Cỏ', 'Xã Hải Chánh', 'Xã Hòa Châu', 'Xã Triệu Phong', 'Xã Vĩnh Lộc'],
  ],
  'Sóc Trăng' => [
    'Thành phố Sóc Trăng' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Huyện Châu Thành' => ['Xã Châu Thành', 'Xã Hòa Tân', 'Xã Long Phú', 'Xã Vĩnh Mỹ', 'Xã Vĩnh Trạch'],
    'Huyện Long Phú' => ['Xã Long Phú', 'Xã Phú Tân', 'Xã Vĩnh Hòa', 'Xã Vĩnh Thuận', 'Xã Xuân Khánh'],
  ],
  'Sơn La' => [
    'Thành phố Sơn La' => ['Phường Chiềng An', 'Phường Huyền Trang', 'Phường Tân Hòa', 'Phường Tân Lập', 'Phường Thuận Châu'],
    'Huyện Mộc Châu' => ['Xã Mộc Châu', 'Xã Nà Ka', 'Xã Tân Lập', 'Xã Thái Hòa', 'Xã Xuân Nha'],
    'Huyện Yên Châu' => ['Xã Bó Y', 'Xã Nậm Mằn', 'Xã Pắc Mỳ', 'Xã Tà Làng', 'Xã Yên Châu'],
  ],
  'Tây Ninh' => [
    'Thành phố Tây Ninh' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Huyện Bến Cầu' => ['Xã Bến Cầu', 'Xã Hòa Hiệp', 'Xã Long Hòa', 'Xã Tân Châu', 'Xã Tân Hòa'],
    'Huyện Trảng Bàng' => ['Xã Hòa Nghĩa', 'Xã Long Hưng', 'Xã Tân Chánh', 'Xã Tân Hòa', 'Xã Vĩnh Tường'],
  ],
  'Thái Bình' => [
    'Thành phố Thái Bình' => ['Phường Bạch Đằng', 'Phường Hoàng Diệu', 'Phường Nam Định', 'Phường Quang Trung', 'Phường Thái Bình'],
    'Huyện Đông Hưng' => ['Xã Hồng Lĩnh', 'Xã Tiền Phong', 'Xã Vũ Phúc', 'Xã Xuân Hòa', 'Xã Yên Hòa'],
    'Huyện Thái Thuỵ' => ['Xã Hưng Hà', 'Xã Kiến Xương', 'Xã Phú Xuyên', 'Xã Tân Hưng', 'Xã Thái Thuỵ'],
  ],
  'Thái Nguyên' => [
    'Thành phố Thái Nguyên' => ['Phường Đồng Quang', 'Phường Lương Ngọc Quyến', 'Phường Phan Đăng Lưu', 'Phường Tân Thịnh', 'Phường Quang Vinh'],
    'Huyện Đại Từ' => ['Xã Định Hóa', 'Xã Hương Sơn', 'Xã Quang Vinh', 'Xã Tân Cương', 'Xã Yên Sơn'],
    'Huyện Sông Công' => ['Xã Hòa Bình', 'Xã Minh Đức', 'Xã Phú Lương', 'Xã Tân Cương', 'Xã Xuân Lương'],
  ],
  'Thanh Hóa' => [
    'Thành phố Thanh Hóa' => ['Phường Đông Hòa', 'Phường Hàm Rồng', 'Phường Lam Sơn', 'Phường Ngọc Trạo', 'Phường Trường Thi'],
    'Huyện Bỉm Sơn' => ['Xã Bỉm Sơn', 'Xã Hậu Lộc', 'Xã Lê Hữu', 'Xã Quảng Xương', 'Xã Yên Định'],
    'Huyện Sầm Sơn' => ['Xã Bưng', 'Xã Hưng Lộc', 'Xã Quảng Tiến', 'Xã Tân Hưng', 'Xã Xuân Hội'],
  ],
  'Thừa Thiên Huế' => [
    'Thành phố Huế' => ['Phường An Cựu', 'Phường Hương An', 'Phường Thuận Hóa', 'Phường Trường An', 'Phường Xuân Phú'],
    'Huyện A Lưới' => ['Xã A Lưới', 'Xã Hồng Vân', 'Xã Lệ Ninh', 'Xã Quảng Cư', 'Xã Tân Hợp'],
    'Huyện Phong Điền' => ['Xã Phong Điền', 'Xã Phú Vang', 'Xã Quảng Châu', 'Xã Tân Châu', 'Xã Vinh Thanh'],
  ],
  'Tiền Giang' => [
    'Thành phố Mỹ Tho' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Huyện Cái Bè' => ['Xã An Hòa', 'Xã Châu Thành', 'Xã Long An', 'Xã Tân Phú', 'Xã Vĩnh Kim'],
    'Huyện Châu Thành' => ['Xã Châu Thành', 'Xã Long An', 'Xã Tân Hòa', 'Xã Tân Phước', 'Xã Vĩnh Hòa'],
  ],
  'Trà Vinh' => [
    'Thành phố Trà Vinh' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Huyện Duyên Hải' => ['Xã Duyên Hải', 'Xã Long Khánh', 'Xã Tân Hải', 'Xã Thạnh Hòa', 'Xã Vĩnh Hảo'],
    'Huyện Cầu Ngang' => ['Xã Cầu Ngang', 'Xã Long Hòa', 'Xã Tân Hòa', 'Xã Vĩnh Tường', 'Xã Xuyên Mộc'],
  ],
  'Tuyên Quang' => [
    'Thành phố Tuyên Quang' => ['Phường Hàm Yên', 'Phường Hồng Quang', 'Phường Minh Xuân', 'Phường Tân An', 'Phường Tân Lập'],
    'Huyện Chiêm Hóa' => ['Xã Chiêm Hóa', 'Xã Hồng Việt', 'Xã Nà Hang', 'Xã Tân Trung', 'Xã Yên Lập'],
    'Huyện Na Hang' => ['Xã Bản Chặt', 'Xã Bản Thước', 'Xã Lâm Bình', 'Xã Tân Lập', 'Xã Xuân Giao'],
  ],
  'Vĩnh Long' => [
    'Thành phố Vĩnh Long' => ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5'],
    'Huyện Long Hồ' => ['Xã Long Hồ', 'Xã Long Mỹ', 'Xã Tân Hòa', 'Xã Vĩnh Tường', 'Xã Xuân Hòa'],
    'Huyện Mang Thít' => ['Xã Long Hòa', 'Xã Mang Thít', 'Xã Tân Long', 'Xã Tân Tiến', 'Xã Vĩnh Hòa'],
  ],
  'Vĩnh Phúc' => [
    'Thành phố Vĩnh Yên' => ['Phường Lê Lợi', 'Phường Nguyễn Tất Thành', 'Phường Tân Dân', 'Phường Tân Hòa', 'Phường Trưng Trắc'],
    'Huyện Bình Xuyên' => ['Xã Bình Xuyên', 'Xã Hòa Phú', 'Xã Phú Hộ', 'Xã Vĩnh Tường', 'Xã Yên Lạc'],
    'Huyện Lập Thạch' => ['Xã Hòa Bình', 'Xã Lập Thạch', 'Xã Phú Thịnh', 'Xã Tam Đảo', 'Xã Vĩnh Ninh'],
  ],
  'Yên Bái' => [
    'Thành phố Yên Bái' => ['Phường Nguyễn Phúc', 'Phường Phan Bội Châu', 'Phường Tân An', 'Phường Trung Tâm', 'Phường Yên Thịnh'],
    'Huyện Lục Yên' => ['Xã Lục Yên', 'Xã Mường Lò', 'Xã Tân Lạc', 'Xã Yên Sơn', 'Xã Yên Thường'],
    'Huyện Văn Yên' => ['Xã Mường Pha', 'Xã Nậm Mười', 'Xã Tân Lập', 'Xã Yên Hòa', 'Xã Yên Phong'],
  ],
];
$defaultProvince = array_key_first($addressOptions);
$defaultDistrict = array_key_first($addressOptions[$defaultProvince] ?? []);
$defaultWard = $addressOptions[$defaultProvince][$defaultDistrict][0] ?? '';
$f = ['ten' => $kh['TenKH'] ?? (me()['HoTen'] ?? ''), 'sdt' => $kh['DienThoai'] ?? '', 'email' => $kh['Email'] ?? '', 'tinh' => $defaultProvince, 'huyen' => $defaultDistrict, 'xa' => $defaultWard, 'dc_detail' => $kh['DiaChi'] ?? '', 'ghichu' => '', 'pt' => 'COD'];
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['ten', 'sdt', 'email', 'tinh', 'huyen', 'xa', 'dc_detail', 'ghichu', 'pt'] as $k) $f[$k] = post($k, $f[$k] ?? '');
  $f['dc'] = trim(implode(', ', array_filter([$f['dc_detail'], $f['xa'], $f['huyen'], $f['tinh']], fn($v) => $v !== '' && $v !== null)));
  $action = post('action', 'place');
  if ($action === 'apply_voucher') {
    $voucherCode = strtoupper(post('voucher'));
    if ($voucherCode === '') { unset($_SESSION['voucher_code']); $voucher = null; $voucherDiscount = 0; $voucherError = ''; }
    else {
      [$voucher, $voucherDiscount, $voucherError] = findVoucher($voucherCode, $sub, $customerId);
      if ($voucherError) { unset($_SESSION['voucher_code']); $voucherCode = ''; }
      else $_SESSION['voucher_code'] = $voucherCode;
    }
  } elseif ($action === 'remove_voucher') {
    unset($_SESSION['voucher_code']);
    $voucherCode = ''; $voucher = null; $voucherDiscount = 0; $voucherError = '';
  } elseif ($action === 'place') {
    $voucherCode = strtoupper(post('voucher'));
    if ($voucherCode === '') {
      unset($_SESSION['voucher_code']);
      $voucher = null; $voucherDiscount = 0; $voucherError = '';
    } else {
      [$voucher, $voucherDiscount, $voucherError] = findVoucher($voucherCode, $sub, $customerId);
      if ($voucherError) unset($_SESSION['voucher_code']);
      else $_SESSION['voucher_code'] = $voucherCode;
    }
  }
  if ($action === 'place' && $f['ten'] === '') $err = 'Vui lòng nhập họ tên người nhận.';
  elseif ($action === 'place' && $f['tinh'] === '') $err = 'Vui lòng chọn tỉnh/thành phố.';
  elseif ($action === 'place' && $f['huyen'] === '') $err = 'Vui lòng chọn quận/huyện.';
  elseif ($action === 'place' && $f['xa'] === '') $err = 'Vui lòng chọn phường/xã.';
  elseif ($action === 'place' && $f['dc_detail'] === '') $err = 'Vui lòng nhập địa chỉ chi tiết.';
  elseif ($action === 'place' && !preg_match('/^[0-9 .+-]{8,15}$/', $f['sdt'])) $err = 'Số điện thoại không hợp lệ.';
  elseif ($action === 'place' && $f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $err = 'Email không hợp lệ.';
  elseif ($action === 'place' && !in_array($f['pt'], ['COD', 'Chuyển khoản'])) $err = 'Phương thức thanh toán không hợp lệ.';
  if (!$err && !$voucherError && $action === 'place') {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            if ($kh) $maKH = $kh['MaKH'];
          elseif (isCustomerAccount()) {
                $maKH = nextCode('KH', 'KhachHang', 'MaKH');
                run('INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (?,?,?,?,?,?)', [$maKH, $f['ten'], $f['dc'], $f['sdt'], '', $f['email']]);
            run('UPDATE Users SET MaKH = ? WHERE UserName = ?', [$maKH, me()['UserName']]);
          } else $maKH = null;
          $orderVoucher = null; $orderDiscount = 0;
          if (!empty($_SESSION['voucher_code'])) {
            [$orderVoucher, $orderDiscount, $voucherError] = findVoucher($_SESSION['voucher_code'], $sub, $maKH);
            if ($voucherError) throw new RuntimeException($voucherError);
          }
            $maHD = nextCode('HD', 'HoaDon', 'MaHD', 3);
      run('INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen, MaVoucher, GiamVoucher) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [$maHD, $maKH, null, nowStr(), null, 'Mới', $f['ten'], $f['dc'], $f['sdt'], $f['ghichu'], $f['pt'], $fee, $orderVoucher['MaVoucher'] ?? null, $orderDiscount]);
      if ($orderVoucher) {
        $used = run('UPDATE Voucher SET DaSuDung = DaSuDung + 1 WHERE MaVoucher = ? AND KichHoat = 1 AND (SoLuong IS NULL OR DaSuDung < SoLuong)', [$orderVoucher['MaVoucher']]);
        if ($used < 1) throw new RuntimeException('Voucher vừa hết lượt sử dụng, vui lòng thử mã khác.');
      }
            foreach ($items as $it) {
                $sp = $it['sp'];
                $ok = run('UPDATE SanPham SET SoLuongTon = SoLuongTon - ? WHERE MaSP = ? AND SoLuongTon >= ?', [$it['qty'], $sp['MaSP'], $it['qty']]);
                if ($ok < 1) throw new RuntimeException('“' . $sp['TenSP'] . '” không còn đủ số lượng trong kho.');
                $monthlyCost = one('SELECT GiaVon FROM GiaVonThang WHERE MaSP = ? AND Thang = ?', [$sp['MaSP'], date('Y-m')]);
                $unitCost = $monthlyCost ? (float)$monthlyCost['GiaVon'] : ((float)$sp['GiaVon'] > 0 ? (float)$sp['GiaVon'] : null);
                run('INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia, GiaVon) VALUES (?,?,?,?,?,?,?,?)',
                    [$maHD, $sp['MaSP'], $it['size'], $it['mau'], $it['qty'], (int)$sp['PhanTramGiam'] / 100, $sp['DonGia'], $unitCost]);
            }
            $pdo->commit();
            if (isCustomerAccount() && empty(me()['MaKH']) && $maKH !== null) $_SESSION['user']['MaKH'] = $maKH;
            $_SESSION['cart'] = [];
            $_SESSION['my_orders'][] = $maHD;
            if ($f['pt'] === 'Chuyển khoản') redirect('order_payment.php?id=' . urlencode($maHD));
            redirect('order_success.php?id=' . $maHD);
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = $e->getMessage();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = 'Không thể tạo đơn hàng, vui lòng thử lại. (' . $e->getMessage() . ')';
        }
    }
}
$title = 'Đặt hàng';
include __DIR__ . '/includes/header.php';
?>
<h1>Đặt hàng</h1>
<?php if ($err): ?><div class="flash err"><?= h($err) ?></div><?php endif; ?>
<?php if (!me()): ?><p class="muted">Bạn có thể <a href="<?= url('login.php') ?>">đăng nhập</a> để lưu lịch sử đơn hàng, hoặc đặt hàng không cần tài khoản.</p><?php endif; ?>
<div class="cart-layout">
  <form method="post" class="form-card">
    <?= csrf() ?>
    <h2>Thông tin nhận hàng</h2>
    <?php if ($voucherError): ?><div class="flash err"><?= h($voucherError) ?></div><?php endif; ?>
    <label>Họ và tên<input name="ten" value="<?= h($f['ten']) ?>" required></label>
    <div class="two"><label>Số điện thoại<input name="sdt" value="<?= h($f['sdt']) ?>" required></label>
    <label>Email (không bắt buộc)<input type="email" name="email" value="<?= h($f['email']) ?>"></label></div>
    <div class="three">
      <label>Tỉnh / Thành phố<select name="tinh" id="address-province" required>
        <option value="">-- Chọn --</option>
        <?php foreach (array_keys($addressOptions) as $province): ?><option value="<?= h($province) ?>" <?= $province === ($f['tinh'] ?? '') ? 'selected' : '' ?>><?= h($province) ?></option><?php endforeach; ?>
      </select></label>
      <label>Quận / Huyện<select name="huyen" id="address-district" required>
        <option value="">-- Chọn --</option>
        <?php foreach (array_keys($addressOptions[$f['tinh']] ?? []) as $district): ?><option value="<?= h($district) ?>" <?= $district === ($f['huyen'] ?? '') ? 'selected' : '' ?>><?= h($district) ?></option><?php endforeach; ?>
      </select></label>
      <label>Phường / Xã<select name="xa" id="address-ward" required>
        <option value="">-- Chọn --</option>
        <?php foreach ($addressOptions[$f['tinh']][$f['huyen']] ?? [] as $ward): ?><option value="<?= h($ward) ?>" <?= $ward === ($f['xa'] ?? '') ? 'selected' : '' ?>><?= h($ward) ?></option><?php endforeach; ?>
      </select></label>
    </div>
    <label>Địa chỉ chi tiết<input name="dc_detail" value="<?= h($f['dc_detail']) ?>" placeholder="Số nhà, tên đường, nơi ở cụ thể..." required></label>
    <label>Ghi chú cho shop<textarea name="ghichu" rows="3"><?= h($f['ghichu']) ?></textarea></label>
    <fieldset class="opts"><legend>Thanh toán</legend>
      <label class="opt"><input id="payment-cod" type="radio" name="pt" value="COD" <?= $f['pt'] === 'COD' ? 'checked' : '' ?>><span>Trả tiền khi nhận hàng</span></label>
      <label class="opt"><input id="payment-transfer" type="radio" name="pt" value="Chuyển khoản" <?= $f['pt'] === 'Chuyển khoản' ? 'checked' : '' ?>><span>Chuyển khoản</span></label>
    </fieldset>
    <label>Mã voucher<div class="two"><input name="voucher" value="<?= h($voucherCode) ?>" placeholder="Nhập mã voucher"><button class="btn ghost" name="action" value="apply_voucher" formnovalidate>Áp dụng</button></div></label>
    <?php if ($voucher): ?><p class="muted">Đã áp dụng <?= h($voucher['MaVoucher']) ?>, giảm <?= money($voucherDiscount) ?>. <button class="link" name="action" value="remove_voucher" formnovalidate>Bỏ mã</button></p><?php endif; ?>
    <button class="btn" id="place-order-button" name="action" value="place"><?= $f['pt'] === 'Chuyển khoản' ? 'Thanh toán chuyển khoản' : 'Đặt hàng' ?> · <?= money($sub - $voucherDiscount + $fee) ?></button>
  </form>
  <aside class="summary">
    <h2>Đơn hàng (<?= cartCount() ?> sản phẩm)</h2>
    <?php foreach ($items as $it): ?>
      <div class="mini"><span><?= h($it['sp']['TenSP']) ?> <small class="muted"><?= h(trim($it['size'] . ' ' . $it['mau'])) ?> × <?= (int)$it['qty'] ?></small></span><b><?= money($it['line']) ?></b></div>
    <?php endforeach; ?>
    <dl><dt>Tạm tính</dt><dd><?= money($sub) ?></dd><?php if ($voucherDiscount): ?><dt>Voucher</dt><dd>−<?= money($voucherDiscount) ?></dd><?php endif; ?><dt>Vận chuyển</dt><dd><?= $fee ? money($fee) : 'Miễn phí' ?></dd>
      <dt class="tot">Tổng cộng</dt><dd class="tot"><?= money($sub - $voucherDiscount + $fee) ?></dd></dl>
  </aside>
</div>
<script>
  const paymentButton = document.getElementById('place-order-button');
  document.querySelectorAll('input[name="pt"]').forEach((option) => option.addEventListener('change', () => {
    paymentButton.textContent = `${option.value === 'Chuyển khoản' ? 'Thanh toán chuyển khoản' : 'Đặt hàng'} · <?= h(money($sub - $voucherDiscount + $fee)) ?>`;
  }));

  const addressData = <?= json_encode($addressOptions, JSON_UNESCAPED_UNICODE) ?>;
  const provinceSelect = document.getElementById('address-province');
  const districtSelect = document.getElementById('address-district');
  const wardSelect = document.getElementById('address-ward');

  function fillOptions(select, values, selected) {
    select.innerHTML = '<option value="">-- Chọn --</option>' + values.map((value) => `
      <option value="${value}" ${value === selected ? 'selected' : ''}>${value}</option>`).join('');
  }

  function updateDistricts() {
    const province = provinceSelect.value;
    const districts = Object.keys(addressData[province] || {});
    const selectedDistrict = districtSelect.value || '<?= h($f['huyen'] ?? '') ?>';
    fillOptions(districtSelect, districts, districts.includes(selectedDistrict) ? selectedDistrict : '');
    updateWards();
  }

  function updateWards() {
    const province = provinceSelect.value;
    const district = districtSelect.value;
    const wards = addressData[province]?.[district] || [];
    const selectedWard = wardSelect.value || '<?= h($f['xa'] ?? '') ?>';
    fillOptions(wardSelect, wards, wards.includes(selectedWard) ? selectedWard : '');
  }

  provinceSelect.addEventListener('change', updateDistricts);
  districtSelect.addEventListener('change', updateWards);
  updateDistricts();
  updateWards();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
