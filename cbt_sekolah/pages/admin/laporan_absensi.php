<?php
session_start();
// Cek sesi admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

// Set timezone
date_default_timezone_set('Asia/Jakarta');

$filter_bulan = $_GET['bulan'] ?? date('m');
$filter_tahun = $_GET['tahun'] ?? date('Y');
$filter_kelas = $_GET['kelas'] ?? '';
$filter_tipe = $_GET['tipe'] ?? 'siswa'; 

$kelas_list = $pdo->query("SELECT * FROM tb_kelas ORDER BY nama_kelas ASC")->fetchAll();

// ==========================================
// 1. DATA REKAP SISWA (MEMBACA DARI absensi_siswa)
// ==========================================
$rekap_siswa = [];
$total_siswa = 0; $total_hadir = 0; $total_sakit = 0; $total_izin = 0; $total_alpha = 0; $total_terlambat = 0;

try {
    // KITA TAMBAHKAN FILTER: status_verifikasi = 'disetujui' AGAR YANG MENUNGGU/DITOLAK TIDAK DIHITUNG
    $query_siswa = "SELECT 
        u.id_user, u.nama_lengkap, u.username, k.nama_kelas,
        COUNT(CASE WHEN LOWER(a.keterangan) = 'hadir' THEN 1 END) as hadir,
        COUNT(CASE WHEN LOWER(a.keterangan) = 'sakit' THEN 1 END) as sakit,
        COUNT(CASE WHEN LOWER(a.keterangan) = 'izin' THEN 1 END) as izin,
        COUNT(CASE WHEN LOWER(a.keterangan) = 'alpha' THEN 1 END) as alpha,
        COUNT(CASE WHEN LOWER(a.keterangan) = 'terlambat' THEN 1 END) as terlambat,
        COUNT(a.id) as total_absensi,
        ROUND((COUNT(CASE WHEN LOWER(a.keterangan) IN ('hadir', 'terlambat') THEN 1 END) / 25) * 100, 2) as persentase
    FROM tb_users u
    LEFT JOIN tb_kelas k ON u.id_kelas = k.id_kelas
    LEFT JOIN absensi_siswa a ON u.id_user = a.id_user 
        AND MONTH(a.tanggal) = :bulan 
        AND YEAR(a.tanggal) = :tahun
        AND a.status_verifikasi = 'disetujui'
    WHERE u.role = 'siswa'";
    
    $params = [':bulan' => $filter_bulan, ':tahun' => $filter_tahun];
    if (!empty($filter_kelas)) {
        $query_siswa .= " AND u.id_kelas = :kelas";
        $params[':kelas'] = $filter_kelas;
    }
    $query_siswa .= " GROUP BY u.id_user ORDER BY k.nama_kelas, u.nama_lengkap ASC";
    
    $stmt = $pdo->prepare($query_siswa);
    $stmt->execute($params);
    $rekap_siswa = $stmt->fetchAll();
    
    foreach ($rekap_siswa as $r) {
        $total_siswa++; $total_hadir += $r['hadir']; $total_sakit += $r['sakit'];
        $total_izin += $r['izin']; $total_alpha += $r['alpha']; $total_terlambat += $r['terlambat'];
    }
} catch (PDOException $e) { }

// ==========================================
// 2. DATA REKAP GURU (DARI tb_absensi)
// ==========================================
$rekap_guru = [];
$total_guru = 0; $total_guru_hadir = 0; $total_guru_sakit = 0; $total_guru_izin = 0; $total_guru_alpha = 0; $total_guru_terlambat = 0;
try {
    $query_guru = "SELECT 
        u.id_user, u.nama_lengkap, u.username, u.role,
        COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as hadir,
        COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as sakit,
        COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as izin,
        COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as alpha,
        COUNT(CASE WHEN a.status = 'terlambat' THEN 1 END) as terlambat,
        COUNT(a.id_absensi) as total_absensi,
        ROUND((COUNT(CASE WHEN a.status IN ('hadir', 'terlambat') THEN 1 END) / 25) * 100, 2) as persentase
    FROM tb_users u
    LEFT JOIN tb_absensi a ON u.id_user = a.id_user 
        AND MONTH(a.tanggal) = :bulan 
        AND YEAR(a.tanggal) = :tahun
    WHERE u.role IN ('guru', 'admin')
    GROUP BY u.id_user 
    ORDER BY u.role, u.nama_lengkap ASC";
    
    $stmt = $pdo->prepare($query_guru);
    $stmt->execute([':bulan' => $filter_bulan, ':tahun' => $filter_tahun]);
    $rekap_guru = $stmt->fetchAll();
    
    foreach ($rekap_guru as $r) {
        $total_guru++; $total_guru_hadir += $r['hadir']; $total_guru_sakit += $r['sakit'];
        $total_guru_izin += $r['izin']; $total_guru_alpha += $r['alpha']; $total_guru_terlambat += $r['terlambat'];
    }
} catch (PDOException $e) {}

