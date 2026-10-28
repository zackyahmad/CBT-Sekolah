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

$filter_tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$filter_tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$filter_status = $_GET['status'] ?? '';
$filter_kelas = $_GET['kelas'] ?? '';
$filter_role = $_GET['role'] ?? 'semua';
$filter_cari = $_GET['cari'] ?? '';
$filter_bulan = $_GET['bulan'] ?? date('m');
$filter_tahun = $_GET['tahun'] ?? date('Y');
$filter_tipe = $_GET['tipe'] ?? 'harian';
$filter_verifikasi = $_GET['verifikasi'] ?? '';
$tab_active = $_GET['tab'] ?? 'siswa';

// ==============================================
// PROSES VERIFIKASI ABSENSI SISWA (absensi_siswa)
// ==============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifikasi_siswa'])) {
    $verifikasi_id = (int)$_POST['verifikasi_id'];
    $aksi = $_POST['aksi_verifikasi'];
    $catatan_admin = trim($_POST['catatan_admin'] ?? '');
    $nama_admin = $_SESSION['nama'] ?? 'Admin';
    
    try {
        $status_verifikasi = ($aksi == 'setuju') ? 'disetujui' : 'ditolak';
        
        // 1. Update status di absensi_siswa
        $stmt = $pdo->prepare("
            UPDATE absensi_siswa 
            SET status_verifikasi = :status_verif, 
                catatan_verifikasi = :catatan,
                verifikasi_oleh = :admin_nama,
                verifikasi_tanggal = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':status_verif' => $status_verifikasi,
            ':catatan' => $catatan_admin,
            ':admin_nama' => $nama_admin,
            ':id' => $verifikasi_id
        ]);

        // 2. Jika disetujui, pindahkan ke tb_absensi
        if ($aksi == 'setuju') {
            $stmt_get = $pdo->prepare("SELECT * FROM absensi_siswa WHERE id = ?");
            $stmt_get->execute([$verifikasi_id]);
            $row = $stmt_get->fetch(PDO::FETCH_ASSOC);

            if ($row && $row['id_user']) {
                $id_user = $row['id_user'];
                $tanggal = $row['tanggal'];
                $jam = $row['jam'];
                
                $ket = strtolower($row['keterangan']);
                $status_absensi = match($ket) {
                    'hadir' => 'hadir', 'sakit' => 'sakit', 'izin' => 'izin', default => 'alpha'
                };

                // Cek data sudah ada belum di tb_absensi
                $cek = $pdo->prepare("SELECT id_absensi FROM tb_absensi WHERE id_user = ? AND tanggal = ?");
                $cek->execute([$id_user, $tanggal]);
                
                if ($cek->rowCount() > 0) {
                    $pdo->prepare("UPDATE tb_absensi SET status = ? WHERE id_user = ? AND tanggal = ?")
                        ->execute([$status_absensi, $id_user, $tanggal]);
                } else {
                    $pdo->prepare("INSERT INTO tb_absensi (id_user, tanggal, jam_masuk, status, created_at) VALUES (?, ?, ?, ?, NOW())")
                        ->execute([$id_user, $tanggal, $jam, $status_absensi]);
                }
            }
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Absensi siswa berhasil disetujui!'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Absensi siswa ditolak.'];
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=siswa&" . http_build_query(array_diff_key($_GET, ['tab'=>''])));
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Gagal verifikasi: ' . $e->getMessage()];
    }
}

// ==============================================
// PROSES VERIFIKASI ABSENSI PENGAWAS
// ==============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifikasi_pengawas'])) {
    $verifikasi_id = (int)$_POST['verifikasi_id'];
    $aksi = $_POST['aksi_verifikasi'];
    $catatan_admin = trim($_POST['catatan_admin'] ?? '');
    $nama_admin = $_SESSION['nama'] ?? 'Admin';
    
    try {
        $status_verifikasi = ($aksi == 'setuju') ? 'disetujui' : 'ditolak';
        
        $stmt = $pdo->prepare("
            UPDATE absensi_pengawas 
            SET status_verifikasi = :status_verif, 
                catatan_admin = :catatan,
                diverifikasi_oleh = :admin_id,
                diverifikasi_pada = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':status_verif' => $status_verifikasi,
            ':catatan' => $catatan_admin,
            ':admin_id' => $_SESSION['id_user'],
            ':id' => $verifikasi_id
        ]);
        
        $_SESSION['flash'] = ['type' => ($aksi == 'setuju' ? 'success' : 'error'), 'msg' => 'Verifikasi pengawas berhasil!'];
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=pengawas");
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Gagal verifikasi: ' . $e->getMessage()];
    }
}

