<?php
session_start();
require_once '../../config/koneksi.php';

// Set Timezone biar durasi akurat
date_default_timezone_set('Asia/Jakarta');

$id_ujian = $_GET['id'];

// 1. Ambil Info Ujian
$stmt = $pdo->prepare("SELECT u.*, m.nama_mapel 
                       FROM tb_ujian u 
                       JOIN tb_mapel m ON u.id_mapel = m.id_mapel 
                       WHERE u.id_ujian = ?");
$stmt->execute([$id_ujian]);
$ujian = $stmt->fetch();

// 2. Ambil Total Soal
$stmt_soal = $pdo->prepare("SELECT COUNT(*) FROM tb_soal WHERE id_ujian = ?");
$stmt_soal->execute([$id_ujian]);
$total_soal = $stmt_soal->fetchColumn();

// 3. Ambil Data Siswa
$sql_siswa = "SELECT s.id_user, s.nama_lengkap, k.nama_kelas, 
              n.id_nilai, n.waktu_mulai, n.waktu_selesai, n.pelanggaran, n.status_pengerjaan,
              (SELECT COUNT(*) FROM tb_jawaban WHERE id_nilai = n.id_nilai AND jawaban != '') as soaldikerjakan
              FROM tb_users s
              JOIN tb_kelas k ON s.id_kelas = k.id_kelas 
              JOIN tb_ujian_kelas uk ON k.id_kelas = uk.id_kelas
              LEFT JOIN tb_nilai n ON (s.id_user = n.id_siswa AND n.id_ujian = ?)
              WHERE uk.id_ujian = ? AND s.role = 'siswa'
              ORDER BY k.nama_kelas ASC, s.nama_lengkap ASC";

$stmt_list = $pdo->prepare($sql_siswa);
$stmt_list->execute([$id_ujian, $id_ujian]);
$peserta = $stmt_list->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="10">
    <title>Live: <?= $ujian['judul_ujian'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-[#F8FAFC] font-sans text-gray-800">

    <?php include '../../layouts/sidebar_admin.php'; ?>

    <div class="md:ml-64 min-h-screen">

        <header class="bg-white border-b border-gray-200 py-4 px-8 sticky top-0 z-40 shadow-sm flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-[#2C3E50]">Live Monitoring</h1>
                <p class="text-xs text-blue-500 font-bold uppercase tracking-widest">
                    <?= $ujian['judul_ujian'] ?> | <?= $ujian['nama_mapel'] ?>
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <div class="text-right mr-4 hidden md:block">
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Status Koneksi</p>
                    <p class="text-xs font-bold text-green-600 animate-pulse"><i class="fas fa-wifi text-[8px] mr-1"></i> Live (10s)</p>
                </div>
                <a href="monitoring_ujian.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-xl text-xs font-bold transition">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>
        </header>

        <main class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex justify-between items-center">
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Total Peserta</p>
                        <p class="text-2xl font-black text-gray-800"><?= count($peserta) ?></p>
                    </div>
                    <div class="bg-gray-50 w-10 h-10 rounded-full flex items-center justify-center text-gray-400"><i class="fas fa-users"></i></div>
                </div>
                <div class="bg-blue-50 p-5 rounded-2xl border border-blue-100 shadow-sm flex justify-between items-center">
                    <div>
                        <p class="text-[10px] text-blue-400 font-bold uppercase tracking-wider">Sedang Ujian</p>
                        <p class="text-2xl font-black text-blue-600">
                            <?= count(array_filter($peserta, fn($p) => $p['status_pengerjaan'] == 'sedang')) ?>
                        </p>
                    </div>
                    <div class="bg-blue-200 w-10 h-10 rounded-full flex items-center justify-center text-blue-600"><i class="fas fa-pen"></i></div>
                </div>
                <div class="bg-green-50 p-5 rounded-2xl border border-green-100 shadow-sm flex justify-between items-center">
                    <div>
                        <p class="text-[10px] text-green-400 font-bold uppercase tracking-wider">Selesai</p>
                        <p class="text-2xl font-black text-green-600">
                            <?= count(array_filter($peserta, fn($p) => $p['status_pengerjaan'] == 'selesai')) ?>
                        </p>
                    </div>
                    <div class="bg-green-200 w-10 h-10 rounded-full flex items-center justify-center text-green-600"><i class="fas fa-check"></i></div>
                </div>
                <div class="bg-red-50 p-5 rounded-2xl border border-red-100 shadow-sm flex justify-between items-center">
                    <div>
                        <p class="text-[10px] text-red-400 font-bold uppercase tracking-wider">Terblokir</p>
                        <p class="text-2xl font-black text-red-600">
                            <?= count(array_filter($peserta, fn($p) => $p['status_pengerjaan'] == 'terkunci')) ?>
                        </p>
                    </div>
                    <div class="bg-red-200 w-10 h-10 rounded-full flex items-center justify-center text-red-600"><i class="fas fa-lock"></i></div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden min-h-[400px]">
                <div class="p-5 border-b border-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide flex items-center">
                        <i class="fas fa-list-ol mr-2 text-blue-500"></i> Progress Peserta
                    </h3>
                    <div class="relative w-full md:w-64">
                        <input type="text" id="cariSiswa" placeholder="Cari Nama / Kelas..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:border-blue-500 focus:ring-2 focus:ring-blue-50 outline-none transition">
                        <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 border-b border-gray-100">
                            <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                <th class="px-6 py-4 w-12">No</th>
                                <th class="px-6 py-4">Nama Siswa</th>
                                <th class="px-6 py-4">Kelas</th>
                                <th class="px-6 py-4 text-center">Waktu Mulai</th>
                                <th class="px-6 py-4 text-center">Durasi</th>
                                <th class="px-6 py-4 w-48">Progress</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm bg-white">
                            <?php if (empty($peserta)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-gray-400 italic">Belum ada siswa yang login.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1;
                                foreach ($peserta as $p):
                                    // 1. Hitung Progress
                                    $persen = ($total_soal > 0) ? round(($p['soaldikerjakan'] / $total_soal) * 100) : 0;

                                    // 2. Tentukan Label & Durasi
                                    $durasi_text = '-';
                                    if ($p['waktu_mulai'] == null) {
                                        $status_label = '<span class="px-3 py-1 rounded-full bg-gray-100 text-gray-400 text-[10px] font-bold uppercase">OFFLINE</span>';
                                        $waktu = '-';
                                        $bar_color = 'bg-gray-300';
                                    } else {
                                        $waktu = date('H:i', strtotime($p['waktu_mulai']));

                                        // HITUNG DURASI
                                        $start_time = new DateTime($p['waktu_mulai']);
                                        if ($p['status_pengerjaan'] == 'sedang') {
                                            $end_time = new DateTime(); // Waktu sekarang
                                        } else {
                                            $end_time = new DateTime($p['waktu_selesai']); // Waktu selesai ujian
                                        }
                                        $interval = $start_time->diff($end_time);

                                        // Format Durasi (Contoh: 01:30:45)
                                        $durasi_text = $interval->format('%H:%I:%S');

                                        // Logic Label Warna
                                        if ($p['status_pengerjaan'] == 'selesai') {
                                            $status_label = '<span class="px-3 py-1 rounded-full bg-green-100 text-green-600 text-[10px] font-bold uppercase">SELESAI</span>';
                                            $bar_color = 'bg-green-500';
                                        } elseif ($p['status_pengerjaan'] == 'terkunci') {
                                            $status_label = '<span class="px-3 py-1 rounded-full bg-red-100 text-red-600 text-[10px] font-bold uppercase"><i class="fas fa-lock mr-1"></i> TERKUNCI</span>';
                                            $bar_color = 'bg-red-500';
                                        } else {
                                            $status_label = '<span class="px-3 py-1 rounded-full bg-blue-100 text-blue-600 text-[10px] font-bold uppercase animate-pulse">MENGERJAKAN</span>';
                                            $bar_color = 'bg-blue-500';
                                        }
                                    }
                                ?>
                                    <tr class="hover:bg-blue-50/30 transition row-siswa group">
                                        <td class="px-6 py-4 font-bold text-blue-500"><?= $no++ ?></td>
                                        <td class="px-6 py-4 font-bold text-gray-700 nama-siswa">
                                            <?= htmlspecialchars($p['nama_lengkap']) ?>
                                            <?php if ($p['pelanggaran'] > 0): ?>
                                                <span class="ml-2 text-[9px] bg-red-50 text-red-500 px-1.5 py-0.5 rounded border border-red-100 font-bold">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i><?= $p['pelanggaran'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-gray-500 text-xs uppercase kelas-siswa"><?= $p['nama_kelas'] ?></td>
                                        <td class="px-6 py-4 text-xs font-mono text-gray-500 text-center font-bold"><?= $waktu ?></td>

                                        <td class="px-6 py-4 text-xs font-mono text-gray-700 text-center font-bold bg-gray-50/50">
                                            <?= $durasi_text ?>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="w-full bg-gray-100 rounded-full h-2 mb-1 overflow-hidden">
                                                <div class="<?= $bar_color ?> h-2 rounded-full transition-all duration-1000" style="width: <?= $persen ?>%"></div>
                                            </div>
                                            <div class="flex justify-between text-[9px] font-bold text-gray-400">
                                                <span><?= $p['soaldikerjakan'] ?> / <?= $total_soal ?> Soal</span>
                                                <span><?= $persen ?>%</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center"><?= $status_label ?></td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex justify-center space-x-2 opacity-60 group-hover:opacity-100 transition-opacity">
                                                <?php if ($p['id_nilai']): ?>
                                                    <button onclick="resetLogin(<?= $ujian['id_ujian'] ?>, <?= $p['id_user'] ?>, '<?= $p['nama_lengkap'] ?>')" class="w-8 h-8 rounded-lg bg-orange-50 text-orange-500 hover:bg-orange-500 hover:text-white transition flex items-center justify-center"><i class="fas fa-sync-alt text-xs"></i></button>
                                                    <button onclick="paksaSelesai(<?= $ujian['id_ujian'] ?>, <?= $p['id_user'] ?>, '<?= $p['nama_lengkap'] ?>')" class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition flex items-center justify-center"><i class="fas fa-ban text-xs"></i></button>
                                                <?php else: ?>
                                                    <span class="text-gray-300 text-xs">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('cariSiswa').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let rows = document.querySelectorAll('.row-siswa');
            rows.forEach(row => {
                let nama = row.querySelector('.nama-siswa').textContent.toUpperCase();
                let kelas = row.querySelector('.kelas-siswa').textContent.toUpperCase();
                if (nama.indexOf(filter) > -1 || kelas.indexOf(filter) > -1) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });

        function resetLogin(id_ujian, id_siswa, nama) {
            Swal.fire({
                title: 'Reset Login?',
                text: "Siswa " + nama + " akan bisa masuk ujian lagi.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#F59E0B',
                confirmButtonText: 'Ya, Reset'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../../proses/monitoring_proses.php?aksi=reset&u=" + id_ujian + "&s=" + id_siswa;
                }
            })
        }

        function paksaSelesai(id_ujian, id_siswa, nama) {
            Swal.fire({
                title: 'Kunci Ujian?',
                text: "Status siswa " + nama + " akan diubah menjadi TERKUNCI.",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                confirmButtonText: 'Ya, Kunci'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../../proses/monitoring_proses.php?aksi=blokir&u=" + id_ujian + "&s=" + id_siswa;
                }
            })
        }
    </script>
</body>

</html>