<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../../index.php"); exit;
}

// 1. Menggunakan koneksi PDO standar aplikasi kita
require_once '../../config/koneksi.php';

$id_guru  = $_SESSION['id_user'] ?? 0;
$nama_guru = $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? 'Guru';
$id_ujian = isset($_GET['ujian_id']) ? (int)$_GET['ujian_id'] : 0;

// 2. Ambil daftar ujian milik guru untuk Dropdown (Menggunakan tb_ujian)
$stmt_list = $pdo->prepare("SELECT id_ujian, judul_ujian, status_ujian FROM tb_ujian WHERE id_guru = ? ORDER BY tanggal_ujian DESC, id_ujian DESC");
$stmt_list->execute([$id_guru]);
$ujian_list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

// Default ke ujian pertama jika belum ada yang dipilih
if (!$id_ujian && count($ujian_list) > 0) {
    $id_ujian = $ujian_list[0]['id_ujian'];
}

$detail_ujian = null;
$rows         = [];
$cnt          = ['total'=>0, 'belum'=>0, 'selesai'=>0];

if ($id_ujian) {
    // 3. Ambil detail ujian terpilih
    $stmt_detail = $pdo->prepare("
        SELECT u.*, m.nama_mapel, 
               GROUP_CONCAT(k.nama_kelas SEPARATOR ', ') as kelas_list
        FROM tb_ujian u
        LEFT JOIN tb_mapel m ON u.id_mapel = m.id_mapel
        LEFT JOIN tb_ujian_kelas uk ON u.id_ujian = uk.id_ujian
        LEFT JOIN tb_kelas k ON uk.id_kelas = k.id_kelas
        WHERE u.id_ujian = ? AND u.id_guru = ?
        GROUP BY u.id_ujian
    ");
    $stmt_detail->execute([$id_ujian, $id_guru]);
    $detail_ujian = $stmt_detail->fetch(PDO::FETCH_ASSOC);

    if ($detail_ujian) {
        // 4. Ambil data SELURUH SISWA di kelas yang ditugaskan pada ujian ini, 
        // lalu LEFT JOIN dengan tb_hasil_ujian untuk mengecek apakah mereka sudah selesai
        $stmt_siswa = $pdo->prepare("
            SELECT u.id_user as id, u.nama_lengkap as nama, u.nis, k.nama_kelas as kelas,
                   h.id_hasil, h.nilai, h.waktu_selesai
            FROM tb_ujian_kelas uk
            JOIN tb_users u ON uk.id_kelas = u.id_kelas AND u.role = 'siswa'
            LEFT JOIN tb_kelas k ON u.id_kelas = k.id_kelas
            LEFT JOIN tb_hasil_ujian h ON h.id_user = u.id_user AND h.id_ujian = uk.id_ujian
            WHERE uk.id_ujian = ?
            ORDER BY k.nama_kelas ASC, u.nama_lengkap ASC
        ");
        $stmt_siswa->execute([$id_ujian]);
        
        while ($r = $stmt_siswa->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $r;
            $cnt['total']++;
            
            // Logika Status Pengerjaan (Jika ada ID Hasil = Selesai, Jika tidak = Belum)
            if (!empty($r['id_hasil'])) {
                $cnt['selesai']++;
            } else {
                $cnt['belum']++;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Monitoring Ujian – CBT Sekolah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta http-equiv="refresh" content="30">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        * { font-family:'Inter',sans-serif; }
        .gradient-bg { background:linear-gradient(135deg,#0A192F 0%,#1E3A5F 30%,#F4A261 60%,#90BE6D 100%); background-size:200% 200%; animation:gm 10s ease infinite; }
        @keyframes gm { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        .btn-gradient { background:linear-gradient(135deg,#0A192F,#1E3A5F,#F4A261,#90BE6D); background-size:300% 300%; animation:bg 5s ease infinite; }
        @keyframes bg { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        tr:hover td { background:#f9fafb; transition: background 0.2s; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<header class="gradient-bg shadow-lg py-4 px-6 flex justify-between items-center sticky top-0 z-40">
    <div class="flex items-center space-x-3">
        <a href="dashboard.php" class="text-white/70 hover:text-white mr-1 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <i class="fas fa-eye text-white text-xl"></i>
        <div>
            <h1 class="text-xl font-bold text-white">Monitoring Ujian</h1>
            <p class="text-white/60 text-xs">Auto-refresh setiap 30 detik</p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <a href="dashboard.php" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl text-sm font-semibold transition border border-white/30">
            <i class="fas fa-home mr-1"></i> Dashboard
        </a>
        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center border-2 border-white/50">
            <span class="text-white font-bold"><?= strtoupper(substr($nama_guru,0,1)) ?></span>
        </div>
    </div>
</header>

<main class="p-6 max-w-7xl mx-auto">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
        <form method="GET" class="flex gap-3 items-end flex-wrap">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">Pilih Ujian</label>
                <select name="ujian_id" onchange="this.form.submit()"
                        class="w-full py-2.5 px-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#0A192F] outline-none font-medium text-gray-700">
                    <?php if (count($ujian_list) > 0): 
                          foreach ($ujian_list as $u): ?>
                    <option value="<?= $u['id_ujian'] ?>" <?= $u['id_ujian']==$id_ujian ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['judul_ujian']) ?> [<?= strtoupper($u['status_ujian'] ?? 'DRAFT') ?>]
                    </option>
                    <?php endforeach; else: ?>
                    <option>Belum ada ujian</option>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit" class="btn-gradient text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-md">
                <i class="fas fa-sync mr-1"></i> Refresh
            </button>
        </form>
    </div>

    <?php if ($detail_ujian): ?>

    <div class="bg-gradient-to-r from-[#0A192F] to-[#1E3A5F] rounded-2xl p-6 mb-6 text-white shadow-md">
        <div class="flex justify-between items-start flex-wrap gap-4">
            <div>
                <h2 class="text-xl font-bold mb-2"><?= htmlspecialchars($detail_ujian['judul_ujian']) ?></h2>
                <div class="flex flex-wrap gap-5 text-sm text-white/80">
                    <span class="bg-white/10 px-3 py-1 rounded-lg"><i class="fas fa-book mr-1.5"></i><?= htmlspecialchars($detail_ujian['nama_mapel']??'-') ?></span>
                    <span class="bg-white/10 px-3 py-1 rounded-lg"><i class="fas fa-users mr-1.5"></i>Kelas: <?= htmlspecialchars($detail_ujian['kelas_list'] ?? '-') ?></span>
                    <span class="bg-white/10 px-3 py-1 rounded-lg"><i class="fas fa-clock mr-1.5"></i>Durasi: <?= htmlspecialchars($detail_ujian['durasi_menit']??'-') ?> Menit</span>
                </div>
            </div>
            <?php 
                $st = strtolower($detail_ujian['status_ujian'] ?? 'draft');
                $sc = $st === 'aktif' ? 'bg-green-500' : ($st === 'selesai' ? 'bg-gray-500' : 'bg-yellow-500'); 
            ?>
            <span class="<?= $sc ?> px-5 py-2 rounded-xl text-sm font-bold shadow-lg border border-white/20"><?= strtoupper($st) ?></span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <?php
        $stats = [
            ['label'=>'Total Siswa',  'val'=>$cnt['total'],   'icon'=>'fa-users',       'color'=>'from-[#0A192F] to-[#1E3A5F]'],
            ['label'=>'Selesai',      'val'=>$cnt['selesai'], 'icon'=>'fa-check-circle','color'=>'from-green-400 to-green-500'],
            ['label'=>'Belum/Proses', 'val'=>$cnt['belum'],   'icon'=>'fa-hourglass',   'color'=>'from-yellow-400 to-yellow-500']
        ];
        foreach ($stats as $stat): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4 hover:-translate-y-1 transition duration-300">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br <?= $stat['color'] ?> flex items-center justify-center flex-shrink-0 shadow-md">
                <i class="fas <?= $stat['icon'] ?> text-white text-lg"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-gray-800"><?= $stat['val'] ?></div>
                <div class="text-xs text-gray-500 font-medium uppercase tracking-wide"><?= $stat['label'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3 bg-gray-50/50">
            <i class="fas fa-list text-[#0A192F]"></i>
            <h3 class="font-bold text-gray-800">Status Pengerjaan Siswa</h3>
            <span class="bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded-full font-bold border border-blue-200"><?= $cnt['total'] ?> siswa</span>
        </div>

        <?php if (!empty($rows)): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-white text-gray-400 text-xs uppercase tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold w-10">No</th>
                        <th class="px-6 py-4 text-left font-semibold">Nama Siswa</th>
                        <th class="px-6 py-4 text-left font-semibold">Kelas</th>
                        <th class="px-6 py-4 text-center font-semibold">Nilai/Skor</th>
                        <th class="px-6 py-4 text-center font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                <?php $no=1; foreach ($rows as $r): ?>
                <tr>
                    <td class="px-6 py-4 text-gray-400"><?= $no++ ?></td>
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-800"><?= htmlspecialchars($r['nama']) ?></div>
                        <div class="text-[10px] text-gray-400 font-mono mt-0.5">NIS: <?= htmlspecialchars($r['nis']??'-') ?></div>
                    </td>
                    <td class="px-6 py-4 text-gray-600 font-medium text-xs">
                        <span class="bg-gray-100 px-2.5 py-1 rounded-md border border-gray-200"><?= htmlspecialchars($r['kelas']??'-') ?></span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if(!empty($r['id_hasil'])): ?>
                            <span class="font-black <?= $r['nilai'] >= 75 ? 'text-green-600' : 'text-red-500' ?> text-base">
                                <?= round($r['nilai']) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-gray-300 font-bold">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if(!empty($r['id_hasil'])): ?>
                            <div class="inline-flex flex-col items-center">
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-[10px] font-bold border border-green-200 uppercase tracking-wide">Selesai</span>
                                <span class="text-[9px] text-gray-400 mt-1 font-mono"><?= date('H:i', strtotime($r['waktu_selesai'])) ?> WIB</span>
                            </div>
                        <?php else: ?>
                            <span class="bg-yellow-50 text-yellow-600 px-3 py-1 rounded-full text-[10px] font-bold border border-yellow-200 uppercase tracking-wide">Belum</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php else: ?>
        <div class="py-16 text-center">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                <i class="fas fa-users text-gray-300 text-3xl"></i>
            </div>
            <h3 class="text-gray-600 font-bold mb-2">Belum ada data siswa</h3>
            <p class="text-gray-400 text-sm max-w-sm mx-auto">
                Admin belum menambahkan kelas untuk ujian ini, atau tidak ada data siswa di kelas tersebut.
            </p>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 py-20 text-center">
        <div class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-clipboard-check text-blue-300 text-4xl"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-700 mb-2">Pilih Ujian</h2>
        <p class="text-gray-400 text-sm">Pilih jadwal ujian di menu atas untuk memulai sesi monitoring secara real-time.</p>
    </div>
    <?php endif; ?>

</main>
</body>
</html>