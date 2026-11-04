-- Bộ cài mới cho Microsoft SQL Server.
-- CẢNH BÁO: script xóa và tạo lại các bảng bên dưới, làm mất dữ liệu hiện có trong chúng.
IF DB_ID(N'QLBanHang') IS NULL CREATE DATABASE QLBanHang;
GO
USE QLBanHang;
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET ARITHABORT ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET NUMERIC_ROUNDABORT OFF;
GO
IF OBJECT_ID(N'dbo.TinNhan', N'U') IS NOT NULL DROP TABLE dbo.TinNhan;
IF OBJECT_ID(N'dbo.HoiThoai', N'U') IS NOT NULL DROP TABLE dbo.HoiThoai;
IF OBJECT_ID(N'dbo.DanhGia', N'U') IS NOT NULL DROP TABLE dbo.DanhGia;
IF OBJECT_ID(N'dbo.ChiTietPhieuNhap', N'U') IS NOT NULL DROP TABLE dbo.ChiTietPhieuNhap;
IF OBJECT_ID(N'dbo.PhieuNhapKho', N'U') IS NOT NULL DROP TABLE dbo.PhieuNhapKho;
IF OBJECT_ID(N'dbo.CTHD', N'U') IS NOT NULL DROP TABLE dbo.CTHD;
IF OBJECT_ID(N'dbo.HoaDon', N'U') IS NOT NULL DROP TABLE dbo.HoaDon;
IF OBJECT_ID(N'dbo.Voucher', N'U') IS NOT NULL DROP TABLE dbo.Voucher;
IF OBJECT_ID(N'dbo.GiaVonThang', N'U') IS NOT NULL DROP TABLE dbo.GiaVonThang;
IF OBJECT_ID(N'dbo.SanPham', N'U') IS NOT NULL DROP TABLE dbo.SanPham;
IF OBJECT_ID(N'dbo.Users', N'U') IS NOT NULL DROP TABLE dbo.Users;
IF OBJECT_ID(N'dbo.KhachHang', N'U') IS NOT NULL DROP TABLE dbo.KhachHang;
IF OBJECT_ID(N'dbo.LoaiSP', N'U') IS NOT NULL DROP TABLE dbo.LoaiSP;
IF OBJECT_ID(N'dbo.Nhanvien', N'U') IS NOT NULL DROP TABLE dbo.Nhanvien;
GO

CREATE TABLE Nhanvien (
  MaNV INT NOT NULL IDENTITY(1,1) PRIMARY KEY,
  HoNV NVARCHAR(50) NULL, Ten NVARCHAR(50) NULL,
  DiaChi NVARCHAR(100) NULL, DienThoai NVARCHAR(20) NULL
);

CREATE TABLE LoaiSP (
  MaLoaiSP INT NOT NULL IDENTITY(1,1) PRIMARY KEY,
  TenLoaiSP NVARCHAR(255) NULL
);

CREATE TABLE KhachHang (
  MaKH NVARCHAR(10) NOT NULL PRIMARY KEY,
  TenKH NVARCHAR(60) NULL, DiaChi NVARCHAR(150) NULL, DienThoai NVARCHAR(20) NULL,
  Fax NVARCHAR(20) NULL, Email NVARCHAR(100) NULL,
  DiemTichLuy INT NOT NULL DEFAULT 0
);

CREATE TABLE Users (
  UserName NVARCHAR(50) NOT NULL PRIMARY KEY,
  Password NVARCHAR(255) NULL, HoTen NVARCHAR(60) NULL,
  Quyen NVARCHAR(10) NOT NULL DEFAULT N'khach',
  MaKH NVARCHAR(10) NULL,
  MaNV INT NULL,
  CONSTRAINT FK_Users_KhachHang FOREIGN KEY (MaKH) REFERENCES KhachHang(MaKH) ON DELETE SET NULL,
  CONSTRAINT FK_Users_Nhanvien FOREIGN KEY (MaNV) REFERENCES Nhanvien(MaNV) ON DELETE SET NULL
);

