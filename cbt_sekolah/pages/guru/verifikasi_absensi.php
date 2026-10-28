<?php
// =============================================
// FILE: pages/guru/verifikasi_absensi.php
// Verifikasi dan kelola kehadiran siswa pada ujian
// =============================================
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../../index.php"); 
    exit;
}
require_once '../../config/koneksi.php'; // pakai PDO punya admin

$nama_guru = $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? 'Guru';
$search    = isset($_GET['search']) ? $_GET['search'] : '';

// Gunakan id_user sesuai nama sesi login yang benar
$guru_id  = $_SESSION['id_user'] ?? 0;
$ujian_id = isset($_GET['ujian_id']) ? (int)$_GET['ujian_id'] : 0;

// Menggunakan PDO untuk mengambil daftar ujian
$stmt_ujian = $pdo->prepare("SELECT id, nama_ujian FROM ujian WHERE guru_id = ? ORDER BY created_at DESC");
$stmt_ujian->execute([$guru_id]);
$ujian_list = $stmt_ujian->fetchAll(PDO::FETCH_ASSOC);

// PROSES: verifikasi / tolak
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $a_id  = (int)$_POST['absensi_id'];
    $aksi  = $_POST['aksi']; // hadir / tidak_hadir / izin
    
    $catatan = trim($_POST['catatan_guru'] ?? '');
    $allowed = ['hadir','tidak_hadir','izin'];
    
    if (in_array($aksi, $allowed)) {
        $stmt_update = $pdo->prepare("UPDATE absensi SET status = ?, catatan_guru = ?, verified_at = NOW(), verified_by = ? WHERE id = ?");
        $stmt_update->execute([$aksi, $catatan, $guru_id, $a_id]);
        
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Status absensi berhasil diperbarui.'];
    }
    header("Location: verifikasi_absensi.php?ujian_id=$ujian_id"); 
    exit();
}

$absensi_list = null;
$detail_ujian = null;