// ==========================================
// 3. DATA REKAP PENGAWAS
// ==========================================
$rekap_pengawas = [];
$total_pengawas = 0; $total_pengawas_hadir = 0; $total_pengawas_sakit = 0; $total_pengawas_izin = 0; $total_pengawas_alpha = 0; $total_pengawas_terlambat = 0;
try {
    // PERBAIKAN: Nama Tabel `absensi_pengawas` dan tambah filter disetujui
    $query_pengawas = "SELECT 
        nama as nama_lengkap,
        COUNT(CASE WHEN LOWER(keterangan) = 'hadir' THEN 1 END) as hadir,
        COUNT(CASE WHEN LOWER(keterangan) = 'sakit' THEN 1 END) as sakit,
        COUNT(CASE WHEN LOWER(keterangan) = 'izin' THEN 1 END) as izin,
        COUNT(CASE WHEN LOWER(keterangan) = 'alpha' THEN 1 END) as alpha,
        COUNT(CASE WHEN LOWER(keterangan) = 'terlambat' THEN 1 END) as terlambat,
        COUNT(id) as total_absensi,
        ROUND((COUNT(CASE WHEN LOWER(keterangan) IN ('hadir', 'terlambat') THEN 1 END) / 25) * 100, 2) as persentase
    FROM absensi_pengawas
    WHERE MONTH(tanggal) = :bulan 
        AND YEAR(tanggal) = :tahun
        AND status_verifikasi = 'disetujui'
    GROUP BY nama 
    ORDER BY nama ASC";
    
    $stmt = $pdo->prepare($query_pengawas);
    $stmt->execute([':bulan' => $filter_bulan, ':tahun' => $filter_tahun]);
    $rekap_pengawas = $stmt->fetchAll();
    
    foreach ($rekap_pengawas as $r) {
        $total_pengawas++; $total_pengawas_hadir += $r['hadir']; $total_pengawas_sakit += $r['sakit'];
        $total_pengawas_izin += $r['izin']; $total_pengawas_alpha += $r['alpha']; $total_pengawas_terlambat += $r['terlambat'];
    }
} catch (PDOException $e) {}