CREATE TABLE HoiThoai (
  MaHT INT NOT NULL IDENTITY(1,1) PRIMARY KEY,
  UserName NVARCHAR(50) NOT NULL, TieuDe NVARCHAR(120) NOT NULL,
  NgayCapNhat DATETIME NOT NULL,
  CONSTRAINT FK_HoiThoai_User FOREIGN KEY (UserName) REFERENCES Users(UserName) ON DELETE CASCADE
);

CREATE TABLE TinNhan (
  MaTN BIGINT NOT NULL IDENTITY(1,1) PRIMARY KEY,
  MaHT INT NOT NULL, NguoiGui NVARCHAR(10) NOT NULL,
  NoiDung NVARCHAR(2000) NOT NULL, NgayGui DATETIME NOT NULL, DaDoc BIT NOT NULL DEFAULT 0,
  CONSTRAINT FK_TinNhan_HoiThoai FOREIGN KEY (MaHT) REFERENCES HoiThoai(MaHT) ON DELETE CASCADE
);

CREATE TABLE SanPham (
  MaSP NVARCHAR(10) NOT NULL PRIMARY KEY,
  TenSP NVARCHAR(100) NULL, DonViTinh NVARCHAR(20) NULL,
  GiaVon DECIMAL(12,0) NOT NULL DEFAULT 0, DonGia DECIMAL(12,0) NULL,
  MaLoaiSP INT NULL, HinhSP NVARCHAR(255) NULL,
  MoTa NVARCHAR(MAX) NULL, KichCo NVARCHAR(60) NULL, MauSac NVARCHAR(100) NULL,
  SoLuongTon INT NOT NULL DEFAULT 0, PhanTramGiam INT NOT NULL DEFAULT 0,
  SPMoi BIT NOT NULL DEFAULT 0, HienThi BIT NOT NULL DEFAULT 1,
  NgayTao DATETIME NULL,
  CONSTRAINT FK_SanPham_LoaiSP FOREIGN KEY (MaLoaiSP) REFERENCES LoaiSP(MaLoaiSP)
);
CREATE TABLE GiaVonThang (
  MaSP NVARCHAR(10) NOT NULL,
  Thang CHAR(7) NOT NULL,
  GiaVon DECIMAL(12,0) NOT NULL,
  PRIMARY KEY (MaSP, Thang),
  CONSTRAINT FK_GiaVonThang_SanPham FOREIGN KEY (MaSP) REFERENCES SanPham(MaSP) ON DELETE CASCADE
);
CREATE TABLE PhieuNhapKho (
  MaPN INT NOT NULL IDENTITY(1,1) PRIMARY KEY,
  NgayNhap DATETIME NOT NULL, GhiChu NVARCHAR(255) NULL, NguoiNhap NVARCHAR(50) NULL,
  TongSoLuong INT NOT NULL, TongTien DECIMAL(18,0) NOT NULL,
  CONSTRAINT FK_PhieuNhapKho_Users FOREIGN KEY (NguoiNhap) REFERENCES Users(UserName) ON DELETE SET NULL
);
CREATE INDEX IX_PhieuNhapKho_NgayNhap ON PhieuNhapKho (NgayNhap);
CREATE TABLE ChiTietPhieuNhap (
  MaPN INT NOT NULL, MaSP NVARCHAR(10) NOT NULL,
  SoLuong INT NOT NULL, DonGiaNhap DECIMAL(12,0) NOT NULL,
  TonTruocNhap INT NOT NULL, GiaVonTruocNhap DECIMAL(12,0) NOT NULL,
  TonSauNhap INT NOT NULL, GiaVonSauNhap DECIMAL(12,0) NOT NULL,
  ThanhTien DECIMAL(18,0) NOT NULL,
  CONSTRAINT PK_ChiTietPhieuNhap PRIMARY KEY (MaPN, MaSP),
  CONSTRAINT FK_ChiTietPhieuNhap_PhieuNhapKho FOREIGN KEY (MaPN) REFERENCES PhieuNhapKho(MaPN) ON DELETE CASCADE,
  CONSTRAINT FK_ChiTietPhieuNhap_SanPham FOREIGN KEY (MaSP) REFERENCES SanPham(MaSP)
);
CREATE TABLE HoaDon (
  MaHD NVARCHAR(10) NOT NULL PRIMARY KEY,
  MaKH NVARCHAR(10) NULL, MaNV INT NULL, NguoiXuLy NVARCHAR(10) NULL,
  NgayLapHD DATETIME NOT NULL, NgayGiaoHang DATETIME NULL,
  TrangThai NVARCHAR(20) NOT NULL DEFAULT N'Mới',
  TenNguoiNhan NVARCHAR(60) NULL, DiaChiGiao NVARCHAR(200) NULL, DienThoaiGiao NVARCHAR(20) NULL,
  GhiChu NVARCHAR(255) NULL, PTThanhToan NVARCHAR(30) NULL,
  TrangThaiThanhToan NVARCHAR(20) NOT NULL DEFAULT N'Chưa thanh toán',
  PhiVanChuyen DECIMAL(12,0) NOT NULL DEFAULT 0,
  MaVoucher NVARCHAR(30) NULL, GiamVoucher DECIMAL(12,0) NOT NULL DEFAULT 0,
  DiemDaCong INT NOT NULL DEFAULT 0,
  CONSTRAINT FK_HoaDon_KhachHang FOREIGN KEY (MaKH) REFERENCES KhachHang(MaKH),
  CONSTRAINT FK_HoaDon_Nhanvien FOREIGN KEY (MaNV) REFERENCES Nhanvien(MaNV) ON DELETE SET NULL
);

