<?php
// =============================================
// FILE: pages/guru/rencana_ujian.php
// =============================================
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../../index.php"); exit;
}
require_once '../../config/koneksi.php';

$id_guru   = $_SESSION['id_user'];
$nama_guru = $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? 'Guru';

// Ambil semua ujian milik guru ini dari tabel ADMIN (tb_ujian)
$stmt = $pdo->prepare("
    SELECT 
        u.*, m.nama_mapel,
        (SELECT GROUP_CONCAT(k.nama_kelas SEPARATOR ', ') 
         FROM tb_ujian_kelas uk 
         JOIN tb_kelas k ON k.id_kelas = uk.id_kelas 
         WHERE uk.id_ujian = u.id_ujian) AS kelas_list,
        (SELECT COUNT(*) FROM tb_ujian_soal us WHERE us.id_ujian = u.id_ujian) AS jml_soal_terpilih,
        (SELECT COUNT(*) FROM tb_soal ts WHERE ts.id_mapel = u.id_mapel) AS jml_bank_soal
    FROM tb_ujian u
    LEFT JOIN tb_mapel m ON m.id_mapel = u.id_mapel
    WHERE u.id_guru = ?
    ORDER BY u.tanggal_ujian DESC
");
$stmt->execute([$id_guru]);
$ujian_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total       = count($ujian_list);
$total_aktif = count(array_filter($ujian_list, fn($u) => strtolower($u['status_ujian']) === 'aktif'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rencana Ujian – Guru CBT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        * { font-family:'Inter',sans-serif; }
        .gradient-bg { background:linear-gradient(135deg,#0A192F 0%,#1E3A5F 30%,#F4A261 60%,#90BE6D 100%); background-size:200% 200%; animation:gm 10s ease infinite; }
        @keyframes gm { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        .btn-gradient { background:linear-gradient(135deg,#0A192F,#1E3A5F,#F4A261,#90BE6D); background-size:300% 300%; animation:bg 5s ease infinite; }
        @keyframes bg { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        tr:hover td { background:#f8fafc; transition: 0.2s; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<header class="gradient-bg shadow-lg py-4 px-6 flex justify-between items-center sticky top-0 z-40">
    <div class="flex items-center space-x-3">
        <a href="dashboard.php" class="text-white/70 hover:text-white mr-1 transition"><i class="fas fa-arrow-left"></i></a>
        <i class="fas fa-calendar-alt text-white text-xl"></i>
        <div>
            <h1 class="text-xl font-bold text-white">Rencana Ujian</h1>
            <p class="text-white/60 text-xs">Ujian yang ditugaskan admin kepada Anda</p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <a href="dashboard.php" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl text-sm font-semibold border border-white/30 transition">
            <i class="fas fa-home mr-1"></i> Dashboard
        </a>
    </div>
</header>

<main class="p-6 max-w-7xl mx-auto">
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-6 flex items-start gap-3">
        <i class="fas fa-info-circle text-blue-500 mt-0.5 text-lg flex-shrink-0"></i>
        <div class="text-sm text-blue-800">
            <strong>Alur Ujian:</strong> 1. Masuk ke Kelola Soal ➔ 2. Tambahkan Soal ➔ 3. <strong>Ubah Status menjadi AKTIF</strong> agar siswa bisa mengerjakannya.
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0A192F] to-[#1E3A5F] flex items-center justify-center"><i class="fas fa-calendar-alt text-white"></i></div>
            <div><div class="text-2xl font-black text-gray-800"><?= $total ?></div><div class="text-xs text-gray-500 font-bold uppercase">Total Ujian</div></div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-500 flex items-center justify-center"><i class="fas fa-play-circle text-white"></i></div>
            <div><div class="text-2xl font-black text-gray-800"><?= $total_aktif ?></div><div class="text-xs text-gray-500 font-bold uppercase">Ujian Aktif (Berjalan)</div></div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-orange-400 flex items-center justify-center"><i class="fas fa-pause-circle text-white"></i></div>
            <div><div class="text-2xl font-black text-gray-800"><?= $total - $total_aktif ?></div><div class="text-xs text-gray-500 font-bold uppercase">Tidak Aktif (Draft)</div></div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3 bg-gray-50">
            <i class="fas fa-list text-[#0A192F]"></i><h3 class="font-bold text-gray-800">Daftar Ujian Anda</h3>
        </div>

        <?php if (!empty($ujian_list)): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-white text-gray-400 text-xs uppercase tracking-wide border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">No</th>
                        <th class="px-6 py-4">Judul Ujian</th>
                        <th class="px-6 py-4">Kelas Tujuan</th>
                        <th class="px-6 py-4">Waktu</th>
                        <th class="px-6 py-4 text-center">Soal</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                <?php $no=1; foreach ($ujian_list as $u): ?>
                <tr>
                    <td class="px-6 py-4 text-gray-400"><?= $no++ ?></td>
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-800 text-base"><?= htmlspecialchars($u['judul_ujian']) ?></div>
                        <div class="text-xs font-semibold text-blue-600 bg-blue-50 inline-block px-2 py-0.5 rounded mt-1"><?= htmlspecialchars($u['nama_mapel'] ?? '-') ?></div>
                    </td>
                    <td class="px-6 py-4 text-gray-600 font-semibold"><?= htmlspecialchars($u['kelas_list'] ?? 'Belum diset') ?></td>
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-700"><i class="far fa-calendar text-gray-400 mr-1"></i> <?= !empty($u['tanggal_ujian']) ? date('d M Y', strtotime($u['tanggal_ujian'])) : '-' ?></div>
                        <div class="text-xs text-gray-500 mt-0.5"><i class="far fa-clock text-gray-400 mr-1"></i> <?= substr($u['jam_mulai'],0,5) ?> – <?= substr($u['jam_selesai'],0,5) ?> (<?= $u['durasi_menit'] ?>m)</div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php $jml = (int)$u['jml_soal_terpilih']; $bank = (int)$u['jml_bank_soal']; ?>
                        <div class="font-black text-lg <?= $jml > 0 ? 'text-emerald-600' : 'text-red-500' ?>"><?= $jml ?></div>
                        <div class="text-[10px] text-gray-400 uppercase font-bold">dari <?= $bank ?> soal</div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if (strtolower($u['status_ujian']) === 'aktif'): ?>
                            <span class="bg-emerald-100 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shadow-sm">Aktif</span>
                        <?php else: ?>
                            <span class="bg-gray-100 text-gray-500 border border-gray-200 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider">Tidak Aktif</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <a href="kelola_soal_ujian.php?id=<?= $u['id_ujian'] ?>" class="btn-gradient text-white px-5 py-2.5 rounded-xl text-xs font-bold inline-flex items-center gap-2 shadow-md hover:scale-105 transition">
                            <i class="fas fa-cog"></i> Kelola Ujian
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="py-20 text-center">
            <i class="fas fa-clipboard-list text-gray-300 text-6xl mb-4 block"></i>
            <h3 class="text-gray-600 font-bold text-lg mb-1">Belum ada ujian yang ditugaskan</h3>
            <p class="text-gray-400 text-sm">Silakan hubungi Admin/Kurikulum untuk membuatkan jadwal ujian Anda.</p>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>