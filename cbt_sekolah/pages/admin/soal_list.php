<?php
session_start();
require_once '../../config/koneksi.php';

$id_mapel = $_GET['id_mapel'];

// 1. Ambil Nama Mapel untuk Judul
$m = $pdo->prepare("SELECT nama_mapel FROM tb_mapel WHERE id_mapel = ?");
$m->execute([$id_mapel]);
$mapel = $m->fetch();

// 2. Ambil Semua Soal untuk Mapel ini
$query = "SELECT * FROM tb_soal WHERE id_mapel = ? ORDER BY id_soal DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$id_mapel]);
$soal = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Soal <?= $mapel['nama_mapel'] ?> - SMKN 8 Bandar Lampung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(145deg, #f1f5f9 0%, #e6edf5 100%);
        }
        .card-hover {
            transition: all 0.2s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.01);
        }
    </style>
</head>

<body class="font-sans text-gray-700 p-6">

    <!-- Header dengan warna tema dashboard -->
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6 mb-6 card-hover">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <nav class="text-xs text-gray-400 mb-2">
                        <a href="data_soal.php" class="hover:text-[#2C3E50] transition">Bank Soal</a>
                        <i class="fas fa-chevron-right mx-2 text-[10px]"></i>
                        <span class="text-[#2C3E50] font-semibold"><?= htmlspecialchars($mapel['nama_mapel']) ?></span>
                    </nav>
                    <h1 class="text-3xl font-bold text-[#2C3E50] flex items-center gap-3">
                        <i class="fas fa-question-circle text-[#3498DB]"></i>
                        Koleksi Soal: <?= htmlspecialchars($mapel['nama_mapel']) ?>
                    </h1>
                </div>
                <a href="data_soal.php" class="text-gray-400 hover:text-[#2C3E50] font-medium text-sm flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-lg border border-gray-100">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Statistik cepat ala dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200/80 p-5 card-hover">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                        <i class="fas fa-database"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Total Soal</p>
                        <p class="text-xl font-bold text-[#2C3E50]"><?= count($soal) ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200/80 p-5 card-hover">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center text-green-600">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">PG</p>
                        <p class="text-xl font-bold text-[#2C3E50]"><?= count(array_filter($soal, fn($s) => $s['jenis_soal'] == '1')) ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200/80 p-5 card-hover">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Essay</p>
                        <p class="text-xl font-bold text-[#2C3E50]"><?= count(array_filter($soal, fn($s) => $s['jenis_soal'] == '0')) ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200/80 p-5 card-hover">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center text-purple-600">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Materi</p>
                        <p class="text-xl font-bold text-[#2C3E50]"><?= count(array_unique(array_column($soal, 'materi'))) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kartu utama konten soal -->
        <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden shadow-sm">
            <!-- Header tools -->
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex flex-col lg:flex-row lg:items-center gap-4 justify-between">
                    <div class="relative flex-1 max-w-md">
                        <i class="fas fa-search absolute left-3 top-3.5 text-gray-300 text-sm"></i>
                        <input type="text" id="cariSoal" onkeyup="filterSoal()" placeholder="Cari pertanyaan atau materi..."
                            class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none bg-white/80">
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button onclick="hapusSemuaSoal(<?= $id_mapel ?>)" class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white font-medium py-2.5 px-5 rounded-xl text-sm transition-all duration-200 flex items-center gap-2 border border-red-100">
                            <i class="fas fa-trash-alt"></i> Hapus Semua
                        </button>
                        <button onclick="bukaModalImport()" class="bg-green-50 text-green-600 hover:bg-green-600 hover:text-white font-medium py-2.5 px-5 rounded-xl text-sm transition-all duration-200 flex items-center gap-2 border border-green-100">
                            <i class="fas fa-file-excel"></i> Import Excel
                        </button>
                        <a href="soal_tambah.php?id_mapel=<?= $id_mapel ?>" class="bg-[#3498DB] hover:bg-[#2980B9] text-white font-medium py-2.5 px-6 rounded-xl text-sm transition-all duration-200 flex items-center gap-2 shadow-md shadow-blue-200">
                            <i class="fas fa-plus-circle"></i> Tambah Manual
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tabel Soal -->
            <div class="overflow-x-auto">
                <table class="w-full text-left" id="tabelSoal">
                    <thead>
                        <tr class="bg-[#2C3E50] text-white text-sm">
                            <th class="px-6 py-4 font-semibold w-16">No</th>
                            <th class="px-6 py-4 font-semibold">Pertanyaan & Materi</th>
                            <th class="px-6 py-4 font-semibold w-32 text-center">Kunci</th>
                            <th class="px-6 py-4 font-semibold w-32 text-center">Tipe</th>
                            <th class="px-6 py-4 font-semibold w-24 text-center">Bobot</th>
                            <th class="px-6 py-4 font-semibold w-32 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (count($soal) > 0): ?>
                            <?php $no = 1;
                            foreach ($soal as $s): ?>
                                <tr class="hover:bg-gray-50/80 transition soal-row">
                                    <td class="px-6 py-5 text-sm text-gray-400 font-mono"><?= $no++ ?></td>
                                    <td class="px-6 py-5">
                                        <div class="space-y-2">
                                            <?php if (!empty($s['materi'])): ?>
                                                <span class="inline-block text-[10px] font-bold bg-blue-50 text-blue-600 px-2 py-1 rounded-md border border-blue-100 searchable">
                                                    <i class="fas fa-tag mr-1"></i> <?= htmlspecialchars($s['materi']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <div class="text-sm text-gray-700 leading-relaxed searchable font-medium">
                                                <?= strip_tags($s['pertanyaan']) ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <?php if ($s['jenis_soal'] == '1'): ?>
                                            <span class="inline-flex items-center gap-1 text-xs font-bold bg-green-50 text-green-700 px-3 py-1.5 rounded-full border border-green-200">
                                                <i class="fas fa-check-circle text-[10px]"></i>
                                                <?= strtoupper($s['kunci_jawaban']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <span class="text-xs font-semibold px-3 py-1.5 rounded-full inline-flex items-center gap-1
                                            <?= $s['jenis_soal'] == '1' ? 'bg-purple-50 text-purple-600 border border-purple-200' : 'bg-orange-50 text-orange-600 border border-orange-200' ?>">
                                            <i class="fas <?= $s['jenis_soal'] == '1' ? 'fa-list' : 'fa-pen' ?> text-[10px]"></i>
                                            <?= $s['jenis_soal'] == '1' ? 'PG' : 'Essay' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <span class="text-sm font-bold text-gray-700 bg-gray-100 px-3 py-1 rounded-full">
                                            <?= $s['bobot'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center justify-center gap-3">
                                            <a href="soal_edit.php?id=<?= $s['id_soal'] ?>" 
                                               class="text-gray-400 hover:text-[#3498DB] transition p-2 hover:bg-blue-50 rounded-lg"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="hapusSoal(<?= $s['id_soal'] ?>)" 
                                                    class="text-gray-400 hover:text-red-500 transition p-2 hover:bg-red-50 rounded-lg"
                                                    title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-20 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i class="fas fa-folder-open text-5xl mb-4 opacity-30"></i>
                                        <p class="text-sm font-medium">Belum ada soal untuk mata pelajaran ini.</p>
                                        <p class="text-xs mt-2">Mulai tambah soal atau import dari Excel</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Import (style disesuaikan) -->
    <div id="modalImport" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-8 transform transition-all">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center text-green-600 text-xl">
                    <i class="fas fa-file-excel"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-[#2C3E50]">Import Soal Excel</h3>
                    <p class="text-xs text-gray-400">Pastikan format sesuai template</p>
                </div>
            </div>

            <div class="mb-6 p-4 bg-blue-50 rounded-xl border border-blue-100">
                <a href="../../proses/download_template.php" class="text-blue-600 hover:text-blue-700 font-medium text-sm flex items-center gap-2">
                    <i class="fas fa-download"></i> Download Template Excel
                </a>
            </div>

            <form action="../../proses/import_soal_bank.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_mapel" value="<?= $id_mapel ?>">
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-600 mb-2">Pilih File Excel</label>
                    <input type="file" name="file_excel" accept=".xlsx,.xls" 
                           class="block w-full text-sm border border-gray-200 rounded-xl p-3 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100"
                           required>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="tutupModalImport()" 
                            class="px-6 py-2.5 text-gray-400 font-medium hover:text-gray-600 transition">
                        Batal
                    </button>
                    <button type="submit" name="import_bank" 
                            class="bg-green-600 text-white px-6 py-2.5 rounded-xl font-medium hover:bg-green-700 transition shadow-lg shadow-green-200 flex items-center gap-2">
                        <i class="fas fa-upload"></i> Unggah
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModalImport() {
            document.getElementById('modalImport').classList.remove('hidden');
            document.getElementById('modalImport').classList.add('flex');
        }

        function tutupModalImport() {
            document.getElementById('modalImport').classList.remove('flex');
            document.getElementById('modalImport').classList.add('hidden');
        }

        function filterSoal() {
            let input = document.getElementById("cariSoal").value.toLowerCase();
            let rows = document.querySelectorAll(".soal-row");
            rows.forEach(row => {
                let searchableTexts = row.querySelectorAll(".searchable");
                let match = false;
                searchableTexts.forEach(el => {
                    if (el.innerText.toLowerCase().includes(input)) match = true;
                });
                row.style.display = match ? "" : "none";
            });
        }

        function hapusSoal(id) {
            Swal.fire({
                title: 'Hapus Soal?',
                text: "Soal yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                background: '#fff',
                backdrop: `rgba(44, 62, 80, 0.4)`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../../proses/soal_proses.php?hapus_soal=" + id;
                }
            })
        }

        function hapusSemuaSoal(idMapel) {
            Swal.fire({
                title: 'Hapus Seluruh Soal?',
                text: "Semua soal di mata pelajaran ini akan musnah. Tindakan ini tidak bisa dibatalkan!",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Habisi Semua!',
                cancelButtonText: 'Batal',
                background: '#fff',
                backdrop: `rgba(44, 62, 80, 0.4)`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../../proses/soal_proses.php?hapus_semua_soal=" + idMapel;
                }
            })
        }

        // Tutup modal jika klik di luar
        window.onclick = function(event) {
            let modal = document.getElementById('modalImport');
            if (event.target == modal) {
                tutupModalImport();
            }
        }
    </script>
</body>

</html>