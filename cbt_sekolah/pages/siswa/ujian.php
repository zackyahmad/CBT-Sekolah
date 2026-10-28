<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../../index.php"); exit;
}
require_once '../../config/koneksi.php';

// Set timezone ke Indonesia/Jakarta
date_default_timezone_set('Asia/Jakarta');

$id_user = $_SESSION['id_user'];

// 1. Ambil ID Kelas siswa dari tb_users
$stmt_user = $pdo->prepare("SELECT id_kelas, nama_lengkap FROM tb_users WHERE id_user = ?");
$stmt_user->execute([$id_user]);
$user = $stmt_user->fetch();
$id_kelas_siswa = $user['id_kelas'];
$nama_siswa = $user['nama_lengkap'];

// 2. QUERY FINAL: Tampilkan ujian HANYA untuk kelas siswa ini
$stmt = $pdo->prepare("
    SELECT u.*, m.nama_mapel, h.id_hasil as sudah_kerjakan,
    (SELECT COUNT(*) FROM tb_ujian_soal WHERE id_ujian = u.id_ujian) as jumlah_soal
    FROM tb_ujian u 
    JOIN tb_ujian_kelas uk ON u.id_ujian = uk.id_ujian
    LEFT JOIN tb_mapel m ON u.id_mapel = m.id_mapel
    LEFT JOIN tb_hasil_ujian h ON h.id_ujian = u.id_ujian AND h.id_user = ?
    WHERE uk.id_kelas = ? AND LOWER(u.status_ujian) = 'aktif'
    ORDER BY u.tanggal_ujian DESC, u.id_ujian DESC
");
$stmt->execute([$id_user, $id_kelas_siswa]);
$daftar_ujian = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sekarang = time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Ujian - Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen">

<div class="max-w-5xl mx-auto p-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Daftar Ujian</h1>
            <p class="text-slate-500">Pilih ujian yang ingin Anda kerjakan hari ini</p>
        </div>
        <div class="text-right flex items-center gap-4">
            <div class="text-right">
                <p class="font-bold text-blue-600"><?= htmlspecialchars($nama_siswa) ?></p>
                <p class="text-xs text-slate-400">Siswa Aktif</p>
            </div>
            <a href="../../logout.php" onclick="return confirm('Yakin ingin keluar?')" class="bg-red-50 text-red-500 hover:bg-red-500 hover:text-white w-10 h-10 rounded-full flex items-center justify-center transition">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (count($daftar_ujian) > 0): ?>
            <?php foreach ($daftar_ujian as $u): 
                // CEK WAKTU UJIAN
                $jam_mulai_db = $u['jam_mulai'] ?? '00:00:00';
                $jam_selesai_db = $u['jam_selesai'] ?? '23:59:59';
                
                $waktu_mulai = strtotime($u['tanggal_ujian'] . ' ' . $jam_mulai_db);
                $waktu_selesai = strtotime($u['tanggal_ujian'] . ' ' . $jam_selesai_db);
                
                $is_terlambat = $sekarang > $waktu_selesai;
                $belum_mulai = $sekarang < $waktu_mulai;
            ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col h-full hover:shadow-md transition">
                    <div class="flex justify-between items-start mb-4">
                        <div class="bg-blue-100 text-blue-600 w-12 h-12 rounded-xl flex items-center justify-center text-xl">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <span class="text-[10px] font-bold uppercase px-2 py-1 bg-slate-100 text-slate-500 rounded">
                            <?= $u['jumlah_soal'] ?> Soal
                        </span>
                    </div>
                    
                    <h3 class="font-bold text-lg text-slate-800 mb-1"><?= htmlspecialchars($u['judul_ujian']) ?></h3>
                    
                    <div class="text-xs text-slate-500 space-y-1 mb-4 flex-1">
                        <p><i class="fas fa-book mr-1.5 text-gray-400"></i> <?= htmlspecialchars($u['nama_mapel'] ?? '-') ?></p>
                        <p><i class="far fa-calendar mr-1.5 text-gray-400"></i> <?= date('d M Y', strtotime($u['tanggal_ujian'])) ?></p>
                        <p><i class="far fa-clock mr-1 text-gray-400"></i> <?= date('H:i', $waktu_mulai) ?> - <?= date('H:i', $waktu_selesai) ?> WIB (<?= $u['durasi_menit'] ?? '-' ?> Menit)</p>
                    </div>

                    <div class="border-t border-slate-50 pt-4 mt-auto">
                        <?php if ($u['sudah_kerjakan']): ?>
                            <button disabled class="block w-full text-center bg-green-100 text-green-600 font-bold py-3 rounded-xl cursor-not-allowed border border-green-200">
                                <i class="fas fa-check-circle mr-1"></i> Telah Dikerjakan
                            </button>
                        <?php elseif ($is_terlambat): ?>
                            <button disabled class="block w-full text-center bg-red-100 text-red-600 font-bold py-3 rounded-xl cursor-not-allowed border border-red-200">
                                <i class="fas fa-times-circle mr-1"></i> Waktu Berakhir
                            </button>
                        <?php elseif ($belum_mulai): ?>
                            <button disabled class="block w-full text-center bg-orange-100 text-orange-600 font-bold py-3 rounded-xl cursor-not-allowed border border-orange-200">
                                <i class="fas fa-clock mr-1"></i> Belum Mulai
                            </button>
                        <?php elseif ($u['jumlah_soal'] > 0): ?>
                            <a href="kerjakan_ujian.php?id_ujian=<?= $u['id_ujian'] ?>" 
                               class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition shadow-sm">
                                Kerjakan Sekarang
                            </a>
                        <?php else: ?>
                            <button disabled class="block w-full text-center bg-slate-100 text-slate-400 font-bold py-3 rounded-xl cursor-not-allowed">
                                Soal Belum Disiapkan
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full py-20 text-center bg-white rounded-3xl border-2 border-dashed border-slate-200">
                <div class="text-6xl mb-4">📅</div>
                <h3 class="text-xl font-bold text-slate-700">Belum Ada Ujian Aktif</h3>
                <p class="text-slate-500 mt-2">Belum ada ujian untuk kelas Anda saat ini.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>