if ($ujian_id) {
    $stmt_detail = $pdo->prepare("SELECT * FROM ujian WHERE id = ? AND guru_id = ?");
    $stmt_detail->execute([$ujian_id, $guru_id]);
    $detail_ujian = $stmt_detail->fetch(PDO::FETCH_ASSOC);
    
    if ($detail_ujian) {
        $stmt_absensi = $pdo->prepare("
            SELECT a.id, a.status, a.keterangan, a.catatan_guru, a.verified_at,
                   s.nama, s.nis, s.kelas
            FROM absensi a
            JOIN siswa s ON s.id = a.siswa_id
            WHERE a.ujian_id = ?
            ORDER BY s.nama
        ");
        $stmt_absensi->execute([$ujian_id]);
        $absensi_list = $stmt_absensi->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Verifikasi Absensi – CBT Sekolah</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        /* Top Navigation */
        .top-nav {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 0 24px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px;
        }
        
        .logo h2 {
            color: #4F46E5;
            font-size: 20px;
        }
        
        .nav-menu {
            display: flex;
            gap: 24px;
            align-items: center;
        }
        
        .nav-link {
            text-decoration: none;
            color: #666;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: #4F46E5;
            color: white;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-name {
            font-weight: 500;
            color: #333;
        }
        
        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background: #dc2626;
        }
        
        /* Main Container */
        .main-container {
            margin-top: 60px;
            padding: 24px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Alert */
        .alert {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Badge */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-gray { background: #e2e3e5; color: #383d41; }
        
        /* Buttons */
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-sm {
            padding: 4px 10px;
            font-size: 11px;
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .btn-success:hover {
            background: #059669;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        .btn-warning:hover {
            background: #d97706;
        }
        .btn-primary {
            background: #4F46E5;
            color: white;
        }
        .btn-primary:hover {
            background: #4338ca;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            font-size: 16px;
            background: #f9fafb;
        }
        .card-body {
            padding: 20px;
        }
        .card-empty {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        /* Form */
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        select.form-control {
            cursor: pointer;
        }
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        /* Table */
        .table-wrapper {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background-color: #f9fafb;
            font-weight: 600;
            font-size: 13px;
            color: #374151;
        }
        td {
            font-size: 14px;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 0;
            border-radius: 12px;
            width: 450px;
            max-width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-header h3 {
            font-size: 18px;
            color: #111827;
        }
        .close {
            cursor: pointer;
            font-size: 24px;
            font-weight: bold;
            color: #6b7280;
        }
        .close:hover {
            color: #111827;
        }
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                padding: 16px;
            }
            .nav-menu {
                gap: 8px;
            }
            .nav-link {
                padding: 6px 8px;
                font-size: 12px;
            }
            th, td {
                padding: 8px 12px;
                font-size: 12px;
            }
            .btn-sm {
                padding: 4px 6px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar (Pengganti Sidebar) -->
    <div class="top-nav">
        <div class="logo">
            <h2>📚 CBT Sekolah</h2>
        </div>
        <div class="nav-menu">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="kelola_ujian.php" class="nav-link">Kelola Ujian</a>
            <a href="verifikasi_absensi.php" class="nav-link active">Verifikasi Absensi</a>
            <a href="lihat_nilai.php" class="nav-link">Lihat Nilai</a>
            <div class="user-info">
                <span class="user-name">👤 <?= htmlspecialchars($nama_guru) ?></span>
                <a href="../../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="main-container">
        <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] === 'success' ? 'success' : 'danger' ?>">
            <?= $_SESSION['flash']['type'] === 'success' ? '✅' : '⚠️' ?> 
            <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
        </div>
        <?php unset($_SESSION['flash']); endif; ?>

        <!-- Pilih Ujian -->
        <div class="card">
            <div class="card-body">
                <form method="GET">
                    <div class="form-group">
                        <label>📝 Pilih Ujian</label>
                        <select name="ujian_id" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Pilih ujian --</option>
                            <?php foreach ($ujian_list as $ujian): ?>
                            <option value="<?= $ujian['id'] ?>" <?= $ujian['id'] == $ujian_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ujian['nama_ujian']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($absensi_list && count($absensi_list) > 0): ?>
        <div class="card">
            <div class="card-header">
                ✅ Absensi – <?= htmlspecialchars($detail_ujian['nama_ujian']) ?>
            </div>
            <div class="card-body" style="padding:0">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Status</th>
                                <th>Keterangan Siswa</th>
                                <th>Catatan Guru</th>
                                <th>Diverifikasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no=1; foreach ($absensi_list as $absensi): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($absensi['nis'] ?? '-') ?></td>
                            <td><strong><?= htmlspecialchars($absensi['nama']) ?></strong></td>
                            <td><?= htmlspecialchars($absensi['kelas'] ?? '-') ?></td>
                            <td>
                                <?php
                                $status = $absensi['status'];
                                $badgeClass = '';
                                $label = '';
                                
                                switch($status) {
                                    case 'hadir':
                                        $badgeClass = 'badge-success';
                                        $label = 'Hadir';
                                        break;
                                    case 'tidak_hadir':
                                        $badgeClass = 'badge-danger';
                                        $label = 'Tidak Hadir';
                                        break;
                                    case 'izin':
                                        $badgeClass = 'badge-warning';
                                        $label = 'Izin';
                                        break;
                                    default:
                                        $badgeClass = 'badge-gray';
                                        $label = 'Belum';
                                }
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $label ?></span>
                            </td>
                            <td style="font-size:12px"><?= htmlspecialchars($absensi['keterangan'] ?? '-') ?></td>
                            <td style="font-size:12px"><?= htmlspecialchars($absensi['catatan_guru'] ?? '-') ?></td>
                            <td style="font-size:12px"><?= $absensi['verified_at'] ? date('d/m H:i', strtotime($absensi['verified_at'])) : '-' ?></td>
                            <td>
                                <div style="display:flex;gap:6px; flex-wrap:wrap;">
                                    <button type="button" class="btn btn-success btn-sm" onclick="openModal(<?= $absensi['id'] ?>, 'hadir', '<?= htmlspecialchars($absensi['catatan_guru'] ?? '') ?>')">
                                        ✓ Hadir
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="openModal(<?= $absensi['id'] ?>, 'tidak_hadir', '<?= htmlspecialchars($absensi['catatan_guru'] ?? '') ?>')">
                                        ✗ Absen
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" onclick="openModal(<?= $absensi['id'] ?>, 'izin', '<?= htmlspecialchars($absensi['catatan_guru'] ?? '') ?>')">
                                        ℹ Izin
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php elseif ($ujian_id && $absensi_list !== null && count($absensi_list) == 0): ?>
        <div class="card">
            <div class="card-empty">
                <div class="empty-icon">📋</div>
                <p>Belum ada data absensi untuk ujian ini.</p>
            </div>
        </div>
        <?php elseif (!$ujian_id): ?>
        <div class="card">
            <div class="card-empty">
                <div class="empty-icon">📌</div>
                <p>Silakan pilih ujian terlebih dahulu untuk melihat data absensi.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal untuk catatan guru -->
    <div id="catatanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Verifikasi Kehadiran</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div style="padding: 20px;">
                <form method="POST" id="verifikasiForm">
                    <input type="hidden" name="absensi_id" id="absensiId">
                    <input type="hidden" name="aksi" id="aksiValue">
                    <div class="form-group">
                        <label for="catatan_guru">📝 Catatan Guru (opsional):</label>
                        <textarea class="form-control" name="catatan_guru" id="catatanGuru" rows="3" placeholder="Masukkan catatan seperti alasan ketidakhadiran, dll..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan & Verifikasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal handling
        function openModal(absensiId, aksi, catatanSaatIni) {
            document.getElementById('absensiId').value = absensiId;
            document.getElementById('aksiValue').value = aksi;
            document.getElementById('catatanGuru').value = catatanSaatIni;
            
            let title = '';
            switch(aksi) {
                case 'hadir':
                    title = '✅ Verifikasi Kehadiran - Hadir';
                    break;
                case 'tidak_hadir':
                    title = '❌ Verifikasi Kehadiran - Tidak Hadir';
                    break;
                case 'izin':
                    title = 'ℹ️ Verifikasi Kehadiran - Izin';
                    break;
            }
            document.getElementById('modalTitle').innerHTML = title;
            
            document.getElementById('catatanModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('catatanModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('catatanModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>