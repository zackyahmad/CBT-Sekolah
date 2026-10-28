-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 16, 2026 at 07:02 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_ujian_sekolah`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi_pengawas`
--

CREATE TABLE `absensi_pengawas` (
  `id` int(11) NOT NULL,
  `pengawas_id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `keterangan` varchar(50) DEFAULT NULL,
  `status_verifikasi` enum('menunggu','disetujui','ditolak') DEFAULT 'menunggu',
  `catatan_admin` text DEFAULT NULL,
  `diverifikasi_oleh` int(11) DEFAULT NULL,
  `diverifikasi_pada` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absensi_pengawas`
--

INSERT INTO `absensi_pengawas` (`id`, `pengawas_id`, `nama`, `tanggal`, `jam_masuk`, `jam_pulang`, `foto`, `keterangan`, `status_verifikasi`, `catatan_admin`, `diverifikasi_oleh`, `diverifikasi_pada`, `created_at`) VALUES
(1, 76, 'pengawas smkn8 balam', '2026-05-18', '11:05:36', '11:05:42', 'absen_1_1779077136.png', 'Hadir', 'disetujui', '', 1, '2026-05-18 11:05:57', '2026-05-18 11:05:36'),
(2, 81, 'Ika Novalia', '2026-05-18', '20:22:42', '20:22:48', 'absen_2_1779110562.png', 'Hadir', 'disetujui', '', 1, '2026-05-18 20:23:23', '2026-05-18 20:22:42'),
(3, 76, 'pengawas smkn8 balam', '2026-05-19', '11:53:21', NULL, 'absen_3_1779166401.png', 'Hadir', 'disetujui', '', 1, '2026-05-19 11:53:49', '2026-05-19 11:53:21'),
(4, 76, 'pengawas smkn8 balam', '2026-05-21', '09:51:03', '09:51:10', 'absen_4_1779331863.png', 'Hadir', 'disetujui', '', 1, '2026-05-21 09:51:27', '2026-05-21 09:51:03'),
(5, 76, 'pengawas smkn8 balam', '2026-05-26', '08:57:27', '08:57:33', 'absen_5_1779760647.png', 'Hadir', 'disetujui', '', 1, '2026-05-26 08:58:05', '2026-05-26 08:57:27'),
(6, 76, 'pengawas smkn8 balam', '2026-06-02', '09:38:16', '09:38:22', 'absen_6_1780367896.png', 'Hadir', 'disetujui', '', 1, '2026-06-02 09:38:42', '2026-06-02 09:38:16');

-- --------------------------------------------------------

--
-- Table structure for table `absensi_siswa`
--

CREATE TABLE `absensi_siswa` (
  `id` int(11) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jam` time DEFAULT NULL,
  `keterangan` varchar(50) DEFAULT NULL,
  `status_verifikasi` varchar(20) DEFAULT 'menunggu',
  `surat_sakit` varchar(255) DEFAULT NULL,
  `verifikasi_oleh` varchar(100) DEFAULT NULL,
  `verifikasi_tanggal` datetime DEFAULT NULL,
  `catatan_verifikasi` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absensi_siswa`
--

INSERT INTO `absensi_siswa` (`id`, `nama_siswa`, `id_user`, `kelas`, `tanggal`, `jam`, `keterangan`, `status_verifikasi`, `surat_sakit`, `verifikasi_oleh`, `verifikasi_tanggal`, `catatan_verifikasi`, `created_at`) VALUES
(1, '', NULL, 'X-PPLG 1', '2026-05-10', '11:37:59', 'Hadir', 'disetujui', NULL, 'pengawas smkn8 balam', '2026-05-10 16:48:06', '', '2026-05-10 16:37:59'),
(2, '', 79, 'X-PPLG 2', '2026-05-13', '06:30:50', 'Hadir', 'disetujui', NULL, 'pengawas smkn8 balam', '2026-05-21 09:49:27', '', '2026-05-13 11:30:50'),
(3, '', 75, 'X-PPLG 1', '2026-05-18', '16:24:22', 'Hadir', 'disetujui', NULL, 'pengawas smkn8 balam', '2026-05-21 09:49:17', 'y', '2026-05-18 21:24:22'),
(4, '', 75, 'X-PPLG 1', '2026-05-19', '07:45:51', 'Hadir', 'disetujui', NULL, 'pengawas smkn8 balam', '2026-05-19 12:47:24', '', '2026-05-19 12:45:51'),
(5, '', 75, 'X-PPLG 1', '2026-05-26', '03:55:10', 'Sakit', 'disetujui', 'surat_1779760510_6a14fd7e05be5.jpeg', 'pengawas smkn8 balam', '2026-05-26 08:55:50', 'semoga cepat sembuh ya', '2026-05-26 08:55:10'),
(6, '', 75, 'X-PPLG 1', '2026-06-02', '04:35:17', 'Sakit', 'disetujui', 'surat_1780367717_6a1e41656cfd0.jpeg', 'pengawas smkn8 balam', '2026-06-02 09:37:13', '', '2026-06-02 09:35:17');

-- --------------------------------------------------------

--
-- Table structure for table `absen_pengawas`
--

CREATE TABLE `absen_pengawas` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `keterangan` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absen_pengawas`
--

INSERT INTO `absen_pengawas` (`id`, `nama`, `tanggal`, `jam_masuk`, `jam_pulang`, `foto`, `keterangan`, `created_at`) VALUES
(2, 'pengawas smkn8 balam', '2026-05-10', '17:30:49', NULL, 'absen_2_1778409049.png', 'Hadir', '2026-05-10 17:30:49'),
(3, 'pengawas smkn8 bal am', '2026-05-18', '09:59:02', '10:00:21', 'absen_3_1779073142.png', 'Hadir', '2026-05-18 09:59:02');

-- --------------------------------------------------------

--
-- Table structure for table `hasil_ujian`
--

CREATE TABLE `hasil_ujian` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `ujian_id` int(11) NOT NULL,
  `nilai` decimal(5,2) DEFAULT NULL,
  `jawaban` text DEFAULT NULL,
  `waktu_mulai` datetime DEFAULT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `status` enum('sedang','selesai') DEFAULT 'sedang',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_ujian`
--

CREATE TABLE `jadwal_ujian` (
  `id` int(11) NOT NULL,
  `ujian_id` int(11) NOT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `soal`
--

CREATE TABLE `soal` (
  `id` int(11) NOT NULL,
  `guru_id` int(11) NOT NULL,
  `ujian_id` int(11) DEFAULT NULL,
  `pertanyaan` text NOT NULL,
  `pilihan_a` text NOT NULL,
  `pilihan_b` text NOT NULL,
  `pilihan_c` text NOT NULL,
  `pilihan_d` text NOT NULL,
  `jawaban` char(1) NOT NULL COMMENT 'A/B/C/D',
  `poin` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `soal`
--

INSERT INTO `soal` (`id`, `guru_id`, `ujian_id`, `pertanyaan`, `pilihan_a`, `pilihan_b`, `pilihan_c`, `pilihan_d`, `jawaban`, `poin`, `created_at`) VALUES
(1, 1, NULL, 'Apa ibukota Indonesia?', 'Jakarta', 'Bandung', 'Surabaya', 'Medan', 'A', 1, '2026-05-05 03:53:50'),
(2, 1, NULL, 'Siapakah presiden pertama Indonesia?', 'BJ Habibie', 'Soekarno', 'Soeharto', 'Susilo Bambang', 'B', 1, '2026-05-05 03:53:50'),
(3, 1, NULL, 'Apa hasil dari 5 + 3 x 2?', '16', '11', '13', '10', 'B', 1, '2026-05-05 03:53:50'),
(4, 1, NULL, 'Bahasa pemrograman yang digunakan untuk membuat website dinamis?', 'Python', 'Java', 'PHP', 'C++', 'C', 1, '2026-05-05 03:53:50'),
(5, 1, NULL, 'Apa kepanjangan dari HTML?', 'Hyper Text Markup Language', 'High Text Markup Language', 'Hyper Transfer Markup Language', 'Home Text Markup Language', 'A', 1, '2026-05-05 03:53:50'),
(6, 1, NULL, 'Apa ibukota Indonesia?', 'Jakarta', 'Bandung', 'Surabaya', 'Medan', 'A', 1, '2026-05-05 03:54:01'),
(7, 1, NULL, 'Siapakah presiden pertama Indonesia?', 'BJ Habibie', 'Soekarno', 'Soeharto', 'Susilo Bambang', 'B', 1, '2026-05-05 03:54:01'),
(8, 1, NULL, 'Apa hasil dari 5 + 3 x 2?', '16', '11', '13', '10', 'B', 1, '2026-05-05 03:54:01'),
(9, 1, NULL, 'Bahasa pemrograman yang digunakan untuk membuat website dinamis?', 'Python', 'Java', 'PHP', 'C++', 'C', 1, '2026-05-05 03:54:01'),
(10, 1, NULL, 'Apa kepanjangan dari HTML?', 'Hyper Text Markup Language', 'High Text Markup Language', 'Hyper Transfer Markup Language', 'Home Text Markup Language', 'A', 1, '2026-05-05 03:54:01'),
(11, 1, NULL, 'Apa ibukota Indonesia?', 'Jakarta', 'Bandung', 'Surabaya', 'Medan', 'A', 1, '2026-05-05 03:54:32'),
(12, 1, NULL, 'Siapakah presiden pertama Indonesia?', 'BJ Habibie', 'Soekarno', 'Soeharto', 'Susilo Bambang', 'B', 1, '2026-05-05 03:54:32'),
(13, 1, NULL, 'Apa hasil dari 5 + 3 x 2?', '16', '11', '13', '10', 'B', 1, '2026-05-05 03:54:32'),
(14, 1, NULL, 'Bahasa pemrograman yang digunakan untuk membuat website dinamis?', 'Python', 'Java', 'PHP', 'C++', 'C', 1, '2026-05-05 03:54:32'),
(15, 1, NULL, 'Apa kepanjangan dari HTML?', 'Hyper Text Markup Language', 'High Text Markup Language', 'Hyper Transfer Markup Language', 'Home Text Markup Language', 'A', 1, '2026-05-05 03:54:32');

-- --------------------------------------------------------

--
-- Table structure for table `tb_absensi`
--

CREATE TABLE `tb_absensi` (
  `id_absensi` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'hadir',
  `surat_sakit` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_absensi`
--

INSERT INTO `tb_absensi` (`id_absensi`, `id_user`, `tanggal`, `jam_masuk`, `status`, `surat_sakit`, `created_at`) VALUES
(1, 75, '2026-05-18', '16:24:22', 'hadir', NULL, '2026-05-21 09:49:17'),
(2, 79, '2026-05-13', '06:30:50', 'hadir', NULL, '2026-05-21 09:49:27'),
(3, 75, '2026-05-26', '03:55:10', 'sakit', NULL, '2026-05-26 08:55:50'),
(4, 75, '2026-06-02', '04:35:17', 'sakit', NULL, '2026-06-02 09:37:13');

-- --------------------------------------------------------

--
-- Table structure for table `tb_hasil_ujian`
--

CREATE TABLE `tb_hasil_ujian` (
  `id_hasil` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_ujian` int(11) NOT NULL,
  `jml_benar` int(11) DEFAULT 0,
  `jml_salah` int(11) DEFAULT 0,
  `nilai` decimal(5,2) DEFAULT 0.00,
  `waktu_selesai` datetime DEFAULT current_timestamp(),
  `catatan_guru` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_hasil_ujian`
--

INSERT INTO `tb_hasil_ujian` (`id_hasil`, `id_user`, `id_ujian`, `jml_benar`, `jml_salah`, `nilai`, `waktu_selesai`, `catatan_guru`) VALUES
(1, 75, 11, 0, 1, 0.00, '2026-05-10 21:10:53', NULL),
(2, 75, 11, 0, 1, 0.00, '2026-05-10 21:12:41', NULL),
(3, 75, 12, 0, 1, 0.00, '2026-05-10 21:12:46', NULL),
(4, 75, 11, 0, 1, 0.00, '2026-05-11 09:05:24', NULL),
(5, 75, 15, 3, 0, 50.00, '2026-05-11 14:22:57', NULL),
(6, 75, 15, 0, 3, 0.00, '2026-05-11 14:23:16', NULL),
(7, 75, 19, 2, 0, 100.00, '2026-05-11 14:32:44', NULL),
(8, 75, 20, 0, 0, 0.00, '2026-05-13 08:56:39', NULL),
(9, 79, 26, 0, 0, 100.00, '2026-05-13 11:01:57', NULL),
(10, 79, 27, 0, 1, 50.00, '2026-05-13 11:26:12', NULL),
(11, 75, 28, 0, 0, 0.00, '2026-05-18 21:29:08', NULL),
(12, 75, 29, 0, 5, 0.00, '2026-05-19 12:08:51', NULL),
(13, 75, 30, 0, 0, 0.00, '2026-05-19 12:15:02', NULL),
(14, 75, 31, 1, 0, 100.00, '2026-05-19 12:19:50', NULL),
(15, 75, 32, 2, 0, 100.00, '2026-05-19 12:23:29', NULL),
(16, 75, 33, 0, 2, 0.00, '2026-05-19 12:25:36', NULL),
(17, 75, 34, 2, 0, 100.00, '2026-05-19 12:29:32', NULL),
(18, 75, 35, 0, 2, 0.00, '2026-05-19 12:34:29', NULL),
(19, 75, 36, 3, 2, 60.00, '2026-05-19 12:41:14', NULL),
(20, 75, 38, 0, 1, 0.00, '2026-05-22 10:03:51', NULL),
(21, 75, 39, 2, 1, 66.67, '2026-05-25 10:12:14', NULL),
(22, 75, 40, 3, 1, 75.00, '2026-05-25 10:12:41', NULL),
(23, 84, 15, 0, 2, 0.00, '2026-05-26 10:22:20', NULL),
(24, 84, 40, 2, 2, 50.00, '2026-05-26 10:23:45', NULL),
(25, 84, 39, 2, 1, 66.67, '2026-05-26 10:25:02', NULL),
(26, 84, 38, 2, 3, 40.00, '2026-05-26 10:34:01', NULL),
(27, 84, 36, 5, 0, 100.00, '2026-05-26 10:36:15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_jadwal_pengawas`
--

CREATE TABLE `tb_jadwal_pengawas` (
  `id_jadwal` int(11) NOT NULL,
  `id_guru` int(11) NOT NULL,
  `id_ruangan` int(11) NOT NULL,
  `id_mapel` int(11) NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_jawaban`
--

CREATE TABLE `tb_jawaban` (
  `id_jawaban` int(11) NOT NULL,
  `id_hasil` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_ujian` int(11) NOT NULL,
  `id_soal` int(11) NOT NULL,
  `jawaban` text DEFAULT NULL,
  `waktu_jawab` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_jawaban`
--

INSERT INTO `tb_jawaban` (`id_jawaban`, `id_hasil`, `id_user`, `id_ujian`, `id_soal`, `jawaban`, `waktu_jawab`) VALUES
(1, 8, 75, 20, 62, 'C', '2026-05-13 08:56:39'),
(2, 9, 79, 26, 60, 'A', '2026-05-13 11:01:57'),
(3, 9, 79, 26, 59, 'A', '2026-05-13 11:01:57'),
(4, 10, 79, 27, 68, 'wirausaha menurut saya adalah berjualan', '2026-05-13 11:26:12'),
(5, 10, 79, 27, 67, 'A', '2026-05-13 11:26:12'),
(6, 11, 75, 28, 62, 'E', '2026-05-18 21:29:08'),
(7, 12, 75, 29, 71, 'A', '2026-05-19 12:08:51'),
(8, 12, 75, 29, 70, '', '2026-05-19 12:08:51'),
(9, 12, 75, 29, 63, '', '2026-05-19 12:08:51'),
(10, 12, 75, 29, 61, '', '2026-05-19 12:08:51'),
(11, 12, 75, 29, 53, 'B', '2026-05-19 12:08:51'),
(12, 12, 75, 29, 52, '', '2026-05-19 12:08:51'),
(13, 12, 75, 29, 51, 'B', '2026-05-19 12:08:51'),
(14, 13, 75, 30, 62, 'E', '2026-05-19 12:15:02'),
(15, 14, 75, 31, 68, 'prakrya adalah', '2026-05-19 12:19:50'),
(16, 14, 75, 31, 67, 'E', '2026-05-19 12:19:50'),
(17, 15, 75, 32, 73, 'A', '2026-05-19 12:23:29'),
(18, 15, 75, 32, 72, 'A', '2026-05-19 12:23:29'),
(19, 15, 75, 32, 58, 'B', '2026-05-19 12:23:29'),
(20, 15, 75, 32, 57, 'A', '2026-05-19 12:23:29'),
(21, 15, 75, 32, 56, 'B', '2026-05-19 12:23:29'),
(22, 16, 75, 33, 73, 'B', '2026-05-19 12:25:36'),
(23, 16, 75, 33, 72, 'B', '2026-05-19 12:25:36'),
(24, 16, 75, 33, 58, 'A', '2026-05-19 12:25:36'),
(25, 16, 75, 33, 57, 'A', '2026-05-19 12:25:36'),
(26, 16, 75, 33, 56, 'A', '2026-05-19 12:25:36'),
(27, 17, 75, 34, 75, 'B', '2026-05-19 12:29:32'),
(28, 17, 75, 34, 74, 'A', '2026-05-19 12:29:32'),
(29, 17, 75, 34, 60, 'A', '2026-05-19 12:29:32'),
(30, 17, 75, 34, 59, 'B', '2026-05-19 12:29:32'),
(31, 18, 75, 35, 75, 'A', '2026-05-19 12:34:30'),
(32, 18, 75, 35, 74, 'B', '2026-05-19 12:34:30'),
(33, 18, 75, 35, 60, 'A', '2026-05-19 12:34:30'),
(34, 18, 75, 35, 59, 'A', '2026-05-19 12:34:30'),
(35, 19, 75, 36, 80, 'A', '2026-05-19 12:41:14'),
(36, 19, 75, 36, 79, 'D', '2026-05-19 12:41:14'),
(37, 19, 75, 36, 78, 'A', '2026-05-19 12:41:14'),
(38, 19, 75, 36, 77, 'A', '2026-05-19 12:41:14'),
(39, 19, 75, 36, 76, 'D', '2026-05-19 12:41:14'),
(40, 20, 75, 38, 89, 'B', '2026-05-22 10:03:51'),
(41, 21, 75, 39, 90, 'A', '2026-05-25 10:12:14'),
(42, 21, 75, 39, 75, 'B', '2026-05-25 10:12:14'),
(43, 21, 75, 39, 74, 'D', '2026-05-25 10:12:14'),
(44, 21, 75, 39, 60, 'A', '2026-05-25 10:12:14'),
(45, 21, 75, 39, 59, 'A', '2026-05-25 10:12:14'),
(46, 22, 75, 40, 92, 'A', '2026-05-25 10:12:41'),
(47, 22, 75, 40, 90, 'A', '2026-05-25 10:12:41'),
(48, 22, 75, 40, 75, 'A', '2026-05-25 10:12:41'),
(49, 22, 75, 40, 74, 'A', '2026-05-25 10:12:41'),
(50, 22, 75, 40, 60, 'A', '2026-05-25 10:12:41'),
(51, 22, 75, 40, 59, 'A', '2026-05-25 10:12:41'),
(52, 23, 84, 15, 51, 'C', '2026-05-26 10:22:20'),
(53, 23, 84, 15, 61, 'A', '2026-05-26 10:22:20'),
(54, 23, 84, 15, 63, 'B', '2026-05-26 10:22:20'),
(55, 23, 84, 15, 91, 'B', '2026-05-26 10:22:20'),
(56, 24, 84, 40, 92, 'B', '2026-05-26 10:23:45'),
(57, 24, 84, 40, 90, 'A', '2026-05-26 10:23:45'),
(58, 24, 84, 40, 75, 'A', '2026-05-26 10:23:45'),
(59, 24, 84, 40, 74, 'A', '2026-05-26 10:23:45'),
(60, 24, 84, 40, 60, 'A', '2026-05-26 10:23:45'),
(61, 24, 84, 40, 59, 'A', '2026-05-26 10:23:45'),
(62, 25, 84, 39, 90, 'A', '2026-05-26 10:25:02'),
(63, 25, 84, 39, 75, 'A', '2026-05-26 10:25:02'),
(64, 25, 84, 39, 74, 'A', '2026-05-26 10:25:02'),
(65, 25, 84, 39, 60, 'A', '2026-05-26 10:25:02'),
(66, 25, 84, 39, 59, 'A', '2026-05-26 10:25:02'),
(67, 26, 84, 38, 89, 'A', '2026-05-26 10:34:01'),
(68, 26, 84, 38, 88, 'A', '2026-05-26 10:34:01'),
(69, 26, 84, 38, 71, 'A', '2026-05-26 10:34:01'),
(70, 26, 84, 38, 70, 'A', '2026-05-26 10:34:01'),
(71, 26, 84, 38, 63, 'B', '2026-05-26 10:34:01'),
(72, 26, 84, 38, 61, 'A', '2026-05-26 10:34:01'),
(73, 26, 84, 38, 51, 'C', '2026-05-26 10:34:01'),
(74, 27, 84, 36, 80, 'A', '2026-05-26 10:36:15'),
(75, 27, 84, 36, 79, 'A', '2026-05-26 10:36:15'),
(76, 27, 84, 36, 78, 'A', '2026-05-26 10:36:15'),
(77, 27, 84, 36, 77, 'A', '2026-05-26 10:36:15'),
(78, 27, 84, 36, 76, 'A', '2026-05-26 10:36:15');

-- --------------------------------------------------------

--
-- Table structure for table `tb_kartu_ujian`
--

CREATE TABLE `tb_kartu_ujian` (
  `id_kartu` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `kelas` varchar(30) NOT NULL,
  `jenis_ujian` varchar(100) DEFAULT NULL,
  `tahun_pelajaran` varchar(50) DEFAULT NULL,
  `jurusan` varchar(50) DEFAULT NULL,
  `sid` varchar(30) DEFAULT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `token_ujian` varchar(20) DEFAULT NULL,
  `password_ujian` varchar(20) DEFAULT NULL,
  `username_ujian` varchar(20) DEFAULT NULL,
  `foto_siswa` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_kartu_ujian`
--

INSERT INTO `tb_kartu_ujian` (`id_kartu`, `nis`, `nama_siswa`, `kelas`, `jenis_ujian`, `tahun_pelajaran`, `jurusan`, `sid`, `tempat_lahir`, `tanggal_lahir`, `token_ujian`, `password_ujian`, `username_ujian`, `foto_siswa`, `created_at`, `updated_at`) VALUES
(1, '2025001', 'Ahmad Fauzi', 'XII RPL 1', NULL, NULL, 'RPL', 'SID001', 'Bandar Lampung', '2007-05-15', 'TOKEN123', '123456', NULL, NULL, '2026-04-02 06:53:04', NULL),
(2, '2025002', 'Budi Santoso', 'XII RPL 1', NULL, NULL, 'RPL', 'SID002', 'Bandar Lampung', '2007-08-22', 'TOKEN456', '789012', NULL, NULL, '2026-04-02 06:53:04', NULL),
(3, '2025003', 'Citra Dewi', 'XII RPL 2', NULL, NULL, 'RPL', 'SID003', 'Metro', '2007-02-10', 'TOKEN789', '345678', NULL, NULL, '2026-04-02 06:53:04', NULL),
(4, '2025004', 'Dian Permata sari', 'XI RPL 1', NULL, NULL, 'RPL', 'SID004', 'Bandar Lampung', '2008-11-30', 'TOKENABC', '901234', '2025004', NULL, '2026-04-02 06:53:04', '2026-05-08 02:35:43'),
(5, '2025005', 'Eka Putra', 'X RPL 1', NULL, NULL, 'RPL', 'SID005', 'Lampung Selatan', '2009-07-19', 'TOKENDEF', '567890', NULL, NULL, '2026-04-02 06:53:04', NULL),
(7, '23753060', 'Zakiiiiia', 'XI RPL 1', NULL, NULL, 'RPL', '23753060', 'Bandar Lampung', '2026-05-19', NULL, '23753060', '23753060', '1779169817_WhatsApp Image 2026-05-07 at 17.20.51.jpeg', '2026-05-19 05:50:17', NULL),
(8, '1234', 'dimas', 'x pplg 1', 'ujian tengah semester', '2025/2026', 'rpl', '1234', NULL, NULL, NULL, 'siswa123', 'siswa', '1779764764_WhatsApp Image 2026-05-07 at 17.20.51.jpeg', '2026-05-26 03:06:04', NULL),
(10, '12345', 'dimas', 'X-PPLG 1', 'ujian tengah semester', '2025/2026', 'rpl', '1234567', NULL, NULL, NULL, 'siswa123', 'siswa', '1779765200_WhatsApp Image 2026-05-26 at 09.39.52.jpeg', '2026-05-26 03:13:20', NULL),
(11, '23753055', 'Dimas Adrian', 'X-PPLG 1', 'ujian tengah semester', '2025/2026', 'rpl', '23753055', NULL, NULL, NULL, '07122004', 'Dimas Adrian', '1779765654_WhatsApp Image 2026-05-26 at 09.39.52.jpeg', '2026-05-26 03:20:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_kehadiran`
--

CREATE TABLE `tb_kehadiran` (
  `id_kehadiran` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_ujian` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_masuk` datetime DEFAULT NULL,
  `status` enum('hadir','izin','sakit','alpha') DEFAULT 'alpha',
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_kelas`
--

CREATE TABLE `tb_kelas` (
  `id_kelas` int(11) NOT NULL,
  `tingkat` enum('X','XI','XII') DEFAULT NULL,
  `nama_kelas` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_kelas`
--

INSERT INTO `tb_kelas` (`id_kelas`, `tingkat`, `nama_kelas`) VALUES
(34, 'X', 'X-PPLG 2'),
(35, 'XI', 'XI-PPLG 1'),
(36, 'XII', 'XII-PPLG 1'),
(37, 'X', 'X-PPLG 1'),
(38, 'XI', 'XI-PPLG 2'),
(39, 'XII', 'XII-PPLG 2'),
(48, 'X', 'X-AKL');

-- --------------------------------------------------------

--
-- Table structure for table `tb_mapel`
--

CREATE TABLE `tb_mapel` (
  `id_mapel` int(11) NOT NULL,
  `kode_mapel` varchar(20) DEFAULT NULL,
  `nama_mapel` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_mapel`
--

INSERT INTO `tb_mapel` (`id_mapel`, `kode_mapel`, `nama_mapel`) VALUES
(8, 'MTK', 'MATEMATIKA'),
(9, 'BHSLPG', 'BAHASA LAMPUNG'),
(10, 'INFORMATIKA', 'INFORMATIKA'),
(11, 'INFTK', 'Informatika'),
(12, 'BD', 'Basis Data'),
(13, 'BLMPPLG1', 'Bahasa Lampung X PPLG 1'),
(14, 'PKWU', 'PRAKARYA DAN KEWIRAUSAHAAN'),
(15, 'PPLG', 'basingaja');

-- --------------------------------------------------------

--
-- Table structure for table `tb_nilai`
--

CREATE TABLE `tb_nilai` (
  `id_nilai` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `id_ujian` int(11) NOT NULL,
  `waktu_mulai` datetime DEFAULT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `jml_benar` int(11) DEFAULT 0,
  `jml_salah` int(11) DEFAULT 0,
  `nilai_pg` decimal(5,2) DEFAULT 0.00,
  `nilai_essay` decimal(5,2) DEFAULT 0.00,
  `total_nilai` decimal(5,2) DEFAULT 0.00,
  `status_pengerjaan` enum('sedang','selesai','terkunci') DEFAULT 'sedang',
  `pelanggaran` int(11) DEFAULT 0,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_nilai`
--

INSERT INTO `tb_nilai` (`id_nilai`, `id_siswa`, `id_ujian`, `waktu_mulai`, `waktu_selesai`, `jml_benar`, `jml_salah`, `nilai_pg`, `nilai_essay`, `total_nilai`, `status_pengerjaan`, `pelanggaran`, `ip_address`, `user_agent`) VALUES
(11, 75, 28, '2026-05-18 22:28:56', '2026-05-18 22:28:56', 0, 0, 100.00, 0.00, 100.00, 'selesai', 0, NULL, NULL),
(12, 79, 23, '2026-05-18 22:29:53', '2026-05-18 22:29:53', 0, 0, 100.00, 0.00, 100.00, 'selesai', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_soal`
--

CREATE TABLE `tb_soal` (
  `id_soal` int(11) NOT NULL,
  `id_ujian` int(11) DEFAULT NULL,
  `id_mapel` int(11) DEFAULT NULL,
  `jenis_soal` enum('1','2') NOT NULL,
  `nilai` int(11) DEFAULT 0,
  `materi` varchar(100) DEFAULT NULL,
  `pertanyaan` text NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `opsi_a` text DEFAULT NULL,
  `opsi_b` text DEFAULT NULL,
  `opsi_c` text DEFAULT NULL,
  `opsi_d` text DEFAULT NULL,
  `opsi_e` text DEFAULT NULL,
  `kunci_jawaban` varchar(5) DEFAULT NULL,
  `bobot` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_soal`
--

INSERT INTO `tb_soal` (`id_soal`, `id_ujian`, `id_mapel`, `jenis_soal`, `nilai`, `materi`, `pertanyaan`, `gambar`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `opsi_e`, `kunci_jawaban`, `bobot`) VALUES
(8, 6, 5, '1', 0, 'Perangkat Keras', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama?', 'soal_1771224213.jpg', 'CPU', 'RAM', 'Harddisk', 'Monitor', 'Keyboard', 'a', 25),
(9, 6, 5, '1', 0, 'Sistem Operasi', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama?', NULL, 'CPU', 'RAM', 'Harddisk', 'Monitor', 'Keyboard', 'a', 25),
(10, 6, 5, '1', 0, 'Sistem Operasi', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama?', NULL, 'CPU', 'RAM', 'Harddisk', 'Monitor', 'Keyboard', 'a', 25),
(11, 6, 5, '1', 0, 'Sistem Operasi', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama?', NULL, 'CPU', 'RAM', 'Harddisk', 'Monitor', 'Keyboard', 'a', 25),
(32, NULL, 6, '1', 0, 'pemahaman dan penggunaan teks-teks tradisional', ' pengertian dari sagata ngebabang adalah ', NULL, 'pantun percintaan', 'pantun anak-anak yang sedang mengasuh adik nya', 'pantun sindiran ', 'pantun duka cita', 'berbalas pantun', 'b', 5),
(33, NULL, 6, '1', 0, 'teks pidato', 'arti dari sagata adalah ', NULL, 'pantun sindiran ', 'pantun lucu', 'pantun dan puisi', 'pantun anak-anak', 'pantun duka cita', 'e', 5),
(34, NULL, 6, '1', 0, 'sagata / pantun', 'sebutkan salah satu jenis -jenis sagata dengan benar kecuali ..', NULL, 'sagata lampung', 'sagata ngebabang', 'sagata nangguh', 'sagata lalayaan', 'sagata budaya', 'a', 5),
(35, NULL, 6, '1', 0, 'wawaghahan (lagenda)', 'warahan ( wawaghanaan) bearti ..', NULL, 'mendidik ', 'asal-usul', 'cerita berirama', 'khayal', 'sejarah', 'c', 5),
(36, NULL, 6, '1', 0, 'pemahaman dan penggunaan teks-teks tradisional', 'kata setimbalan jika di artikan dalam bahasa indonesia adalah ..', NULL, '             berbalas pantun', 'bersahut-sautan ', 'mempelajari', 'mengandung', 'memiliki', 'b', 5),
(37, NULL, 6, '1', 0, 'teks pidato', 'Apu arti kata \"muakhi\" dalam bahasa Lampung?', NULL, 'Bermusuhan', 'Berjalan', 'Berpisah', 'Bertengkar', 'Bersahabat', 'e', 5),
(38, NULL, 6, '1', 0, 'sagata / pantun', 'Kalimat yang tepat untuk pembukaan pidato adalah …', NULL, 'Tabik pun, nyak minta izin', 'Nyak mak ngejak', 'Nyak pai lawok', 'Mak kughak', 'Sai lapah', 'a', 5),
(39, NULL, 6, '1', 0, 'wawaghahan (lagenda)', 'Teks yang berisi ajakan dan disampaikan di depan umum disebut …', NULL, 'Cerita', 'Pantun', 'Pidato', 'Dialog', 'Pengumuman', 'c', 5),
(40, NULL, 6, '1', 0, 'pemahaman dan penggunaan teks-teks tradisional', 'Ungkapan \"tabik pun\" biasa digunakan untuk …', NULL, 'Berpamitan', 'Mengucap terima kasih', 'Memberi salam', 'Menyuruh', 'Melarang', 'c', 5),
(41, NULL, 6, '1', 0, 'teks pidato', 'Bahasa Lampung memiliki dua dialek utama, yaitu …', NULL, 'Api dan Nyo', 'A dan O', 'Pesisir dan Abung', 'Halus dan kasar', 'Formal dan nonformal', 'c', 5),
(42, NULL, 6, '1', 0, 'sagata / pantun', 'Tokoh yang terlibat dalam teks percakapan disebut …', NULL, 'Penonton', 'Narator', 'Pelaku', 'Tokoh', 'Pendengar', 'd', 5),
(43, NULL, 6, '1', 0, 'wawaghahan (lagenda)', 'Tujuan utama pidato persuasif adalah …', NULL, 'Menghibur', 'Menyampaikan cerita', 'Mempengaruhi pendengar', 'Menyampaikan berita', 'Mengkritik', 'c', 5),
(44, NULL, 6, '1', 0, 'pemahaman dan penggunaan teks-teks tradisional', 'Kalimat ajakan dalam bahasa Lampung biasanya diawali kata …', NULL, 'Dang', 'Ayo', 'Kudo', 'Mak', 'Sai', 'b', 5),
(45, NULL, 6, '1', 0, 'teks pidato', 'Bagian akhir pidato berisi …', NULL, 'Salam pembuka', 'Isi utama', 'Penutup dan kesimpulan', 'Argumentasi', 'Latar belakang', 'c', 5),
(46, NULL, 6, '1', 0, 'sagata / pantun', 'Kata \"nyak\" dalam bahasa Lampung berarti …', NULL, 'Kamu', 'Dia', 'Kami', 'Saya', 'Mereka', 'd', 5),
(47, NULL, 6, '1', 0, 'wawaghahan (lagenda)', 'Percakapan dua orang atau lebih disebut …', NULL, 'Pidato', 'Monolog', 'Dialog', 'Ceramah', 'Narasi', 'c', 5),
(48, NULL, 6, '1', 0, 'pemahaman dan penggunaan teks-teks tradisional', 'Fungsi penggunaan bahasa halus adalah …', NULL, 'Untuk bercanda', 'Menunjukkan emosi', 'Menghormati lawan bicara', 'Mengejek', 'Memerintah', 'c', 5),
(49, NULL, 6, '1', 0, 'teks pidato', 'Kalimat larangan dalam bahasa Lampung ditandai kata …', NULL, 'Ayo', 'Dang', 'Sai', 'Nyak', 'Kudo', 'c', 5),
(50, NULL, 6, '1', 0, 'sagata / pantun', 'Pidato adat biasanya disampaikan pada acara …', NULL, 'Sekolah', 'Olahraga', 'Adat istiadat', 'Pasar', 'Hiburan', 'c', 5),
(51, 15, 9, '1', 0, 'pemahaman', 'apkah a itu abjad', NULL, 'y', 'salh', 'bnar', 'terserah', 'basing', 'a', 10),
(56, 41, 8, '', 0, NULL, 'apa ya', NULL, '3', '2', '1', '9', '5', 'C', 1),
(57, 41, 8, '', 0, NULL, '2*8', NULL, '18', '19', '20', '21', '22', 'A', 1),
(58, 41, 8, '', 0, NULL, '1+1', NULL, '1', '2', '3', '4', '5', 'B', 1),
(59, NULL, 12, '', 0, NULL, 'apa yg dimaksud dengan database?', NULL, 'tempat penyimpanan', 'tempat debugging', 'tempat kode program', 'hardware', 'software', 'A', 1),
(60, NULL, 12, '', 0, NULL, 'apa itu database', NULL, 'penyimpanan', 'hardaware', 'software', 'kode program', 'debugging', 'A', 1),
(61, NULL, 9, '', 0, NULL, '12', NULL, 'tempat penyimpanan', '19', 'tempat kode program', 'biasa aja', 'jelek', 'A', 1),
(62, NULL, 10, '', 0, NULL, 'apa itu informatika', NULL, 'komputer', 'kecerdasan buatan', 'bahasa pemrograman', 'koding', 'semua benar', 'E', 1),
(63, NULL, 9, '', 0, NULL, 'bahasa lampungnya tidur apa', NULL, 'cukuk', 'pedom', 'nyak', 'tiyan', 'sekelik', 'B', 1),
(64, NULL, 13, '', 0, NULL, 'lampung adalah provinsi yg terletak di pulau mana?', NULL, 'jawa', 'kalimantan', 'sumatera', 'nusa tenggara', 'sulawesi', 'C', 1),
(65, NULL, 13, '', 0, NULL, 'lampung memiliki 2 adat dan budaya yaitu', NULL, 'sebambang dan ngabambang', 'nyow dan api', 'lunik dan cawa', 'pepadun dan saibatin', 'tulang dan bawang', 'D', 1),
(66, NULL, 11, '', 0, NULL, 'it itu apa', NULL, 'komputer', 'kecerdasan buatan', 'informatika', 'informasi dan teknologi', 'C dan D', 'E', 1),
(67, NULL, 14, '1', 0, NULL, 'APA ITU PKWU', NULL, 'BERWIRAUSAHA', 'BERUSAHA', 'TIDAK MENYERAH', 'BERKREASI', 'SEMUA BENAR', 'E', 1),
(68, NULL, 14, '2', 0, NULL, 'jelaskan wirausaha menurutmu!', NULL, '', '', '', '', '', '', 1),
(70, NULL, 9, '1', 0, 'pemahaman', 'coba aja', 'soal_1779166726.jpeg', 'mie ayam', '2', '3', '4', '5', 'd', 1),
(71, NULL, 9, '1', 0, 'pemahaman', 'apa aja', 'soal_1779167093.jpeg', '1', '2', '3', '4', '5', 'e', 1),
(72, 41, 8, '1', 0, NULL, '2*8=', NULL, '16', '17', '18', '19', '10', 'A', 1),
(73, 41, 8, '1', 0, NULL, '4*4', NULL, '16', '20', '24', '30', '34', 'A', 1),
(74, NULL, 12, '1', 0, NULL, 'apa itu basis data?', 'soal_1779421569_6a0fd181f0619.jpeg', 'data', '1', '2', '3', '4', 'A', 1),
(75, NULL, 12, '1', 0, NULL, '1', NULL, '1', '2', '3', '4', '5', 'B', 1),
(76, NULL, 15, '1', 0, NULL, 'apaaja', NULL, 'a', 'b', 'c', 'd', 'e', 'A', 1),
(77, NULL, 15, '1', 0, NULL, 'yyy', NULL, '1', '2', '3', '4', '5', 'A', 1),
(78, NULL, 15, '1', 0, NULL, 'ertyyfdfghj', NULL, '1', '2', '3', '4', '5', 'A', 1),
(79, NULL, 15, '1', 0, NULL, 'qwwergrrg', NULL, '12', '22', '24', '45', '55', 'A', 1),
(80, NULL, 15, '1', 0, NULL, 'terserah op aja', NULL, 'q', 'w', 'e', 'r', 'y', 'A', 1),
(81, 37, NULL, '', 0, '', '1', '', '1', '2', 'Sistem Operasi', '[TUMPUK/INSERT GAMBAR ANDA DI KOTAK INI]', '', 'Sebut', 1),
(82, 37, NULL, '', 0, '', '1', '', '1', '2', 'Sistem Operasi', '[TUMPUK/INSERT GAMBAR ANDA DI KOTAK INI]', '', 'Sebut', 1),
(83, 37, NULL, '1', 0, '', '1', '', '1', '2', 'Sistem Operasi', '[TUMPUK/INSERT GAMBAR ANDA DI KOTAK INI]', '', 'Sebut', 1),
(84, 37, 15, '1', 0, '', '1', '', '1', '2', 'Sistem Operasi', '[TUMPUK/INSERT GAMBAR ANDA DI KOTAK INI]', '', 'Sebut', 1),
(85, 37, 15, '1', 0, 'Sistem Operasi', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama?', '[TUMPUK/INSERT GAMBAR ANDA DI KOTAK INI]', '', 'CPU', 'RAM', 'Harddisk', 'Monitor', 'Keybo', 2),
(86, 37, 15, '1', 0, 'Sistem Operasi', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama?', '[TUMPUK/INSERT GAMBAR ANDA DI KOTAK INI]', '', 'CPU', 'RAM', 'Harddisk', 'Monitor', 'Keybo', 2),
(87, 37, 15, '1', 0, 'Sistem Operasi', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama?', NULL, 'CPU', 'RAM', 'Harddisk', 'Monitor', 'Keyboard', 'a', 2),
(88, 38, 9, '1', 0, 'Sistem Operasi', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama?', NULL, 'CPU', 'RAM', 'Harddisk', 'Monitor', 'Keyboard', 'a', 2),
(89, 38, 9, '1', 0, 'Sistem Operasi', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama?', 'soal_1779421348_6a0fd0a401edd.jpeg', 'CPU', 'RAM', 'Harddisk', 'Monitor', 'Keyboard', 'a', 2),
(90, 39, 12, '1', 0, 'Sistem Operasi', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama?', NULL, 'CPU', 'RAM', 'Harddisk', 'Monitor', 'Keyboard', 'a', 2),
(91, 15, 9, '1', 0, 'Sistem Operasi', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama??', 'soal_1779677889_6a13bac136a33.jpeg', 'CPU', 'RAM', 'Harddisk', 'Monitor', 'Keyboard', 'a', 3),
(92, 40, 12, '1', 0, 'Sistem Operasi', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama?', 'soal_1779678409_6a13bcc93ac6e.jpeg', 'CPU', 'RAM', 'Harddisk', 'Monitor', 'Keyboard', 'a', 2),
(93, NULL, 12, '1', 0, NULL, 'apa', NULL, '1', '2', '3', '4', '5', 'D', 1),
(94, 15, 9, '1', 0, 'Sistem Operasi', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama?', 'soal_1780367511_6a1e40970aa68.jpeg', 'CPU', 'RAM', 'Harddisk', 'Monitor', 'Keyboard', 'a', 2),
(95, 15, 9, '2', 0, 'Sistem Operasi', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama?', NULL, 'CPU', 'RAM', 'Harddisk', 'Monitor', 'Keyboard', 'a', 2);

-- --------------------------------------------------------

--
-- Table structure for table `tb_tab_switch_log`
--

CREATE TABLE `tb_tab_switch_log` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `ujian_id` int(11) NOT NULL,
  `switch_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_tata_tertib`
--

CREATE TABLE `tb_tata_tertib` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text NOT NULL,
  `kategori` enum('Siswa','Guru','Ujian','Umum') DEFAULT 'Ujian',
  `status` enum('Aktif','Nonaktif') DEFAULT 'Aktif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_tata_tertib`
--

INSERT INTO `tb_tata_tertib` (`id`, `judul`, `deskripsi`, `kategori`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Tata Tertib STS Genap 2025/2026', '1. Peserta Sumatif Tengah Semester (STS) Genap memasuki ruangan 15 menit sebelum pelaksanaan ujian.\r\n2. Peserta ujian membawa Nomor Peserta Sumatif Tengah Semester (STS) Genap.\r\n3. Peserta ujian Login dengan menggunakan User yang tertera dikartu Peserta.\r\n4. Peserta ujian:\r\n   - Dilarang Membuka Tab lain selain PIJAR.\r\n   - Nonaktifkan Mode Sleep.\r\n   - Nonaktifkan Notif SMS.\r\n   - Hindari menyentuh tombol kembali.\r\n   - 3 kali keluar dari PIJAR/terblokir, tidak dapat melanjutkan ujian.\r\n5. Peserta ujian mengisi daftar hadir menggunakan Bolpoint.\r\n6. Bagi peserta ujian yang tidak memiliki Handphone dapat mengerjakan di Ruang Guru.\r\n7. Selama Sumatif Tengah Semester (STS) Genap berlangsung, peserta hanya dapat meninggalkan ruangan dengan izin dan pengawasan dari pengawas ruang.\r\n8. Peserta STS Genap dapat menyampaikan kepihak panitia terkait kendala teknis.\r\n9. Peserta STS Genap yang telah selesai mengerjakan soal ujian sebelum waktu STS berakhir tidak diperbolehkan meninggalkan ruangan sebelum berakhirnya waktu ujian.\r\n10. Peserta ujian yang tidak hadir, bisa mengikuti susulan dengan menghubungi guru mata pelajaran masing-masing.', 'Ujian', 'Aktif', '2026-04-02 06:41:25', '2026-05-19 04:44:39');

-- --------------------------------------------------------

--
-- Table structure for table `tb_ujian`
--

CREATE TABLE `tb_ujian` (
  `id_ujian` int(11) NOT NULL,
  `id_mapel` int(11) NOT NULL,
  `id_guru` int(11) NOT NULL,
  `judul_ujian` varchar(200) NOT NULL,
  `tanggal_ujian` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `durasi_menit` int(11) NOT NULL,
  `token` varchar(10) DEFAULT NULL,
  `tampil_nilai` enum('0','1') DEFAULT '0',
  `acak_soal` enum('0','1') DEFAULT '0',
  `anti_tab` enum('0','1') DEFAULT '0',
  `status_ujian` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_ujian`
--

INSERT INTO `tb_ujian` (`id_ujian`, `id_mapel`, `id_guru`, `judul_ujian`, `tanggal_ujian`, `jam_mulai`, `jam_selesai`, `durasi_menit`, `token`, `tampil_nilai`, `acak_soal`, `anti_tab`, `status_ujian`) VALUES
(14, 10, 8, 'STS GANJIL', '2026-02-25', '08:00:00', '09:30:00', 90, '7AAB3F', '1', '1', '1', 'aktif'),
(15, 9, 74, 'PTS GANJIL 2026', '2026-07-14', '13:00:00', '14:30:00', 90, '5B51C2', '1', '1', '1', 'aktif'),
(17, 12, 8, 'PTS GANJIL 2026', '2026-07-14', '13:00:00', '00:00:00', 90, 'A8852F', '0', '0', '0', 'aktif'),
(18, 13, 74, 'UKK ', '2026-05-11', '14:00:00', '00:00:00', 90, '36BF66', '0', '0', '0', 'aktif'),
(19, 12, 74, 'UAS-KKA GENAP 2026', '2026-05-11', '15:00:00', '00:00:00', 90, '8679EF', '0', '0', '0', 'aktif'),
(20, 10, 74, 'UAS GENAP 2026', '2026-05-13', '09:00:00', '00:00:00', 90, 'CC4DE0', '0', '0', '0', 'aktif'),
(21, 8, 80, 'UTS', '2026-05-13', '09:09:00', '00:00:00', 90, '93CE92', '0', '0', '0', 'aktif'),
(22, 9, 80, 'UAS BAHASA LAMPUNG', '2026-05-13', '09:35:00', '00:00:00', 90, '69DDE4', '0', '0', '0', 'aktif'),
(23, 10, 80, 'UTS INFORMATIKA', '2026-05-13', '10:22:00', '00:00:00', 90, 'BBF296', '0', '0', '0', 'aktif'),
(24, 12, 74, 'UTS BASIS DATA', '2026-05-13', '10:24:00', '00:00:00', 90, '383ED7', '0', '0', '0', 'aktif'),
(25, 8, 80, 'UTS MATEMATIKA', '2026-05-13', '11:00:00', '00:00:00', 90, '55B3B9', '0', '0', '0', 'aktif'),
(26, 12, 80, 'UKK BASIS DATA', '2026-05-13', '11:01:00', '00:00:00', 90, '0D9ABC', '0', '0', '0', 'aktif'),
(27, 14, 74, 'UTS PKWU', '2026-05-13', '11:19:00', '00:00:00', 90, 'B2C51C', '0', '0', '0', 'aktif'),
(28, 10, 74, 'UAS GENAP', '2026-05-18', '21:27:00', '00:00:00', 90, '9990B4', '0', '0', '0', 'aktif'),
(29, 9, 74, 'uts b lampung', '2026-05-19', '12:06:00', '00:00:00', 90, 'B2CAB5', '0', '0', '0', 'aktif'),
(30, 10, 74, 'PTS GANJIL 2026', '2026-05-19', '12:13:00', '00:00:00', 90, '55CB29', '0', '0', '0', 'aktif'),
(31, 14, 74, 'UKK ', '2026-05-19', '12:18:00', '00:00:00', 90, '5C4F6D', '0', '0', '0', 'aktif'),
(32, 8, 74, 'PTS GANJIL 2026', '2026-05-19', '12:22:00', '00:00:00', 90, '21B037', '0', '0', '0', 'aktif'),
(33, 8, 74, 'UKK ', '2026-05-19', '12:24:00', '00:00:00', 90, 'C0B28D', '0', '0', '0', 'aktif'),
(34, 12, 74, 'PTS GANJIL 2026', '2026-05-19', '12:26:00', '00:00:00', 90, '01FFFF', '0', '0', '0', 'aktif'),
(35, 12, 74, 'UAS GENAP 2026', '2026-05-19', '12:33:00', '00:00:00', 90, 'BF1A0A', '0', '0', '0', 'aktif'),
(36, 15, 74, 'pplg', '2026-05-19', '12:39:00', '00:00:00', 90, '56941D', '0', '0', '0', 'aktif'),
(37, 15, 74, 'UKK ', '2026-05-20', '12:52:00', '00:00:00', 90, 'B3CE7F', '0', '0', '0', 'aktif'),
(38, 9, 74, 'UTS lampung', '2026-05-22', '09:00:00', '00:00:00', 90, '3C75BE', '0', '0', '0', 'aktif'),
(39, 12, 74, 'ujian baseng', '2026-05-22', '10:45:00', '00:00:00', 90, '0CE0D8', '0', '0', '0', 'aktif'),
(40, 12, 74, 'semester genap', '2026-05-25', '10:06:00', '00:00:00', 90, '1931AD', '0', '0', '0', 'aktif'),
(41, 8, 1, 'ujian', '2026-06-03', '12:00:00', '13:30:00', 90, '483A58', '0', '1', '1', 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `tb_ujian_kelas`
--

CREATE TABLE `tb_ujian_kelas` (
  `id` int(11) NOT NULL,
  `id_ujian` int(11) NOT NULL,
  `id_kelas` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_ujian_kelas`
--

INSERT INTO `tb_ujian_kelas` (`id`, `id_ujian`, `id_kelas`) VALUES
(121, 14, 37),
(122, 14, 34),
(123, 15, 37),
(125, 25, 38),
(126, 26, 34),
(127, 27, 34),
(128, 28, 37),
(129, 29, 37),
(130, 30, 37),
(131, 31, 37),
(132, 32, 37),
(133, 33, 37),
(134, 34, 37),
(135, 35, 37),
(136, 36, 37),
(137, 37, 36),
(138, 38, 37),
(139, 39, 37),
(140, 40, 37),
(141, 41, 48);

-- --------------------------------------------------------

--
-- Table structure for table `tb_ujian_soal`
--

CREATE TABLE `tb_ujian_soal` (
  `id_mapping` int(11) NOT NULL,
  `id_ujian` int(11) NOT NULL,
  `id_soal` int(11) NOT NULL,
  `ditambahkan_pada` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_ujian_soal`
--

INSERT INTO `tb_ujian_soal` (`id_mapping`, `id_ujian`, `id_soal`, `ditambahkan_pada`) VALUES
(1, 12, 8, '2026-05-10 20:42:07'),
(2, 11, 49, '2026-05-10 20:50:34'),
(5, 15, 51, '2026-05-11 14:06:57'),
(6, 15, 61, '2026-05-11 14:30:41'),
(7, 19, 60, '2026-05-11 14:31:05'),
(8, 19, 59, '2026-05-11 14:31:05'),
(9, 20, 62, '2026-05-13 08:56:16'),
(10, 21, 58, '2026-05-13 09:10:23'),
(11, 21, 57, '2026-05-13 09:10:23'),
(12, 21, 56, '2026-05-13 09:10:23'),
(13, 22, 63, '2026-05-13 09:29:57'),
(14, 22, 61, '2026-05-13 09:29:57'),
(15, 22, 53, '2026-05-13 09:29:57'),
(16, 22, 52, '2026-05-13 09:29:57'),
(17, 22, 51, '2026-05-13 09:29:57'),
(18, 24, 60, '2026-05-13 10:24:45'),
(19, 24, 59, '2026-05-13 10:24:45'),
(20, 18, 65, '2026-05-13 10:42:46'),
(21, 18, 64, '2026-05-13 10:42:46'),
(22, 25, 58, '2026-05-13 10:58:54'),
(23, 25, 57, '2026-05-13 10:58:54'),
(24, 25, 56, '2026-05-13 10:58:54'),
(25, 26, 60, '2026-05-13 11:00:54'),
(26, 26, 59, '2026-05-13 11:00:54'),
(27, 27, 68, '2026-05-13 11:21:23'),
(28, 27, 67, '2026-05-13 11:21:23'),
(29, 15, 63, '2026-05-18 20:56:28'),
(30, 28, 62, '2026-05-18 21:28:24'),
(31, 29, 71, '2026-05-19 12:07:03'),
(32, 29, 70, '2026-05-19 12:07:03'),
(33, 29, 63, '2026-05-19 12:07:03'),
(34, 29, 61, '2026-05-19 12:07:03'),
(35, 29, 53, '2026-05-19 12:07:03'),
(36, 29, 52, '2026-05-19 12:07:03'),
(37, 29, 51, '2026-05-19 12:07:03'),
(38, 30, 62, '2026-05-19 12:14:22'),
(39, 31, 68, '2026-05-19 12:19:00'),
(40, 31, 67, '2026-05-19 12:19:00'),
(41, 32, 73, '2026-05-19 12:22:28'),
(42, 32, 72, '2026-05-19 12:22:28'),
(43, 32, 58, '2026-05-19 12:22:28'),
(44, 32, 57, '2026-05-19 12:22:28'),
(45, 32, 56, '2026-05-19 12:22:28'),
(46, 33, 73, '2026-05-19 12:25:02'),
(47, 33, 72, '2026-05-19 12:25:02'),
(48, 33, 58, '2026-05-19 12:25:02'),
(49, 33, 57, '2026-05-19 12:25:02'),
(50, 33, 56, '2026-05-19 12:25:02'),
(51, 34, 75, '2026-05-19 12:27:54'),
(52, 34, 74, '2026-05-19 12:27:54'),
(53, 34, 60, '2026-05-19 12:27:54'),
(54, 34, 59, '2026-05-19 12:27:54'),
(55, 35, 75, '2026-05-19 12:33:33'),
(56, 35, 74, '2026-05-19 12:33:33'),
(57, 35, 60, '2026-05-19 12:33:33'),
(58, 35, 59, '2026-05-19 12:33:33'),
(59, 36, 80, '2026-05-19 12:40:16'),
(60, 36, 79, '2026-05-19 12:40:16'),
(61, 36, 78, '2026-05-19 12:40:16'),
(62, 36, 77, '2026-05-19 12:40:16'),
(63, 36, 76, '2026-05-19 12:40:16'),
(65, 37, 80, '2026-05-21 10:55:10'),
(66, 37, 79, '2026-05-21 10:55:10'),
(67, 37, 78, '2026-05-21 10:55:10'),
(68, 37, 77, '2026-05-21 10:55:10'),
(69, 37, 76, '2026-05-21 10:55:10'),
(70, 38, 89, '2026-05-22 10:02:38'),
(71, 38, 88, '2026-05-22 10:42:41'),
(72, 38, 71, '2026-05-22 10:42:41'),
(73, 38, 70, '2026-05-22 10:42:41'),
(74, 38, 63, '2026-05-22 10:42:41'),
(75, 38, 61, '2026-05-22 10:42:41'),
(76, 38, 53, '2026-05-22 10:42:41'),
(77, 38, 52, '2026-05-22 10:42:41'),
(78, 38, 51, '2026-05-22 10:42:41'),
(79, 39, 90, '2026-05-22 10:45:44'),
(80, 39, 75, '2026-05-22 10:45:51'),
(81, 39, 74, '2026-05-22 10:45:51'),
(82, 39, 60, '2026-05-22 10:45:51'),
(83, 39, 59, '2026-05-22 10:45:51'),
(84, 15, 91, '2026-05-25 09:57:57'),
(85, 40, 92, '2026-05-25 10:06:41'),
(86, 40, 90, '2026-05-25 10:06:57'),
(87, 40, 75, '2026-05-25 10:06:57'),
(88, 40, 74, '2026-05-25 10:06:57'),
(89, 40, 60, '2026-05-25 10:06:57'),
(90, 40, 59, '2026-05-25 10:06:57'),
(91, 15, 94, '2026-06-02 09:31:36'),
(92, 15, 71, '2026-06-02 09:32:27'),
(93, 15, 70, '2026-06-02 09:32:27'),
(94, 15, 95, '2026-06-02 09:33:17');

-- --------------------------------------------------------

--
-- Table structure for table `tb_users`
--

CREATE TABLE `tb_users` (
  `id_user` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `ruangan` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','guru','siswa','pengawas') NOT NULL,
  `id_kelas` int(11) DEFAULT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `agama` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_users`
--

INSERT INTO `tb_users` (`id_user`, `nama_lengkap`, `ruangan`, `username`, `password`, `role`, `id_kelas`, `nisn`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `nis`, `agama`, `alamat`, `created_at`, `status`) VALUES
(1, 'Administrator Utama', NULL, 'admin', '$2y$10$bIMDCZqqU/UqG8HnpoWqZOoFrfTyiLDczAlXAqDrxsIF.xwkvBpH.', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 12:14:49', 'aktif'),
(8, 'Zacky Ahmad', NULL, '1988131297123', '$2y$10$8STRwe78ChRSZ7TzGdW1nO6s5zMjOSxSkrNoFcZeTvxkw2sck1VsK', 'guru', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 15:46:16', 'aktif'),
(74, 'CITRA SOPTI SETIANINGSIH', NULL, '23753093', '$2y$10$0Ly4Aqxv.UBS17ilSp.Sv.lvIHdl7pbTEtIzVL8hAGR158CrglMa.', 'guru', NULL, NULL, 'P', 'ADIWARNO', '2005-07-14', NULL, 'Islam', NULL, '2026-04-30 05:03:50', 'aktif'),
(75, 'siswasmkn8bandarlampung', NULL, 'siswa', '$2y$10$nOs30u3NDo6UZz0a.6uph.DS1qQ6.WxNTahIEgPagyBCoStuxXagm', 'siswa', 37, '123456', 'L', 'Bandar Lampung', '2007-11-11', '123456', 'Islam', 'kemiling, bandar lampung', '2026-05-06 12:13:48', 'aktif'),
(76, 'pengawas smkn8 balam', NULL, 'pengawas1', '$2y$10$mYg75qfA9Ypd00FqihxAV..VX4h8FKGKmGLI33Efgh9hf7KW2HeRK', 'pengawas', NULL, NULL, 'L', 'Bandar Lampung', '2000-12-12', NULL, 'Islam', NULL, '2026-05-06 13:47:53', 'aktif'),
(79, 'Dimas Prasetiyo', NULL, '23753054', '$2y$10$6OUN5eFaT/7mDUPuF4vVSuFfaN3QHar89HQQLD1SjoyX9mwRsp6CC', 'siswa', 34, '7124', 'L', 'tulang bawang', '2004-12-07', '23753054', 'Islam', 'gang senen', '2026-05-13 02:05:56', 'aktif'),
(80, 'guru skapan', NULL, 'guru', '$2y$10$rGG76dgPtmwtYL2WRypQVOTdxqep7j5QFAFEQWB8HMNJumR4.uwze', 'guru', NULL, NULL, 'L', 'ADIWARNO', '2000-05-13', NULL, 'Kristen', 'imbon', '2026-05-13 02:07:15', 'aktif'),
(81, 'Ika Novalia', NULL, 'pengaawas2', '$2y$10$ols1VJVR2mAUdSYcskmjeOs1NSfNGxgvRYqBPN0HOD.S199OQ2jae', 'pengawas', NULL, NULL, 'P', 'tulang bawang', '2000-12-12', NULL, 'Islam', NULL, '2026-05-18 13:18:34', 'aktif'),
(82, 'dimas', NULL, '23753099', '$2y$10$rttRZlNqr9JbTh84U6xUC.r8bPtGWkLMJBxlOKOy8J7suqzxZ85Ce', 'siswa', 39, '23753099', NULL, NULL, NULL, '23753099', NULL, NULL, '2026-05-19 04:43:26', 'aktif'),
(83, 'dimas', NULL, '236', '$2y$10$yCr61kXwLrPnM9fd4/HovevcbK8HikKifcpQG8itF.39wtqqm1Rt2', 'siswa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-19 05:12:38', 'aktif'),
(84, 'Dimas Prasetyo', NULL, 'Dimas Adrian', '$2y$10$f60cl.Pq./kgjKofAuqSZe/h9FF7xqp/IzuImyYsNjneb.wl0ugam', 'siswa', 37, '23753055', 'L', 'tulangbawang, 7 desember 2004', '2004-12-07', '23753055', 'Islam', 'apa aja kepo', '2026-05-26 03:19:11', 'aktif'),
(86, 'caca i', 'Lab PPLG', 'caca', '$2y$10$EByh2Zua3GQ7RQckPNbIn.p7opPsAWidB2Sg3YGVhDZPADAf/wTei', 'guru', NULL, NULL, 'P', 'Bandar Lampung', '1999-11-11', NULL, 'Islam', NULL, '2026-06-02 02:07:18', 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `ujian`
--

CREATE TABLE `ujian` (
  `id` int(11) NOT NULL,
  `guru_id` int(11) NOT NULL,
  `nama_ujian` varchar(100) NOT NULL,
  `mata_pelajaran` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `waktu` int(11) NOT NULL COMMENT 'Durasi dalam menit',
  `jumlah_soal` int(11) DEFAULT 0,
  `status` enum('draft','aktif','selesai') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ujian`
--

INSERT INTO `ujian` (`id`, `guru_id`, `nama_ujian`, `mata_pelajaran`, `deskripsi`, `tanggal_mulai`, `tanggal_selesai`, `waktu`, `jumlah_soal`, `status`, `created_at`) VALUES
(1, 1, 'UTS Matematika Semester Ganjil', 'Matematika', 'Ujian Tengah Semester Matematika Kelas XI', '2026-05-05', '2026-05-12', 90, 25, 'aktif', '2026-05-05 03:53:50'),
(2, 1, 'PTS Bahasa Indonesia', 'Bahasa Indonesia', 'Penilaian Tengah Semester', '2026-05-05', '2026-05-12', 60, 20, 'aktif', '2026-05-05 03:53:50'),
(3, 1, 'Ujian Akhir Semester IPA', 'IPA', 'Ujian Akhir Semester Genap', '2026-05-12', '2026-05-19', 90, 30, 'draft', '2026-05-05 03:53:50'),
(4, 1, 'UTS Matematika Semester Ganjil', 'Matematika', 'Ujian Tengah Semester Matematika Kelas XI', '2026-05-05', '2026-05-12', 90, 25, 'aktif', '2026-05-05 03:54:01'),
(5, 1, 'PTS Bahasa Indonesia', 'Bahasa Indonesia', 'Penilaian Tengah Semester', '2026-05-05', '2026-05-12', 60, 20, 'aktif', '2026-05-05 03:54:01'),
(6, 1, 'Ujian Akhir Semester IPA', 'IPA', 'Ujian Akhir Semester Genap', '2026-05-12', '2026-05-19', 90, 30, 'draft', '2026-05-05 03:54:01'),
(7, 1, 'UTS Matematika Semester Ganjil', 'Matematika', 'Ujian Tengah Semester Matematika Kelas XI', '2026-05-05', '2026-05-12', 90, 25, 'aktif', '2026-05-05 03:54:32'),
(8, 1, 'PTS Bahasa Indonesia', 'Bahasa Indonesia', 'Penilaian Tengah Semester', '2026-05-05', '2026-05-12', 60, 20, 'aktif', '2026-05-05 03:54:32'),
(9, 1, 'Ujian Akhir Semester IPA', 'IPA', 'Ujian Akhir Semester Genap', '2026-05-12', '2026-05-19', 90, 30, 'draft', '2026-05-05 03:54:32'),
(10, 74, 'uts bindo', 'bahasa indonesia', 'semangat', '2026-05-07', '2026-05-08', 90, 30, 'aktif', '2026-05-07 16:07:14'),
(11, 74, 'UTS KKA', 'Koding dan kecerdasan artificial', 'smgt', '2026-05-07', '2026-05-07', 90, 30, 'draft', '2026-05-07 16:31:05'),
(12, 74, 'UTS bahasa indonesia', 'bahasa indonesia', 'semangat aja', '2026-05-10', '2026-05-10', 90, 30, 'aktif', '2026-05-10 12:57:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi_pengawas`
--
ALTER TABLE `absensi_pengawas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengawas_id` (`pengawas_id`);

--
-- Indexes for table `absensi_siswa`
--
ALTER TABLE `absensi_siswa`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `absen_pengawas`
--
ALTER TABLE `absen_pengawas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hasil_ujian`
--
ALTER TABLE `hasil_ujian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `ujian_id` (`ujian_id`);

--
-- Indexes for table `jadwal_ujian`
--
ALTER TABLE `jadwal_ujian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ujian_id` (`ujian_id`);

--
-- Indexes for table `soal`
--
ALTER TABLE `soal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `guru_id` (`guru_id`),
  ADD KEY `ujian_id` (`ujian_id`);

--
-- Indexes for table `tb_absensi`
--
ALTER TABLE `tb_absensi`
  ADD PRIMARY KEY (`id_absensi`);

--
-- Indexes for table `tb_hasil_ujian`
--
ALTER TABLE `tb_hasil_ujian`
  ADD PRIMARY KEY (`id_hasil`);

--
-- Indexes for table `tb_jadwal_pengawas`
--
ALTER TABLE `tb_jadwal_pengawas`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `idx_guru` (`id_guru`),
  ADD KEY `idx_ruangan` (`id_ruangan`),
  ADD KEY `idx_mapel` (`id_mapel`);

--
-- Indexes for table `tb_jawaban`
--
ALTER TABLE `tb_jawaban`
  ADD PRIMARY KEY (`id_jawaban`);

--
-- Indexes for table `tb_kartu_ujian`
--
ALTER TABLE `tb_kartu_ujian`
  ADD PRIMARY KEY (`id_kartu`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD KEY `idx_kelas` (`kelas`);

--
-- Indexes for table `tb_kehadiran`
--
ALTER TABLE `tb_kehadiran`
  ADD PRIMARY KEY (`id_kehadiran`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_ujian` (`id_ujian`);

--
-- Indexes for table `tb_kelas`
--
ALTER TABLE `tb_kelas`
  ADD PRIMARY KEY (`id_kelas`);

--
-- Indexes for table `tb_mapel`
--
ALTER TABLE `tb_mapel`
  ADD PRIMARY KEY (`id_mapel`);

--
-- Indexes for table `tb_nilai`
--
ALTER TABLE `tb_nilai`
  ADD PRIMARY KEY (`id_nilai`),
  ADD KEY `id_siswa` (`id_siswa`),
  ADD KEY `tb_nilai_ibfk_2` (`id_ujian`);

--
-- Indexes for table `tb_soal`
--
ALTER TABLE `tb_soal`
  ADD PRIMARY KEY (`id_soal`),
  ADD KEY `id_ujian` (`id_ujian`);

--
-- Indexes for table `tb_tab_switch_log`
--
ALTER TABLE `tb_tab_switch_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`,`ujian_id`);

--
-- Indexes for table `tb_tata_tertib`
--
ALTER TABLE `tb_tata_tertib`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tb_ujian`
--
ALTER TABLE `tb_ujian`
  ADD PRIMARY KEY (`id_ujian`),
  ADD KEY `id_mapel` (`id_mapel`),
  ADD KEY `id_guru` (`id_guru`);

--
-- Indexes for table `tb_ujian_kelas`
--
ALTER TABLE `tb_ujian_kelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ujian` (`id_ujian`),
  ADD KEY `id_kelas` (`id_kelas`);

--
-- Indexes for table `tb_ujian_soal`
--
ALTER TABLE `tb_ujian_soal`
  ADD PRIMARY KEY (`id_mapping`);

--
-- Indexes for table `tb_users`
--
ALTER TABLE `tb_users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `id_kelas` (`id_kelas`);

--
-- Indexes for table `ujian`
--
ALTER TABLE `ujian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `guru_id` (`guru_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi_pengawas`
--
ALTER TABLE `absensi_pengawas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `absensi_siswa`
--
ALTER TABLE `absensi_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `absen_pengawas`
--
ALTER TABLE `absen_pengawas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hasil_ujian`
--
ALTER TABLE `hasil_ujian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `jadwal_ujian`
--
ALTER TABLE `jadwal_ujian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `soal`
--
ALTER TABLE `soal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tb_absensi`
--
ALTER TABLE `tb_absensi`
  MODIFY `id_absensi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_hasil_ujian`
--
ALTER TABLE `tb_hasil_ujian`
  MODIFY `id_hasil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `tb_jadwal_pengawas`
--
ALTER TABLE `tb_jadwal_pengawas`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_jawaban`
--
ALTER TABLE `tb_jawaban`
  MODIFY `id_jawaban` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `tb_kartu_ujian`
--
ALTER TABLE `tb_kartu_ujian`
  MODIFY `id_kartu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tb_kehadiran`
--
ALTER TABLE `tb_kehadiran`
  MODIFY `id_kehadiran` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_kelas`
--
ALTER TABLE `tb_kelas`
  MODIFY `id_kelas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `tb_mapel`
--
ALTER TABLE `tb_mapel`
  MODIFY `id_mapel` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tb_nilai`
--
ALTER TABLE `tb_nilai`
  MODIFY `id_nilai` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tb_soal`
--
ALTER TABLE `tb_soal`
  MODIFY `id_soal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `tb_tab_switch_log`
--
ALTER TABLE `tb_tab_switch_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_tata_tertib`
--
ALTER TABLE `tb_tata_tertib`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_ujian`
--
ALTER TABLE `tb_ujian`
  MODIFY `id_ujian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `tb_ujian_kelas`
--
ALTER TABLE `tb_ujian_kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `tb_ujian_soal`
--
ALTER TABLE `tb_ujian_soal`
  MODIFY `id_mapping` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `tb_users`
--
ALTER TABLE `tb_users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `ujian`
--
ALTER TABLE `ujian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `hasil_ujian`
--
ALTER TABLE `hasil_ujian`
  ADD CONSTRAINT `hasil_ujian_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `tb_users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `hasil_ujian_ibfk_2` FOREIGN KEY (`ujian_id`) REFERENCES `ujian` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jadwal_ujian`
--
ALTER TABLE `jadwal_ujian`
  ADD CONSTRAINT `jadwal_ujian_ibfk_1` FOREIGN KEY (`ujian_id`) REFERENCES `ujian` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `soal`
--
ALTER TABLE `soal`
  ADD CONSTRAINT `soal_ibfk_1` FOREIGN KEY (`guru_id`) REFERENCES `tb_users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `soal_ibfk_2` FOREIGN KEY (`ujian_id`) REFERENCES `ujian` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tb_nilai`
--
ALTER TABLE `tb_nilai`
  ADD CONSTRAINT `tb_nilai_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `tb_users` (`id_user`),
  ADD CONSTRAINT `tb_nilai_ibfk_2` FOREIGN KEY (`id_ujian`) REFERENCES `tb_ujian` (`id_ujian`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_ujian`
--
ALTER TABLE `tb_ujian`
  ADD CONSTRAINT `tb_ujian_ibfk_1` FOREIGN KEY (`id_mapel`) REFERENCES `tb_mapel` (`id_mapel`),
  ADD CONSTRAINT `tb_ujian_ibfk_2` FOREIGN KEY (`id_guru`) REFERENCES `tb_users` (`id_user`);

--
-- Constraints for table `tb_ujian_kelas`
--
ALTER TABLE `tb_ujian_kelas`
  ADD CONSTRAINT `tb_ujian_kelas_ibfk_1` FOREIGN KEY (`id_ujian`) REFERENCES `tb_ujian` (`id_ujian`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_ujian_kelas_ibfk_2` FOREIGN KEY (`id_kelas`) REFERENCES `tb_kelas` (`id_kelas`) ON DELETE CASCADE;

--
-- Constraints for table `tb_users`
--
ALTER TABLE `tb_users`
  ADD CONSTRAINT `tb_users_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `tb_kelas` (`id_kelas`) ON DELETE SET NULL;

--
-- Constraints for table `ujian`
--
ALTER TABLE `ujian`
  ADD CONSTRAINT `ujian_ibfk_1` FOREIGN KEY (`guru_id`) REFERENCES `tb_users` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
