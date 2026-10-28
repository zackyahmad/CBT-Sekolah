<?php
// =============================================
// FILE: pages/guru/tambah_ujian.php
// Form tambah ujian baru
// =============================================

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/koneksi.php';

$conn = new mysqli("localhost", "root", "", "db_ujian_sekolah");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$guru_id = $_SESSION['id_user'] ?? $_SESSION['user_id'];
$errors  = [];

// =============================================
// SIMPAN DATA
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama          = trim($_POST['nama_ujian'] ?? '');
    $mapel         = trim($_POST['mata_pelajaran'] ?? '');
    $tgl_mulai     = $_POST['tanggal_mulai'] ?? '';
    $tgl_selesai   = $_POST['tanggal_selesai'] ?? '';
    $waktu         = (int)($_POST['durasi'] ?? 0);
    $jml_soal      = (int)($_POST['jumlah_soal'] ?? 0);
    $status        = $_POST['status'] ?? 'draft';
    $deskripsi     = trim($_POST['deskripsi'] ?? '');

    // VALIDASI
    if (!$nama) {
        $errors[] = 'Nama ujian wajib diisi.';
    }

    if (!$mapel) {
        $errors[] = 'Mata pelajaran wajib diisi.';
    }

    if (!$tgl_mulai) {
        $errors[] = 'Tanggal mulai wajib diisi.';
    }

    if (!$tgl_selesai) {
        $errors[] = 'Tanggal selesai wajib diisi.';
    }

    if ($waktu <= 0) {
        $errors[] = 'Durasi harus lebih dari 0 menit.';
    }

    if ($tgl_selesai < $tgl_mulai) {
        $errors[] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
    }

    // =============================================
    // INSERT DATABASE
    // =============================================
    if (empty($errors)) {

        $stmt = $conn->prepare("
            INSERT INTO ujian (
                guru_id,
                nama_ujian,
                mata_pelajaran,
                deskripsi,
                tanggal_mulai,
                tanggal_selesai,
                waktu,
                jumlah_soal,
                status,
                created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");

        $stmt->bind_param(
            "isssssiis",
            $guru_id,
            $nama,
            $mapel,
            $deskripsi,
            $tgl_mulai,
            $tgl_selesai,
            $waktu,
            $jml_soal,
            $status
        );

        $stmt->execute();

        $_SESSION['flash'] = [
            'type' => 'success',
            'msg'  => 'Ujian berhasil dibuat!'
        ];

        header("Location: setting_ujian.php");
        exit;
    }
}

// =============================================
// HELPER VALUE
// =============================================
function field($name, $default='') {
    return htmlspecialchars($_POST[$name] ?? $default);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Ujian</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body{
            background:#f3f4f6;
        }
    </style>
</head>

<body>

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <?php
    if (file_exists('../../layouts/sidebar_guru.php')) {
        include('../../layouts/sidebar_guru.php');
    }
    ?>

    <!-- CONTENT -->
    <div class="flex-1 p-8">

        <!-- HEADER -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">
                    Buat Ujian Baru
                </h1>

                <p class="text-gray-500 mt-1">
                    Isi detail konfigurasi ujian
                </p>
            </div>

            <a href="setting_ujian.php"
               class="bg-white border border-gray-300 px-4 py-2 rounded-xl shadow-sm hover:bg-gray-50">
                ← Kembali
            </a>
        </div>

        <!-- ERROR -->
        <?php foreach ($errors as $e): ?>
            <div class="mb-4 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                ⚠️ <?= htmlspecialchars($e) ?>
            </div>
        <?php endforeach; ?>

        <!-- CARD -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 max-w-4xl">

            <div class="px-6 py-5 border-b border-gray-100">
                <h2 class="text-xl font-bold text-gray-800">
                    📅 Form Ujian
                </h2>
            </div>

            <div class="p-6">

                <form method="POST">

                    <!-- NAMA -->
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Nama Ujian *
                        </label>

                        <input
                            type="text"
                            name="nama_ujian"
                            value="<?= field('nama_ujian') ?>"
                            placeholder="Contoh: UTS Matematika"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none"
                            required
                        >
                    </div>

                    <!-- MAPEL -->
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Mata Pelajaran *
                        </label>

                        <input
                            type="text"
                            name="mata_pelajaran"
                            value="<?= field('mata_pelajaran') ?>"
                            placeholder="Contoh: Matematika"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none"
                            required
                        >
                    </div>

                    <!-- TANGGAL -->
                    <div class="grid md:grid-cols-2 gap-5 mb-5">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Tanggal Mulai *
                            </label>

                            <input
                                type="date"
                                name="tanggal_mulai"
                                value="<?= field('tanggal_mulai') ?>"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none"
                                required
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Tanggal Selesai *
                            </label>

                            <input
                                type="date"
                                name="tanggal_selesai"
                                value="<?= field('tanggal_selesai') ?>"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none"
                                required
                            >
                        </div>

                    </div>

                    <!-- DURASI -->
                    <div class="grid md:grid-cols-2 gap-5 mb-5">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Durasi (Menit)
                            </label>

                            <input
                                type="number"
                                name="durasi"
                                value="<?= field('durasi', '90') ?>"
                                min="1"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Jumlah Soal
                            </label>

                            <input
                                type="number"
                                name="jumlah_soal"
                                value="<?= field('jumlah_soal', '30') ?>"
                                min="1"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none"
                            >
                        </div>

                    </div>

                    <!-- STATUS -->
                    <div class="mb-5">

                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Status
                        </label>

                        <select
                            name="status"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none"
                        >
                            <option value="draft">Draft</option>
                            <option value="aktif">Aktif</option>
                            <option value="selesai">Selesai</option>
                        </select>

                    </div>

                    <!-- DESKRIPSI -->
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Deskripsi / Petunjuk
                        </label>

                        <textarea
                            name="deskripsi"
                            rows="4"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none"
                            placeholder="Petunjuk pengerjaan ujian..."
                        ><?= field('deskripsi') ?></textarea>
                    </div>

                    <!-- BUTTON -->
                    <div class="flex gap-3">

                        <button
                            type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold"
                        >
                            💾 Simpan Ujian
                        </button>

                        <a
                            href="setting_ujian.php"
                            class="border border-gray-300 px-6 py-3 rounded-xl hover:bg-gray-50"
                        >
                            Batal
                        </a>

                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

</body>
</html>