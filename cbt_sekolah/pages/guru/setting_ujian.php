<?php
// =============================================
// FILE: pages/guru/rencana_ujian.php
// Guru melihat ujian yang dibuat admin untuk mereka
// Data dari tb_ujian (tabel admin)
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
        u.*,
        m.nama_mapel,
        GROUP_CONCAT(k.nama_kelas ORDER BY k.nama_kelas SEPARATOR ', ') AS kelas_list,
        (SELECT COUNT(*) FROM tb_ujian_soal us WHERE us.id_ujian = u.id_ujian) AS jml_soal_terpilih,
        (SELECT COUNT(*) FROM tb_soal ts WHERE ts.id_mapel = u.id_mapel) AS jml_bank_soal
    FROM tb_ujian u
    LEFT JOIN tb_mapel m ON m.id_mapel = u.id_mapel
    LEFT JOIN tb_ujian_kelas uk ON uk.id_ujian = u.id_ujian
    LEFT JOIN tb_kelas k ON k.id_kelas = uk.id_kelas
    WHERE u.id_guru = ?
    GROUP BY u.id_ujian
    ORDER BY u.tanggal_ujian DESC
");
$stmt->execute([$id_guru]);
$ujian_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total       = count($ujian_list);
$total_aktif = count(array_filter($ujian_list, fn($u) => $u['status_ujian'] === 'aktif'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
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
        tr:hover td { background:#f9fafb; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<!-- HEADER -->
<header class="gradient-bg shadow-lg py-4 px-6 flex justify-between items-center sticky top-0 z-40">
    <div class="flex items-center space-x-3">
        <a href="dashboard.php" class="text-white/70 hover:text-white mr-1 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
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
        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center border-2 border-white/50">
            <span class="text-white font-bold"><?= strtoupper(substr($nama_guru,0,1)) ?></span>
        </div>
    </div>
</header>

<main class="p-6 max-w-7xl mx-auto">

    <!-- Info banner -->
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-6 flex items-start gap-3">
        <i class="fas fa-info-circle text-blue-500 mt-0.5 text-lg flex-shrink-0"></i>
        <div class="text-sm text-blue-800">
            <strong>Info:</strong> Daftar ujian di bawah dibuat oleh Admin. Tugas Anda adalah
            <strong>memilih soal</strong> dari bank soal untuk setiap ujian dengan klik tombol
            <span class="bg-blue-100 px-2 py-0.5 rounded font-bold">📚 Kelola Soal</span>.
        </div>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0A192F] to-[#1E3A5F] flex items-center justify-center">
                <i class="fas fa-calendar-alt text-white"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-gray-800"><?= $total ?></div>
                <div class="text-xs text-gray-500">Total Ujian</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-green-500 flex items-center justify-center">
                <i class="fas fa-check-circle text-white"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-gray-800"><?= $total_aktif ?></div>
                <div class="text-xs text-gray-500">Ujian Aktif</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#F4A261] to-orange-400 flex items-center justify-center">
                <i class="fas fa-pause-circle text-white"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-gray-800"><?= $total - $total_aktif ?></div>
                <div class="text-xs text-gray-500">Tidak Aktif</div>
            </div>
        </div>
    </div>

    <!-- Tabel ujian -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
            <i class="fas fa-list text-[#0A192F]"></i>
            <h3 class="font-bold text-gray-800">Daftar Ujian Anda</h3>
            <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full font-semibold"><?= $total ?> ujian</span>
        </div>

        <?php if (!empty($ujian_list)): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">No</th>
                        <th class="px-4 py-3 text-left">Judul Ujian</th>
                        <th class="px-4 py-3 text-left">Mata Pelajaran</th>
                        <th class="px-4 py-3 text-left">Kelas</th>
                        <th class="px-4 py-3 text-left">Tanggal & Waktu</th>
                        <th class="px-4 py-3 text-center">Soal Terpilih</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                <?php $no=1; foreach ($ujian_list as $u): ?>
                <tr>
                    <td class="px-4 py-3 text-gray-400"><?= $no++ ?></td>
                    <td class="px-4 py-3">
                        <div class="font-semibold text-gray-800"><?= htmlspecialchars($u['judul_ujian']) ?></div>
                        <div class="text-xs text-gray-400 mt-0.5">Durasi: <?= $u['durasi_menit'] ?> menit</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded-lg text-xs font-semibold">
                            <?= htmlspecialchars($u['nama_mapel'] ?? '-') ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-xs"><?= htmlspecialchars($u['kelas_list'] ?? '-') ?></td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-700"><?= date('d M Y', strtotime($u['tanggal_ujian'])) ?></div>
                        <div class="text-xs text-gray-400"><?= substr($u['jam_mulai'],0,5) ?> – <?= substr($u['jam_selesai'],0,5) ?></div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <?php
                        $jml    = (int)$u['jml_soal_terpilih'];
                        $bank   = (int)$u['jml_bank_soal'];
                        $pct    = $bank > 0 ? round($jml/$bank*100) : 0;
                        $color  = $jml > 0 ? 'text-green-600' : 'text-red-500';
                        ?>
                        <div class="<?= $color ?> font-bold text-base"><?= $jml ?></div>
                        <div class="text-xs text-gray-400">dari <?= $bank ?> soal</div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <?php if ($u['status_ujian']==='aktif'): ?>
                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold">Aktif</span>
                        <?php else: ?>
                        <span class="bg-gray-100 text-gray-500 px-2 py-1 rounded-full text-xs font-bold">Tidak Aktif</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <!-- Tombol utama: Kelola Soal untuk ujian ini -->
                        <a href="kelola_soal_ujian.php?id=<?= $u['id_ujian'] ?>"
                           class="btn-gradient text-white px-4 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 hover:opacity-90 transition">
                            <i class="fas fa-book-open"></i> Kelola Soal
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php else: ?>
        <div class="py-16 text-center">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-calendar-times text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-gray-600 font-semibold mb-1">Belum ada ujian</h3>
            <p class="text-gray-400 text-sm">Admin belum menugaskan ujian kepada Anda.</p>
        </div>
        <?php endif; ?>
    </div>

</main>
</body>
</html>