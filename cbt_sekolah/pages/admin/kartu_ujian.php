<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

// Ambil daftar master kelas dari database untuk dropdown
$master_kelas = $pdo->query("SELECT * FROM tb_kelas ORDER BY nama_kelas ASC")->fetchAll(PDO::FETCH_ASSOC);

// ========== PROSES TAMBAH DATA ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah') {
    // Gunakan null coalescing (??) agar tidak error jika form dikosongkan
    $nis = $_POST['nis'] ?? '';
    $nama_siswa = $_POST['nama_siswa'] ?? '';
    $kelas = $_POST['kelas'] ?? '';
    $jenis_ujian = $_POST['jenis_ujian'] ?? ''; 
    $tahun_pelajaran = $_POST['tahun_pelajaran'] ?? ''; 
    $jurusan = $_POST['jurusan'] ?? '';
    $sid = $_POST['sid'] ?? '';
    $tempat_lahir = $_POST['tempat_lahir'] ?? null; 
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? null; 
    $username_ujian = !empty($_POST['username_ujian']) ? $_POST['username_ujian'] : $nis;
    $password_ujian = !empty($_POST['password_ujian']) ? $_POST['password_ujian'] : '123456';
    
    // CEK NIS DUPLIKAT
    $cek_nis = $pdo->prepare("SELECT id_kartu FROM tb_kartu_ujian WHERE nis = ?");
    $cek_nis->execute([$nis]);
    if ($cek_nis->rowCount() > 0) {
        $_SESSION['error'] = "Gagal! Siswa dengan NIS '$nis' sudah memiliki kartu ujian.";
        header("Location: kartu_ujian.php");
        exit;
    }

    $foto_siswa = null;
    if (isset($_FILES['foto_siswa']) && $_FILES['foto_siswa']['error'] == 0) {
        $target_dir = "../../uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $foto_siswa = time() . "_" . basename($_FILES['foto_siswa']['name']);
        move_uploaded_file($_FILES['foto_siswa']['tmp_name'], $target_dir . $foto_siswa);
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO tb_kartu_ujian (nis, nama_siswa, kelas, jenis_ujian, tahun_pelajaran, jurusan, sid, tempat_lahir, tanggal_lahir, username_ujian, password_ujian, foto_siswa) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$nis, $nama_siswa, $kelas, $jenis_ujian, $tahun_pelajaran, $jurusan, $sid, $tempat_lahir, $tanggal_lahir, $username_ujian, $password_ujian, $foto_siswa]);
        $_SESSION['success'] = "Kartu ujian berhasil ditambahkan";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menyimpan data: " . $e->getMessage();
    }
    header("Location: kartu_ujian.php");
    exit;
}

// ========== PROSES EDIT DATA ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id_kartu'];
    $nis = $_POST['nis'] ?? '';
    $nama_siswa = $_POST['nama_siswa'] ?? '';
    $kelas = $_POST['kelas'] ?? '';
    $jenis_ujian = $_POST['jenis_ujian'] ?? ''; 
    $tahun_pelajaran = $_POST['tahun_pelajaran'] ?? ''; 
    $jurusan = $_POST['jurusan'] ?? '';
    $sid = $_POST['sid'] ?? '';
    $tempat_lahir = $_POST['tempat_lahir'] ?? null;
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
    $username_ujian = $_POST['username_ujian'] ?? '';
    $password_ujian = $_POST['password_ujian'] ?? '';
    
    // Cek duplikat saat edit (pastikan NIS yang diubah bukan milik orang lain)
    $cek_nis = $pdo->prepare("SELECT id_kartu FROM tb_kartu_ujian WHERE nis = ? AND id_kartu != ?");
    $cek_nis->execute([$nis, $id]);
    if ($cek_nis->rowCount() > 0) {
        $_SESSION['error'] = "Gagal update! NIS '$nis' sudah digunakan oleh siswa lain.";
        header("Location: kartu_ujian.php");
        exit;
    }

    $stmt = $pdo->prepare("SELECT foto_siswa FROM tb_kartu_ujian WHERE id_kartu = ?");
    $stmt->execute([$id]);
    $foto_lama = $stmt->fetchColumn();
    $foto_siswa = $foto_lama;
    
    if (isset($_FILES['foto_siswa']) && $_FILES['foto_siswa']['error'] == 0) {
        if ($foto_lama && file_exists("../../uploads/" . $foto_lama)) {
            unlink("../../uploads/" . $foto_lama);
        }
        $foto_siswa = time() . "_" . basename($_FILES['foto_siswa']['name']);
        move_uploaded_file($_FILES['foto_siswa']['tmp_name'], "../../uploads/" . $foto_siswa);
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE tb_kartu_ujian SET nis=?, nama_siswa=?, kelas=?, jenis_ujian=?, tahun_pelajaran=?, jurusan=?, sid=?, tempat_lahir=?, tanggal_lahir=?, username_ujian=?, password_ujian=?, foto_siswa=? WHERE id_kartu=?");
        $stmt->execute([$nis, $nama_siswa, $kelas, $jenis_ujian, $tahun_pelajaran, $jurusan, $sid, $tempat_lahir, $tanggal_lahir, $username_ujian, $password_ujian, $foto_siswa, $id]);
        $_SESSION['success'] = "Kartu ujian berhasil diupdate";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal update data: " . $e->getMessage();
    }
    header("Location: kartu_ujian.php");
    exit;
}

