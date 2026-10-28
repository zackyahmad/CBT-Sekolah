<?php
// File: pages/admin/verifikasi_absen_pengawas.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

date_default_timezone_set('Asia/Jakarta');

// Proses Verifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_verifikasi'])) {
    $id = (int)$_POST['id'];
    $aksi = $_POST['aksi_verifikasi'];
    $catatan = trim($_POST['catatan_admin'] ?? '');
    
    try {
        if ($aksi == 'setuju') {
            $stmt = $pdo->prepare("UPDATE absensi_pengawas SET status_verifikasi = 'disetujui', catatan_admin = ?, diverifikasi_oleh = ?, diverifikasi_pada = NOW() WHERE id = ?");
            $stmt->execute([$catatan, $_SESSION['id_user'], $id]);
            $pesan = "✅ Absensi pengawas berhasil disetujui!";
        } else {
            $stmt = $pdo->prepare("UPDATE absensi_pengawas SET status_verifikasi = 'ditolak', catatan_admin = ?, diverifikasi_oleh = ?, diverifikasi_pada = NOW() WHERE id = ?");
            $stmt->execute([$catatan, $_SESSION['id_user'], $id]);
            $pesan = "⚠️ Absensi pengawas ditolak.";
        }
        $_SESSION['flash'] = ['type' => 'success', 'msg' => $pesan];
    } catch (PDOException $e) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Gagal verifikasi: ' . $e->getMessage()];
    }
    header("Location: verifikasi_absen_pengawas.php");
    exit;
}

// Ambil data absen menunggu verifikasi
$stmt = $pdo->prepare("SELECT a.*, u.nama_lengkap as admin_nama FROM absensi_pengawas a LEFT JOIN tb_users u ON a.diverifikasi_oleh = u.id_user WHERE a.status_verifikasi = 'menunggu' ORDER BY a.tanggal DESC, a.jam_masuk DESC");
$stmt->execute();
$menunggu_list = $stmt->fetchAll();

// Ambil semua data absen
$stmt_all = $pdo->prepare("SELECT a.*, u.nama_lengkap as admin_nama FROM absensi_pengawas a LEFT JOIN tb_users u ON a.diverifikasi_oleh = u.id_user ORDER BY a.tanggal DESC, a.jam_masuk DESC");
$stmt_all->execute();
$all_absen = $stmt_all->fetchAll();

// Statistik
$total_menunggu = count($menunggu_list);
$total_disetujui = 0;
$total_ditolak = 0;
foreach ($all_absen as $row) {
    if ($row['status_verifikasi'] == 'disetujui') $total_disetujui++;
    elseif ($row['status_verifikasi'] == 'ditolak') $total_ditolak++;
}

