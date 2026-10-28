<?php
// =============================================
// FILE: pages/guru/edit_ujian.php
// Form edit ujian yang sudah ada
// =============================================
require_once '../includes/config.php';
cekLoginGuru();

$guru_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$q = $conn->query("SELECT * FROM ujian WHERE id=$id AND guru_id=$guru_id");
if ($q->num_rows === 0) { header("Location: setting_ujian.php"); exit(); }
$ujian = $q->fetch_assoc();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama        = trim($_POST['nama_ujian'] ?? '');
    $mapel       = trim($_POST['mata_pelajaran'] ?? '');
    $kelas       = trim($_POST['kelas'] ?? '');
    $tgl_mulai   = $_POST['tanggal_mulai'] ?? '';
    $tgl_selesai = $_POST['tanggal_selesai'] ?? '';
    $durasi      = (int)($_POST['durasi'] ?? 0);
    $jml_soal    = (int)($_POST['jumlah_soal'] ?? 0);
    $token       = trim($_POST['token_ujian'] ?? '');
    $acak_soal   = isset($_POST['acak_soal']) ? 1 : 0;
    $acak_jwb    = isset($_POST['acak_jawaban']) ? 1 : 0;
    $status      = $_POST['status'] ?? 'draft';
    $deskripsi   = trim($_POST['deskripsi'] ?? '');

    if (!$nama)        $errors[] = 'Nama ujian wajib diisi.';
    if (!$tgl_mulai)   $errors[] = 'Tanggal mulai wajib diisi.';
    if (!$tgl_selesai) $errors[] = 'Tanggal selesai wajib diisi.';
    if ($durasi <= 0)  $errors[] = 'Durasi harus lebih dari 0.';

    if (empty($errors)) {
        $n = $conn->real_escape_string($nama);
        $m = $conn->real_escape_string($mapel);
        $k = $conn->real_escape_string($kelas);
        $tk = $conn->real_escape_string($token);
        $d = $conn->real_escape_string($deskripsi);
        $conn->query("UPDATE ujian SET nama_ujian='$n',mata_pelajaran='$m',kelas='$k',tanggal_mulai='$tgl_mulai',tanggal_selesai='$tgl_selesai',durasi=$durasi,jumlah_soal=$jml_soal,token_ujian='$tk',acak_soal=$acak_soal,acak_jawaban=$acak_jwb,status='$status',deskripsi='$d',updated_at=NOW() WHERE id=$id AND guru_id=$guru_id");
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Ujian berhasil diperbarui.'];
        header("Location: setting_ujian.php"); exit();
    }

    // Merge POST ke $ujian supaya form tetap terisi
    $ujian = array_merge($ujian, $_POST);
}

function val($d, $k, $fmt='') {
    $v = htmlspecialchars($d[$k] ?? '');
    if ($fmt === 'datetime' && $v) $v = date('Y-m-d\TH:i', strtotime($v));
    return $v;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Ujian – CBT Sekolah</title>
    <link rel="stylesheet" href="../assets/css/guru.css">
</head>
<body>
<?php include '../includes/sidebar_guru.php'; ?>
<div class="main-wrapper">
    <header class="topbar">
        <div class="topbar-left"><h1>Edit Ujian</h1><p><?= htmlspecialchars($ujian['nama_ujian']) ?></p></div>
        <div class="topbar-right"><a href="setting_ujian.php" class="btn btn-outline btn-sm">← Kembali</a></div>
    </header>
    <main class="page-content">
        <?php foreach ($errors as $e): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        <div class="card" style="max-width:800px">
            <div class="card-header"><span class="card-title">✏️ Edit Form Ujian</span></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Nama Ujian <span style="color:red">*</span></label>
                        <input type="text" name="nama_ujian" class="form-control" value="<?= val($ujian,'nama_ujian') ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Mata Pelajaran</label>
                            <input type="text" name="mata_pelajaran" class="form-control" value="<?= val($ujian,'mata_pelajaran') ?>">
                        </div>
                        <div class="form-group">
                            <label>Kelas / Jurusan</label>
                            <input type="text" name="kelas" class="form-control" value="<?= val($ujian,'kelas') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tanggal Mulai</label>
                            <input type="datetime-local" name="tanggal_mulai" class="form-control" value="<?= val($ujian,'tanggal_mulai','datetime') ?>">
                        </div>
                        <div class="form-group">
                            <label>Tanggal Selesai</label>
                            <input type="datetime-local" name="tanggal_selesai" class="form-control" value="<?= val($ujian,'tanggal_selesai','datetime') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Durasi (menit)</label>
                            <input type="number" name="durasi" class="form-control" value="<?= val($ujian,'durasi') ?>" min="1">
                        </div>
                        <div class="form-group">
                            <label>Jumlah Soal</label>
                            <input type="number" name="jumlah_soal" class="form-control" value="<?= val($ujian,'jumlah_soal') ?>" min="1">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Token Ujian</label>
                            <input type="text" name="token_ujian" class="form-control" value="<?= val($ujian,'token_ujian') ?>">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <?php foreach(['draft','aktif','selesai'] as $st): ?>
                                <option value="<?= $st ?>" <?= ($ujian['status']??'')===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3"><?= val($ujian,'deskripsi') ?></textarea>
                    </div>
                    <div style="display:flex;gap:24px;margin-bottom:18px">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500">
                            <input type="checkbox" name="acak_soal" <?= ($ujian['acak_soal']??0)?'checked':'' ?> style="width:16px;height:16px"> Acak Soal
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500">
                            <input type="checkbox" name="acak_jawaban" <?= ($ujian['acak_jawaban']??0)?'checked':'' ?> style="width:16px;height:16px"> Acak Pilihan Jawaban
                        </label>
                    </div>
                    <div style="display:flex;gap:10px">
                        <button type="submit" class="btn btn-primary">💾 Perbarui Ujian</button>
                        <a href="setting_ujian.php" class="btn btn-outline">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