CREATE TABLE Voucher (
  MaVoucher NVARCHAR(30) NOT NULL PRIMARY KEY,
  MaKH NVARCHAR(10) NULL,
  LoaiGiam NVARCHAR(10) NOT NULL,
  GiaTri DECIMAL(12,0) NOT NULL,
  DonToiThieu DECIMAL(12,0) NOT NULL DEFAULT 0,
  GiamToiDa DECIMAL(12,0) NULL,
  SoLuong INT NULL,
  DaSuDung INT NOT NULL DEFAULT 0,
  NgayBatDau DATETIME NULL, NgayKetThuc DATETIME NULL,
  KichHoat BIT NOT NULL DEFAULT 1
);

INSERT INTO Voucher (MaVoucher, LoaiGiam, GiaTri, DonToiThieu, GiamToiDa, SoLuong, DaSuDung, KichHoat)
VALUES (N'CHAO10', N'phantram', 10, 300000, 100000, 1000, 0, 1);

CREATE TABLE CTHD (
  MaHD NVARCHAR(10) NOT NULL, MaSP NVARCHAR(10) NOT NULL,
  KichCo NVARCHAR(10) NOT NULL DEFAULT N'', MauSac NVARCHAR(30) NOT NULL DEFAULT N'',
  SoLuong INT NULL, GiamGia DECIMAL(4,2) NULL, DonGia DECIMAL(12,0) NULL, GiaVon DECIMAL(12,0) NULL,
  PRIMARY KEY (MaHD, MaSP, KichCo, MauSac),
  CONSTRAINT FK_CTHD_HoaDon FOREIGN KEY (MaHD) REFERENCES HoaDon(MaHD) ON DELETE CASCADE,
  CONSTRAINT FK_CTHD_SanPham FOREIGN KEY (MaSP) REFERENCES SanPham(MaSP)
);

CREATE TABLE DanhGia (
  MaDG BIGINT NOT NULL IDENTITY(1,1) PRIMARY KEY,
  MaSP NVARCHAR(10) NOT NULL, UserName NVARCHAR(50) NOT NULL,
  SoSao TINYINT NOT NULL, NoiDung NVARCHAR(1000) NOT NULL DEFAULT N'',
  NgayTao DATETIME NOT NULL DEFAULT GETDATE(),
  CONSTRAINT UQ_DanhGia_SanPham_User UNIQUE (MaSP, UserName),
  CONSTRAINT CK_DanhGia_SoSao CHECK (SoSao BETWEEN 1 AND 5),
  CONSTRAINT FK_DanhGia_SanPham FOREIGN KEY (MaSP) REFERENCES SanPham(MaSP) ON DELETE CASCADE,
  CONSTRAINT FK_DanhGia_User FOREIGN KEY (UserName) REFERENCES Users(UserName) ON DELETE CASCADE
);


