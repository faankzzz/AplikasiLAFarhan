-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 07 Jul 2026 pada 10.17
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bismillah_la`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `komplain`
--

CREATE TABLE `komplain` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pesanan_id` int(11) DEFAULT NULL,
  `tipe` varchar(50) NOT NULL,
  `deskripsi` text NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `balasan` text DEFAULT NULL,
  `sopir_id` int(11) DEFAULT NULL,
  `tanggal_penggantian` datetime DEFAULT NULL,
  `konfirmasi_konsumen` tinyint(1) NOT NULL DEFAULT 0,
  `rating` int(11) DEFAULT NULL,
  `status` enum('menunggu','terjadwal_penggantian','selesai','ditolak') DEFAULT 'menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `mutasi_stok`
--

CREATE TABLE `mutasi_stok` (
  `id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `tipe` enum('masuk','keluar') NOT NULL,
  `jumlah` int(11) NOT NULL,
  `kategori` varchar(50) DEFAULT 'lainnya',
  `stok_awal` int(11) NOT NULL,
  `stok_akhir` int(11) NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `tanggal` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `mutasi_stok`
--

INSERT INTO `mutasi_stok` (`id`, `produk_id`, `tipe`, `jumlah`, `kategori`, `stok_awal`, `stok_akhir`, `keterangan`, `tanggal`, `created_at`) VALUES
(1, 7, 'masuk', 50, 'lainnya', 0, 0, 'pabrik', '2026-03-03', '2026-03-03 06:02:24'),
(2, 3, 'masuk', 50, 'lainnya', 0, 0, 'pabrik', '2026-03-03', '2026-03-03 06:03:00'),
(3, 1, 'masuk', 20, 'lainnya', 0, 0, 'gudang', '2026-03-04', '2026-03-04 01:11:46'),
(4, 3, 'masuk', 1, 'lainnya', 0, 0, 'pabrik', '2026-03-26', '2026-03-26 13:19:29'),
(5, 3, 'masuk', 10, 'lainnya', 0, 0, 'pabrik', '2026-03-26', '2026-03-26 13:19:57'),
(8, 3, 'keluar', 60, 'lainnya', 0, 0, 'Dibeli Orang langsung', '2026-03-30', '2026-03-30 15:50:10'),
(9, 7, 'keluar', 50, 'lainnya', 0, 0, 'di borong orang', '2026-03-30', '2026-03-30 15:51:16'),
(10, 5, 'keluar', 1, 'penjualan_app', 0, 0, 'Penjualan - Pesanan #000006', '2026-04-07', '2026-04-07 15:04:40'),
(11, 5, 'keluar', 1, 'penjualan_app', 0, 0, 'Penjualan - Pesanan #000008', '2026-04-10', '2026-04-10 05:06:22'),
(12, 1, 'keluar', 1, 'penjualan_app', 0, 0, 'Penjualan - Pesanan #000009', '2026-04-10', '2026-04-10 07:51:47'),
(13, 1, 'keluar', 1, 'penjualan_app', 0, 0, 'Penjualan - Pesanan #000009', '2026-04-10', '2026-04-10 07:51:52'),
(14, 5, 'keluar', 1, 'penjualan_app', 0, 0, 'Penjualan - Pesanan #000007', '2026-04-10', '2026-04-10 07:52:59'),
(17, 7, 'keluar', 12, 'lainnya', 0, 0, 'Dibeli Orang langsung', '2026-04-11', '2026-04-11 15:06:17'),
(18, 5, 'keluar', 47, 'lainnya', 0, 0, 'Dibeli Orang langsung', '2026-04-11', '2026-04-11 15:38:56'),
(19, 5, 'masuk', 100, 'lainnya', 0, 0, 'di borong orang', '2026-04-11', '2026-04-11 15:39:12'),
(20, 5, 'keluar', 200, 'lainnya', 0, 0, 'di borong orang', '2026-04-11', '2026-04-11 15:39:23'),
(25, 1, 'keluar', 2, 'penjualan_app', 100, 98, 'Penjualan - Pesanan #000013', '2026-04-27', '2026-04-27 08:28:27'),
(26, 1, 'masuk', 10, 'lainnya', 98, 108, 'dikirim dari gudang', '2026-04-29', '2026-04-29 10:58:25'),
(27, 10, 'keluar', 9, 'penjualan_app', 120, 111, 'Penjualan - Pesanan #000016', '2026-05-05', '2026-05-05 09:55:44'),
(28, 8, 'keluar', 2, 'penjualan_app', 15, 13, 'Penjualan - Pesanan #000015', '2026-05-09', '2026-05-09 12:27:19'),
(29, 9, 'keluar', 10, 'penjualan_app', 30, 20, 'Penjualan - Pesanan #000017', '2026-05-09', '2026-05-09 12:28:56'),
(30, 8, 'keluar', 2, 'penjualan_app', 13, 11, 'Penjualan - Pesanan #000014', '2026-05-09', '2026-05-09 12:34:53'),
(31, 8, 'keluar', 2, 'penjualan_app', 11, 9, 'Penjualan - Pesanan #000014', '2026-05-09', '2026-05-09 12:34:56'),
(32, 9, 'masuk', 600, 'pabrik', 20, 620, 'dari pabrik', '2026-05-10', '2026-05-10 03:13:32'),
(33, 1, 'masuk', 500, 'pabrik', 108, 608, 'dari pabrik sukomoro', '2026-05-10', '2026-05-10 03:13:55'),
(34, 9, 'keluar', 400, 'penjualan_app', 620, 220, 'Penjualan - Pesanan #000018', '2026-05-10', '2026-05-10 03:25:04'),
(35, 9, 'keluar', 200, 'penjualan_app', 220, 20, 'Penjualan - Pesanan #000020', '2026-05-10', '2026-05-10 03:29:21'),
(36, 1, 'keluar', 200, 'penjualan_app', 608, 408, 'Penjualan - Pesanan #000021', '2026-05-10', '2026-05-10 03:30:20'),
(37, 9, 'keluar', 200, 'penjualan_app', 20, 0, 'Penjualan - Pesanan #000020', '2026-05-10', '2026-05-10 03:39:41'),
(38, 1, 'keluar', 400, 'penjualan_app', 408, 8, 'Penjualan - Pesanan #000022', '2026-05-12', '2026-05-12 07:01:58'),
(39, 9, 'masuk', 100, 'pabrik', 0, 100, 'stok dari pabrik', '2026-05-18', '2026-05-18 07:40:36'),
(40, 9, 'masuk', 2000, 'pabrik', 100, 2100, 'Kiriman dari Sukomoro', '2026-06-05', '2026-06-05 07:05:22'),
(41, 1, 'masuk', 1500, 'pabrik', 8, 1508, 'dari pabrik sukomoro', '2026-06-03', '2026-06-05 07:05:54'),
(42, 7, 'masuk', 1000, 'pabrik', 1, 1001, 'dari pabrik sukomoro', '2026-06-03', '2026-06-05 07:19:08'),
(43, 8, 'masuk', 500, 'pabrik', 9, 509, 'dari pabrik sukomoro', '2026-06-02', '2026-06-05 07:19:22'),
(44, 10, 'masuk', 500, 'pabrik', 111, 611, 'dari pabrik sukomoro', '2026-06-01', '2026-06-05 07:19:42'),
(45, 11, 'masuk', 500, 'pabrik', 50, 550, 'dari pabrik sukomoro', '2026-06-05', '2026-06-05 07:19:54'),
(46, 9, 'keluar', 2, 'penjualan_app', 2100, 2098, 'Penjualan - Pesanan #000123', '2026-06-10', '2026-06-10 14:22:57'),
(47, 9, 'keluar', 3, 'penjualan_app', 854, 851, 'Penjualan - Pesanan #000124', '2026-06-12', '2026-06-12 11:44:57'),
(48, 9, 'keluar', 2, 'penjualan_app', 851, 849, 'Penjualan - Pesanan #000126', '2026-06-26', '2026-06-26 03:01:13'),
(49, 1, 'keluar', 2, 'penjualan_app', 650, 648, 'Penjualan - Pesanan #000126', '2026-06-26', '2026-06-26 03:01:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `metode_bayar` enum('cash','transfer') NOT NULL DEFAULT 'cash',
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `status` enum('menunggu','diproses','siap_dikirim','dikirim','selesai') NOT NULL DEFAULT 'menunggu',
  `rating` tinyint(4) DEFAULT NULL,
  `sopir_id` int(11) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `jam_pengantaran` varchar(30) DEFAULT NULL,
  `alamat_pengiriman` text DEFAULT NULL,
  `latitude_pengiriman` decimal(10,8) DEFAULT NULL,
  `longitude_pengiriman` decimal(11,8) DEFAULT NULL,
  `bukti_pengiriman` varchar(255) DEFAULT NULL,
  `catatan_pengiriman` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id`, `user_id`, `total`, `metode_bayar`, `bukti_transfer`, `status`, `rating`, `sopir_id`, `catatan`, `jam_pengantaran`, `alamat_pengiriman`, `latitude_pengiriman`, `longitude_pengiriman`, `bukti_pengiriman`, `catatan_pengiriman`, `created_at`, `updated_at`) VALUES