$kelas_list = $pdo->query("SELECT * FROM tb_kelas ORDER BY nama_kelas ASC")->fetchAll();

// ==============================================
// AMBIL DATA SISWA MENUNGGU VERIFIKASI
// ==============================================
$absensi_siswa_menunggu = [];
try {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               DATE_FORMAT(a.tanggal, '%d/%m/%Y') as tgl_format,
               TIME_FORMAT(a.jam, '%H:%i') as jam_datang,
               DATEDIFF(NOW(), a.created_at) as hari_menunggu
        FROM absensi_siswa a
        WHERE a.status_verifikasi = 'menunggu'
        ORDER BY a.created_at ASC
    ");
    $stmt->execute();
    $absensi_siswa_menunggu = $stmt->fetchAll();
} catch (PDOException $e) {}

// ==============================================
// AMBIL DATA PENGAWAS MENUNGGU VERIFIKASI
// ==============================================
$absensi_pengawas_menunggu = [];
try {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               DATE_FORMAT(a.tanggal, '%d/%m/%Y') as tgl_format,
               TIME_FORMAT(a.jam_masuk, '%H:%i') as jam_masuk_format,
               DATEDIFF(NOW(), a.created_at) as hari_menunggu
        FROM absensi_pengawas a
        WHERE a.status_verifikasi = 'menunggu'
        ORDER BY a.created_at ASC
    ");
    $stmt->execute();
    $absensi_pengawas_menunggu = $stmt->fetchAll();
} catch (PDOException $e) {}

// ==============================================
// AMBIL DATA RIWAYAT (SUDAH DIVERIFIKASI)
// ==============================================
$data_absensi = [];
try {
    if ($filter_role == 'siswa' || $filter_role == 'semua') {
        $q_siswa = "SELECT a.*, a.nama_siswa as nama_lengkap, 'siswa' as role,
                           DATE_FORMAT(a.tanggal, '%d/%m/%Y') as tgl_format,
                           TIME_FORMAT(a.jam, '%H:%i') as jam_datang,
                           a.catatan_verifikasi as catatan_admin
                    FROM absensi_siswa a
                    WHERE a.status_verifikasi IN ('disetujui', 'ditolak')";
        
        if ($filter_tipe == 'harian') {
            $q_siswa .= " AND DATE(a.tanggal) = '$filter_tanggal'";
        } elseif ($filter_tipe == 'rentang') {
            $q_siswa .= " AND DATE(a.tanggal) BETWEEN '$filter_tanggal' AND '$filter_tanggal_akhir'";
        } else {
            $q_siswa .= " AND MONTH(a.tanggal) = '$filter_bulan' AND YEAR(a.tanggal) = '$filter_tahun'";
        }
        
        if (!empty($filter_verifikasi)) $q_siswa .= " AND a.status_verifikasi = '$filter_verifikasi'";
        if (!empty($filter_cari)) $q_siswa .= " AND a.nama_siswa LIKE '%$filter_cari%'";
        
        $data_siswa = $pdo->query($q_siswa)->fetchAll();
    } else { $data_siswa = []; }
    
    if ($filter_role == 'pengawas' || $filter_role == 'semua') {
        $q_pengawas = "SELECT a.*, a.nama as nama_lengkap, 'pengawas' as role,
                              DATE_FORMAT(a.tanggal, '%d/%m/%Y') as tgl_format,
                              TIME_FORMAT(a.jam_masuk, '%H:%i') as jam_datang,
                              a.catatan_admin
                       FROM absensi_pengawas a
                       WHERE a.status_verifikasi IN ('disetujui', 'ditolak')";
        
        if ($filter_tipe == 'harian') {
            $q_pengawas .= " AND DATE(a.tanggal) = '$filter_tanggal'";
        } elseif ($filter_tipe == 'rentang') {
            $q_pengawas .= " AND DATE(a.tanggal) BETWEEN '$filter_tanggal' AND '$filter_tanggal_akhir'";
        } else {
            $q_pengawas .= " AND MONTH(a.tanggal) = '$filter_bulan' AND YEAR(a.tanggal) = '$filter_tahun'";
        }
        
        if (!empty($filter_verifikasi)) $q_pengawas .= " AND a.status_verifikasi = '$filter_verifikasi'";
        if (!empty($filter_cari)) $q_pengawas .= " AND a.nama LIKE '%$filter_cari%'";
        
        $data_pengawas = $pdo->query($q_pengawas)->fetchAll();
    } else { $data_pengawas = []; }
    
    if ($filter_role == 'semua') {
        $data_absensi = array_merge($data_siswa, $data_pengawas);
        usort($data_absensi, fn($a, $b) => strtotime($b['tanggal']) - strtotime($a['tanggal']));
    } elseif ($filter_role == 'siswa') {
        $data_absensi = $data_siswa;
    } else {
        $data_absensi = $data_pengawas;
    }
} catch (PDOException $e) {}