// ========== PROSES HAPUS DATA ==========
if (isset($_GET['hapus_id'])) {
    $id = $_GET['hapus_id'];
    $stmt = $pdo->prepare("SELECT foto_siswa FROM tb_kartu_ujian WHERE id_kartu = ?");
    $stmt->execute([$id]);
    $foto = $stmt->fetchColumn();
    if ($foto && file_exists("../../uploads/" . $foto)) unlink("../../uploads/" . $foto);
    $stmt = $pdo->prepare("DELETE FROM tb_kartu_ujian WHERE id_kartu = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = "Data kartu ujian berhasil dihapus";
    header("Location: kartu_ujian.php");
    exit;
}

// ========== AMBIL DATA UNTUK EDIT (AJAX) ==========
if (isset($_GET['get_data'])) {
    $id = $_GET['get_data'];
    $stmt = $pdo->prepare("SELECT * FROM tb_kartu_ujian WHERE id_kartu = ?");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: ['error' => 'Data tidak ditemukan']);
    exit;
}

// ========== FILTER DATA ==========
$search = isset($_GET['search']) ? $_GET['search'] : '';
$kelas_filter = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$query = "SELECT * FROM tb_kartu_ujian WHERE 1=1";
$params = [];
if (!empty($search)) { $query .= " AND (nis LIKE ? OR nama_siswa LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if (!empty($kelas_filter)) { 
    if (in_array($kelas_filter, ['XII', 'XI', 'X'])) { $query .= " AND kelas LIKE ?"; $params[] = "$kelas_filter%"; } 
    else { $query .= " AND kelas = ?"; $params[] = $kelas_filter; }
}
$query .= " ORDER BY kelas, nama_siswa ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$kartu_ujian = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Ujian - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f4f6f9; font-family: 'Inter', sans-serif; }
        .card-kartu { background: white; border: 1px solid #000; border-radius: 4px; overflow: hidden; width: 100%; font-family: 'Times New Roman', Times, serif; color: #000; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .kartu-header { display: flex; align-items: center; justify-content: space-between; padding: 10px 15px; border-bottom: 3px solid #000; border-bottom-style: double; }
        .logo-img { width: 50px; height: auto; }
        .kop-section { flex: 1; text-align: center; line-height: 1.2; }
        .kop-prov { font-size: 11px; }
        .kop-dinas { font-size: 13px; font-weight: bold; }
        .kop-sekolah { font-size: 15px; font-weight: bold; letter-spacing: 0.5px; }
        .kop-alamat { font-size: 8px; font-style: italic; }
        .kartu-judul { text-align: center; padding: 8px 0; line-height: 1.2; }
        .judul-utama { font-size: 13px; font-weight: bold; text-decoration: underline; }
        .judul-sub { font-size: 11px; font-weight: bold; }
        .kartu-body { display: flex; padding: 0 15px 15px; gap: 15px; }
        .foto-section { width: 80px; }
        .foto-container { width: 80px; height: 100px; border: 1px solid #000; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #fff; }
        .data-section { flex: 1; font-size: 11px; }
        .data-row { display: flex; margin-bottom: 3px; }
        .data-label { width: 95px; }
        .data-titik { width: 10px; }
        .data-value { flex: 1; font-weight: bold; }
        .login-box { border: 1px dashed #000; padding: 5px; margin-top: 5px; background: #fdfdfd; }
        .kartu-footer { padding: 0 15px 15px; display: flex; justify-content: flex-end; text-align: center; font-size: 11px; }
        .ttd-area { width: 140px; }
        .ttd-nama { margin-top: 45px; font-weight: bold; text-decoration: underline; }
        .action-buttons { background: #f8f9fa; padding: 8px; display: flex; gap: 5px; border-top: 1px solid #ddd; font-family: 'Inter', sans-serif;}
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 8px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
        .top-header { background: #e1ddd3; padding: 15px 30px; position: sticky; top: 0; z-index: 40; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
    </style>
</head>
<body>
    <header class="top-header">
        <div class="flex items-center gap-6">
            <a href="dashboard.php" class="text-gray-800 font-bold hover:text-gray-600"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>
        <div class="flex gap-3 items-center flex-wrap">
            
            <form method="GET" class="flex items-center gap-2 m-0">
                <input type="text" name="search" placeholder="Cari NIS/Nama..." value="<?= htmlspecialchars($search) ?>" class="p-2 rounded border text-sm text-black outline-none w-32 md:w-48">
                
                <select name="kelas" class="p-2 rounded border text-sm text-black outline-none bg-white">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach($master_kelas as $mk): ?>
                        <option value="<?= htmlspecialchars($mk['nama_kelas']) ?>" <?= ($kelas_filter == $mk['nama_kelas']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mk['nama_kelas']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 text-sm"><i class="fas fa-filter"></i> Filter</button>
                
                <?php if(!empty($search) || !empty($kelas_filter)): ?>
                    <a href="kartu_ujian.php" class="bg-gray-500 text-white px-4 py-2 rounded shadow hover:bg-gray-600 text-sm" title="Reset Filter"><i class="fas fa-sync-alt"></i></a>
                <?php endif; ?>
            </form>
            <button onclick="bukaModalTambah()" class="bg-purple-600 text-white px-4 py-2 rounded shadow hover:bg-purple-700 text-sm"><i class="fas fa-plus"></i> Tambah Kartu</button>
        </div>
    </header>

    <main class="p-8">
        <?php if(isset($_SESSION['error'])): ?>
            <div class="bg-red-100 text-red-700 p-4 mb-6 rounded border border-red-300 font-bold">
                <i class="fas fa-exclamation-circle mr-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="bg-green-100 text-green-700 p-4 mb-6 rounded border border-green-300 font-bold">
                <i class="fas fa-check-circle mr-2"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if(empty($kartu_ujian)): ?>
            <div class="text-center py-10 bg-white rounded shadow text-gray-500">
                <i class="fas fa-id-card fa-3x mb-3"></i>
                <p>Data kartu ujian tidak ditemukan untuk filter ini.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($kartu_ujian as $kartu): ?>
                <div class="card-kartu flex flex-col justify-between">
                    <div>
                        <div class="kartu-header">
                            <img src="../../assets/img/logo-lampung.png" alt="Logo Provinsi" class="logo-img" onerror="this.src='https://placehold.co/50x60?text=Prov'">
                            <div class="kop-section">
                                <div class="kop-prov">PEMERINTAH PROVINSI LAMPUNG</div>
                                <div class="kop-dinas">DINAS PENDIDIKAN DAN KEBUDAYAAN</div>
                                <div class="kop-sekolah">SMK NEGERI 8 BANDAR LAMPUNG</div>
                                <div class="kop-alamat">Jl. Imam Bonjol No. 52, Kemiling, Bandar Lampung 35153</div>
                            </div>
                            <img src="../../assets/img/logo-smk8.png" alt="Logo SMK" class="logo-img" onerror="this.src='https://placehold.co/50x50?text=SMK'">
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
                                        <span style="font-size: 10px; color:#999; text-align:center;">Pas<br>Foto<br>3x4</span>
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
                                Bandar Lampung, <?= date('d M Y') ?><br>Kepala Sekolah<br>
                                <div class="ttd-nama">Nama Kepala Sekolah, S.Pd</div>
                                <div>NIP. 19841121...</div>
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button onclick="window.open(`cetak_kartu.php?id=<?= $kartu['id_kartu'] ?>`, '_blank')" class="flex-1 bg-blue-600 text-white p-2 rounded text-xs hover:bg-blue-700"><i class="fas fa-print"></i> Cetak</button>
                        <button onclick="bukaModalEdit(<?= $kartu['id_kartu'] ?>)" class="flex-1 bg-yellow-500 text-white p-2 rounded text-xs hover:bg-yellow-600"><i class="fas fa-edit"></i> Edit</button>
                        <button onclick="if(confirm('Hapus kartu ini?')) window.location.href='kartu_ujian.php?hapus_id=<?= $kartu['id_kartu'] ?>'" class="flex-1 bg-red-600 text-white p-2 rounded text-xs hover:bg-red-700"><i class="fas fa-trash"></i> Hapus</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <div id="modalForm" class="modal">
        <div class="modal-content">
            <div class="bg-gray-800 text-white p-4 flex justify-between">
                <h3 id="modalTitle" class="font-bold">Form Kartu Ujian</h3>
                <button onclick="document.getElementById('modalForm').classList.remove('active')" class="text-xl">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="p-4" id="formKartu">
                <input type="hidden" name="action" id="formAction" value="tambah">
                <input type="hidden" name="id_kartu" id="formId">
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div><label class="text-xs font-bold">NIS</label><input type="text" name="nis" id="inp_nis" required class="w-full border p-2 rounded"></div>
                    <div><label class="text-xs font-bold">Nama Siswa</label><input type="text" name="nama_siswa" id="inp_nama" required class="w-full border p-2 rounded"></div>
                    
                    <div>
                        <label class="text-xs font-bold text-purple-700">Kelas</label>
                        <select name="kelas" id="inp_kelas" required class="w-full border p-2 rounded bg-white">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach($master_kelas as $mk): ?>
                                <option value="<?= htmlspecialchars($mk['nama_kelas']) ?>"><?= htmlspecialchars($mk['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div><label class="text-xs font-bold">Jurusan</label><input type="text" name="jurusan" id="inp_jurusan" class="w-full border p-2 rounded"></div>
                    <div><label class="text-xs font-bold text-blue-600">Jenis Ujian</label><input type="text" name="jenis_ujian" id="inp_jenis" placeholder="Cth: SUMATIF TENGAH SEMESTER" class="w-full border p-2 rounded"></div>
                    <div><label class="text-xs font-bold text-blue-600">Tahun Pelajaran</label><input type="text" name="tahun_pelajaran" id="inp_tahun" placeholder="Cth: 2025/2026" class="w-full border p-2 rounded"></div>
                    <div><label class="text-xs font-bold">SID/NISN</label><input type="text" name="sid" id="inp_sid" class="w-full border p-2 rounded"></div>
                    <div><label class="text-xs font-bold">Username Ujian</label><input type="text" name="username_ujian" id="inp_username" class="w-full border p-2 rounded"></div>
                    <div><label class="text-xs font-bold">Password Ujian</label><input type="text" name="password_ujian" id="inp_password" class="w-full border p-2 rounded"></div>
                    <div><label class="text-xs font-bold">Foto Siswa</label><input type="file" name="foto_siswa" accept="image/*" class="w-full border p-1 rounded"></div>
                </div>
                <div class="text-right border-t pt-3">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModalTambah() {
            document.getElementById('formKartu').reset();
            document.getElementById('formAction').value = 'tambah';
            document.getElementById('modalTitle').innerText = 'Tambah Kartu Ujian';
            document.getElementById('modalForm').classList.add('active');
        }
        function bukaModalEdit(id) {
            fetch(`kartu_ujian.php?get_data=${id}`).then(res => res.json()).then(data => {
                if(!data.error) {
                    document.getElementById('formAction').value = 'edit';
                    document.getElementById('formId').value = data.id_kartu;
                    document.getElementById('inp_nis').value = data.nis;
                    document.getElementById('inp_nama').value = data.nama_siswa;
                    document.getElementById('inp_kelas').value = data.kelas;
                    document.getElementById('inp_jurusan').value = data.jurusan;
                    document.getElementById('inp_jenis').value = data.jenis_ujian;
                    document.getElementById('inp_tahun').value = data.tahun_pelajaran;
                    document.getElementById('inp_sid').value = data.sid;
                    document.getElementById('inp_username').value = data.username_ujian;
                    document.getElementById('inp_password').value = data.password_ujian;
                    document.getElementById('modalTitle').innerText = 'Edit Kartu Ujian';
                    document.getElementById('modalForm').classList.add('active');
                }
            });
        }
    </script>
</body>
</html>