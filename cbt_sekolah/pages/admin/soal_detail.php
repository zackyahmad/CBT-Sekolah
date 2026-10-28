<?php
session_start();
require_once '../../config/koneksi.php';

$id_ujian = $_GET['id_ujian'];
// Ambil info ujian
$stmt = $pdo->prepare("SELECT u.*, m.nama_mapel FROM tb_ujian u JOIN tb_mapel m ON u.id_mapel = m.id_mapel WHERE id_ujian = ?");
$stmt->execute([$id_ujian]);
$det_ujian = $stmt->fetch();

// Ambil daftar soal
$stmt_soal = $pdo->prepare("SELECT * FROM tb_soal WHERE id_ujian = ? ORDER BY id_soal ASC");
$stmt_soal->execute([$id_ujian]);
$soal = $stmt_soal->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Soal - <?= $det_ujian['nama_mapel']; ?></title>
    <link href="../../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50">
    <?php include '../../layouts/sidebar_admin.php'; ?>
    <div class="md:ml-64 p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-xl font-bold">Soal: <?= $det_ujian['nama_mapel']; ?></h1>
            <button onclick="bukaModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg font-bold">+ Tambah Soal</button>
        </div>

        <?php foreach ($soal as $index => $s): ?>
            <div class="bg-white p-5 rounded-lg shadow mb-4">
                <div class="font-bold mb-2">Soal No. <?= $index + 1; ?> (Bobot: <?= $s['bobot']; ?>)</div>
                <div class="mb-4 text-gray-700"><?= $s['pertanyaan']; ?></div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    <div class="<?= $s['kunci_jawaban'] == 'A' ? 'text-green-600 font-bold' : '' ?>">A. <?= $s['opsi_a']; ?></div>
                    <div class="<?= $s['kunci_jawaban'] == 'B' ? 'text-green-600 font-bold' : '' ?>">B. <?= $s['opsi_b']; ?></div>
                    <div class="<?= $s['kunci_jawaban'] == 'C' ? 'text-green-600 font-bold' : '' ?>">C. <?= $s['opsi_c']; ?></div>
                    <div class="<?= $s['kunci_jawaban'] == 'D' ? 'text-green-600 font-bold' : '' ?>">D. <?= $s['opsi_d']; ?></div>
                    <div class="<?= $s['kunci_jawaban'] == 'E' ? 'text-green-600 font-bold' : '' ?>">E. <?= $s['opsi_e']; ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="modalSoal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto">
        <div class="bg-white w-full max-w-2xl p-6 rounded-lg my-10">
            <h2 class="text-xl font-bold mb-4">Input Soal Baru</h2>
            <form action="../../proses/soal_proses.php" method="POST">
                <input type="hidden" name="id_ujian" value="<?= $id_ujian; ?>">
                <div class="mb-3">
                    <label class="block font-bold">Pertanyaan</label>
                    <textarea name="pertanyaan" class="w-full border p-2 rounded" rows="3" required></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-3">
                    <input type="text" name="opsi_a" placeholder="Opsi A" class="border p-2 rounded" required>
                    <input type="text" name="opsi_b" placeholder="Opsi B" class="border p-2 rounded" required>
                    <input type="text" name="opsi_c" placeholder="Opsi C" class="border p-2 rounded" required>
                    <input type="text" name="opsi_d" placeholder="Opsi D" class="border p-2 rounded" required>
                    <input type="text" name="opsi_e" placeholder="Opsi E" class="border p-2 rounded" required>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block font-bold">Kunci Jawaban</label>
                        <select name="kunci_jawaban" class="w-full border p-2 rounded">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold">Bobot Nilai</label>
                        <input type="number" name="bobot" value="1" class="w-full border p-2 rounded">
                    </div>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="tutupModal()" class="bg-gray-300 px-4 py-2 rounded">Batal</button>
                    <button type="submit" name="add_soal" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan Soal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModal() {
            document.getElementById('modalSoal').classList.replace('hidden', 'flex');
        }

        function tutupModal() {
            document.getElementById('modalSoal').classList.replace('flex', 'hidden');
        }
    </script>
</body>

</html>