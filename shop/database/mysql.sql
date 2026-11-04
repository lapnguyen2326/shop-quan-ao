-- Bộ cài mới cho MySQL/MariaDB.
-- CẢNH BÁO: script xóa và tạo lại các bảng bên dưới, làm mất dữ liệu hiện có trong chúng.
CREATE DATABASE IF NOT EXISTS QLBanHang CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE QLBanHang;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS TinNhan, HoiThoai, DanhGia, ChiTietPhieuNhap, PhieuNhapKho, CTHD, HoaDon, Voucher, GiaVonThang, SanPham, Users, KhachHang, LoaiSP, Nhanvien;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE Nhanvien (
  MaNV INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  HoNV VARCHAR(50) NULL, Ten VARCHAR(50) NULL,
  DiaChi VARCHAR(100) NULL, DienThoai VARCHAR(20) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE LoaiSP (
  MaLoaiSP INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  TenLoaiSP VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE KhachHang (
  MaKH VARCHAR(10) NOT NULL PRIMARY KEY,
  TenKH VARCHAR(60) NULL, DiaChi VARCHAR(150) NULL, DienThoai VARCHAR(20) NULL,
  Fax VARCHAR(20) NULL, Email VARCHAR(100) NULL,
  DiemTichLuy INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Users (
  UserName VARCHAR(50) NOT NULL PRIMARY KEY,
  Password VARCHAR(255) NULL, HoTen VARCHAR(60) NULL,
  Quyen VARCHAR(10) NOT NULL DEFAULT 'khach',
  MaKH VARCHAR(10) NULL,
  MaNV INT NULL,
  CONSTRAINT FK_Users_KhachHang FOREIGN KEY (MaKH) REFERENCES KhachHang(MaKH) ON DELETE SET NULL,
  CONSTRAINT FK_Users_Nhanvien FOREIGN KEY (MaNV) REFERENCES Nhanvien(MaNV) ON DELETE SET NULL,
  CONSTRAINT UQ_Users_MaNV UNIQUE (MaNV)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE HoiThoai (
  MaHT INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  UserName VARCHAR(50) NOT NULL, TieuDe VARCHAR(120) NOT NULL,
  NgayCapNhat DATETIME NOT NULL,
  INDEX IX_HoiThoai_User_Ngay (UserName, NgayCapNhat),
  CONSTRAINT FK_HoiThoai_User FOREIGN KEY (UserName) REFERENCES Users(UserName) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE TinNhan (
  MaTN BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  MaHT INT NOT NULL, NguoiGui VARCHAR(10) NOT NULL,
  NoiDung VARCHAR(2000) NOT NULL, NgayGui DATETIME NOT NULL, DaDoc TINYINT(1) NOT NULL DEFAULT 0,
  INDEX IX_TinNhan_HoiThoai_Ngay (MaHT, NgayGui),
  INDEX IX_TinNhan_ChuaDoc (NguoiGui, DaDoc),
  CONSTRAINT FK_TinNhan_HoiThoai FOREIGN KEY (MaHT) REFERENCES HoiThoai(MaHT) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE SanPham (
  MaSP VARCHAR(10) NOT NULL PRIMARY KEY,
  TenSP VARCHAR(100) NULL, DonViTinh VARCHAR(20) NULL,
  GiaVon DECIMAL(12,0) NOT NULL DEFAULT 0, DonGia DECIMAL(12,0) NULL,
  MaLoaiSP INT NULL, HinhSP VARCHAR(255) NULL,
  MoTa TEXT NULL, KichCo VARCHAR(60) NULL, MauSac VARCHAR(100) NULL,
  SoLuongTon INT NOT NULL DEFAULT 0, PhanTramGiam INT NOT NULL DEFAULT 0,
  SPMoi TINYINT(1) NOT NULL DEFAULT 0, HienThi TINYINT(1) NOT NULL DEFAULT 1,
  NgayTao DATETIME NULL,
  CONSTRAINT FK_SanPham_LoaiSP FOREIGN KEY (MaLoaiSP) REFERENCES LoaiSP(MaLoaiSP)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE GiaVonThang (
  MaSP VARCHAR(10) NOT NULL,
  Thang CHAR(7) NOT NULL,
  GiaVon DECIMAL(12,0) NOT NULL,
  PRIMARY KEY (MaSP, Thang),
  CONSTRAINT FK_GiaVonThang_SanPham FOREIGN KEY (MaSP) REFERENCES SanPham(MaSP) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE PhieuNhapKho (
  MaPN INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  NgayNhap DATETIME NOT NULL, GhiChu VARCHAR(255) NULL, NguoiNhap VARCHAR(50) NULL,
  TongSoLuong INT NOT NULL, TongTien DECIMAL(18,0) NOT NULL,
  INDEX IX_PhieuNhapKho_NgayNhap (NgayNhap),
  CONSTRAINT FK_PhieuNhapKho_Users FOREIGN KEY (NguoiNhap) REFERENCES Users(UserName) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE ChiTietPhieuNhap (
  MaPN INT NOT NULL, MaSP VARCHAR(10) NOT NULL,
  SoLuong INT NOT NULL, DonGiaNhap DECIMAL(12,0) NOT NULL,
  TonTruocNhap INT NOT NULL, GiaVonTruocNhap DECIMAL(12,0) NOT NULL,
  TonSauNhap INT NOT NULL, GiaVonSauNhap DECIMAL(12,0) NOT NULL,
  ThanhTien DECIMAL(18,0) NOT NULL,
  PRIMARY KEY (MaPN, MaSP),
  CONSTRAINT FK_ChiTietPhieuNhap_PhieuNhapKho FOREIGN KEY (MaPN) REFERENCES PhieuNhapKho(MaPN) ON DELETE CASCADE,
  CONSTRAINT FK_ChiTietPhieuNhap_SanPham FOREIGN KEY (MaSP) REFERENCES SanPham(MaSP)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE HoaDon (
  MaHD VARCHAR(10) NOT NULL PRIMARY KEY,
  MaKH VARCHAR(10) NULL, MaNV INT NULL, NguoiXuLy VARCHAR(10) NULL,
  NgayLapHD DATETIME NOT NULL, NgayGiaoHang DATETIME NULL,
  TrangThai VARCHAR(20) NOT NULL DEFAULT 'Mới',
  TenNguoiNhan VARCHAR(60) NULL, DiaChiGiao VARCHAR(200) NULL, DienThoaiGiao VARCHAR(20) NULL,
  GhiChu VARCHAR(255) NULL, PTThanhToan VARCHAR(30) NULL,
  TrangThaiThanhToan VARCHAR(20) NOT NULL DEFAULT 'Chưa thanh toán',
  PhiVanChuyen DECIMAL(12,0) NOT NULL DEFAULT 0,
  MaVoucher VARCHAR(30) NULL, GiamVoucher DECIMAL(12,0) NOT NULL DEFAULT 0,
  DiemDaCong INT NOT NULL DEFAULT 0,
  CONSTRAINT FK_HoaDon_KhachHang FOREIGN KEY (MaKH) REFERENCES KhachHang(MaKH),
  CONSTRAINT FK_HoaDon_Nhanvien FOREIGN KEY (MaNV) REFERENCES Nhanvien(MaNV) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Voucher (
  MaVoucher VARCHAR(30) NOT NULL PRIMARY KEY,
  MaKH VARCHAR(10) NULL,
  LoaiGiam VARCHAR(10) NOT NULL,
  GiaTri DECIMAL(12,0) NOT NULL,
  DonToiThieu DECIMAL(12,0) NOT NULL DEFAULT 0,
  GiamToiDa DECIMAL(12,0) NULL,
  SoLuong INT NULL,
  DaSuDung INT NOT NULL DEFAULT 0,
  NgayBatDau DATETIME NULL, NgayKetThuc DATETIME NULL,
  KichHoat TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO Voucher (MaVoucher, LoaiGiam, GiaTri, DonToiThieu, GiamToiDa, SoLuong, DaSuDung, KichHoat)
VALUES ('CHAO10', 'phantram', 10, 300000, 100000, 1000, 0, 1);

CREATE TABLE CTHD (
  MaHD VARCHAR(10) NOT NULL, MaSP VARCHAR(10) NOT NULL,
  KichCo VARCHAR(10) NOT NULL DEFAULT '', MauSac VARCHAR(30) NOT NULL DEFAULT '',
  SoLuong INT NULL, GiamGia DECIMAL(4,2) NULL, DonGia DECIMAL(12,0) NULL, GiaVon DECIMAL(12,0) NULL,
  PRIMARY KEY (MaHD, MaSP, KichCo, MauSac),
  CONSTRAINT FK_CTHD_HoaDon FOREIGN KEY (MaHD) REFERENCES HoaDon(MaHD) ON DELETE CASCADE,
  CONSTRAINT FK_CTHD_SanPham FOREIGN KEY (MaSP) REFERENCES SanPham(MaSP)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE DanhGia (
  MaDG BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  MaSP VARCHAR(10) NOT NULL, UserName VARCHAR(50) NOT NULL,
  SoSao TINYINT UNSIGNED NOT NULL, NoiDung VARCHAR(1000) NOT NULL DEFAULT '',
  NgayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT UQ_DanhGia_SanPham_User UNIQUE (MaSP, UserName),
  CONSTRAINT CK_DanhGia_SoSao CHECK (SoSao BETWEEN 1 AND 5),
  CONSTRAINT FK_DanhGia_SanPham FOREIGN KEY (MaSP) REFERENCES SanPham(MaSP) ON DELETE CASCADE,
  CONSTRAINT FK_DanhGia_User FOREIGN KEY (UserName) REFERENCES Users(UserName) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (1, 'Lê văn', 'Tâm', '45 Trần Phú', '8663447');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (2, 'Trần thị', 'An', '15 Nguyễn Trãi', '');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (3, 'Tạ Thành', 'Tâm', '20 Võ thị Sáu', '8565666');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (4, 'Ngô Thành', 'Sơn', '122 Trần Phú', '');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (5, 'Lê thị', 'Thuỷ', '25 Ngô Quyền', '9767677');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (6, 'Nguyễn Hữu', 'An', '12 Lê Thánh Tôn', '');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (7, 'Thái Chí', 'An', '97 Võ văn Tần', '');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (8, 'Trần Văn', 'Thanh', '25 Trần Văn Đang', '0918158');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (9, 'Nguyễn Dũng', 'Nghi', '65 Trần Văn Thời', '');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (10, 'Lê Thế', 'Bảo', '12 Lê Quý Đôn', '');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (11, 'Lê Dũng', 'Nguyễn', '321 Lê Lợi', '6987456');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (12, 'Nguyễn Thị', 'Thủy', '654 Nguyễn Huệ', '9998745');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (13, 'Nguyễn Thu', 'Hồng', '987 Lê Lợi', '9874561');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (14, 'Trần Thị Thủy', 'Tiên', '6543 Nguyễn Huệ', '');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (15, 'Lê Thị', 'Hiền', '654 Mai Xuân Thời', '');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (16, 'Trần Văn', 'Thanh', 'Tân Bình', '123456');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (17, 'Trần Văn', 'Thành', 'Tân Bình', '12345');

INSERT INTO LoaiSP (MaLoaiSP, TenLoaiSP) VALUES (1, 'Áo thun');
INSERT INTO LoaiSP (MaLoaiSP, TenLoaiSP) VALUES (2, 'Áo sơ mi');
INSERT INTO LoaiSP (MaLoaiSP, TenLoaiSP) VALUES (3, 'Áo khoác');
INSERT INTO LoaiSP (MaLoaiSP, TenLoaiSP) VALUES (4, 'Quần');
INSERT INTO LoaiSP (MaLoaiSP, TenLoaiSP) VALUES (5, 'Váy & Đầm');
INSERT INTO LoaiSP (MaLoaiSP, TenLoaiSP) VALUES (6, 'Phụ kiện');

INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH01', 'Anh Hang', '120 Ha Ton Quyen', '8171717', '084088171717', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH02', 'Bong Hong', '24 Ky Con', '', '084088800256', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH03', 'Em Anh', '6 Ky Hoa', '', '084088852258', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH04', 'Ho Han', '8 Pham van Khoe', '8430156', '084088430156', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH05', 'Koko Company', '90 An Duong Vuong', '8250102', '', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH06', 'Queen Cozinha', '891 An Duong Vuong', '', '', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH07', 'Quoc Cuong', '10 Tan Da', '8950203', '', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH08', 'Suoi Tra', '2817 Minh Phung', '8356210', '084088356210', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH09', 'Song Trang', '187 Lao Tu', '9450210', '', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH10', 'Vinh Vien', '45 Su Van hanh', '', '084088654790', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH11', 'TRần Vĩnh Viễn', '87 Trần Hưng Đạo', '8855464', '', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH12', 'Lý Văn Trung', '123 Tân Tẩn', '', '', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH13', 'Cty Minh Hằng', '456 Công Hòa', '', '', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH14', 'Cty Nghĩa Đường', '12 Lý chính Thắng', '9874564', '', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH15', 'Minh Anh', '15/1 Hậu Giang', '6548797', '', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH16', 'Trần Tùng', '12/15 Hậu Nghĩa', '6547898', '', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH17', 'Cty Vĩnh Lợi', '1812 TRần Quang Khải', '', '', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH18', 'Cty Thái Bình Dương', '145 Nguyễn Huệ', '6548797', '', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH19', 'Cty Thần Tài', '14 Lê Lợi', '6523154', '', '');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES ('KH20', 'Cty Trần Văn Thời', '45 Nguyễn Thị Minh Khai', '4561234', '', '');

INSERT INTO Users (UserName, Password, HoTen, Quyen, MaKH) VALUES ('admin', '$2y$10$tEpt8GPzZQoJp3lK1f.Yzu8XZ33O5kKAXyEYUZzELdz7GG6tlQCAG', 'Quản trị viên', 'admin', NULL);
INSERT INTO Users (UserName, Password, HoTen, Quyen, MaKH) VALUES ('us1', '$2y$10$tEpt8GPzZQoJp3lK1f.Yzu8XZ33O5kKAXyEYUZzELdz7GG6tlQCAG', 'Anh Hang', 'khach', 'KH01');
INSERT INTO Users (UserName, Password, HoTen, Quyen, MaKH) VALUES ('us2', '$2y$10$tEpt8GPzZQoJp3lK1f.Yzu8XZ33O5kKAXyEYUZzELdz7GG6tlQCAG', 'Bong Hong', 'khach', 'KH02');

INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('AT01', 'Áo thun basic cổ tròn', 'Cái', 199000, 1, 'images/2.jpg', 'Áo thun cotton 100% co giãn 2 chiều, form regular dễ phối đồ, thấm hút mồ hôi tốt.', 'S,M,L,XL', 'Trắng,Đen,Xám', 60, 0, 1, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('AT02', 'Áo polo pique', 'Cái', 259000, 1, 'images/3.jpg', 'Polo vải cá sấu thoáng mát, cổ dệt bền form, phù hợp đi làm lẫn đi chơi.', 'S,M,L,XL', 'Xanh navy,Trắng,Đỏ đô', 45, 10, 1, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('AT03', 'Áo thun oversize in hình', 'Cái', 229000, 1, 'images/AT03.jpg', 'Form rộng phong cách streetwear, chất cotton dày dặn, hình in bền màu.', 'M,L,XL', 'Đen,Be', 50, 0, 1, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('SM01', 'Áo sơ mi trắng công sở', 'Cái', 349000, 2, 'images/SM01.jpg', 'Sơ mi trắng chống nhăn, đường may tinh tế, lựa chọn cơ bản cho tủ đồ công sở.', 'S,M,L,XL', 'Trắng', 40, 0, 0, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('SM02', 'Áo sơ mi kẻ sọc', 'Cái', 329000, 2, 'images/SM02.jpg', 'Kẻ sọc nhỏ thanh lịch, vải cotton pha mềm mịn, ít nhăn.', 'S,M,L,XL', 'Xanh sọc,Đen sọc', 35, 15, 0, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('SM03', 'Áo sơ mi linen', 'Cái', 379000, 2, 'images/SM03.jpg', 'Vải linen tự nhiên mát nhẹ, thích hợp thời tiết nóng ẩm.', 'M,L,XL', 'Be,Xanh rêu', 30, 0, 1, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('AK01', 'Áo khoác jean', 'Cái', 549000, 3, 'images/AK01.jpg', 'Denim wash nhẹ, phom boxy, túi ngực tiện dụng, mặc quanh năm.', 'S,M,L,XL', 'Xanh nhạt,Xanh đậm', 25, 0, 0, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('AK02', 'Áo khoác bomber', 'Cái', 599000, 3, 'images/AK02.jpg', 'Bomber hai lớp chống gió nhẹ, bo tay chun chắc chắn, lót trong mềm.', 'M,L,XL', 'Đen,Xanh rêu', 20, 20, 0, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('AK03', 'Áo hoodie nỉ', 'Cái', 429000, 3, 'images/1.webp', 'Nỉ bông dày ấm, mũ 2 lớp, túi kangaroo rộng rãi.', 'S,M,L,XL', 'Xám,Đen,Kem', 55, 0, 1, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('QJ01', 'Quần jeans slim fit', 'Cái', 459000, 4, 'images/QJ01.jpg', 'Jeans co giãn nhẹ, ôm vừa vặn, wash đậm dễ phối áo.', '29,30,31,32,34', 'Xanh đậm,Đen', 50, 0, 0, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('QT01', 'Quần tây âu', 'Cái', 399000, 4, 'images/QT01.jpg', 'Vải tuyết mưa đứng form, ly nổi, phù hợp công sở.', '29,30,31,32,34', 'Đen,Xám đậm', 40, 10, 0, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('QS01', 'Quần short kaki', 'Cái', 249000, 4, 'images/QS01.jpg', 'Kaki mềm, lưng chun phối dây rút, thoải mái ngày hè.', 'S,M,L,XL', 'Be,Xanh navy,Đen', 60, 0, 1, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('VD01', 'Váy midi hoa nhí', 'Cái', 429000, 5, 'images/VD01.jpg', 'Váy dáng xòe dài qua gối, họa tiết hoa nhí nhẹ nhàng, có lót trong.', 'S,M,L', 'Hồng,Xanh mint', 30, 0, 1, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('VD02', 'Đầm dự tiệc', 'Cái', 690000, 5, 'images/VD02.jpg', 'Đầm ôm nhẹ, chất liệu satin bóng mềm, phù hợp tiệc tối và sự kiện.', 'S,M,L', 'Đen,Đỏ đô', 15, 25, 0, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('PK01', 'Nón lưỡi trai', 'Cái', 129000, 6, 'images/PK01.jpg', 'Nón kaki thêu logo nhỏ, khóa cài điều chỉnh vòng đầu.', 'Free size', 'Đen,Be,Xanh navy', 80, 0, 0, 1, '2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES ('PK02', 'Thắt lưng da', 'Cái', 249000, 6, 'images/PK02.jpg', 'Da bò thật, mặt khóa kim loại không gỉ, bản 3.5cm.', '100cm,110cm,120cm', 'Đen,Nâu', 40, 0, 0, 1, '2026-09-01 00:00:00');

INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES ('HD001', 'KH01', 4, '2000-02-26 00:00:00', '2000-07-02 00:00:00', 'Hoàn thành', 'Anh Hang', '120 Ha Ton Quyen', '8171717', '', 'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES ('HD002', 'KH08', 7, '2001-12-15 00:00:00', '2001-12-15 00:00:00', 'Hoàn thành', 'Suoi Tra', '2817 Minh Phung', '8356210', '', 'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES ('HD003', 'KH10', 8, '2001-02-02 00:00:00', '2001-12-12 00:00:00', 'Hoàn thành', 'Vinh Vien', '45 Su Van hanh', '', '', 'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES ('HD004', 'KH09', 7, '2000-08-04 00:00:00', '2000-08-06 00:00:00', 'Hoàn thành', 'Song Trang', '187 Lao Tu', '9450210', '', 'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES ('HD005', 'KH05', 1, '2000-08-05 00:00:00', '2000-08-10 00:00:00', 'Hoàn thành', 'Koko Company', '90 An Duong Vuong', '8250102', '', 'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES ('HD006', 'KH03', 15, '2003-12-15 00:00:00', '2003-12-16 00:00:00', 'Hoàn thành', 'Em Anh', '6 Ky Hoa', '', '', 'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES ('HD007', 'KH10', 12, '2002-04-04 00:00:00', '2002-04-15 00:00:00', 'Hoàn thành', 'Vinh Vien', '45 Su Van hanh', '', '', 'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES ('HD008', 'KH09', 1, '2000-10-21 00:00:00', '2000-10-30 00:00:00', 'Hoàn thành', 'Song Trang', '187 Lao Tu', '9450210', '', 'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES ('HD009', 'KH06', 10, '2002-06-06 00:00:00', '2002-12-06 00:00:00', 'Hoàn thành', 'Queen Cozinha', '891 An Duong Vuong', '', '', 'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES ('HD010', 'KH04', 9, '2001-08-08 00:00:00', '2001-12-08 00:00:00', 'Hoàn thành', 'Ho Han', '8 Pham van Khoe', '8430156', '', 'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES ('HD011', 'KH03', 3, '2001-02-16 00:00:00', '2001-03-12 00:00:00', 'Hoàn thành', 'Em Anh', '6 Ky Hoa', '', '', 'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES ('HD012', 'KH08', 4, '2002-09-09 00:00:00', '2002-12-09 00:00:00', 'Hoàn thành', 'Suoi Tra', '2817 Minh Phung', '8356210', '', 'COD', 0);

INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD001', 'AT01', 'M', 'Trắng', 10, 0, 199000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD001', 'AT02', 'M', 'Xanh navy', 12, 0.5, 259000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD001', 'AT03', 'M', 'Đen', 4, 0.5, 229000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD001', 'SM01', 'M', 'Trắng', 100, 0.2, 349000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD002', 'SM02', 'M', 'Xanh sọc', 4, 1, 329000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD002', 'SM03', 'M', 'Be', 10, 0.1, 379000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD002', 'AK01', 'M', 'Xanh nhạt', 2, 0.3, 549000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD004', 'AK02', 'M', 'Đen', 2, 0, 599000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD004', 'AK03', 'M', 'Xám', 3, 0.8, 429000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD005', 'QJ01', 'M', 'Xanh đậm', 100, 0, 459000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD005', 'QT01', 'M', 'Đen', 25, 0, 399000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD005', 'QS01', 'M', 'Be', 32, 0, 249000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD006', 'VD01', 'M', 'Hồng', 50, 0, 429000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD006', 'VD02', 'M', 'Đen', 9, 0, 690000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD006', 'PK01', 'M', 'Đen', 5, 0, 129000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD007', 'PK02', 'M', 'Đen', 10, 0, 249000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD007', 'AT01', 'M', 'Trắng', 8, 0, 199000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD008', 'AT02', 'M', 'Xanh navy', 7, 0, 259000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD008', 'AT03', 'M', 'Đen', 6, 0, 229000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD009', 'SM01', 'M', 'Trắng', 30, 0, 349000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD009', 'SM02', 'M', 'Xanh sọc', 20, 0, 329000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD010', 'AT03', 'M', 'Đen', 5, 0.4, 229000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD010', 'SM01', 'M', 'Trắng', 5, 0, 349000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD010', 'SM02', 'M', 'Xanh sọc', 10, 0, 329000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD011', 'AT02', 'M', 'Xanh navy', 10, 0, 259000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD011', 'AT03', 'M', 'Đen', 21, 0, 229000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD011', 'SM01', 'M', 'Trắng', 12, 0, 349000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD012', 'QJ01', 'M', 'Xanh đậm', 34, 0.5, 459000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES ('HD012', 'QT01', 'M', 'Đen', 20, 0, 399000);