CREATE INDEX IX_HoiThoai_User_Ngay ON HoiThoai (UserName, NgayCapNhat);
CREATE INDEX IX_TinNhan_HoiThoai_Ngay ON TinNhan (MaHT, NgayGui);
CREATE INDEX IX_TinNhan_ChuaDoc ON TinNhan (NguoiGui, DaDoc);
CREATE UNIQUE INDEX UQ_Users_MaNV ON Users (MaNV) WHERE MaNV IS NOT NULL;
GO
SET IDENTITY_INSERT Nhanvien ON;
GO
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (1, N'Lê văn', N'Tâm', N'45 Trần Phú', N'8663447');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (2, N'Trần thị', N'An', N'15 Nguyễn Trãi', N'');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (3, N'Tạ Thành', N'Tâm', N'20 Võ thị Sáu', N'8565666');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (4, N'Ngô Thành', N'Sơn', N'122 Trần Phú', N'');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (5, N'Lê thị', N'Thuỷ', N'25 Ngô Quyền', N'9767677');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (6, N'Nguyễn Hữu', N'An', N'12 Lê Thánh Tôn', N'');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (7, N'Thái Chí', N'An', N'97 Võ văn Tần', N'');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (8, N'Trần Văn', N'Thanh', N'25 Trần Văn Đang', N'0918158');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (9, N'Nguyễn Dũng', N'Nghi', N'65 Trần Văn Thời', N'');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (10, N'Lê Thế', N'Bảo', N'12 Lê Quý Đôn', N'');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (11, N'Lê Dũng', N'Nguyễn', N'321 Lê Lợi', N'6987456');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (12, N'Nguyễn Thị', N'Thủy', N'654 Nguyễn Huệ', N'9998745');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (13, N'Nguyễn Thu', N'Hồng', N'987 Lê Lợi', N'9874561');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (14, N'Trần Thị Thủy', N'Tiên', N'6543 Nguyễn Huệ', N'');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (15, N'Lê Thị', N'Hiền', N'654 Mai Xuân Thời', N'');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (16, N'Trần Văn', N'Thanh', N'Tân Bình', N'123456');
INSERT INTO Nhanvien (MaNV, HoNV, Ten, DiaChi, DienThoai) VALUES (17, N'Trần Văn', N'Thành', N'Tân Bình', N'12345');

SET IDENTITY_INSERT Nhanvien OFF;
GO
SET IDENTITY_INSERT LoaiSP ON;
GO
INSERT INTO LoaiSP (MaLoaiSP, TenLoaiSP) VALUES (1, N'Áo thun');
INSERT INTO LoaiSP (MaLoaiSP, TenLoaiSP) VALUES (2, N'Áo sơ mi');
INSERT INTO LoaiSP (MaLoaiSP, TenLoaiSP) VALUES (3, N'Áo khoác');
INSERT INTO LoaiSP (MaLoaiSP, TenLoaiSP) VALUES (4, N'Quần');
INSERT INTO LoaiSP (MaLoaiSP, TenLoaiSP) VALUES (5, N'Váy & Đầm');
INSERT INTO LoaiSP (MaLoaiSP, TenLoaiSP) VALUES (6, N'Phụ kiện');

SET IDENTITY_INSERT LoaiSP OFF;
GO
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH01', N'Anh Hang', N'120 Ha Ton Quyen', N'8171717', N'084088171717', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH02', N'Bong Hong', N'24 Ky Con', N'', N'084088800256', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH03', N'Em Anh', N'6 Ky Hoa', N'', N'084088852258', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH04', N'Ho Han', N'8 Pham van Khoe', N'8430156', N'084088430156', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH05', N'Koko Company', N'90 An Duong Vuong', N'8250102', N'', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH06', N'Queen Cozinha', N'891 An Duong Vuong', N'', N'', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH07', N'Quoc Cuong', N'10 Tan Da', N'8950203', N'', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH08', N'Suoi Tra', N'2817 Minh Phung', N'8356210', N'084088356210', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH09', N'Song Trang', N'187 Lao Tu', N'9450210', N'', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH10', N'Vinh Vien', N'45 Su Van hanh', N'', N'084088654790', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH11', N'TRần Vĩnh Viễn', N'87 Trần Hưng Đạo', N'8855464', N'', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH12', N'Lý Văn Trung', N'123 Tân Tẩn', N'', N'', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH13', N'Cty Minh Hằng', N'456 Công Hòa', N'', N'', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH14', N'Cty Nghĩa Đường', N'12 Lý chính Thắng', N'9874564', N'', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH15', N'Minh Anh', N'15/1 Hậu Giang', N'6548797', N'', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH16', N'Trần Tùng', N'12/15 Hậu Nghĩa', N'6547898', N'', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH17', N'Cty Vĩnh Lợi', N'1812 TRần Quang Khải', N'', N'', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH18', N'Cty Thái Bình Dương', N'145 Nguyễn Huệ', N'6548797', N'', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH19', N'Cty Thần Tài', N'14 Lê Lợi', N'6523154', N'', N'');
INSERT INTO KhachHang (MaKH, TenKH, DiaChi, DienThoai, Fax, Email) VALUES (N'KH20', N'Cty Trần Văn Thời', N'45 Nguyễn Thị Minh Khai', N'4561234', N'', N'');