function getNamaBulan($bulan) {
    $nama = [1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'];
    return $nama[(int)$bulan] ?? '';
}
function getPersentaseColor($persen) {
    if ($persen >= 90) return 'text-green-600 bg-green-100';
    if ($persen >= 75) return 'text-yellow-600 bg-yellow-100';
    return 'text-red-600 bg-red-100';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi - ADMINSKAPAN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        :root { --primary-1: #2B3A67; --primary-2: #496A81; --secondary-1: #66999B; --accent-1: #FFC482; --neutral-1: #F8F4E9; --neutral-2: #E8E1D3; }
        body { background: linear-gradient(135deg, var(--neutral-1), var(--neutral-2)); font-family: 'Inter', sans-serif; }
        .sidebar { background: linear-gradient(180deg, var(--primary-1), var(--primary-2)); position: fixed; left: 0; top: 0; height: 100%; width: 16rem; }
        .sidebar-item { display: flex; align-items: center; padding: 0.75rem 1.5rem; color: white; transition: 0.3s; }
        .sidebar-item:hover, .sidebar-item.active { background: rgba(255,255,255,0.1); color: var(--accent-1); }
        .main-content { margin-left: 16rem; min-height: 100vh; }
        .card { background: white; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { background: linear-gradient(135deg, var(--primary-1), var(--primary-2)); color: white; padding: 12px; font-size: 0.8rem; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid var(--neutral-2); font-size: 0.8rem; }
        @media print { .no-print { display: none !important; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="sidebar no-print">
        <div class="p-4 border-b border-white/10 text-center"><h2 class="font-bold text-white">ADMINSKAPAN</h2></div>
        <div class="py-4">
            <a href="dashboard.php" class="sidebar-item"><i class="fas fa-home w-6"></i> Dashboard</a>
            <a href="absensi.php" class="sidebar-item"><i class="fas fa-check-double w-6"></i> Verifikasi</a>
            <a href="laporan_absensi.php" class="sidebar-item active"><i class="fas fa-chart-pie w-6"></i> Rekap Absensi</a>
        </div>
    </div>

    <div class="main-content">
        <header class="bg-white py-4 px-6 flex justify-between items-center shadow-sm no-print">
            <h1 class="text-xl font-bold" style="color: var(--primary-1);">Rekap Absensi</h1>
        </header>

        <main class="p-6">
            <div class="card p-6 mb-6 no-print">
                <form method="GET" class="flex gap-4 items-end">
                    <div>
                        <label class="text-xs font-bold text-gray-500 block mb-1">Tipe Rekap</label>
                        <select name="tipe" class="border p-2 rounded text-sm w-40">
                            <option value="siswa" <?= $filter_tipe=='siswa'?'selected':'' ?>>Siswa</option>
                            <option value="guru" <?= $filter_tipe=='guru'?'selected':'' ?>>Guru</option>
                            <option value="pengawas" <?= $filter_tipe=='pengawas'?'selected':'' ?>>Pengawas</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 block mb-1">Bulan</label>
                        <select name="bulan" class="border p-2 rounded text-sm w-32">
                            <?php for($i=1; $i<=12; $i++): ?>
                                <option value="<?= $i ?>" <?= $filter_bulan==$i?'selected':'' ?>><?= getNamaBulan($i) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <?php if($filter_tipe == 'siswa'): ?>
                    <div>
                        <label class="text-xs font-bold text-gray-500 block mb-1">Kelas</label>
                        <select name="kelas" class="border p-2 rounded text-sm w-40">
                            <option value="">Semua Kelas</option>
                            <?php foreach($kelas_list as $k): ?>
                                <option value="<?= $k['id_kelas'] ?>" <?= $filter_kelas==$k['id_kelas']?'selected':'' ?>><?= $k['nama_kelas'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold">Filter</button>
                    <button type="button" onclick="window.print()" class="bg-gray-600 text-white px-4 py-2 rounded text-sm font-bold ml-auto"><i class="fas fa-print"></i> Cetak</button>
                    <button type="button" onclick="exportExcel()" class="bg-green-600 text-white px-4 py-2 rounded text-sm font-bold"><i class="fas fa-file-excel"></i> Excel</button>
                </form>
            </div>

            <div class="card p-6 print-area">
                <div class="hidden print:block text-center mb-6">
                    <h2 class="text-xl font-bold">REKAP ABSENSI <?= strtoupper($filter_tipe) ?></h2>
                    <p>Bulan: <?= getNamaBulan($filter_bulan) ?> <?= $filter_tahun ?></p>
                    <hr class="mt-2 border-black">
                </div>

                <table id="tabelRekap">
                    <thead>
                        <tr>
                            <th>No</th><th>Nama</th>
                            <?php if($filter_tipe == 'siswa'): ?><th>Kelas</th><?php endif; ?>
                            <th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alpha</th><th>Terlambat</th><th>Total</th><th>Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $data_loop = ($filter_tipe == 'siswa') ? $rekap_siswa : (($filter_tipe == 'guru') ? $rekap_guru : $rekap_pengawas);
                        if(empty($data_loop)): ?>
                            <tr><td colspan="10" class="text-center py-4 text-gray-400">Tidak ada data terverifikasi bulan ini.</td></tr>
                        <?php else: $no=1; foreach($data_loop as $r): 
                            $tot = $r['hadir']+$r['sakit']+$r['izin']+$r['alpha']+$r['terlambat'];
                            $pct = $tot>0 ? round(($r['hadir']+$r['terlambat'])/$tot*100,2) : 0;
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="font-bold"><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                            <?php if($filter_tipe == 'siswa'): ?><td><?= htmlspecialchars($r['nama_kelas'] ?? '-') ?></td><?php endif; ?>
                            <td class="text-green-600"><?= $r['hadir'] ?></td>
                            <td class="text-yellow-600"><?= $r['sakit'] ?></td>
                            <td class="text-blue-600"><?= $r['izin'] ?></td>
                            <td class="text-red-600"><?= $r['alpha'] ?></td>
                            <td class="text-orange-600"><?= $r['terlambat'] ?></td>
                            <td class="font-bold"><?= $tot ?></td>
                            <td><span class="<?= getPersentaseColor($pct) ?> px-2 py-1 rounded text-xs font-bold"><?= $pct ?>%</span></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function exportExcel() {
            let table = document.getElementById("tabelRekap");
            let wb = XLSX.utils.table_to_book(table, {sheet: "Rekap"});
            XLSX.writeFile(wb, "Rekap_Absensi_<?= $filter_tipe ?>.xlsx");
        }
    </script>
</body>
</html>