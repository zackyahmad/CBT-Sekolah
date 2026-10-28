<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

// 1. Ambil semua kemungkinan Session yang tersedia saat siswa login
$id_user = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
$session_nis = $_SESSION['nis'] ?? $_SESSION['nis_siswa'] ?? '';
$session_username = $_SESSION['username'] ?? $_SESSION['email'] ?? '';

$kartu = null;
$data_tersedia = false;

// Pastikan ada salah satu identitas yang bisa dilacak
if ($id_user > 0 || !empty($session_nis) || !empty($session_username)) {
    
    // 2. Cari data utuh siswa di tabel tb_users terlebih dahulu
    $query_user = "SELECT * FROM tb_users WHERE role = 'siswa' AND ";
    $params_user = [];
    
    if ($id_user > 0) {
        $query_user .= "id_user = ?";
        $params_user[] = $id_user;
    } elseif (!empty($session_nis)) {
        $query_user .= "nis = ?";
        $params_user[] = $session_nis;
    } else {
        $query_user .= "username = ?";
        $params_user[] = $session_username;
    }
    
    $stmt_user = $pdo->prepare($query_user);
    $stmt_user->execute($params_user);
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // 3. Jika akun siswanya ketemu, cocokkan dengan tabel tb_kartu_ujian
    if ($user_data) {
        $db_nis = $user_data['nis'] ?? '';
        $db_username = $user_data['username'] ?? '';
        $db_nama = $user_data['nama_lengkap'] ?? $user_data['nama'] ?? '';

        // PENCARIAN SUPER KETAT: Cocokkan NIS, ATAU Username Ujian, ATAU Nama Siswa
        $stmt_kartu = $pdo->prepare("SELECT * FROM tb_kartu_ujian WHERE nis = ? OR username_ujian = ? OR nama_siswa = ? LIMIT 1");
        
        // Eksekusi pencarian
        $stmt_kartu->execute([$db_nis, $db_username, $db_nama]);
        $kartu = $stmt_kartu->fetch(PDO::FETCH_ASSOC);
        
        if ($kartu) {
            $data_tersedia = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Ujian</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Inter', sans-serif; display: flex; flex-direction: column; align-items: center; padding: 40px 20px; }
        .card-kartu { background: white; border: 1px solid #000; border-radius: 4px; overflow: hidden; width: 100%; max-width: 500px; font-family: 'Times New Roman', Times, serif; color: #000; margin-bottom: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .kartu-header { display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; border-bottom: 3px solid #000; border-bottom-style: double; }
        .logo-img { width: 55px; height: auto; }
        .kop-section { flex: 1; text-align: center; line-height: 1.2; }
        .kop-prov { font-size: 13px; }
        .kop-dinas { font-size: 15px; font-weight: bold; }
        .kop-sekolah { font-size: 17px; font-weight: bold; letter-spacing: 0.5px; margin-top: 2px;}
        .kop-alamat { font-size: 9px; font-style: italic; margin-top: 3px;}
        .kartu-judul { text-align: center; padding: 15px 0; line-height: 1.4; }
        .judul-utama { font-size: 16px; font-weight: bold; text-decoration: underline; }
        .judul-sub { font-size: 13px; font-weight: bold; }
        .kartu-body { display: flex; padding: 0 20px 20px; gap: 20px; }
        .foto-section { width: 100px; }
        .foto-container { width: 100px; height: 130px; border: 1px solid #000; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #fff; }
        .data-section { flex: 1; font-size: 13px; }
        .data-row { display: flex; margin-bottom: 6px; }
        .data-label { width: 110px; }
        .data-titik { width: 15px; }
        .data-value { flex: 1; font-weight: bold; }
        .login-box { border: 1px dashed #000; padding: 8px; margin-top: 10px; background: #fdfdfd; }
        .kartu-footer { padding: 0 20px 20px; display: flex; justify-content: flex-end; text-align: center; font-size: 13px; }
        .ttd-area { width: 160px; }
        .ttd-nama { margin-top: 50px; font-weight: bold; text-decoration: underline; }
        .btn-container { display: flex; gap: 15px; }
        .btn { padding: 12px 25px; border-radius: 6px; font-weight: bold; text-decoration: none; cursor: pointer; border: none; color: white; display: flex; align-items: center; gap: 8px;}
        .btn-print { background: #2563eb; }
        .btn-back { background: #475569; }
        .alert { background: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 15px; border-radius: 8px; max-width: 500px; text-align: center; }
        @media print {
            body { background: white; padding: 0; align-items: flex-start; }
            .btn-container, .alert { display: none !important; }
            .card-kartu { box-shadow: none; border: 1px solid #000; max-width: none; width: 13cm; margin: 0; }
            @page { margin: 1cm; size: auto; }
        }
    </style>
</head>
<body>

    <?php if (!$data_tersedia): ?>
        <div class="alert">
            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
            <strong>Mohon Maaf!</strong><br>Kartu ujian Anda belum diterbitkan oleh Admin.<br>Silahkan konfirmasi ke pihak kurikulum / panitia ujian.
        </div>
        <div class="btn-container mt-4">
            <a href="dashboard.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>
    <?php else: ?>

        <div class="card-kartu">
            <div class="kartu-header">
                <img src="../../assets/img/logo-lampung.png" alt="Provinsi" class="logo-img">
                <div class="kop-section">
                    <div class="kop-prov">PEMERINTAH PROVINSI LAMPUNG</div>
                    <div class="kop-dinas">DINAS PENDIDIKAN DAN KEBUDAYAAN</div>
                    <div class="kop-sekolah">SMK NEGERI 8 BANDAR LAMPUNG</div>
                    <div class="kop-alamat">Jl. Imam Bonjol No. 52, Kemiling, Bandar Lampung 35153</div>
                </div>
                <img src="../../assets/img/logo-smk8.png" alt="SMK" class="logo-img">
            </div>

            <div class="kartu-judul">
                <div class="judul-utama">KARTU PESERTA UJIAN</div>
                <div class="judul-sub">
                    <?= strtoupper(htmlspecialchars($kartu['jenis_ujian'] ?? 'UJIAN SEKOLAH')) ?><br>
                    TAHUN PELAJARAN <?= htmlspecialchars($kartu['tahun_pelajaran'] ?? '2025/2026') ?>
                </div>
            </div>

            <div class="kartu-body">
                <div class="foto-section">
                    <div class="foto-container">
                        <?php if(!empty($kartu['foto_siswa'])): ?>
                            <img src="../../uploads/<?= $kartu['foto_siswa'] ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <span style="font-size: 14px; color:#999; text-align:center;">Pas<br>Foto<br>3x4</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="data-section">
                    <div class="data-row"><div class="data-label">Nama Lengkap</div><div class="data-titik">:</div><div class="data-value"><?= htmlspecialchars($kartu['nama_siswa']) ?></div></div>
                    <div class="data-row"><div class="data-label">NIS / NISN</div><div class="data-titik">:</div><div class="data-value"><?= htmlspecialchars($kartu['nis']) ?> / <?= htmlspecialchars($kartu['sid'] ?? '-') ?></div></div>
                    <div class="data-row"><div class="data-label">Kelas / Jurusan</div><div class="data-titik">:</div><div class="data-value"><?= htmlspecialchars($kartu['kelas']) ?> / <?= htmlspecialchars($kartu['jurusan'] ?? '-') ?></div></div>
                    
                    <div class="login-box">
                        <div><strong>Username :</strong> <?= htmlspecialchars($kartu['username_ujian'] ?? $kartu['nis']) ?></div>
                        <div><strong>Password :</strong> <?= htmlspecialchars($kartu['password_ujian'] ?? '123456') ?></div>
                    </div>
                </div>
            </div>
            
            <div class="kartu-footer">
                <div class="ttd-area">
                    Bandar Lampung, <?= date('d F Y') ?><br>Kepala Sekolah<br>
                    <div class="ttd-nama">Nama Kepala Sekolah, S.Pd</div>
                    <div>NIP. 19841121 200903 2 003</div>
                </div>
            </div>
        </div>

        <div class="btn-container">
            <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Cetak Kartu</button>
            <a href="dashboard.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

    <?php endif; ?>

</body>
</html>