INSERT INTO Users (UserName, Password, HoTen, Quyen, MaKH) VALUES (N'admin', N'$2y$10$tEpt8GPzZQoJp3lK1f.Yzu8XZ33O5kKAXyEYUZzELdz7GG6tlQCAG', N'Quản trị viên', N'admin', NULL);
INSERT INTO Users (UserName, Password, HoTen, Quyen, MaKH) VALUES (N'us1', N'$2y$10$tEpt8GPzZQoJp3lK1f.Yzu8XZ33O5kKAXyEYUZzELdz7GG6tlQCAG', N'Anh Hang', N'khach', N'KH01');
INSERT INTO Users (UserName, Password, HoTen, Quyen, MaKH) VALUES (N'us2', N'$2y$10$tEpt8GPzZQoJp3lK1f.Yzu8XZ33O5kKAXyEYUZzELdz7GG6tlQCAG', N'Bong Hong', N'khach', N'KH02');

INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'AT01', N'Áo thun basic cổ tròn', N'Cái', 199000, 1, N'images/2.jpg', N'Áo thun cotton 100% co giãn 2 chiều, form regular dễ phối đồ, thấm hút mồ hôi tốt.', N'S,M,L,XL', N'Trắng,Đen,Xám', 60, 0, 1, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'AT02', N'Áo polo pique', N'Cái', 259000, 1, N'images/3.jpg', N'Polo vải cá sấu thoáng mát, cổ dệt bền form, phù hợp đi làm lẫn đi chơi.', N'S,M,L,XL', N'Xanh navy,Trắng,Đỏ đô', 45, 10, 1, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'AT03', N'Áo thun oversize in hình', N'Cái', 229000, 1, N'images/AT03.jpg', N'Form rộng phong cách streetwear, chất cotton dày dặn, hình in bền màu.', N'M,L,XL', N'Đen,Be', 50, 0, 1, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'SM01', N'Áo sơ mi trắng công sở', N'Cái', 349000, 2, N'images/SM01.jpg', N'Sơ mi trắng chống nhăn, đường may tinh tế, lựa chọn cơ bản cho tủ đồ công sở.', N'S,M,L,XL', N'Trắng', 40, 0, 0, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'SM02', N'Áo sơ mi kẻ sọc', N'Cái', 329000, 2, N'images/SM02.jpg', N'Kẻ sọc nhỏ thanh lịch, vải cotton pha mềm mịn, ít nhăn.', N'S,M,L,XL', N'Xanh sọc,Đen sọc', 35, 15, 0, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'SM03', N'Áo sơ mi linen', N'Cái', 379000, 2, N'images/SM03.jpg', N'Vải linen tự nhiên mát nhẹ, thích hợp thời tiết nóng ẩm.', N'M,L,XL', N'Be,Xanh rêu', 30, 0, 1, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'AK01', N'Áo khoác jean', N'Cái', 549000, 3, N'images/AK01.jpg', N'Denim wash nhẹ, phom boxy, túi ngực tiện dụng, mặc quanh năm.', N'S,M,L,XL', N'Xanh nhạt,Xanh đậm', 25, 0, 0, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'AK02', N'Áo khoác bomber', N'Cái', 599000, 3, N'images/AK02.jpg', N'Bomber hai lớp chống gió nhẹ, bo tay chun chắc chắn, lót trong mềm.', N'M,L,XL', N'Đen,Xanh rêu', 20, 20, 0, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'AK03', N'Áo hoodie nỉ', N'Cái', 429000, 3, N'images/1.webp', N'Nỉ bông dày ấm, mũ 2 lớp, túi kangaroo rộng rãi.', N'S,M,L,XL', N'Xám,Đen,Kem', 55, 0, 1, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'QJ01', N'Quần jeans slim fit', N'Cái', 459000, 4, N'images/QJ01.jpg', N'Jeans co giãn nhẹ, ôm vừa vặn, wash đậm dễ phối áo.', N'29,30,31,32,34', N'Xanh đậm,Đen', 50, 0, 0, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'QT01', N'Quần tây âu', N'Cái', 399000, 4, N'images/QT01.jpg', N'Vải tuyết mưa đứng form, ly nổi, phù hợp công sở.', N'29,30,31,32,34', N'Đen,Xám đậm', 40, 10, 0, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'QS01', N'Quần short kaki', N'Cái', 249000, 4, N'images/QS01.jpg', N'Kaki mềm, lưng chun phối dây rút, thoải mái ngày hè.', N'S,M,L,XL', N'Be,Xanh navy,Đen', 60, 0, 1, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'VD01', N'Váy midi hoa nhí', N'Cái', 429000, 5, N'images/VD01.jpg', N'Váy dáng xòe dài qua gối, họa tiết hoa nhí nhẹ nhàng, có lót trong.', N'S,M,L', N'Hồng,Xanh mint', 30, 0, 1, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'VD02', N'Đầm dự tiệc', N'Cái', 690000, 5, N'images/VD02.jpg', N'Đầm ôm nhẹ, chất liệu satin bóng mềm, phù hợp tiệc tối và sự kiện.', N'S,M,L', N'Đen,Đỏ đô', 15, 25, 0, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'PK01', N'Nón lưỡi trai', N'Cái', 129000, 6, N'images/PK01.jpg', N'Nón kaki thêu logo nhỏ, khóa cài điều chỉnh vòng đầu.', N'Free size', N'Đen,Be,Xanh navy', 80, 0, 0, 1, N'2026-09-01 00:00:00');
INSERT INTO SanPham (MaSP, TenSP, DonViTinh, DonGia, MaLoaiSP, HinhSP, MoTa, KichCo, MauSac, SoLuongTon, PhanTramGiam, SPMoi, HienThi, NgayTao) VALUES (N'PK02', N'Thắt lưng da', N'Cái', 249000, 6, N'images/PK02.jpg', N'Da bò thật, mặt khóa kim loại không gỉ, bản 3.5cm.', N'100cm,110cm,120cm', N'Đen,Nâu', 40, 0, 0, 1, N'2026-09-01 00:00:00');

INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES (N'HD001', N'KH01', 4, N'2000-02-26 00:00:00', N'2000-07-02 00:00:00', N'Hoàn thành', N'Anh Hang', N'120 Ha Ton Quyen', N'8171717', N'', N'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES (N'HD002', N'KH08', 7, N'2001-12-15 00:00:00', N'2001-12-15 00:00:00', N'Hoàn thành', N'Suoi Tra', N'2817 Minh Phung', N'8356210', N'', N'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES (N'HD003', N'KH10', 8, N'2001-02-02 00:00:00', N'2001-12-12 00:00:00', N'Hoàn thành', N'Vinh Vien', N'45 Su Van hanh', N'', N'', N'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES (N'HD004', N'KH09', 7, N'2000-08-04 00:00:00', N'2000-08-06 00:00:00', N'Hoàn thành', N'Song Trang', N'187 Lao Tu', N'9450210', N'', N'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES (N'HD005', N'KH05', 1, N'2000-08-05 00:00:00', N'2000-08-10 00:00:00', N'Hoàn thành', N'Koko Company', N'90 An Duong Vuong', N'8250102', N'', N'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES (N'HD006', N'KH03', 15, N'2003-12-15 00:00:00', N'2003-12-16 00:00:00', N'Hoàn thành', N'Em Anh', N'6 Ky Hoa', N'', N'', N'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES (N'HD007', N'KH10', 12, N'2002-04-04 00:00:00', N'2002-04-15 00:00:00', N'Hoàn thành', N'Vinh Vien', N'45 Su Van hanh', N'', N'', N'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES (N'HD008', N'KH09', 1, N'2000-10-21 00:00:00', N'2000-10-30 00:00:00', N'Hoàn thành', N'Song Trang', N'187 Lao Tu', N'9450210', N'', N'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES (N'HD009', N'KH06', 10, N'2002-06-06 00:00:00', N'2002-12-06 00:00:00', N'Hoàn thành', N'Queen Cozinha', N'891 An Duong Vuong', N'', N'', N'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES (N'HD010', N'KH04', 9, N'2001-08-08 00:00:00', N'2001-12-08 00:00:00', N'Hoàn thành', N'Ho Han', N'8 Pham van Khoe', N'8430156', N'', N'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES (N'HD011', N'KH03', 3, N'2001-02-16 00:00:00', N'2001-03-12 00:00:00', N'Hoàn thành', N'Em Anh', N'6 Ky Hoa', N'', N'', N'COD', 0);
INSERT INTO HoaDon (MaHD, MaKH, MaNV, NgayLapHD, NgayGiaoHang, TrangThai, TenNguoiNhan, DiaChiGiao, DienThoaiGiao, GhiChu, PTThanhToan, PhiVanChuyen) VALUES (N'HD012', N'KH08', 4, N'2002-09-09 00:00:00', N'2002-12-09 00:00:00', N'Hoàn thành', N'Suoi Tra', N'2817 Minh Phung', N'8356210', N'', N'COD', 0);

INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD001', N'AT01', N'M', N'Trắng', 10, 0, 199000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD001', N'AT02', N'M', N'Xanh navy', 12, 0.5, 259000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD001', N'AT03', N'M', N'Đen', 4, 0.5, 229000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD001', N'SM01', N'M', N'Trắng', 100, 0.2, 349000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD002', N'SM02', N'M', N'Xanh sọc', 4, 1, 329000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD002', N'SM03', N'M', N'Be', 10, 0.1, 379000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD002', N'AK01', N'M', N'Xanh nhạt', 2, 0.3, 549000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD004', N'AK02', N'M', N'Đen', 2, 0, 599000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD004', N'AK03', N'M', N'Xám', 3, 0.8, 429000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD005', N'QJ01', N'M', N'Xanh đậm', 100, 0, 459000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD005', N'QT01', N'M', N'Đen', 25, 0, 399000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD005', N'QS01', N'M', N'Be', 32, 0, 249000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD006', N'VD01', N'M', N'Hồng', 50, 0, 429000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD006', N'VD02', N'M', N'Đen', 9, 0, 690000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD006', N'PK01', N'M', N'Đen', 5, 0, 129000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD007', N'PK02', N'M', N'Đen', 10, 0, 249000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD007', N'AT01', N'M', N'Trắng', 8, 0, 199000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD008', N'AT02', N'M', N'Xanh navy', 7, 0, 259000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD008', N'AT03', N'M', N'Đen', 6, 0, 229000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD009', N'SM01', N'M', N'Trắng', 30, 0, 349000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD009', N'SM02', N'M', N'Xanh sọc', 20, 0, 329000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD010', N'AT03', N'M', N'Đen', 5, 0.4, 229000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD010', N'SM01', N'M', N'Trắng', 5, 0, 349000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD010', N'SM02', N'M', N'Xanh sọc', 10, 0, 329000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD011', N'AT02', N'M', N'Xanh navy', 10, 0, 259000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD011', N'AT03', N'M', N'Đen', 21, 0, 229000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD011', N'SM01', N'M', N'Trắng', 12, 0, 349000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD012', N'QJ01', N'M', N'Xanh đậm', 34, 0.5, 459000);
INSERT INTO CTHD (MaHD, MaSP, KichCo, MauSac, SoLuong, GiamGia, DonGia) VALUES (N'HD012', N'QT01', N'M', N'Đen', 20, 0, 399000);
