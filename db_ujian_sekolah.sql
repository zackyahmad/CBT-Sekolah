-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 24 Feb 2026 pada 03.24
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

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
-- Struktur dari tabel `tb_jawaban`
--

CREATE TABLE `tb_jawaban` (
  `id_jawaban` int(11) NOT NULL,
  `id_nilai` int(11) NOT NULL,
  `id_ujian` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `id_soal` int(11) NOT NULL,
  `jawaban_siswa` text DEFAULT NULL,
  `ragu_ragu` enum('0','1') DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_kelas`
--

CREATE TABLE `tb_kelas` (
  `id_kelas` int(11) NOT NULL,
  `tingkat` enum('X','XI','XII') DEFAULT NULL,
  `nama_kelas` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_kelas`
--

INSERT INTO `tb_kelas` (`id_kelas`, `tingkat`, `nama_kelas`) VALUES
(34, 'X', 'X-PPLG 2'),
(35, 'XI', 'XI-PPLG 1'),
(36, 'XII', 'XII-PPLG 1'),
(37, 'X', 'X-PPLG 1'),
(38, 'XI', 'XI-PPLG 2'),
(39, 'XII', 'XII-PPLG 2');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_mapel`
--

CREATE TABLE `tb_mapel` (
  `id_mapel` int(11) NOT NULL,
  `kode_mapel` varchar(20) DEFAULT NULL,
  `nama_mapel` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_mapel`
--

INSERT INTO `tb_mapel` (`id_mapel`, `kode_mapel`, `nama_mapel`) VALUES
(8, 'MTK', 'MATEMATIKA'),
(9, 'BHSLPG', 'BAHASA LAMPUNG'),
(10, 'INFORMATIKA', 'INFORMATIKA');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_nilai`
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

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_soal`
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
-- Dumping data untuk tabel `tb_soal`
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
(50, NULL, 6, '1', 0, 'sagata / pantun', 'Pidato adat biasanya disampaikan pada acara …', NULL, 'Sekolah', 'Olahraga', 'Adat istiadat', 'Pasar', 'Hiburan', 'c', 5);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_ujian`
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
-- Dumping data untuk tabel `tb_ujian`
--

INSERT INTO `tb_ujian` (`id_ujian`, `id_mapel`, `id_guru`, `judul_ujian`, `tanggal_ujian`, `jam_mulai`, `jam_selesai`, `durasi_menit`, `token`, `tampil_nilai`, `acak_soal`, `anti_tab`, `status_ujian`) VALUES
(14, 10, 8, 'STS GANJIL', '2026-02-25', '08:00:00', '09:30:00', 90, '7AAB3F', '1', '1', '1', 'aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_ujian_kelas`
--

CREATE TABLE `tb_ujian_kelas` (
  `id` int(11) NOT NULL,
  `id_ujian` int(11) NOT NULL,
  `id_kelas` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_ujian_kelas`
--

INSERT INTO `tb_ujian_kelas` (`id`, `id_ujian`, `id_kelas`) VALUES
(121, 14, 37),
(122, 14, 34);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_users`
--

CREATE TABLE `tb_users` (
  `id_user` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','guru','siswa') NOT NULL,
  `id_kelas` int(11) DEFAULT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `agama` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_users`
--

INSERT INTO `tb_users` (`id_user`, `nama_lengkap`, `username`, `password`, `role`, `id_kelas`, `nisn`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `nis`, `agama`, `alamat`, `created_at`) VALUES
(1, 'Administrator Utama', 'admin', '$2y$10$bIMDCZqqU/UqG8HnpoWqZOoFrfTyiLDczAlXAqDrxsIF.xwkvBpH.', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 12:14:49'),
(5, 'Nama Guru Contoh', '19800101', '$2y$10$TSI8f/ukBrYxk8nxkCD6OuQnH6wmxVBbsRs.HCQ5gBX3jFKHq77xa', 'guru', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 15:41:11'),
(8, 'Zacky Ahmad', '1988131297123', '$2y$10$8STRwe78ChRSZ7TzGdW1nO6s5zMjOSxSkrNoFcZeTvxkw2sck1VsK', 'guru', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 15:46:16');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `tb_jawaban`
--
ALTER TABLE `tb_jawaban`
  ADD PRIMARY KEY (`id_jawaban`),
  ADD KEY `id_nilai` (`id_nilai`),
  ADD KEY `id_soal` (`id_soal`),
  ADD KEY `idx_monitor` (`id_ujian`,`id_siswa`);

--
-- Indeks untuk tabel `tb_kelas`
--
ALTER TABLE `tb_kelas`
  ADD PRIMARY KEY (`id_kelas`);

--
-- Indeks untuk tabel `tb_mapel`
--
ALTER TABLE `tb_mapel`
  ADD PRIMARY KEY (`id_mapel`);

--
-- Indeks untuk tabel `tb_nilai`
--
ALTER TABLE `tb_nilai`
  ADD PRIMARY KEY (`id_nilai`),
  ADD KEY `id_siswa` (`id_siswa`),
  ADD KEY `tb_nilai_ibfk_2` (`id_ujian`);

--
-- Indeks untuk tabel `tb_soal`
--
ALTER TABLE `tb_soal`
  ADD PRIMARY KEY (`id_soal`),
  ADD KEY `id_ujian` (`id_ujian`);

--
-- Indeks untuk tabel `tb_ujian`
--
ALTER TABLE `tb_ujian`
  ADD PRIMARY KEY (`id_ujian`),
  ADD KEY `id_mapel` (`id_mapel`),
  ADD KEY `id_guru` (`id_guru`);

--
-- Indeks untuk tabel `tb_ujian_kelas`
--
ALTER TABLE `tb_ujian_kelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ujian` (`id_ujian`),
  ADD KEY `id_kelas` (`id_kelas`);

--
-- Indeks untuk tabel `tb_users`
--
ALTER TABLE `tb_users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `id_kelas` (`id_kelas`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `tb_jawaban`
--
ALTER TABLE `tb_jawaban`
  MODIFY `id_jawaban` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT untuk tabel `tb_kelas`
--
ALTER TABLE `tb_kelas`
  MODIFY `id_kelas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT untuk tabel `tb_mapel`
--
ALTER TABLE `tb_mapel`
  MODIFY `id_mapel` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `tb_nilai`
--
ALTER TABLE `tb_nilai`
  MODIFY `id_nilai` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `tb_soal`
--
ALTER TABLE `tb_soal`
  MODIFY `id_soal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT untuk tabel `tb_ujian`
--
ALTER TABLE `tb_ujian`
  MODIFY `id_ujian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `tb_ujian_kelas`
--
ALTER TABLE `tb_ujian_kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT untuk tabel `tb_users`
--
ALTER TABLE `tb_users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `tb_jawaban`
--
ALTER TABLE `tb_jawaban`
  ADD CONSTRAINT `tb_jawaban_ibfk_1` FOREIGN KEY (`id_nilai`) REFERENCES `tb_nilai` (`id_nilai`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_jawaban_ibfk_2` FOREIGN KEY (`id_soal`) REFERENCES `tb_soal` (`id_soal`);

--
-- Ketidakleluasaan untuk tabel `tb_nilai`
--
ALTER TABLE `tb_nilai`
  ADD CONSTRAINT `tb_nilai_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `tb_users` (`id_user`),
  ADD CONSTRAINT `tb_nilai_ibfk_2` FOREIGN KEY (`id_ujian`) REFERENCES `tb_ujian` (`id_ujian`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_ujian`
--
ALTER TABLE `tb_ujian`
  ADD CONSTRAINT `tb_ujian_ibfk_1` FOREIGN KEY (`id_mapel`) REFERENCES `tb_mapel` (`id_mapel`),
  ADD CONSTRAINT `tb_ujian_ibfk_2` FOREIGN KEY (`id_guru`) REFERENCES `tb_users` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `tb_ujian_kelas`
--
ALTER TABLE `tb_ujian_kelas`
  ADD CONSTRAINT `tb_ujian_kelas_ibfk_1` FOREIGN KEY (`id_ujian`) REFERENCES `tb_ujian` (`id_ujian`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_ujian_kelas_ibfk_2` FOREIGN KEY (`id_kelas`) REFERENCES `tb_kelas` (`id_kelas`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_users`
--
ALTER TABLE `tb_users`
  ADD CONSTRAINT `tb_users_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `tb_kelas` (`id_kelas`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