$upload_dir = '../../../uploads/absen_pengawas/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Absen Pengawas - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-1: #2B3A67;
            --primary-2: #496A81;
            --secondary-1: #66999B;
            --accent-1: #FFC482;
            --success: #77BFA3;
            --warning: #FFB347;
            --danger: #E58C7C;
        }
        body {
            background: linear-gradient(135deg, #F8F4E9 0%, #E8E1D3 100%);
            font-family: 'Inter', system-ui, sans-serif;
        }
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .badge-menunggu { background: #FFB34720; color: #FFB347; border: 1px solid #FFB347; padding: 4px 12px; border-radius: 30px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .badge-disetujui { background: #77BFA320; color: #77BFA3; border: 1px solid #77BFA3; padding: 4px 12px; border-radius: 30px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .badge-ditolak { background: #E58C7C20; color: #E58C7C; border: 1px solid #E58C7C; padding: 4px 12px; border-radius: 30px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .btn-primary {
            background: linear-gradient(135deg, var(--secondary-1), var(--primary-2));
            color: white;
            padding: 8px 20px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover { transform: translateY(-2px); }
        .foto-thumbnail {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid var(--accent-1);
        }
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            padding: 20px;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #E8E1D3; }
        th { background: #F8F4E9; font-weight: 600; color: var(--primary-1); }
        tr:hover { background: #F8F4E9; }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            border: 1px solid #E8E1D3;
        }
        .stat-value { font-size: 32px; font-weight: 800; }
        .flex { display: flex; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        .justify-between { justify-content: space-between; }
        .text-center { text-align: center; }
        .mt-2 { margin-top: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .p-6 { padding: 24px; }
        .grid { display: grid; }
        .grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
        .text-green-600 { color: var(--success); }
        .text-yellow-600 { color: var(--warning); }
        .text-red-600 { color: var(--danger); }
        .text-2xl { font-size: 24px; }
        .font-bold { font-weight: 700; }
    </style>
</head>
<body class="p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold" style="color: var(--primary-1);">
                <i class="fas fa-check-double mr-2" style="color: var(--secondary-1);"></i>
                Verifikasi Absensi Pengawas
            </h1>
            <p class="text-gray-500 mt-1">Kelola dan verifikasi absensi pengawas ujian</p>
        </div>

        <!-- Flash Message -->
        <?php if(isset($_SESSION['flash'])): ?>
        <div class="mb-4 p-4 rounded-lg <?= $_SESSION['flash']['type'] == 'success' ? 'bg-green-100 text-green-700 border-l-4 border-green-500' : 'bg-red-100 text-red-700 border-l-4 border-red-500' ?>">
            <i class="fas fa-<?= $_SESSION['flash']['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?> mr-2"></i>
            <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
        </div>
        <?php unset($_SESSION['flash']); endif; ?>

        <!-- Statistik -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="stat-card">
                <div class="stat-value text-yellow-600"><?= $total_menunggu ?></div>
                <div class="text-gray-500">Menunggu Verifikasi</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-green-600"><?= $total_disetujui ?></div>
                <div class="text-gray-500">Disetujui</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-red-600"><?= $total_ditolak ?></div>
                <div class="text-gray-500">Ditolak</div>
            </div>
        </div>

        <!-- Daftar Menunggu Verifikasi -->
        <?php if($total_menunggu > 0): ?>
        <div class="card p-6 mb-6">
            <h2 class="font-bold text-lg mb-4" style="color: var(--primary-1);">
                <i class="fas fa-bell mr-2" style="color: var(--warning);"></i>
                Menunggu Verifikasi (<?= $total_menunggu ?>)
            </h2>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr><th>No</th><th>Tanggal</th><th>Nama Pengawas</th><th>Jam Masuk</th><th>Keterangan</th><th>Foto</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php $no=1; foreach($menunggu_list as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                            <td class="font-semibold"><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= $row['jam_masuk'] ?> WIB</td>
                            <td><?= $row['keterangan'] ?></td>
                            <td>
                                <?php if($row['foto']): ?>
                                <img src="../../../uploads/absen_pengawas/<?= $row['foto'] ?>" class="foto-thumbnail" onclick="showFoto('../../../uploads/absen_pengawas/<?= $row['foto'] ?>')">
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <button onclick="openVerifModal(<?= $row['id'] ?>, '<?= addslashes($row['nama']) ?>', 'setuju')" class="px-3 py-1 bg-green-500 text-white rounded-lg text-sm hover:bg-green-600">
                                        <i class="fas fa-check"></i> Setuju
                                    </button>
                                    <button onclick="openVerifModal(<?= $row['id'] ?>, '<?= addslashes($row['nama']) ?>', 'tolak')" class="px-3 py-1 bg-red-500 text-white rounded-lg text-sm hover:bg-red-600">
                                        <i class="fas fa-times"></i> Tolak
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Riwayat Semua Absen -->
        <div class="card p-6">
            <h2 class="font-bold text-lg mb-4" style="color: var(--primary-1);">
                <i class="fas fa-history mr-2"></i>
                Riwayat Absensi Pengawas
            </h2>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr><th>No</th><th>Tanggal</th><th>Nama</th><th>Jam Masuk</th><th>Jam Pulang</th><th>Keterangan</th><th>Status</th><th>Foto</th><th>Catatan Admin</th></tr>
                    </thead>
                    <tbody>
                        <?php $no=1; foreach($all_absen as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= $row['jam_masuk'] ?> WIB</td>
                            <td><?= $row['jam_pulang'] ?? '-' ?> WIB</td>
                            <td><?= $row['keterangan'] ?></td>
                            <td>
                                <?php if($row['status_verifikasi'] == 'menunggu'): ?>
                                <span class="badge-menunggu"><i class="fas fa-clock"></i> Menunggu</span>
                                <?php elseif($row['status_verifikasi'] == 'disetujui'): ?>
                                <span class="badge-disetujui"><i class="fas fa-check-circle"></i> Disetujui</span>
                                <?php else: ?>
                                <span class="badge-ditolak"><i class="fas fa-times-circle"></i> Ditolak</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($row['foto']): ?>
                                <img src="../../../uploads/absen_pengawas/<?= $row['foto'] ?>" class="foto-thumbnail" onclick="showFoto('../../../uploads/absen_pengawas/<?= $row['foto'] ?>')">
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td class="text-sm"><?= htmlspecialchars($row['catatan_admin'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Verifikasi -->
    <div id="verifModal" class="modal-overlay">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold" id="modalTitle">Verifikasi Absensi</h3>
                <button onclick="closeVerifModal()" class="text-2xl">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="verifId">
                <input type="hidden" name="aksi_verifikasi" id="verifAksi">
                <p class="mb-4"><i class="fas fa-user"></i> <span id="pengawasNama"></span></p>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Catatan Admin</label>
                    <textarea name="catatan_admin" id="catatanAdmin" rows="3" class="w-full border rounded-lg p-2"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary w-full" id="submitBtn">Verifikasi</button>
                    <button type="button" onclick="closeVerifModal()" class="px-4 py-2 border rounded-lg">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Foto -->
    <div id="fotoModal" class="modal-overlay">
        <div class="modal-content text-center">
            <img id="fotoPreview" src="" style="max-width:100%; border-radius:12px;">
            <button onclick="closeFotoModal()" class="btn-primary mt-3 w-full">Tutup</button>
        </div>
    </div>

    <script>
        function openVerifModal(id, nama, aksi) {
            document.getElementById('verifId').value = id;
            document.getElementById('verifAksi').value = aksi;
            document.getElementById('pengawasNama').innerHTML = '<strong>' + nama + '</strong>';
            
            const modalTitle = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('submitBtn');
            
            if (aksi === 'setuju') {
                modalTitle.innerHTML = '<i class="fas fa-check-circle text-green-500"></i> Setujui Absensi';
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Setujui';
                submitBtn.className = 'btn-primary w-full bg-green-500';
            } else {
                modalTitle.innerHTML = '<i class="fas fa-times-circle text-red-500"></i> Tolak Absensi';
                submitBtn.innerHTML = '<i class="fas fa-times"></i> Tolak';
                submitBtn.className = 'btn-primary w-full bg-red-500';
            }
            
            document.getElementById('verifModal').style.display = 'flex';
        }
        
        function closeVerifModal() {
            document.getElementById('verifModal').style.display = 'none';
            document.getElementById('catatanAdmin').value = '';
        }
        
        function showFoto(src) {
            document.getElementById('fotoPreview').src = src;
            document.getElementById('fotoModal').style.display = 'flex';
        }
        
        function closeFotoModal() {
            document.getElementById('fotoModal').style.display = 'none';
        }
        
        window.onclick = function(e) {
            if (e.target == document.getElementById('verifModal')) closeVerifModal();
            if (e.target == document.getElementById('fotoModal')) closeFotoModal();
        }
    </script>
</body>
</html>