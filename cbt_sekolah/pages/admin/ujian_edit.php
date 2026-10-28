<?php
session_start();
// Aktifkan laporan error sementara untuk deteksi jika ada yang kurang
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once '../../config/koneksi.php';

// 1. Ambil ID Ujian dari URL
$id_ujian = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id_ujian) {
    header("Location: rencana_ujian.php");
    exit;
}

// 2. Ambil data ujian lama
$stmt = $pdo->prepare("SELECT * FROM tb_ujian WHERE id_ujian = ?");
$stmt->execute([$id_ujian]);
$u = $stmt->fetch();

if (!$u) {
    die("Data ujian tidak ditemukan di database!");
}

// 3. Ambil daftar kelas yang sudah terpilih untuk ujian ini
$pilih = $pdo->prepare("SELECT id_kelas FROM tb_ujian_kelas WHERE id_ujian = ?");
$pilih->execute([$id_ujian]);
$id_kelas_terpilih = $pilih->fetchAll(PDO::FETCH_COLUMN);

// 4. Data Master untuk pilihan
$mapel_list = $pdo->query("SELECT * FROM tb_mapel ORDER BY nama_mapel ASC")->fetchAll();
$guru_list  = $pdo->query("SELECT * FROM tb_users WHERE role IN ('admin', 'guru') ORDER BY nama_lengkap ASC")->fetchAll();
$kelas_list = $pdo->query("SELECT * FROM tb_kelas ORDER BY nama_kelas ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Jadwal: <?= $u['judul_ujian'] ?> - ADMINSKAPAN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

<body class="bg-[#F8FAFC] font-sans text-gray-700">
    <?php include '../../layouts/sidebar_admin.php'; ?>

    <div class="md:ml-64 min-h-screen">
        <header class="bg-white border-b border-gray-200 py-4 px-8 sticky top-0 z-40 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-[#2C3E50]">Edit Jadwal Ujian</h1>
                <p class="text-[10px] text-blue-500 font-bold uppercase tracking-widest">Token: <?= $u['token'] ?></p>
            </div>
            <a href="rencana_ujian.php" class="text-sm font-bold text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left mr-2"></i> Kembali</a>
        </header>

        <main class="p-8">
            <form action="../../proses/ujian_proses.php" method="POST" class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
                <input type="hidden" name="id_ujian" value="<?= $u['id_ujian'] ?>">

                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                        <div>
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Judul Ujian</label>
                            <input type="text" name="judul_ujian" value="<?= htmlspecialchars($u['judul_ujian']) ?>" class="w-full border-b-2 border-gray-100 py-2 outline-none focus:border-blue-500 transition font-bold text-lg" required>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Mata Pelajaran</label>
                                <select name="id_mapel" class="w-full border-b-2 border-gray-100 py-2 outline-none focus:border-blue-500 transition font-medium" required>
                                    <?php foreach ($mapel_list as $m): ?>
                                        <option value="<?= $m['id_mapel'] ?>" <?= $m['id_mapel'] == $u['id_mapel'] ? 'selected' : '' ?>><?= $m['nama_mapel'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Guru Pengampu</label>
                                <select name="id_guru" class="w-full border-b-2 border-gray-100 py-2 outline-none focus:border-blue-500 transition font-medium" required>
                                    <?php foreach ($guru_list as $g): ?>
                                        <option value="<?= $g['id_user'] ?>" <?= $g['id_user'] == $u['id_guru'] ? 'selected' : '' ?>><?= $g['nama_lengkap'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-6">
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Tanggal</label>
                                <input type="date" name="tanggal_ujian" value="<?= $u['tanggal_ujian'] ?>" class="w-full border-b-2 border-gray-100 py-2 outline-none focus:border-blue-500 transition" required>
                            </div>
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Jam Mulai</label>
                                    <button type="button" onclick="setJamSekarang()" class="text-[9px] font-black text-blue-600 hover:text-blue-800 uppercase tracking-tighter bg-blue-50 px-2 py-0.5 rounded-md transition-all">
                                        <i class="fas fa-bolt mr-1"></i> Terapkan Jam Sekarang
                                    </button>
                                </div>
                                <div class="relative">
                                    <input type="text" name="jam_mulai" id="pilihJam" placeholder="--:--"
                                        class="w-full border-b-2 border-gray-100 py-2 outline-none focus:border-blue-500 transition font-medium bg-transparent cursor-pointer" required>
                                    <i class="fas fa-clock absolute right-2 top-2 text-gray-300 pointer-events-none"></i>
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Durasi (Menit)</label>
                                <input type="number" name="durasi_menit" value="<?= $u['durasi_menit'] ?>" class="w-full border-b-2 border-gray-100 py-2 outline-none focus:border-blue-500 transition" required>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest mb-6"><i class="fas fa-users mr-2 text-blue-500"></i> Distribusi Kelas</h3>

                        <?php
                        $kelas_x   = [];
                        $kelas_xi  = [];
                        $kelas_xii = [];
                        $kelas_lain = [];
                        foreach ($kelas_list as $k) {
                            $n = strtoupper($k['nama_kelas']);
                            if (strpos($n, 'XII') === 0) {
                                $kelas_xii[] = $k;
                            } elseif (strpos($n, 'XI') === 0) {
                                $kelas_xi[] = $k;
                            } elseif (strpos($n, 'X') === 0) {
                                $kelas_x[] = $k;
                            } else {
                                $kelas_lain[] = $k;
                            }
                        }
                        ?>

                        <div class="flex space-x-2 mb-4 border-b border-gray-100 pb-2 overflow-x-auto">
                            <button type="button" onclick="bukaTabKelas('tab-x', this)" class="tab-btn px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 text-white transition shadow-md hover:shadow-lg">KELAS X</button>
                            <button type="button" onclick="bukaTabKelas('tab-xi', this)" class="tab-btn px-4 py-2 rounded-xl text-xs font-bold bg-gray-100 text-gray-500 hover:bg-gray-200 transition">KELAS XI</button>
                            <button type="button" onclick="bukaTabKelas('tab-xii', this)" class="tab-btn px-4 py-2 rounded-xl text-xs font-bold bg-gray-100 text-gray-500 hover:bg-gray-200 transition">KELAS XII</button>
                            <?php if (!empty($kelas_lain)): ?>
                                <button type="button" onclick="bukaTabKelas('tab-lain', this)" class="tab-btn px-4 py-2 rounded-xl text-xs font-bold bg-gray-100 text-gray-500 hover:bg-gray-200 transition">LAINNYA</button>
                            <?php endif; ?>
                        </div>

                        <div class="max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">

                            <div id="tab-x" class="tab-content grid grid-cols-2 lg:grid-cols-3 gap-3">
                                <?php foreach ($kelas_x as $k): ?>
                                    <label class="flex items-center p-3 border rounded-xl hover:bg-blue-50 cursor-pointer transition bg-white group hover:border-blue-300">
                                        <input type="checkbox" name="id_kelas[]" value="<?= $k['id_kelas'] ?>" <?= in_array($k['id_kelas'], $id_kelas_terpilih) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded">
                                        <span class="ml-3 text-xs font-bold text-gray-600 uppercase group-hover:text-blue-700 transition"><?= $k['nama_kelas'] ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <div id="tab-xi" class="tab-content hidden grid grid-cols-2 lg:grid-cols-3 gap-3">
                                <?php foreach ($kelas_xi as $k): ?>
                                    <label class="flex items-center p-3 border rounded-xl hover:bg-blue-50 cursor-pointer transition bg-white group hover:border-blue-300">
                                        <input type="checkbox" name="id_kelas[]" value="<?= $k['id_kelas'] ?>" <?= in_array($k['id_kelas'], $id_kelas_terpilih) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded">
                                        <span class="ml-3 text-xs font-bold text-gray-600 uppercase group-hover:text-blue-700 transition"><?= $k['nama_kelas'] ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <div id="tab-xii" class="tab-content hidden grid grid-cols-2 lg:grid-cols-3 gap-3">
                                <?php foreach ($kelas_xii as $k): ?>
                                    <label class="flex items-center p-3 border rounded-xl hover:bg-blue-50 cursor-pointer transition bg-white group hover:border-blue-300">
                                        <input type="checkbox" name="id_kelas[]" value="<?= $k['id_kelas'] ?>" <?= in_array($k['id_kelas'], $id_kelas_terpilih) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded">
                                        <span class="ml-3 text-xs font-bold text-gray-600 uppercase group-hover:text-blue-700 transition"><?= $k['nama_kelas'] ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <div id="tab-lain" class="tab-content hidden grid grid-cols-2 lg:grid-cols-3 gap-3">
                                <?php foreach ($kelas_lain as $k): ?>
                                    <label class="flex items-center p-3 border rounded-xl hover:bg-blue-50 cursor-pointer transition bg-white group hover:border-blue-300">
                                        <input type="checkbox" name="id_kelas[]" value="<?= $k['id_kelas'] ?>" <?= in_array($k['id_kelas'], $id_kelas_terpilih) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded">
                                        <span class="ml-3 text-xs font-bold text-gray-600 uppercase group-hover:text-blue-700 transition"><?= $k['nama_kelas'] ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest mb-2"><i class="fas fa-shield-alt mr-2 text-red-500"></i> Proteksi</h3>

                        <div class="space-y-4">
                            <?php
                            $proteksi = [
                                ['name' => 'acak_soal', 'label' => 'Acak Soal', 'icon' => 'fa-random', 'color' => 'blue'],
                                ['name' => 'tampil_nilai', 'label' => 'Tampilkan Nilai', 'icon' => 'fa-eye', 'color' => 'green'],
                                ['name' => 'anti_tab', 'label' => 'Anti-Tab', 'icon' => 'fa-shield-alt', 'color' => 'red']
                            ];
                            foreach ($proteksi as $p): ?>
                                <label class="flex items-center justify-between p-3 bg-gray-50 rounded-2xl cursor-pointer">
                                    <div class="flex items-center">
                                        <i class="fas <?= $p['icon'] ?> text-<?= $p['color'] ?>-500 w-5"></i>
                                        <span class="ml-3 text-xs font-bold"><?= $p['label'] ?></span>
                                    </div>
                                    <div class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="<?= $p['name'] ?>" value="1" <?= $u[$p['name']] == '1' ? 'checked' : '' ?> class="sr-only peer">
                                        <div class="w-10 h-5 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-<?= $p['color'] ?>-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" name="update_ujian" class="w-full bg-gray-900 text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-600 shadow-xl transition-all">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        flatpickr("#pilihJam", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true
        });

        // Inisialisasi Flatpickr untuk Edit
        const fp = flatpickr("#pilihJam", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true
        });

        // Fungsi Terapkan Jam Sekarang
        function setJamSekarang() {
            const sekarang = new Date();
            const jam = String(sekarang.getHours()).padStart(2, '0');
            const menit = String(sekarang.getMinutes()).padStart(2, '0');
            const waktuAktual = jam + ":" + menit;

            const inputJam = document.getElementById('pilihJam');
            inputJam.value = waktuAktual;

            // Update tampilan Flatpickr jika ada
            if (inputJam._flatpickr) {
                inputJam._flatpickr.setDate(waktuAktual);
            }

            // Notifikasi Toast manis
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Jam ' + waktuAktual + ' diterapkan',
                showConfirmButton: false,
                timer: 1500
            });
        }
        // ... Script Jam Mulai Abang yang lama ...

        // Fungsi Ganti Tab Kelas (Copy dari Rencana Ujian)
        function bukaTabKelas(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.getElementById(tabId).classList.remove('hidden');

            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('bg-blue-600', 'text-white', 'shadow-md');
                b.classList.add('bg-gray-100', 'text-gray-500');
            });

            btn.classList.remove('bg-gray-100', 'text-gray-500');
            btn.classList.add('bg-blue-600', 'text-white', 'shadow-md');
        }
    </script>
</body>

</html>