function getNamaBulan($bulan) {
    $nama = [1=>'Jan', 2=>'Feb', 3=>'Mar', 4=>'Apr', 5=>'Mei', 6=>'Jun', 7=>'Jul', 8=>'Ags', 9=>'Sep', 10=>'Okt', 11=>'Nov', 12=>'Des'];
    return $nama[(int)$bulan] ?? '';
}
function getVerifikasiBadge($status) {
    if($status == 'menunggu') return '<span class="badge-verifikasi menunggu">Menunggu</span>';
    if($status == 'disetujui') return '<span class="badge-verifikasi disetujui">Disetujui</span>';
    return '<span class="badge-verifikasi ditolak">Ditolak</span>';
}

$total_menunggu = count($absensi_siswa_menunggu) + count($absensi_pengawas_menunggu);
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Absensi - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary-1: #2B3A67; --primary-2: #496A81; --secondary-1: #66999B; --secondary-2: #B3AF8F; --accent-1: #FFC482; --accent-2: #DDB7AB; --neutral-1: #F8F4E9; --neutral-2: #E8E1D3; --success: #77BFA3; --danger: #E58C7C; }
        body { background: linear-gradient(135deg, var(--neutral-1) 0%, var(--neutral-2) 100%); font-family: 'Inter', sans-serif; }
        .sidebar { background: linear-gradient(180deg, var(--primary-1) 0%, var(--primary-2) 100%); position: fixed; left: 0; top: 0; height: 100%; width: 16rem; z-index: 50; }
        .sidebar-item { display: flex; align-items: center; padding: 0.75rem 1.5rem; color: white; transition: 0.3s; }
        .sidebar-item:hover { background: rgba(255,255,255,0.1); color: var(--accent-1); }
        .sidebar-item.active { background: linear-gradient(90deg, var(--accent-1), var(--accent-2)); color: var(--primary-1); font-weight: 600; }
        .main-content { margin-left: 16rem; min-height: 100vh; }
        .card { background: white; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .badge-verifikasi { padding: 4px 12px; border-radius: 50px; font-size: 0.7rem; font-weight: 600; }
        .badge-verifikasi.menunggu { background: #FFB34720; color: #FFB347; border: 1px solid #FFB347; }
        .badge-verifikasi.disetujui { background: #77BFA320; color: #77BFA3; border: 1px solid #77BFA3; }
        .badge-verifikasi.ditolak { background: #E58C7C20; color: #E58C7C; border: 1px solid #E58C7C; }
        table { width: 100%; border-collapse: collapse; }
        th { background: linear-gradient(135deg, var(--primary-1), var(--primary-2)); color: white; padding: 12px; text-align: left; font-size: 0.8rem; }
        td { padding: 12px; border-bottom: 1px solid var(--neutral-2); font-size: 0.8rem; }
        .tabs { display: flex; gap: 10px; border-bottom: 2px solid var(--neutral-2); margin-bottom: 20px; }
        .tab { padding: 10px 20px; cursor: pointer; border-radius: 12px 12px 0 0; font-weight: 600; }
        .tab.active { background: var(--primary-1); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .btn-lihat { background: #eff6ff; color: #1d4ed8; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; text-decoration: none; display: inline-block; border: 1px solid #bfdbfe; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="p-4 border-b border-white/10 text-center">
            <h2 class="font-bold text-white">ADMINSKAPAN</h2>
        </div>
        <div class="py-4">
            <a href="dashboard.php" class="sidebar-item"><i class="fas fa-home w-6"></i> Dashboard</a>
            <a href="absensi.php" class="sidebar-item active">
                <i class="fas fa-check-double w-6"></i> Verifikasi
                <?php if($total_menunggu > 0): ?>
                    <span class="ml-auto bg-red-500 text-white rounded-full px-2 text-xs"><?= $total_menunggu ?></span>
                <?php endif; ?>
            </a>
            <a href="laporan_absensi.php" class="sidebar-item"><i class="fas fa-chart-line w-6"></i> Rekap Absensi</a>
        </div>
    </div>

    <div class="main-content">
        <header class="bg-white shadow-sm py-4 px-6 flex justify-between items-center sticky top-0 z-40">
            <h1 class="text-xl font-bold" style="color: var(--primary-1);">Verifikasi Absensi</h1>
        </header>

        <main class="p-6">
            <?php if(isset($_SESSION['flash'])): ?>
                <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-4"><?= $_SESSION['flash']['msg'] ?></div>
            <?php unset($_SESSION['flash']); endif; ?>

            <div class="card p-6">
                <div class="tabs">
                    <div class="tab <?= $tab_active == 'siswa' ? 'active' : '' ?>" onclick="window.location='?tab=siswa'">Siswa (<?= count($absensi_siswa_menunggu) ?>)</div>
                    <div class="tab <?= $tab_active == 'pengawas' ? 'active' : '' ?>" onclick="window.location='?tab=pengawas'">Pengawas (<?= count($absensi_pengawas_menunggu) ?>)</div>
                    <div class="tab <?= $tab_active == 'riwayat' ? 'active' : '' ?>" onclick="window.location='?tab=riwayat'">Riwayat Verifikasi</div>
                </div>

                <div class="tab-content <?= $tab_active == 'siswa' ? 'active' : '' ?>">
                    <?php if(empty($absensi_siswa_menunggu)): ?>
                        <p class="text-center text-gray-400 py-8">Tidak ada data.</p>
                    <?php else: ?>
                        <table class="w-full">
                            <thead><tr><th>Tanggal</th><th>Nama Siswa</th><th>Kelas</th><th>Keterangan</th><th>Bukti Surat</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach($absensi_siswa_menunggu as $a): ?>
                                <tr>
                                    <td><?= $a['tgl_format'] ?> (<?= $a['jam_datang'] ?>)</td>
                                    <td class="font-bold"><?= htmlspecialchars($a['nama_siswa']) ?></td>
                                    <td><?= htmlspecialchars($a['kelas']) ?></td>
                                    <td><?= ucfirst($a['keterangan']) ?></td>
                                    <td>
                                        <?php if (strtolower($a['keterangan']) == 'sakit' && !empty($a['surat_sakit'])): ?>
                                            <a href="../../uploads/surat_sakit/<?= $a['surat_sakit'] ?>" target="_blank" class="btn-lihat">Lihat Surat</a>
                                        <?php else: ?> - <?php endif; ?>
                                    </td>
                                    <td class="flex gap-2">
                                        <button onclick="openVerif(<?= $a['id'] ?>, '<?= addslashes($a['nama_siswa']) ?>', 'setuju', 'siswa')" class="bg-green-500 text-white px-3 py-1 rounded text-xs">Setuju</button>
                                        <button onclick="openVerif(<?= $a['id'] ?>, '<?= addslashes($a['nama_siswa']) ?>', 'tolak', 'siswa')" class="bg-red-500 text-white px-3 py-1 rounded text-xs">Tolak</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="tab-content <?= $tab_active == 'pengawas' ? 'active' : '' ?>">
                    <?php if(empty($absensi_pengawas_menunggu)): ?>
                        <p class="text-center text-gray-400 py-8">Tidak ada data.</p>
                    <?php else: ?>
                        <table class="w-full">
                            <thead><tr><th>Tanggal</th><th>Nama Pengawas</th><th>Keterangan</th><th>Foto Bukti</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach($absensi_pengawas_menunggu as $a): ?>
                                <tr>
                                    <td><?= $a['tgl_format'] ?> (<?= $a['jam_masuk_format'] ?>)</td>
                                    <td class="font-bold"><?= htmlspecialchars($a['nama']) ?></td>
                                    <td><?= ucfirst($a['keterangan']) ?></td>
                                    <td>
                                        <?php if(!empty($a['foto'])): ?>
                                            <a href="../../../uploads/absen_pengawas/<?= $a['foto'] ?>" target="_blank" class="btn-lihat">Lihat Foto</a>
                                        <?php else: ?> - <?php endif; ?>
                                    </td>
                                    <td class="flex gap-2">
                                        <button onclick="openVerif(<?= $a['id'] ?>, '<?= addslashes($a['nama']) ?>', 'setuju', 'pengawas')" class="bg-green-500 text-white px-3 py-1 rounded text-xs">Setuju</button>
                                        <button onclick="openVerif(<?= $a['id'] ?>, '<?= addslashes($a['nama']) ?>', 'tolak', 'pengawas')" class="bg-red-500 text-white px-3 py-1 rounded text-xs">Tolak</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="tab-content <?= $tab_active == 'riwayat' ? 'active' : '' ?>">
                    <form method="GET" class="mb-4 flex gap-2">
                        <input type="hidden" name="tab" value="riwayat">
                        <select name="role" class="border p-2 rounded text-sm"><option value="semua">Semua Role</option><option value="siswa">Siswa</option><option value="pengawas">Pengawas</option></select>
                        <select name="bulan" class="border p-2 rounded text-sm"><option value="<?= date('m') ?>">Bulan Ini</option></select>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Filter</button>
                    </form>
                    <div style="max-height: 500px; overflow:auto;">
                        <table class="w-full">
                            <thead><tr><th>Tgl</th><th>Nama</th><th>Role</th><th>Keterangan</th><th>Status Verif</th><th>Catatan Admin</th></tr></thead>
                            <tbody>
                                <?php foreach($data_absensi as $d): ?>
                                <tr>
                                    <td><?= $d['tgl_format'] ?></td>
                                    <td class="font-bold"><?= htmlspecialchars($d['nama_lengkap']) ?></td>
                                    <td><?= ucfirst($d['role']) ?></td>
                                    <td><?= ucfirst($d['keterangan'] ?? '-') ?></td>
                                    <td><?= getVerifikasiBadge($d['status_verifikasi']) ?></td>
                                    <td><?= $d['catatan_admin'] ?? '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <div id="modalVerif" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white p-6 rounded-xl w-96">
            <h3 class="font-bold mb-4" id="modalTitle">Verifikasi</h3>
            <form method="POST">
                <input type="hidden" name="verifikasi_id" id="v_id">
                <input type="hidden" name="aksi_verifikasi" id="v_aksi">
                <input type="hidden" name="verifikasi_siswa" id="v_siswa">
                <input type="hidden" name="verifikasi_pengawas" id="v_pengawas">
                
                <p class="mb-2 text-sm font-bold text-gray-700" id="v_nama"></p>
                <textarea name="catatan_admin" class="w-full border p-2 rounded text-sm mb-4" rows="3" placeholder="Catatan opsional..."></textarea>
                
                <div class="flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('modalVerif').style.display='none'" class="px-4 py-2 bg-gray-200 rounded text-sm">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openVerif(id, nama, aksi, tipe) {
            document.getElementById('v_id').value = id;
            document.getElementById('v_aksi').value = aksi;
            document.getElementById('v_nama').innerText = "Verifikasi untuk: " + nama;
            
            if (tipe === 'siswa') {
                document.getElementById('v_siswa').name = "verifikasi_siswa";
                document.getElementById('v_pengawas').name = "";
            } else {
                document.getElementById('v_pengawas').name = "verifikasi_pengawas";
                document.getElementById('v_siswa').name = "";
            }
            
            document.getElementById('modalTitle').innerText = (aksi === 'setuju') ? "Setujui Absensi" : "Tolak Absensi";
            document.getElementById('modalVerif').style.display = 'flex';
        }
    </script>
</body>
</html>