(1, 38, 846000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, '3MPJ+2M3, Jalan Bypass, Alang Alang Lebar, Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-03-01 03:00:00', '2026-06-09 17:26:03'),
(2, 27, 780000.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Jalan Letnan Jaimas, RW. 05, Kel. 24 ILIR, Kec. Bukit Kecil, Palembang, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-03-01 03:00:00', '2026-06-09 17:26:03'),
(3, 24, 715000.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Jalan Mayor Salim Batubara, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-03-01 04:00:00', '2026-06-09 17:26:03'),
(4, 24, 715000.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Jalan Mayor Salim Batubara, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-03-01 04:00:00', '2026-06-09 17:26:03'),
(5, 39, 1207500.00, 'cash', NULL, 'selesai', NULL, 3, NULL, NULL, 'Jl. D.I. Panjaitan, Bagus Kuning, Kec. Plaju, Kota Palembang, Sumatera Selatan 30268, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-03-02 03:00:00', '2026-06-09 17:26:03'),
(6, 29, 1010000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, '3M6Q+3JJ, Jl. Klp. Gading Raya, Talang Klp., Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-03-02 03:00:00', '2026-06-09 17:26:03'),
(7, 23, 1430000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Jalan Mayor Salim Batubara, Kemuning, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-03-02 04:00:00', '2026-06-09 17:26:03'),
(8, 25, 286000.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-03-02 04:00:00', '2026-06-09 17:26:03'),
(9, 38, 1010000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, '3MPJ+2M3, Jalan Bypass, Alang Alang Lebar, Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-03-03 03:00:00', '2026-06-09 17:26:03'),
(10, 30, 2020000.00, 'cash', NULL, 'selesai', NULL, 32, NULL, NULL, 'Jl. Mayor Salim Batubara No.95, Sekip Jaya, Kec. Kemuning, Kota Palembang, Sumatera Selatan 30127, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-03-03 03:00:00', '2026-06-09 17:26:03'),
(11, 19, 286000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Seberang Ulu I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-03-03 04:00:00', '2026-06-09 17:26:03'),
(12, 38, 363600.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, '3MPJ+2M3, Jalan Bypass, Alang Alang Lebar, Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-03-04 03:00:00', '2026-06-09 17:26:03'),
(13, 27, 780000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Jalan Letnan Jaimas, RW. 05, Kel. 24 ILIR, Kec. Bukit Kecil, Palembang, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-03-04 03:00:00', '2026-06-09 17:26:03'),
(14, 23, 1430000.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Jalan Mayor Salim Batubara, Kemuning, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-03-04 04:00:00', '2026-06-09 17:26:03'),
(15, 24, 2020000.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Jalan Mayor Salim Batubara, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-03-05 03:00:00', '2026-06-09 17:26:03'),
(16, 31, 5553000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Jl. Pangeran Ratu No.02 - 03 Blok B, 15 Ulu, Kecamatan Seberang Ulu I, Kota Palembang, Sumatera Selatan 30267, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-03-05 03:00:00', '2026-06-09 17:26:03'),
(17, 24, 715000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Jalan Mayor Salim Batubara, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-03-05 04:00:00', '2026-06-09 17:26:03'),
(18, 28, 352500.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Jalan Veteran, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-03-06 03:00:00', '2026-06-09 17:26:03'),
(19, 31, 5553000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Jl. Pangeran Ratu No.02 - 03 Blok B, 15 Ulu, Kecamatan Seberang Ulu I, Kota Palembang, Sumatera Selatan 30267, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-03-06 03:00:00', '2026-06-09 17:26:03'),
(20, 26, 572000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Jalan Veteran, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-03-06 04:00:00', '2026-06-09 17:26:03'),
(21, 38, 670000.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, '3MPJ+2M3, Jalan Bypass, Alang Alang Lebar, Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-03-07 03:00:00', '2026-06-09 17:26:03'),
(22, 39, 1010000.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Jl. D.I. Panjaitan, Bagus Kuning, Kec. Plaju, Kota Palembang, Sumatera Selatan 30268, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-03-07 03:00:00', '2026-06-09 17:26:03'),
(23, 22, 357500.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Kemuning, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-03-07 04:00:00', '2026-06-09 17:26:03'),
(24, 31, 5553000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Jl. Pangeran Ratu No.02 - 03 Blok B, 15 Ulu, Kecamatan Seberang Ulu I, Kota Palembang, Sumatera Selatan 30267, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-03-08 03:00:00', '2026-06-09 17:26:03'),
(25, 17, 42900.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Jalan Masjid Lama, RW 02 Kelurahan 17 Ilir, Kec Ilir Timur 1, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-03-08 04:00:00', '2026-06-09 17:26:03'),
(26, 38, 1010000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, '3MPJ+2M3, Jalan Bypass, Alang Alang Lebar, Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-03-09 03:00:00', '2026-06-09 17:26:03'),
(27, 22, 357500.00, 'cash', NULL, 'selesai', NULL, 32, NULL, NULL, 'Kemuning, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-03-09 04:00:00', '2026-06-09 17:26:03'),
(28, 28, 352500.00, 'cash', NULL, 'selesai', NULL, 32, NULL, NULL, 'Jalan Veteran, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-03-10 03:00:00', '2026-06-09 17:26:03'),
(29, 21, 357500.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Kemuning, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-03-10 04:00:00', '2026-06-09 17:26:03'),
(30, 38, 2020000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, '3MPJ+2M3, Jalan Bypass, Alang Alang Lebar, Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-03-11 03:00:00', '2026-06-09 17:26:03'),
(31, 26, 572000.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Jalan Veteran, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-03-11 04:00:00', '2026-06-09 17:26:03'),
(32, 39, 1010000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Jl. D.I. Panjaitan, Bagus Kuning, Kec. Plaju, Kota Palembang, Sumatera Selatan 30268, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-03-12 03:00:00', '2026-06-09 17:26:03'),
(33, 19, 286000.00, 'cash', NULL, 'selesai', NULL, 32, NULL, NULL, 'Seberang Ulu I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-03-12 04:00:00', '2026-06-09 17:26:03'),
(34, 16, 670000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Jalan Patinayah, RW. 01, Kel. 24 Ilir, Kec. Bukit Kecil, Palembang, Bukit Kecil, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-03-13 03:00:00', '2026-06-09 17:26:03'),
(35, 25, 286000.00, 'cash', NULL, 'selesai', NULL, 32, NULL, NULL, 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-03-13 04:00:00', '2026-06-09 17:26:03'),
(36, 39, 5553000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Jl. D.I. Panjaitan, Bagus Kuning, Kec. Plaju, Kota Palembang, Sumatera Selatan 30268, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-03-14 03:00:00', '2026-06-09 17:26:03'),
(37, 20, 1430000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-03-14 04:00:00', '2026-06-09 17:26:03'),
(38, 29, 1010000.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, '3M6Q+3JJ, Jl. Klp. Gading Raya, Talang Klp., Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-03-15 03:00:00', '2026-06-09 17:26:03'),
(39, 21, 357500.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Kemuning, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-03-15 04:00:00', '2026-06-09 17:26:03'),
(40, 39, 5553000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Jl. D.I. Panjaitan, Bagus Kuning, Kec. Plaju, Kota Palembang, Sumatera Selatan 30268, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-03-16 03:00:00', '2026-06-09 17:26:03'),
(41, 26, 572000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Jalan Veteran, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-03-16 04:00:00', '2026-06-09 17:26:03'),
(42, 24, 2020000.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Jalan Mayor Salim Batubara, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-03-17 03:00:00', '2026-06-09 17:26:03'),
(43, 25, 286000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-03-17 04:00:00', '2026-06-09 17:26:03'),
(44, 24, 2020000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Jalan Mayor Salim Batubara, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-03-18 03:00:00', '2026-06-09 17:26:03'),
(45, 26, 572000.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Jalan Veteran, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-03-18 04:00:00', '2026-06-09 17:26:03'),
(46, 24, 2020000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Jalan Mayor Salim Batubara, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-03-19 03:00:00', '2026-06-09 17:26:03'),
(47, 17, 42900.00, 'cash', NULL, 'selesai', NULL, 32, NULL, NULL, 'Jalan Masjid Lama, RW 02 Kelurahan 17 Ilir, Kec Ilir Timur 1, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-03-19 04:00:00', '2026-06-09 17:26:03'),
(48, 31, 5553000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Jl. Pangeran Ratu No.02 - 03 Blok B, 15 Ulu, Kecamatan Seberang Ulu I, Kota Palembang, Sumatera Selatan 30267, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-03-20 03:00:00', '2026-06-09 17:26:03'),
(49, 26, 572000.00, 'cash', NULL, 'selesai', NULL, 32, NULL, NULL, 'Jalan Veteran, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-03-20 04:00:00', '2026-06-09 17:26:03'),
(50, 31, 5553000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Jl. Pangeran Ratu No.02 - 03 Blok B, 15 Ulu, Kecamatan Seberang Ulu I, Kota Palembang, Sumatera Selatan 30267, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-03-21 03:00:00', '2026-06-09 17:26:03'),
(51, 18, 114400.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Gang Kaba, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-03-21 04:00:00', '2026-06-09 17:26:03'),
(52, 39, 670000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Jl. D.I. Panjaitan, Bagus Kuning, Kec. Plaju, Kota Palembang, Sumatera Selatan 30268, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-03-22 03:00:00', '2026-06-09 17:26:03'),
(53, 22, 357500.00, 'cash', NULL, 'selesai', NULL, 32, NULL, NULL, 'Kemuning, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-03-22 04:00:00', '2026-06-09 17:26:03'),
(54, 29, 1010000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, '3M6Q+3JJ, Jl. Klp. Gading Raya, Talang Klp., Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-03-23 03:00:00', '2026-06-09 17:26:03'),
(55, 20, 1430000.00, 'cash', NULL, 'selesai', NULL, 3, NULL, NULL, 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-03-23 04:00:00', '2026-06-09 17:26:03'),
(56, 16, 670000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Jalan Patinayah, RW. 01, Kel. 24 Ilir, Kec. Bukit Kecil, Palembang, Bukit Kecil, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-03-24 03:00:00', '2026-06-09 17:26:03'),
(57, 18, 114400.00, 'cash', NULL, 'selesai', NULL, 3, NULL, NULL, 'Gang Kaba, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-03-24 04:00:00', '2026-06-09 17:26:03'),
(58, 39, 352500.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Jl. D.I. Panjaitan, Bagus Kuning, Kec. Plaju, Kota Palembang, Sumatera Selatan 30268, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-03-25 03:00:00', '2026-06-09 17:26:03'),
(59, 17, 42900.00, 'cash', NULL, 'selesai', NULL, 3, NULL, NULL, 'Jalan Masjid Lama, RW 02 Kelurahan 17 Ilir, Kec Ilir Timur 1, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-03-25 04:00:00', '2026-06-09 17:26:03'),
(60, 31, 5553000.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Jl. Pangeran Ratu No.02 - 03 Blok B, 15 Ulu, Kecamatan Seberang Ulu I, Kota Palembang, Sumatera Selatan 30267, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-03-26 03:00:00', '2026-06-09 17:26:03'),
(61, 24, 715000.00, 'cash', NULL, 'selesai', NULL, 32, NULL, NULL, 'Jalan Mayor Salim Batubara, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-03-26 04:00:00', '2026-06-09 17:26:03'),
(62, 29, 1010000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, '3M6Q+3JJ, Jl. Klp. Gading Raya, Talang Klp., Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-03-27 03:00:00', '2026-06-09 17:26:03'),
(63, 19, 286000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Seberang Ulu I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-03-27 04:00:00', '2026-06-09 17:26:03'),
(64, 24, 2020000.00, 'cash', NULL, 'selesai', NULL, 32, NULL, NULL, 'Jalan Mayor Salim Batubara, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-03-28 03:00:00', '2026-06-09 17:26:03'),
(65, 21, 357500.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Kemuning, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-03-28 04:00:00', '2026-06-09 17:26:03'),
(66, 40, 1010000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, '3JMP+QJ4, Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-04-01 03:00:00', '2026-06-09 17:26:03'),
(67, 14, 128700.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Jalan Dempo Luar, RW 03 Kelurahan 15 Ilir, Kec Ilir Timur 1, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-04-01 04:00:00', '2026-06-09 17:26:03'),
(68, 31, 5553000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Jl. Pangeran Ratu No.02 - 03 Blok B, 15 Ulu, Kecamatan Seberang Ulu I, Kota Palembang, Sumatera Selatan 30267, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-04-02 03:00:00', '2026-06-09 17:26:03'),
(69, 18, 114400.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Gang Kaba, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-04-02 04:00:00', '2026-06-09 17:26:03'),
(70, 27, 780000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Jalan Letnan Jaimas, RW. 05, Kel. 24 ILIR, Kec. Bukit Kecil, Palembang, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-04-03 03:00:00', '2026-06-09 17:26:03'),
(71, 17, 42900.00, 'cash', NULL, 'selesai', NULL, 3, NULL, NULL, 'Jalan Masjid Lama, RW 02 Kelurahan 17 Ilir, Kec Ilir Timur 1, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-04-03 04:00:00', '2026-06-09 17:26:03'),
(72, 27, 780000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Jalan Letnan Jaimas, RW. 05, Kel. 24 ILIR, Kec. Bukit Kecil, Palembang, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-04-04 03:00:00', '2026-06-09 17:26:03'),
(73, 25, 286000.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-04-04 04:00:00', '2026-06-09 17:26:03'),
(74, 28, 352500.00, 'cash', NULL, 'selesai', NULL, 3, NULL, NULL, 'Jalan Veteran, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-04-05 03:00:00', '2026-06-09 17:26:03'),
(75, 20, 1430000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-04-05 04:00:00', '2026-06-09 17:26:03'),
(76, 24, 2020000.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Jalan Mayor Salim Batubara, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-04-06 03:00:00', '2026-06-09 17:26:03'),
(77, 20, 1430000.00, 'cash', NULL, 'selesai', NULL, 32, NULL, NULL, 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-04-06 04:00:00', '2026-06-09 17:26:03'),
(78, 24, 2020000.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Jalan Mayor Salim Batubara, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-04-07 03:00:00', '2026-06-09 17:26:03'),
(79, 19, 286000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Seberang Ulu I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-04-07 04:00:00', '2026-06-09 17:26:03'),
(80, 30, 2020000.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Jl. Mayor Salim Batubara No.95, Sekip Jaya, Kec. Kemuning, Kota Palembang, Sumatera Selatan 30127, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-04-08 03:00:00', '2026-06-09 17:26:03'),
(81, 18, 114400.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Gang Kaba, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-04-08 04:00:00', '2026-06-09 17:26:03'),
(82, 30, 2020000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Jl. Mayor Salim Batubara No.95, Sekip Jaya, Kec. Kemuning, Kota Palembang, Sumatera Selatan 30127, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-04-09 03:00:00', '2026-06-09 17:26:03'),
(83, 14, 128700.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Jalan Dempo Luar, RW 03 Kelurahan 15 Ilir, Kec Ilir Timur 1, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-04-09 04:00:00', '2026-06-09 17:26:03'),
(84, 40, 1010000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, '3JMP+QJ4, Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-04-10 03:00:00', '2026-06-09 17:26:03'),
(85, 20, 1430000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-04-10 04:00:00', '2026-06-09 17:26:03'),
(86, 27, 780000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Jalan Letnan Jaimas, RW. 05, Kel. 24 ILIR, Kec. Bukit Kecil, Palembang, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-04-11 03:00:00', '2026-06-09 17:26:03'),
(87, 25, 286000.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-04-11 04:00:00', '2026-06-09 17:26:03'),
(88, 16, 670000.00, 'cash', NULL, 'selesai', NULL, 32, NULL, NULL, 'Jalan Patinayah, RW. 01, Kel. 24 Ilir, Kec. Bukit Kecil, Palembang, Bukit Kecil, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-04-12 03:00:00', '2026-06-09 17:26:03'),
(89, 14, 128700.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Jalan Dempo Luar, RW 03 Kelurahan 15 Ilir, Kec Ilir Timur 1, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-04-12 04:00:00', '2026-06-09 17:26:03'),
(90, 16, 670000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Jalan Patinayah, RW. 01, Kel. 24 Ilir, Kec. Bukit Kecil, Palembang, Bukit Kecil, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-04-13 03:00:00', '2026-06-09 17:26:03'),
(91, 16, 214500.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Jalan Patinayah, RW. 01, Kel. 24 Ilir, Kec. Bukit Kecil, Palembang, Bukit Kecil, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-04-13 04:00:00', '2026-06-09 17:26:03'),
(92, 28, 352500.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Jalan Veteran, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-04-14 03:00:00', '2026-06-09 17:26:03'),
(93, 19, 286000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Seberang Ulu I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-04-14 04:00:00', '2026-06-09 17:26:03'),
(94, 27, 780000.00, 'cash', NULL, 'selesai', NULL, 3, NULL, NULL, 'Jalan Letnan Jaimas, RW. 05, Kel. 24 ILIR, Kec. Bukit Kecil, Palembang, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-04-15 03:00:00', '2026-06-09 17:26:03'),
(95, 25, 286000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-04-15 04:00:00', '2026-06-09 17:26:03'),
(96, 40, 670000.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, '3JMP+QJ4, Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-04-16 03:00:00', '2026-06-09 17:26:03'),
(97, 29, 1010000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, '3M6Q+3JJ, Jl. Klp. Gading Raya, Talang Klp., Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-04-17 03:00:00', '2026-06-09 17:26:03'),
(98, 40, 352500.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, '3JMP+QJ4, Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-04-18 03:00:00', '2026-06-09 17:26:03'),
(99, 40, 670000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, '3JMP+QJ4, Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-04-19 03:00:00', '2026-06-09 17:26:03'),
(100, 40, 2020000.00, 'cash', NULL, 'selesai', NULL, 32, NULL, NULL, '3JMP+QJ4, Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-04-20 03:00:00', '2026-06-09 17:26:03'),
(101, 41, 1010000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-05-01 03:00:00', '2026-06-09 17:26:03'),
(102, 17, 42900.00, 'cash', NULL, 'selesai', NULL, 3, NULL, NULL, 'Jalan Masjid Lama, RW 02 Kelurahan 17 Ilir, Kec Ilir Timur 1, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-05-01 04:00:00', '2026-06-09 17:26:03'),
(103, 41, 670000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-05-02 03:00:00', '2026-06-09 17:26:03'),
(104, 23, 1430000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Jalan Mayor Salim Batubara, Kemuning, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-05-02 04:00:00', '2026-06-09 17:26:03'),
(105, 41, 5553000.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-05-03 03:00:00', '2026-06-09 17:26:03'),
(106, 26, 572000.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Jalan Veteran, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-05-03 04:00:00', '2026-06-09 17:26:03'),
(107, 30, 2020000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Jl. Mayor Salim Batubara No.95, Sekip Jaya, Kec. Kemuning, Kota Palembang, Sumatera Selatan 30127, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-05-04 03:00:00', '2026-06-09 17:26:03'),
(108, 23, 1430000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Jalan Mayor Salim Batubara, Kemuning, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-05-04 04:00:00', '2026-06-09 17:26:03'),
(109, 29, 1010000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, '3M6Q+3JJ, Jl. Klp. Gading Raya, Talang Klp., Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-05-05 03:00:00', '2026-06-09 17:26:03'),
(110, 25, 286000.00, 'cash', NULL, 'selesai', NULL, 3, NULL, NULL, 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-05-05 04:00:00', '2026-06-09 17:26:03'),
(111, 41, 352500.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-05-06 03:00:00', '2026-06-09 17:26:03'),
(112, 24, 715000.00, 'cash', NULL, 'selesai', NULL, 32, NULL, NULL, 'Jalan Mayor Salim Batubara, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-05-06 04:00:00', '2026-06-09 17:26:03'),
(113, 29, 1010000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, '3M6Q+3JJ, Jl. Klp. Gading Raya, Talang Klp., Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-05-07 03:00:00', '2026-06-09 17:26:03'),
(114, 20, 1430000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-05-07 04:00:00', '2026-06-09 17:26:03'),
(115, 27, 780000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Jalan Letnan Jaimas, RW. 05, Kel. 24 ILIR, Kec. Bukit Kecil, Palembang, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-05-08 03:00:00', '2026-06-09 17:26:03'),
(116, 22, 357500.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Kemuning, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman4.jpeg', NULL, '2026-05-08 04:00:00', '2026-06-09 17:26:03'),
(117, 16, 670000.00, 'cash', NULL, 'selesai', NULL, 34, NULL, NULL, 'Jalan Patinayah, RW. 01, Kel. 24 Ilir, Kec. Bukit Kecil, Palembang, Bukit Kecil, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman6.jpeg', NULL, '2026-05-09 03:00:00', '2026-06-09 17:26:03'),
(118, 41, 2020000.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-05-10 03:00:00', '2026-06-09 17:26:03'),
(119, 41, 1010000.00, 'cash', NULL, 'selesai', NULL, 37, NULL, NULL, 'Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman3.jpeg', NULL, '2026-05-11 03:00:00', '2026-06-09 17:26:03'),
(120, 27, 780000.00, 'cash', NULL, 'selesai', NULL, 33, NULL, NULL, 'Jalan Letnan Jaimas, RW. 05, Kel. 24 ILIR, Kec. Bukit Kecil, Palembang, Ilir Timur I, Palembang, Sumatera Selatan', NULL, NULL, 'bukti_pengiriman/pengiriman1.jpeg', NULL, '2026-05-12 03:00:00', '2026-06-09 17:26:03'),
(121, 41, 2020000.00, 'cash', NULL, 'selesai', NULL, 36, NULL, NULL, 'Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman2.jpeg', NULL, '2026-05-13 03:00:00', '2026-06-09 17:26:03'),
(122, 30, 2020000.00, 'cash', NULL, 'selesai', NULL, 35, NULL, NULL, 'Jl. Mayor Salim Batubara No.95, Sekip Jaya, Kec. Kemuning, Kota Palembang, Sumatera Selatan 30127, Indonesia', NULL, NULL, 'bukti_pengiriman/pengiriman5.jpeg', NULL, '2026-05-14 03:00:00', '2026-06-09 17:26:03'),
(123, 14, 70500.00, 'cash', NULL, 'selesai', NULL, 37, 'letakkan di pagar', NULL, 'Jalan Dempo Luar, RW 03 Kelurahan 15 Ilir, Kec Ilir Timur 1, Ilir Timur I, Palembang, Sumatera Selatan', -2.98139893, 104.76271746, 'bukti_pengiriman/6a2973418a28c_1781101377.jpeg', 'pesanan selesai diantar', '2026-06-10 13:48:57', '2026-06-10 14:22:57'),
(124, 42, 112500.00, 'cash', NULL, 'selesai', NULL, 3, '', NULL, 'Jl. Srijaya Negara, Bukit Lama, Kec. Ilir Bar. I, Kota Palembang, Sumatera Selatan 30128, Indonesia', -2.98403170, 104.73265980, 'bukti_pengiriman/6a2bf13959416_1781264697.jpeg', 'barang sudah diantar', '2026-06-12 11:40:34', '2026-06-12 11:44:57'),
(125, 16, 40400.00, 'cash', NULL, 'dikirim', NULL, 37, 'titip di gerbang', NULL, 'Jalan Patinayah, RW. 01, Kel. 24 Ilir, Kec. Bukit Kecil, Palembang, Bukit Kecil, Palembang, Sumatera Selatan', -2.98646670, 104.75489610, NULL, NULL, '2026-06-24 01:27:46', '2026-06-24 01:34:19'),
(126, 16, 137500.00, 'cash', NULL, 'selesai', NULL, 32, '', NULL, 'Jalan Patinayah, RW. 01, Kel. 24 Ilir, Kec. Bukit Kecil, Palembang, Bukit Kecil, Palembang, Sumatera Selatan', -2.98646670, 104.75489610, 'bukti_pengiriman/6a3deb799eb9f_1782442873.jpeg', 'pengiriman selesai', '2026-06-24 02:53:10', '2026-06-26 03:01:13'),
(127, 41, 81300.00, 'cash', NULL, 'dikirim', NULL, 3, 'antrer depan lorong', NULL, 'Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', -2.91589290, 104.62877520, NULL, NULL, '2026-06-25 15:48:46', '2026-06-26 02:00:28'),
(128, 41, 105750.00, 'cash', NULL, 'dikirim', NULL, 3, '', NULL, 'Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', -2.91589290, 104.62877520, NULL, NULL, '2026-06-26 02:57:16', '2026-06-30 12:41:50'),
(132, 44, 35750.00, 'cash', NULL, 'dikirim', NULL, 32, '', '🌤️ Sore (13:00 – 16:00)', '3M4P+622, Jl. Talang Klp., Talang Klp., Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30152, Indonesia', -2.94449320, 104.68502730, NULL, NULL, '2026-06-27 17:05:24', '2026-06-27 17:17:29'),
(133, 16, 35250.00, 'cash', NULL, 'dikirim', NULL, 37, '', 'Siang (10:00 – 13:00)', 'Jalan Patinayah, RW. 01, Kel. 24 Ilir, Kec. Bukit Kecil, Palembang, Bukit Kecil, Palembang, Sumatera Selatan', -2.98646670, 104.75489610, NULL, NULL, '2026-06-30 09:30:31', '2026-06-30 12:36:26'),
(134, 16, 105750.00, 'cash', NULL, 'menunggu', NULL, NULL, '', 'Siang (10:00 – 13:00)', 'Jalan Patinayah, RW. 01, Kel. 24 Ilir, Kec. Bukit Kecil, Palembang, Bukit Kecil, Palembang, Sumatera Selatan', -2.98646670, 104.75489610, NULL, NULL, '2026-07-01 05:40:53', '2026-07-01 05:40:53'),
(135, 41, 105750.00, 'cash', NULL, 'dikirim', NULL, 36, '', 'Siang (10:00 – 13:00)', 'Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', -2.91589290, 104.62877520, NULL, NULL, '2026-07-01 05:44:59', '2026-07-01 05:47:28');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga_toko` decimal(12,2) NOT NULL DEFAULT 0.00,
  `harga_umum` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stok` int(11) NOT NULL DEFAULT 0,
  `satuan` varchar(50) DEFAULT 'pcs',
  `foto` varchar(255) DEFAULT NULL,
  `foto2` varchar(255) DEFAULT NULL,
  `foto3` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id`, `nama`, `deskripsi`, `harga_toko`, `harga_umum`, `stok`, `satuan`, `foto`, `foto2`, `foto3`, `created_at`) VALUES
(1, 'ALFA ONE BOTOL 330ml', 'Air mineral Alfa One kemasan botol 330ml Isi 24 pcs', 33500.00, 35750.00, 648, 'dus', 'produk/69ea342e40e74_1776956462.jpg', 'produk/69ea342e41381_1776956462.jpg', 'produk/69ea342e418f0_1776956462.jpg', '2026-02-20 07:14:30'),
(7, 'ALFA ONE BOTOL 55O ML', 'Air mineral Alfa One kemasan botol 55O ML  isi 24 pcs', 34500.00, 36750.00, 700, 'dus', 'produk/69ea34662bcf9_1776956518.jpg', 'produk/69ea34662c18f_1776956518.jpg', 'produk/69ea34662c5d1_1776956518.jpg', '2026-02-20 07:14:30'),
(8, 'ALFA ONE CUP 200 ML', 'Alfa One Kemasan cup 200 ML isi 48 pcs', 20200.00, 21500.00, 509, 'DUS', 'produk/69ea34010f4f1_1776956417.jpg', 'produk/69ea34010f86d_1776956417.jpg', 'produk/69ea34010fce7_1776956417.jpg', '2026-04-23 15:00:17'),
(9, 'ALFA ONE BOTOL 1500 ML', 'Alfa One Botol 1500 ML isi 12 pcs', 35250.00, 37500.00, 849, 'DUS', 'produk/69f61724bbc7b_1777735460.jpg', 'produk/69f61724bc67f_1777735460.jpg', 'produk/69f61724bf199_1777735460.jpg', '2026-04-23 15:05:07'),
(10, 'ALFA ONE REFILL GALON 19 LITER', 'Alfa One Galon Refill 19 Liter  (Isi nya saja)', 14300.00, 17000.00, 611, 'GALON', 'produk/69ee2b0f40253_1777216271.jpg', 'produk/69ee2b0f40a93_1777216271.jpg', 'produk/69ee2b0f40ea6_1777216271.jpg', '2026-04-23 15:09:52'),
(11, 'Galon + Isi Air 19 Liter', 'Beli Galon + Isi Air 19 Liter', 54300.00, 57000.00, 550, 'GALON', 'produk/69ee2b3493342_1777216308.jpg', 'produk/69ee2b349369b_1777216308.jpg', 'produk/69ee2b3493934_1777216308.jpg', '2026-04-23 15:11:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ritase`
--

CREATE TABLE `ritase` (
  `id` int(11) NOT NULL,
  `sopir_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jumlah` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `ritase`
--

INSERT INTO `ritase` (`id`, `sopir_id`, `tanggal`, `jumlah`, `last_updated`) VALUES
(1, 3, '2026-05-09', 1, '2026-05-09 12:54:54'),
(2, 3, '2026-05-10', 3, '2026-05-10 03:39:45'),
(5, 3, '2026-05-12', 1, '2026-05-12 07:05:38'),
(6, 3, '2026-06-10', 1, '2026-06-09 17:39:54'),
(7, 32, '2026-06-26', 1, '2026-06-26 03:08:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi_pesanan`
--

CREATE TABLE `transaksi_pesanan` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `harga_satuan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `transaksi_pesanan`
--

INSERT INTO `transaksi_pesanan` (`id`, `pesanan_id`, `produk_id`, `jumlah`, `harga_satuan`, `subtotal`) VALUES
(1, 1, 9, 24, 35250.00, 846000.00),
(2, 2, 8, 30, 20200.00, 606000.00),
(3, 2, 7, 3, 34500.00, 103500.00),
(4, 2, 9, 2, 35250.00, 70500.00),
(5, 3, 10, 50, 14300.00, 715000.00),
(6, 4, 10, 50, 14300.00, 715000.00),
(7, 5, 7, 35, 34500.00, 1207500.00),
(8, 6, 8, 50, 20200.00, 1010000.00),
(9, 7, 10, 100, 14300.00, 1430000.00),
(10, 8, 10, 20, 14300.00, 286000.00),
(11, 9, 8, 50, 20200.00, 1010000.00),
(12, 10, 8, 100, 20200.00, 2020000.00),
(13, 11, 10, 20, 14300.00, 286000.00),
(14, 12, 8, 18, 20200.00, 363600.00),
(15, 13, 8, 30, 20200.00, 606000.00),
(16, 13, 7, 3, 34500.00, 103500.00),
(17, 13, 9, 2, 35250.00, 70500.00),
(18, 14, 10, 100, 14300.00, 1430000.00),
(19, 15, 8, 100, 20200.00, 2020000.00),
(20, 16, 8, 15, 20200.00, 303000.00),
(21, 16, 7, 50, 34500.00, 1725000.00),
(22, 16, 9, 100, 35250.00, 3525000.00),
(23, 17, 10, 50, 14300.00, 715000.00),
(24, 18, 9, 10, 35250.00, 352500.00),
(25, 19, 8, 15, 20200.00, 303000.00),
(26, 19, 7, 50, 34500.00, 1725000.00),
(27, 19, 9, 100, 35250.00, 3525000.00),
(28, 20, 10, 40, 14300.00, 572000.00),
(29, 21, 1, 20, 33500.00, 670000.00),
(30, 22, 8, 50, 20200.00, 1010000.00),
(31, 23, 10, 25, 14300.00, 357500.00),
(32, 24, 8, 15, 20200.00, 303000.00),
(33, 24, 7, 50, 34500.00, 1725000.00),
(34, 24, 9, 100, 35250.00, 3525000.00),
(35, 25, 10, 3, 14300.00, 42900.00),
(36, 26, 8, 50, 20200.00, 1010000.00),
(37, 27, 10, 25, 14300.00, 357500.00),
(38, 28, 9, 10, 35250.00, 352500.00),
(39, 29, 10, 25, 14300.00, 357500.00),
(40, 30, 8, 100, 20200.00, 2020000.00),
(41, 31, 10, 40, 14300.00, 572000.00),
(42, 32, 8, 50, 20200.00, 1010000.00),
(43, 33, 10, 20, 14300.00, 286000.00),
(44, 34, 1, 20, 33500.00, 670000.00),
(45, 35, 10, 20, 14300.00, 286000.00),
(46, 36, 8, 15, 20200.00, 303000.00),
(47, 36, 7, 50, 34500.00, 1725000.00),
(48, 36, 9, 100, 35250.00, 3525000.00),
(49, 37, 10, 100, 14300.00, 1430000.00),
(50, 38, 8, 50, 20200.00, 1010000.00),
(51, 39, 10, 25, 14300.00, 357500.00),
(52, 40, 8, 15, 20200.00, 303000.00),
(53, 40, 7, 50, 34500.00, 1725000.00),
(54, 40, 9, 100, 35250.00, 3525000.00),
(55, 41, 10, 40, 14300.00, 572000.00),
(56, 42, 8, 100, 20200.00, 2020000.00),
(57, 43, 10, 20, 14300.00, 286000.00),
(58, 44, 8, 100, 20200.00, 2020000.00),
(59, 45, 10, 40, 14300.00, 572000.00),
(60, 46, 8, 100, 20200.00, 2020000.00),
(61, 47, 10, 3, 14300.00, 42900.00),
(62, 48, 8, 15, 20200.00, 303000.00),
(63, 48, 7, 50, 34500.00, 1725000.00),
(64, 48, 9, 100, 35250.00, 3525000.00),
(65, 49, 10, 40, 14300.00, 572000.00),
(66, 50, 8, 15, 20200.00, 303000.00),
(67, 50, 7, 50, 34500.00, 1725000.00),
(68, 50, 9, 100, 35250.00, 3525000.00),
(69, 51, 10, 8, 14300.00, 114400.00),
(70, 52, 1, 20, 33500.00, 670000.00),
(71, 53, 10, 25, 14300.00, 357500.00),
(72, 54, 8, 50, 20200.00, 1010000.00),
(73, 55, 10, 100, 14300.00, 1430000.00),
(74, 56, 1, 20, 33500.00, 670000.00),
(75, 57, 10, 8, 14300.00, 114400.00),
(76, 58, 9, 10, 35250.00, 352500.00),
(77, 59, 10, 3, 14300.00, 42900.00),
(78, 60, 8, 15, 20200.00, 303000.00),
(79, 60, 7, 50, 34500.00, 1725000.00),
(80, 60, 9, 100, 35250.00, 3525000.00),
(81, 61, 10, 50, 14300.00, 715000.00),
(82, 62, 8, 50, 20200.00, 1010000.00),
(83, 63, 10, 20, 14300.00, 286000.00),
(84, 64, 8, 100, 20200.00, 2020000.00),
(85, 65, 10, 25, 14300.00, 357500.00),
(86, 66, 8, 50, 20200.00, 1010000.00),
(87, 67, 10, 9, 14300.00, 128700.00),
(88, 68, 8, 15, 20200.00, 303000.00),
(89, 68, 7, 50, 34500.00, 1725000.00),
(90, 68, 9, 100, 35250.00, 3525000.00),
(91, 69, 10, 8, 14300.00, 114400.00),
(92, 70, 8, 30, 20200.00, 606000.00),
(93, 70, 7, 3, 34500.00, 103500.00),
(94, 70, 9, 2, 35250.00, 70500.00),
(95, 71, 10, 3, 14300.00, 42900.00),
(96, 72, 8, 30, 20200.00, 606000.00),
(97, 72, 7, 3, 34500.00, 103500.00),
(98, 72, 9, 2, 35250.00, 70500.00),
(99, 73, 10, 20, 14300.00, 286000.00),
(100, 74, 9, 10, 35250.00, 352500.00),
(101, 75, 10, 100, 14300.00, 1430000.00),
(102, 76, 8, 100, 20200.00, 2020000.00),
(103, 77, 10, 100, 14300.00, 1430000.00),
(104, 78, 8, 100, 20200.00, 2020000.00),
(105, 79, 10, 20, 14300.00, 286000.00),
(106, 80, 8, 100, 20200.00, 2020000.00),
(107, 81, 10, 8, 14300.00, 114400.00),
(108, 82, 8, 100, 20200.00, 2020000.00),
(109, 83, 10, 9, 14300.00, 128700.00),
(110, 84, 8, 50, 20200.00, 1010000.00),
(111, 85, 10, 100, 14300.00, 1430000.00),
(112, 86, 8, 30, 20200.00, 606000.00),
(113, 86, 7, 3, 34500.00, 103500.00),
(114, 86, 9, 2, 35250.00, 70500.00),
(115, 87, 10, 20, 14300.00, 286000.00),
(116, 88, 1, 20, 33500.00, 670000.00),
(117, 89, 10, 9, 14300.00, 128700.00),
(118, 90, 1, 20, 33500.00, 670000.00),
(119, 91, 10, 15, 14300.00, 214500.00),
(120, 92, 9, 10, 35250.00, 352500.00),
(121, 93, 10, 20, 14300.00, 286000.00),
(122, 94, 8, 30, 20200.00, 606000.00),
(123, 94, 7, 3, 34500.00, 103500.00),
(124, 94, 9, 2, 35250.00, 70500.00),
(125, 95, 10, 20, 14300.00, 286000.00),
(126, 96, 1, 20, 33500.00, 670000.00),
(127, 97, 8, 50, 20200.00, 1010000.00),
(128, 98, 9, 10, 35250.00, 352500.00),
(129, 99, 1, 20, 33500.00, 670000.00),
(130, 100, 8, 100, 20200.00, 2020000.00),
(131, 101, 8, 50, 20200.00, 1010000.00),
(132, 102, 10, 3, 14300.00, 42900.00),
(133, 103, 1, 20, 33500.00, 670000.00),
(134, 104, 10, 100, 14300.00, 1430000.00),
(135, 105, 8, 15, 20200.00, 303000.00),
(136, 105, 7, 50, 34500.00, 1725000.00),
(137, 105, 9, 100, 35250.00, 3525000.00),
(138, 106, 10, 40, 14300.00, 572000.00),
(139, 107, 8, 100, 20200.00, 2020000.00),
(140, 108, 10, 100, 14300.00, 1430000.00),
(141, 109, 8, 50, 20200.00, 1010000.00),
(142, 110, 10, 20, 14300.00, 286000.00),
(143, 111, 9, 10, 35250.00, 352500.00),
(144, 112, 10, 50, 14300.00, 715000.00),
(145, 113, 8, 50, 20200.00, 1010000.00),
(146, 114, 10, 100, 14300.00, 1430000.00),
(147, 115, 8, 30, 20200.00, 606000.00),
(148, 115, 7, 3, 34500.00, 103500.00),
(149, 115, 9, 2, 35250.00, 70500.00),
(150, 116, 10, 25, 14300.00, 357500.00),
(151, 117, 1, 20, 33500.00, 670000.00),
(152, 118, 8, 100, 20200.00, 2020000.00),
(153, 119, 8, 50, 20200.00, 1010000.00),
(154, 120, 8, 30, 20200.00, 606000.00),
(155, 120, 7, 3, 34500.00, 103500.00),
(156, 120, 9, 2, 35250.00, 70500.00),
(157, 121, 8, 100, 20200.00, 2020000.00),
(158, 122, 8, 100, 20200.00, 2020000.00),
(159, 123, 9, 2, 35250.00, 70500.00),
(160, 124, 9, 3, 37500.00, 112500.00),
(161, 125, 8, 2, 20200.00, 40400.00),
(162, 126, 9, 2, 35250.00, 70500.00),
(163, 126, 1, 2, 33500.00, 67000.00),
(164, 127, 1, 2, 33500.00, 67000.00),
(165, 127, 10, 1, 14300.00, 14300.00),
(166, 128, 9, 3, 35250.00, 105750.00),
(170, 132, 1, 1, 35750.00, 35750.00),
(171, 133, 9, 1, 35250.00, 35250.00),
(172, 134, 9, 3, 35250.00, 105750.00),
(173, 135, 9, 3, 35250.00, 105750.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `no_wa` varchar(20) DEFAULT NULL,
  `role` enum('konsumen','admin_barang','admin_penjualan','sopir','super_admin') NOT NULL DEFAULT 'konsumen',
  `max_dus` int(11) DEFAULT 0,
  `max_galon` int(11) DEFAULT 0,
  `rute_asal` varchar(100) DEFAULT NULL,
  `rute_tujuan` varchar(100) DEFAULT NULL,
  `role_customer` enum('toko','umum') DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `kartu_member` varchar(255) DEFAULT NULL,
  `surat_usaha` varchar(255) DEFAULT NULL,
  `halaman_depan_toko` varchar(255) DEFAULT NULL,
  `status_verifikasi` enum('pending','verified','rejected') DEFAULT 'verified',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `telepon`, `no_wa`, `role`, `max_dus`, `max_galon`, `rute_asal`, `rute_tujuan`, `role_customer`, `alamat`, `foto`, `latitude`, `longitude`, `kartu_member`, `surat_usaha`, `halaman_depan_toko`, `status_verifikasi`, `created_at`) VALUES
(1, 'Devi Admin Barang', 'deviapriani0201@gmail.com', '$2y$10$JXZeM7d0FJlPN9DXbJGhP.si/RMEbiBRw5QhoLrwnQdONSMuaWEIK', '081278127486', NULL, 'admin_barang', 0, 0, NULL, NULL, NULL, 'Pakjo Irigasi', 'profiles/6a015bb438d9b_1778473908.jpg', NULL, NULL, NULL, NULL, NULL, 'verified', '2026-02-20 07:14:29'),
(2, 'Rozanatun Admin Penjualan', 'atunadminpenjualan@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081367007577', NULL, 'admin_penjualan', 0, 0, NULL, NULL, NULL, 'Sukomoro KM18', NULL, NULL, NULL, NULL, NULL, NULL, 'verified', '2026-02-20 07:14:29'),
(3, 'Alfin', 'alfinial096@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567892', NULL, 'sopir', 350, 200, NULL, NULL, NULL, 'kolonel atmo', 'profiles/6a0d407ac6a00_1779253370.jpg', -2.94449487, 104.68487143, NULL, NULL, NULL, 'verified', '2026-02-20 07:14:29'),
(4, 'Farhan', 'padli@gmail.com', '$2y$10$bZOFvMn6QRZcCsWhKD.qG.O.f05MMdwpbjJXVRZLH0ON4TTIpVCmG', '0812812831', NULL, 'konsumen', 0, 0, NULL, NULL, 'umum', 'Jalan Lebak Rejo Lorong langgar rt. 0019 rw.006', 'profiles/69f6148716a1d_1777734791.jpeg', -2.96168964, 104.75350145, NULL, NULL, NULL, 'verified', '2026-02-20 08:40:33'),
(6, 'Super Admin Alfa One', 'admin@alfaone.com', '$2y$10$AG52CUFGuQvmBCGdr6ZL5eYdwrCvPtKYW5s4zwvTB2STeONoKlRSC', '', NULL, 'super_admin', 0, 0, NULL, NULL, NULL, NULL, 'profiles/6a0d409a3f92a_1779253402.jpg', NULL, NULL, NULL, NULL, NULL, 'verified', '2026-03-18 05:07:16'),
(12, 'Sri Mulyati', 'srimulyatii@gmail.com', '$2y$10$3cOM3edXPciR9NesU9p8DO3VKJc3LH0VxIusSx5tg4LHhf3bH/9Fu', '08123818232', NULL, 'konsumen', 0, 0, NULL, NULL, 'umum', 'Sekip Pahlawan lebak mulyo', NULL, NULL, NULL, NULL, NULL, NULL, 'verified', '2026-04-07 15:01:27'),
(13, 'Farhan Pratama', 'pratamapaan@gmail.com', '$2y$10$xqnDfHlM3.gSSMsJtGSb1u2CGvjKfuaZ97dLAjJurN/rAtagkexYO', '081277058901', NULL, 'konsumen', 0, 0, NULL, NULL, 'umum', 'JL. MAY MAHIDIN LR LANGGAR NO. 1190 Rt. 019 Rw. 006', NULL, -2.94450723, 104.68492662, NULL, NULL, NULL, 'verified', '2026-05-02 15:11:37'),
(14, 'Jus Atun', 'JusAtun@gmail.com', '$2y$10$Pgw/eTF7seWr7G9yM0O1XO9sB9GsGJLUCfh7SSQXD4FNLZ0sVIc7C', '081517030688', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Jalan Dempo Luar, RW 03 Kelurahan 15 Ilir, Kec Ilir Timur 1, Ilir Timur I, Palembang, Sumatera Selatan', 'profiles/6a0d1521ad67a_1779242273.jpg', -2.98139893, 104.76271746, 'kartu_member/69f9b629ee19f_1777972777.png', 'surat_usaha/69f9b629ee669_1777972777.png', 'foto_toko/69f9b629eee0a_1777972777.jpg', 'verified', '2026-05-05 09:19:38'),
(16, 'KEDAI PUTRI', 'KedaiPutri@gmail.com', '$2y$10$C3zvWVEvSFJ/auev8T6t4OKRhfOYwgjPM9CJvnGvIE2tQ8.vI8uVq', '081379047437', '', 'konsumen', 0, 0, NULL, NULL, 'toko', 'Jalan Patinayah, RW. 01, Kel. 24 Ilir, Kec. Bukit Kecil, Palembang, Bukit Kecil, Palembang, Sumatera Selatan', NULL, -2.98646670, 104.75489610, 'kartu_member/6a102824d9a7b_1779443748.png', 'surat_usaha/6a102824d9ff2_1779443748.png', 'foto_toko/6a102824da251_1779443748.png', 'verified', '2026-05-22 09:55:48'),
(17, 'Notaris Heniwati', 'Heniwati@gmail.com', '$2y$10$CUu8uYWWXCeyJBpeYzetN.VNlKrvK0rLll0QfOo1kYlZSYel8dgmm', '0711356605', NULL, 'konsumen', 0, 0, NULL, NULL, 'umum', 'Jalan Masjid Lama, RW 02 Kelurahan 17 Ilir, Kec Ilir Timur 1, Ilir Timur I, Palembang, Sumatera Selatan', NULL, -2.98636500, 104.76360780, NULL, NULL, NULL, 'verified', '2026-05-25 14:43:40'),
(18, 'Asia Seafood', 'AsiaSeafood@gmail.com', '$2y$10$HnH.ILiFrvHwtcIkrXsv5.TQoZpTPsxR9eAVDQr2wf9Lba95lq5Na', '08127894811', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Gang Kaba, Ilir Timur I, Palembang, Sumatera Selatan', NULL, -2.97598920, 104.76082820, 'kartu_member/6a146e3a93eb3_1779723834.png', 'surat_usaha/6a146e3a9478a_1779723834.png', 'foto_toko/6a146e3a94b5f_1779723834.png', 'verified', '2026-05-25 15:43:54'),
(19, 'TOKO AKBAR', 'TokoAkbar@gmail.com', '$2y$10$I6LbfjoLFXe6H4Y9ge0EoOz9.OiKhvBrgq0qDegZmARgQM6z8pBpW', '082182233505', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Seberang Ulu I, Palembang, Sumatera Selatan', NULL, -3.00940110, 104.75815750, 'kartu_member/6a146fd13976b_1779724241.png', 'surat_usaha/6a146fd139a46_1779724241.png', 'foto_toko/6a146fd139ccc_1779724241.png', 'verified', '2026-05-25 15:50:41'),
(20, 'Toko Aston', 'TokoAston@gmail.com', '$2y$10$D6l7qi1sE9frOhIeBnIieeB/LK2.OBsyp6SzXFGLlAdUM5qtd83gi', '082176156058', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, -2.94449840, 104.68503570, 'kartu_member/6a1471039b721_1779724547.png', 'surat_usaha/6a1471039b9fa_1779724547.png', 'foto_toko/6a1471039bc7a_1779724547.png', 'verified', '2026-05-25 15:55:47'),
(21, 'Warung Basiron', 'Basiron@gmail', '$2y$10$6GGyO5IJJIPIzkg3bIJDZuXfwnhh2tOKdwr/7/F7vEujJhJOz01VS', '081271392043', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Kemuning, Palembang, Sumatera Selatan', NULL, -2.96193600, 104.75318490, 'kartu_member/6a1473adeda09_1779725229.png', 'surat_usaha/6a1473adedc7a_1779725229.png', 'foto_toko/6a1473adedeaa_1779725229.png', 'verified', '2026-05-25 16:07:10'),
(22, 'Ibu Titi', 'IbuTiti@gmail.com', '$2y$10$hJe0VAaK1UCLr0qY87z2A.4DeBA/X5YaNUQaHzN3S3Nnh4rouvBQ.', '088286760311', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Kemuning, Palembang, Sumatera Selatan', NULL, -2.96168360, 104.75280320, 'kartu_member/6a16fec5d7c02_1779891909.png', 'surat_usaha/6a16fec5d9e1b_1779891909.png', 'foto_toko/6a16fec5db2b8_1779891909.png', 'verified', '2026-05-27 14:25:10'),
(23, 'Toko Sekip Makmur', 'SekipMakmur@gmail.com', '$2y$10$rfaV9DUZD5XI8efGlASSbeSk6usMRBvSpLnh6DDquzn8ReWCvntzi', '08975300268', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Jalan Mayor Salim Batubara, Kemuning, Palembang, Sumatera Selatan', NULL, -2.96095020, 104.75859230, 'kartu_member/6a170016b9420_1779892246.png', 'surat_usaha/6a170016b9791_1779892246.png', 'foto_toko/6a170016b9a71_1779892246.png', 'verified', '2026-05-27 14:30:46'),
(24, 'Toko Alyong Beni', 'AlyongBeni@gmail.com', '$2y$10$muZv5R7sXTQPCi2/DkoiNuY/glxMQH4BfoBhSHOxxzwSUzDX/l9YS', '364743', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Jalan Mayor Salim Batubara, Ilir Timur I, Palembang, Sumatera Selatan', NULL, -2.96865250, 104.75534610, 'kartu_member/6a1701868b520_1779892614.png', 'surat_usaha/6a1701868b74c_1779892614.png', 'foto_toko/6a1701868b8fa_1779892614.png', 'verified', '2026-05-27 14:36:54'),
(25, 'Iyus Frozen', 'IyusFrozen@gmail.com', '$2y$10$jg6y2MPy6mMGUTcwIBp9eeJqSpR.r7emj83G1LaQ1r7VG5zrJNO.m', '082179291090', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Alang-Alang Lebar, Palembang, Sumatera Selatan', NULL, -2.94442350, 104.68449360, 'kartu_member/6a1703266aceb_1779893030.png', 'surat_usaha/6a1703266b112_1779893030.png', 'foto_toko/6a1703266b4bf_1779893030.png', 'verified', '2026-05-27 14:43:50'),
(26, 'Toko Shanti', 'Shanti@gmail.com', '$2y$10$gE5BFKWwRvXwf6.omxLdz.Z4Fk2xJMmeGeujILvCbq3zQOFj885Bm', '08127116878', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Jalan Veteran, Ilir Timur I, Palembang, Sumatera Selatan', NULL, -2.97619740, 104.75493340, 'kartu_member/6a17054c55b41_1779893580.png', 'surat_usaha/6a17054c55e59_1779893580.png', 'foto_toko/6a17054c56195_1779893580.png', 'verified', '2026-05-27 14:53:00'),
(27, 'Toko Shanesa', 'Shanesa@gmail.com', '$2y$10$dzbIg5tmhwvhN3yb.NYmCuR0ar4R3AyZ0AGoMlFGjpUZxvJnpRHKC', '081332808980', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Jalan Letnan Jaimas, RW. 05, Kel. 24 ILIR, Kec. Bukit Kecil, Palembang, Ilir Timur I, Palembang, Sumatera Selatan', NULL, -2.98131860, 104.75246070, 'kartu_member/6a17c6ca79bcf_1779943114.png', 'surat_usaha/6a17c6ca7a161_1779943114.png', 'foto_toko/6a17c6ca7a785_1779943114.png', 'verified', '2026-05-28 04:38:34'),
(28, 'Sumatera Motor', 'SumateraMotor@gmail.com', '$2y$10$uMqK5ngtylXLH5ysNzLCOOOPqEq7xGuJU2y344jyQ2IPOTn11GlVS', '08153835238', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Jalan Veteran, Ilir Timur I, Palembang, Sumatera Selatan', NULL, -2.97611690, 104.75513730, 'kartu_member/6a17c915bc7fe_1779943701.png', 'surat_usaha/6a17c915bca96_1779943701.png', 'foto_toko/6a17c915bcd55_1779943701.png', 'verified', '2026-05-28 04:48:21'),
(29, 'Toko Sumber Rezeki', 'SumberRezeki@gmail.com', '$2y$10$GCu.heDmLQH4WuMX.2lMkeFttxIhpRXrXTHF5xzL3KJ9M9rKL0lFm', '085267038478', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', '3M6Q+3JJ, Jl. Klp. Gading Raya, Talang Klp., Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, -2.93980440, 104.68905040, 'kartu_member/6a194fa4e6aa7_1780043684.png', 'surat_usaha/6a194fa4e7046_1780043684.png', 'foto_toko/6a194fa4e72bc_1780043684.png', 'verified', '2026-05-29 08:34:45'),
(30, 'P & D MANIS', 'PDMANIS@gmail.com', '$2y$10$4k0HppqJnPwD1c30dtz8QeeMXH326QAhjKoM5hFZ3osHZOvhxaDSe', '082178583104', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Jl. Mayor Salim Batubara No.95, Sekip Jaya, Kec. Kemuning, Kota Palembang, Sumatera Selatan 30127, Indonesia', NULL, -2.96697310, 104.75693610, 'kartu_member/6a195b8dbf20c_1780046733.png', 'surat_usaha/6a195b8dbf4a0_1780046733.png', 'foto_toko/6a195b8dbf6e9_1780046733.png', 'verified', '2026-05-29 09:25:33'),
(31, '808 Jakabaring', '808jakabaring@gmail.com', '$2y$10$s1pe9qGonIELpfEZv1ZpHONhuHq1ZtNT2LvdcbUHlLFWHT8bFW70y', '081367701688', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Jl. Pangeran Ratu No.02 - 03 Blok B, 15 Ulu, Kecamatan Seberang Ulu I, Kota Palembang, Sumatera Selatan 30267, Indonesia', NULL, -3.02655710, 104.77612090, 'kartu_member/6a195d1529aa0_1780047125.png', 'surat_usaha/6a195d1529f0d_1780047125.png', 'foto_toko/6a195d152a4b6_1780047125.png', 'verified', '2026-05-29 09:32:05'),
(32, 'Firman', 'firmanardiani1975@gmail.com', '$2y$10$R1GqFmasvsQfXZ3sU/7OpeEa94urcv0brq6t9cYC6TNprZUOOIpdq', '08127434897', NULL, 'sopir', 350, 200, NULL, NULL, NULL, 'Jalan Sukabangun 2 Lr Mawar/puding no 109 RT 08', NULL, -2.97775553, 104.73776771, NULL, NULL, NULL, 'verified', '2026-06-02 09:32:30'),
(33, 'Iwan', 'mgsridwan1974@gmail.com', '$2y$10$Ko6iqyLoEzGxT/RRHeBBOuR2tXPjxHwYh8Dv7ybCGFJSN.r8M3xWm', '089524423153', NULL, 'sopir', 350, 200, NULL, NULL, NULL, 'Perumnas Borang', NULL, -2.97219941, 104.75157928, NULL, NULL, NULL, 'verified', '2026-06-02 09:32:30'),
(34, 'Tedi Sabrun', 'salsasabrun@gmail.com', '$2y$10$0AXyUm99CJrEFggQJmg58eAioEZGk9He0KicItjyIG8VDQswtEffy', '08992367302', NULL, 'sopir', 350, 200, NULL, NULL, NULL, 'Griya Interbis Indah Talang Kelapa', NULL, NULL, NULL, NULL, NULL, NULL, 'verified', '2026-06-02 09:32:30'),
(35, 'Jeri', 'yansahjeri075@gmail.com', '$2y$10$xihGIFqeO17kxYNW/Yvlf.axq685KtqRzStdne9Iv2W4xNycWhoH6', '081373152296', NULL, 'sopir', 350, 200, NULL, NULL, NULL, 'Sukomoro KM 18', NULL, -2.97962144, 104.73169928, NULL, NULL, NULL, 'verified', '2026-06-02 09:32:30'),
(36, 'Hamid', 'Ha6476258@gmail.com', '$2y$10$H1esMsJbe3aWg.2IFucyTeXWW5xvO/iv4OuUkXe03E4CeiFahawmO', '0895610461670', NULL, 'sopir', 350, 200, NULL, NULL, NULL, 'Srigading KM 14', NULL, -2.98109375, 104.73254300, NULL, NULL, NULL, 'verified', '2026-06-02 09:32:30'),
(37, 'Arie Beradat', 'Arieberadat@gmail.com', '$2y$10$.hPWvokv3yvWL/wKmvsuEuMMfYiolaqbqiYWmhhQPinaWKzIQGZeO', '082269823033', NULL, 'sopir', 350, 200, NULL, NULL, NULL, 'Tanjung Barangan', NULL, -2.97960931, 104.73170657, NULL, NULL, NULL, 'verified', '2026-06-02 09:32:30'),
(38, 'TOKO DEWI', 'Dewi@gmail.com', '$2y$10$VNs94KP9wQ.pj2K0oUFWAesru1FNTeEmIbiPm0KH9k1jMAR.bwrWa', '081367701688', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', '3MPJ+2M3, Jalan Bypass, Alang Alang Lebar, Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30961, Indonesia', NULL, -2.91497920, 104.68167770, 'kartu_member/6a21ba3f43b8d_1780595263.png', 'surat_usaha/6a21ba3f440f4_1780595263.png', 'foto_toko/6a21ba3f44378_1780595263.png', 'verified', '2026-06-04 17:47:43'),
(39, 'TOKO SUMBER TERANG', 'SumberTerang@gmail.com', '$2y$10$uE7x37CkdayXP7p2pFHClOEeo25yHWGRY9B2HVoCcwu3mLU7eOcCm', '082184831554', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', 'Jl. D.I. Panjaitan, Bagus Kuning, Kec. Plaju, Kota Palembang, Sumatera Selatan 30268, Indonesia', NULL, -2.99512390, 104.80803550, 'kartu_member/6a21bb5260325_1780595538.png', 'surat_usaha/6a21bb5260a00_1780595538.png', 'foto_toko/6a21bb5260c04_1780595538.png', 'verified', '2026-06-04 17:52:18'),
(40, 'Toko Linda Jaya', 'LindaJaya@gmail.com', '$2y$10$3Sj0Zoi6s.7yZeNR0gvcbOOX2qx/LjZS5gBwN0EWN/SxWXpkQPa.2', '085215038907', NULL, 'konsumen', 0, 0, NULL, NULL, 'toko', '3JMP+QJ4, Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, -2.91560270, 104.63658670, 'kartu_member/6a21bbefadb3e_1780595695.png', 'surat_usaha/6a21bbefaddda_1780595695.png', 'foto_toko/6a21bbefae294_1780595695.png', 'verified', '2026-06-04 17:54:55'),
(41, 'Toko Zest', 'Zest@gmail.com', '$2y$10$Ay0AvR3Jlk/E.6JAYKO7QeH66iPciDLbKy6Y5VXkVqlT.0jiZqxbS', '08971629675', '', 'konsumen', 0, 0, NULL, NULL, 'toko', 'Jl. Palembang - Betung, Sukomoro, Kec. Talang Klp., Kab. Banyuasin, Sumatera Selatan 30961, Indonesia', NULL, -2.91589290, 104.62877520, 'kartu_member/6a21bccdafea0_1780595917.png', 'surat_usaha/6a21bccdb0812_1780595917.png', 'foto_toko/6a21bccdb0b55_1780595917.png', 'verified', '2026-06-04 17:58:37'),
(42, 'Manajemen Informatika Polsri', 'manajemeninformatika@gmail.com', '$2y$10$mbyODRLlC8EysNW98638Yeh0RPjM43IamQvRendxeYlWDJtN7FU0e', '0812831283', NULL, 'konsumen', 0, 0, NULL, NULL, 'umum', 'Jl. Sungai Sahang No.3654, Lorok Pakjo, Kec. Ilir Bar. I, Kota Palembang, Sumatera Selatan 30151, Indonesia', NULL, -2.97956240, 104.73113150, NULL, NULL, NULL, 'verified', '2026-06-12 11:38:18'),
(44, 'nisa', 'pratamapaaaan@gmail.com', '$2y$10$5JFbNPmAnI/xoM3PsUb12ewI5SRdJYyAq1hrBQJuYh53L7fAgSKy.', '081379762341', '081379762341', 'konsumen', 0, 0, NULL, NULL, 'umum', '3M4P+622, Jl. Talang Klp., Talang Klp., Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30152, Indonesia', NULL, -2.94449320, 104.68502730, NULL, NULL, NULL, 'verified', '2026-06-27 16:33:45');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `komplain`
--
ALTER TABLE `komplain`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `pesanan_id` (`pesanan_id`);

--
-- Indeks untuk tabel `mutasi_stok`
--
ALTER TABLE `mutasi_stok`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `sopir_id` (`sopir_id`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `ritase`
--
ALTER TABLE `ritase`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_sopir_tanggal` (`sopir_id`,`tanggal`);

--
-- Indeks untuk tabel `transaksi_pesanan`
--
ALTER TABLE `transaksi_pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `komplain`
--
ALTER TABLE `komplain`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `mutasi_stok`
--
ALTER TABLE `mutasi_stok`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `ritase`
--
ALTER TABLE `ritase`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `transaksi_pesanan`
--
ALTER TABLE `transaksi_pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=174;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `komplain`
--
ALTER TABLE `komplain`
  ADD CONSTRAINT `komplain_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `komplain_ibfk_2` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pesanan_ibfk_2` FOREIGN KEY (`sopir_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `transaksi_pesanan`
--
ALTER TABLE `transaksi_pesanan`
  ADD CONSTRAINT `transaksi_pesanan_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaksi_pesanan_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
