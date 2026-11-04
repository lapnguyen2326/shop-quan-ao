<?php
// ===== CẤU HÌNH =====
// Đổi 'driver' thành 'mysql' hoặc 'sqlsrv' để chọn loại database.
return [
    'driver' => 'mysql',

    'mysql' => [
        'host' => '127.0.0.1', 'port' => 3306,
        'dbname' => 'QLBanHang', 'user' => 'root', 'pass' => '',
    ],
    // Cần cài extension pdo_sqlsrv + Microsoft ODBC Driver.
    // Để 'user' rỗng nếu dùng Windows Authentication.
    'sqlsrv' => [
        'host' => 'localhost\\SQLEXPRESS',
        'dbname' => 'QLBanHang', 'user' => 'sa', 'pass' => '',
    ],

    'shop_name'     => 'Indigo Wear',
    'shop_phone'    => '0969431710',
    'shop_email'    => 'lapn33742@gmail.com',
    'shop_address'  => 'Thành phố Hồ Chí Minh',
    'bank_bin'      => '970415',
    'bank_account'  => '100876813724',
    'bank_account_name' => 'NGUYEN HOANG TRUNG LAP',
    'per_page'      => 8,        // số sản phẩm mỗi trang
    'ship_fee'      => 30000,    // phí vận chuyển
    'free_ship_from'=> 500000,   // miễn phí ship từ mức này
];
