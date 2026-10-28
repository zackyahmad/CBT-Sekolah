<?php
// =============================================
// FILE: pages/guru/detail_soal.php
// Detail soal lengkap beserta semua pilihan jawaban
// =============================================
require_once '../includes/config.php';
cekLoginGuru();

$guru_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$q = $conn->query("SELECT * FROM soal WHERE id = $id AND guru_id = $guru_id");
if ($q->num_rows === 0) { header("Location: bank_soal.php"); exit(); }
$soal = $q->fetch_assoc();

$pilihans = $conn->query("SELECT * FROM pilihan_jawaban WHERE soal_id = $id ORDER BY urutan");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Detail Soal – CBT Sekolah</title>
    <link rel="stylesheet" href="../assets/css/guru.css">
</head>
<body>
<?php include '../includes/sidebar_guru.php'; ?>
<div class="main-wrapper">
    <header class="topbar">
        <div class="topbar-left"><h1>Detail Soal</h1><p>Informasi lengkap soal</p></div>
        <div class="topbar-right">
            <a href="tambah_soal.php?edit=<?= $id ?>" class="btn btn-primary btn-sm">✏️ Edit</a>
            <a href="bank_soal.php" class="btn btn-outline btn-sm">← Kembali</a>
        </div>
    </header>
    <main class="page-content">
        <div class="card" style="max-width:720px">
            <div class="card-header">
                <span class="card-title">📄 Soal #<?= $id ?></span>
                <div style="display:flex;gap:8px">
                    <?php $t=$soal['tingkat_kesulitan']; $tc=$t==='mudah'?'success':($t==='sulit'?'danger':'warning'); ?>
                    <span class="badge badge-<?= $tc ?>"><?= ucfirst($t??'-') ?></span>
                    <span class="badge badge-info"><?= ucfirst($soal['tipe_soal']??'PG') ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Mata Pelajaran</label>
                    <div style="font-size:14px;font-weight:600;color:var(--text-dark)"><?= htmlspecialchars($soal['mata_pelajaran']??'-') ?></div>
                </div>
                <div class="form-group">
                    <label>Pertanyaan</label>
                    <div style="font-size:14px;color:var(--text-dark);line-height:1.7;padding:14px;background:var(--bg);border-radius:8px"><?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?></div>
                </div>
                <div class="form-group">
                    <label>Pilihan Jawaban</label>
                    <?php $pj_arr = []; while ($p = $pilihans->fetch_assoc()) $pj_arr[] = $p; ?>
                    <?php foreach ($pj_arr as $p): ?>
                    <div style="display:flex;gap:12px;align-items:flex-start;padding:10px 14px;margin-bottom:8px;border-radius:8px;
                         background:<?= $p['kode']===$soal['kunci_jawaban']?'var(--success-soft)':'var(--bg)' ?>;
                         border:1.5px solid <?= $p['kode']===$soal['kunci_jawaban']?'var(--success)':'var(--border)' ?>">
                        <span style="width:28px;height:28px;border-radius:50%;background:<?= $p['kode']===$soal['kunci_jawaban']?'var(--success)':'var(--border)' ?>;color:<?= $p['kode']===$soal['kunci_jawaban']?'#fff':'var(--text-mid)' ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0"><?= $p['kode'] ?></span>
                        <span style="font-size:13.5px;padding-top:3px"><?= htmlspecialchars($p['teks']) ?></span>
                        <?php if ($p['kode']===$soal['kunci_jawaban']): ?>
                        <span style="margin-left:auto;font-size:11px;font-weight:700;color:var(--success)">✓ KUNCI</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
