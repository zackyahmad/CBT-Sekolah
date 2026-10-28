<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../../index.php"); exit;
}
require_once '../../config/koneksi.php';

$id_ujian = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_guru  = $_SESSION['id_user'] ?? 0;

if (!$id_ujian) { header("Location: rencana_ujian.php"); exit; }

// ==========================================
// FUNGSI PINTAR DETEKSI JENIS SOAL
// ==========================================
function isPG($jenis) {
    if ($jenis === null || $jenis === '') return true; // Default anggap PG
    $j = strtolower(trim((string)$jenis));
    return in_array($j, ['1', 'pg', 'pilihan ganda', 'pilihan_ganda']);
}

// ==========================================
// 1. PROSES UBAH STATUS UJIAN (AKTIF/TIDAK)
// ==========================================
if (isset($_GET['ubah_status'])) {
    $status_baru = $_GET['ubah_status'] === 'aktif' ? 'aktif' : 'tidak_aktif';
    $pdo->prepare("UPDATE tb_ujian SET status_ujian = ? WHERE id_ujian = ? AND id_guru = ?")->execute([$status_baru, $id_ujian, $id_guru]);
    
    $pesan = $status_baru === 'aktif' ? 'Ujian Berhasil DIAKTIFKAN. Siswa sekarang dapat melihat ujian ini!' : 'Ujian dinonaktifkan sementara.';
    $_SESSION['flash'] = ['type' => 'success', 'msg' => $pesan];
    header("Location: kelola_soal_ujian.php?id=$id_ujian"); exit;
}

// ==========================================
// 2. Ambil data ujian & Mapel
// ==========================================
$stmt = $pdo->prepare("
    SELECT u.*, m.nama_mapel
    FROM tb_ujian u
    LEFT JOIN tb_mapel m ON m.id_mapel = u.id_mapel
    WHERE u.id_ujian = ? AND u.id_guru = ?
");
$stmt->execute([$id_ujian, $id_guru]);
$ujian = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ujian) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Ujian tidak valid.'];
    header("Location: rencana_ujian.php"); exit;
}

$nama_ujian = $ujian['judul_ujian'] ?? 'Ujian';
$id_mapel = $ujian['id_mapel'] ?? 0;
$status_saat_ini = strtolower($ujian['status_ujian'] ?? 'tidak_aktif');

// ==========================================
// 3. PROSES AKSI KELOLA SOAL
// ==========================================

// Tambah dari Bank
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_dari_bank'])) {
    $soal_ids = $_POST['soal_ids'] ?? [];
    $ditambah = 0;
    foreach ($soal_ids as $id_soal) {
        $cek = $pdo->prepare("SELECT id_mapping FROM tb_ujian_soal WHERE id_ujian=? AND id_soal=?");
        $cek->execute([$id_ujian, (int)$id_soal]);
        if ($cek->rowCount() === 0) {
            $pdo->prepare("INSERT INTO tb_ujian_soal (id_ujian, id_soal) VALUES (?,?)")->execute([$id_ujian, (int)$id_soal]);
            $ditambah++;
        }
    }
    if ($ditambah > 0) $_SESSION['flash'] = ['type' => 'success', 'msg' => "$ditambah soal berhasil dimasukkan."];
    header("Location: kelola_soal_ujian.php?id=$id_ujian"); exit;
}

// Keluarkan 1 Soal
if (isset($_GET['keluarkan'])) {
    $pdo->prepare("DELETE FROM tb_ujian_soal WHERE id_ujian=? AND id_soal=?")->execute([$id_ujian, (int)$_GET['keluarkan']]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Soal dikeluarkan dari ujian.'];
    header("Location: kelola_soal_ujian.php?id=$id_ujian"); exit;
}

// Kosongkan Ujian
if (isset($_GET['kosongkan'])) {
    $pdo->prepare("DELETE FROM tb_ujian_soal WHERE id_ujian=?")->execute([$id_ujian]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Semua soal berhasil dibersihkan.'];
    header("Location: kelola_soal_ujian.php?id=$id_ujian"); exit;
}

// ==========================================
// 4. AMBIL DATA UNTUK DITAMPILKAN
// ==========================================
$stmt_soal = $pdo->prepare("SELECT s.* FROM tb_ujian_soal us JOIN tb_soal s ON us.id_soal = s.id_soal WHERE us.id_ujian = ? ORDER BY us.ditambahkan_pada DESC");
$stmt_soal->execute([$id_ujian]);
$soal_ujian = $stmt_soal->fetchAll(PDO::FETCH_ASSOC);

$stmt_bank = $pdo->prepare("SELECT * FROM tb_soal WHERE id_mapel = ? AND id_soal NOT IN (SELECT id_soal FROM tb_ujian_soal WHERE id_ujian = ?) ORDER BY id_soal DESC");
$stmt_bank->execute([$id_mapel, $id_ujian]);
$bank_soal = $stmt_bank->fetchAll(PDO::FETCH_ASSOC);

$jml_soal_ujian = count($soal_ujian);
$jml_pg = count(array_filter($soal_ujian, fn($s) => isPG($s['jenis_soal'] ?? null)));
$jml_essay = $jml_soal_ujian - $jml_pg;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Soal Ujian: <?= htmlspecialchars($nama_ujian) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 text-slate-700 p-6 min-h-screen">

    <?php if (isset($_SESSION['flash'])): ?>
        <script>
            Swal.fire({ icon: '<?= $_SESSION['flash']['type'] ?>', title: 'Informasi', text: '<?= $_SESSION['flash']['msg'] ?>', timer: 2500, showConfirmButton: false });
        </script>
    <?php unset($_SESSION['flash']); endif; ?>

    <div class="max-w-7xl mx-auto">
        
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <nav class="text-xs text-slate-400 mb-2 font-semibold uppercase tracking-wider">
                        <a href="rencana_ujian.php" class="hover:text-blue-600 transition">Rencana Ujian</a> <i class="fas fa-chevron-right mx-2 text-[10px]"></i> <span class="text-blue-600">Kelola Soal</span>
                    </nav>
                    <h1 class="text-2xl font-black text-slate-800 mb-1"><?= htmlspecialchars($nama_ujian) ?></h1>
                    <p class="text-sm text-slate-500 font-medium"><i class="fas fa-book text-blue-500 mr-1"></i> <?= htmlspecialchars($ujian['nama_mapel']) ?></p>
                </div>
                
                <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <div class="text-right mr-2">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Status Ujian</p>
                        <?php if ($status_saat_ini === 'aktif'): ?>
                            <p class="text-emerald-600 font-black text-lg"><i class="fas fa-play-circle animate-pulse mr-1"></i> SEDANG AKTIF</p>
                        <?php else: ?>
                            <p class="text-amber-500 font-black text-lg"><i class="fas fa-pause-circle mr-1"></i> TIDAK AKTIF</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($status_saat_ini === 'aktif'): ?>
                        <a href="?id=<?= $id_ujian ?>&ubah_status=tidak_aktif" onclick="return confirm('Nonaktifkan ujian ini? Siswa tidak akan bisa mengerjakannya lagi.')" 
                           class="bg-amber-100 text-amber-700 hover:bg-amber-200 px-6 py-3 rounded-xl font-bold transition shadow-sm border border-amber-200">
                            Nonaktifkan
                        </a>
                    <?php else: ?>
                        <?php if ($jml_soal_ujian > 0): ?>
                            <a href="?id=<?= $id_ujian ?>&ubah_status=aktif" onclick="return confirm('Aktifkan ujian? Ujian akan langsung muncul di dashboard siswa!')" 
                               class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-xl font-bold transition shadow-lg shadow-emerald-200 flex items-center gap-2">
                                <i class="fas fa-rocket"></i> Aktifkan Ujian!
                            </a>
                        <?php else: ?>
                            <button onclick="Swal.fire('Gagal','Anda harus memasukkan minimal 1 soal sebelum mengaktifkan ujian!','error')" 
                                    class="bg-slate-200 text-slate-400 px-6 py-3 rounded-xl font-bold cursor-not-allowed">
                                Isi Soal Dulu
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 shadow-sm">
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 text-xl"><i class="fas fa-database"></i></div>
                <div><p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Soal</p><p class="text-2xl font-black text-slate-800"><?= $jml_soal_ujian ?></p></div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 shadow-sm">
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center text-green-600 text-xl"><i class="fas fa-list-ul"></i></div>
                <div><p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pilihan Ganda</p><p class="text-2xl font-black text-slate-800"><?= $jml_pg ?></p></div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 shadow-sm">
                <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center text-orange-600 text-xl"><i class="fas fa-pen"></i></div>
                <div><p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Essay</p><p class="text-2xl font-black text-slate-800"><?= $jml_essay ?></p></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-100 bg-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h3 class="font-bold text-slate-800 text-lg"><i class="fas fa-tasks text-blue-600 mr-2"></i>Daftar Soal Terpilih</h3>
                
                <div class="flex flex-wrap gap-2">
                    <button onclick="kosongkanUjian(<?= $id_ujian ?>)" class="bg-white text-red-500 hover:bg-red-50 font-bold py-2 px-4 rounded-xl text-xs border border-red-100 transition shadow-sm">
                        <i class="fas fa-trash-alt mr-1"></i> Kosongkan
                    </button>
                    <button onclick="bukaModalBank()" class="bg-purple-50 text-purple-600 hover:bg-purple-600 hover:text-white font-bold py-2 px-4 rounded-xl text-xs border border-purple-100 transition shadow-sm">
                        <i class="fas fa-server mr-1"></i> Ambil dari Bank
                    </button>
                    <button onclick="bukaModalImport()" class="bg-green-50 text-green-600 hover:bg-green-600 hover:text-white font-bold py-2 px-4 rounded-xl text-xs border border-green-100 transition shadow-sm">
                        <i class="fas fa-file-excel mr-1"></i> Import Excel
                    </button>
                    <a href="tambah_soal_guru.php?id_ujian=<?= $id_ujian ?>&id_mapel=<?= $id_mapel ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl text-xs transition shadow-md flex items-center">
                        <i class="fas fa-plus-circle mr-1"></i> Buat Manual
                    </a>
                    <a href="preview_ujian.php?id_ujian=<?= $id_ujian ?>" 
   class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-4 py-2.5 rounded-xl text-sm transition shadow-md shadow-amber-100 flex items-center gap-2">
    <i class="fas fa-eye"></i> Pratinjau POV Siswa
</a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-white text-slate-400 uppercase tracking-wide border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4 font-bold w-16">No</th>
                            <th class="px-6 py-4 font-bold">Pertanyaan</th>
                            <th class="px-6 py-4 font-bold text-center">Tipe</th>
                            <th class="px-6 py-4 font-bold text-center">Kunci</th>
                            <th class="px-6 py-4 font-bold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if ($jml_soal_ujian > 0): ?>
                            <?php $no = 1; foreach ($soal_ujian as $s): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 text-slate-400 font-mono"><?= $no++ ?></td>
                                    <td class="px-6 py-4">
                                        <div class="text-slate-700 font-medium leading-relaxed"><?= strip_tags($s['pertanyaan']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if(isPG($s['jenis_soal'] ?? null)): ?>
                                            <span class="bg-blue-50 text-blue-600 border border-blue-200 px-2 py-1 rounded text-[10px] font-bold">PG</span>
                                        <?php else: ?>
                                            <span class="bg-orange-50 text-orange-600 border border-orange-200 px-2 py-1 rounded text-[10px] font-bold">ESSAY</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center font-black text-slate-800">
                                        <?= isPG($s['jenis_soal'] ?? null) ? strtoupper($s['kunci_jawaban'] ?? '-') : '-' ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="edit_gambar_soal.php?id=<?= $s['id_soal'] ?>&ujian=<?= $id_ujian ?>" 
                                               class="text-blue-600 hover:text-white bg-blue-50 hover:bg-blue-600 px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm">
                                                <i class="fas fa-image"></i> Gambar
                                            </a>
                                            
                                            <button onclick="keluarkanSoal(<?= $id_ujian ?>, <?= $s['id_soal'] ?>)" 
                                                    class="text-red-600 hover:text-white bg-red-50 hover:bg-red-600 px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-16 text-center">
                                    <i class="fas fa-folder-open text-4xl mb-3 text-slate-300 block"></i>
                                    <p class="text-slate-500 font-bold">Ujian ini belum memiliki soal.</p>
                                    <p class="text-xs text-slate-400 mt-1">Gunakan tombol di atas untuk menambahkan soal.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modalBank" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl flex flex-col max-h-[85vh] overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="text-lg font-bold text-slate-800"><i class="fas fa-server text-purple-600 mr-2"></i> Pilih Soal dari Bank</h3>
                <button onclick="tutupModalBank()" class="text-slate-400 hover:text-red-500"><i class="fas fa-times text-xl"></i></button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
                <form action="" method="POST" id="formBank">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-100 text-slate-500 font-bold">
                            <tr>
                                <th class="p-4 text-center w-16"><input type="checkbox" id="checkAllBank" class="w-4 h-4 rounded cursor-pointer"></th>
                                <th class="p-4">Potongan Pertanyaan</th>
                                <th class="p-4 text-center w-24">Tipe</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach($bank_soal as $b): ?>
                            <tr class="hover:bg-blue-50 transition cursor-pointer" onclick="document.getElementById('cb_<?= $b['id_soal'] ?>').click()">
                                <td class="p-4 text-center" onclick="event.stopPropagation()">
                                    <input type="checkbox" name="soal_ids[]" value="<?= $b['id_soal'] ?>" id="cb_<?= $b['id_soal'] ?>" class="cb-bank w-4 h-4 cursor-pointer text-blue-600">
                                </td>
                                <td class="p-4 text-slate-700 font-medium"><?= mb_strimwidth(strip_tags($b['pertanyaan']), 0, 90, "...") ?></td>
                                <td class="p-4 text-center">
                                    <?php if(isPG($b['jenis_soal'] ?? null)): ?>
                                        <span class="bg-blue-50 text-blue-600 border border-blue-200 px-2 py-1 rounded text-[10px] font-bold">PG</span>
                                    <?php else: ?>
                                        <span class="bg-orange-50 text-orange-600 border border-orange-200 px-2 py-1 rounded text-[10px] font-bold">ESSAY</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($bank_soal)): ?>
                            <tr><td colspan="3" class="p-16 text-center text-slate-400 font-bold">Bank soal kosong atau semua soal sudah ditambahkan ke ujian ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </form>
            </div>
            
            <div class="p-6 border-t border-slate-100 flex justify-end gap-3 bg-white">
                <button onclick="tutupModalBank()" type="button" class="px-6 py-2.5 text-slate-500 font-bold hover:bg-slate-50 rounded-xl">Batal</button>
                <button type="submit" form="formBank" name="tambah_dari_bank" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200">Tambahkan ke Ujian</button>
            </div>
        </div>
    </div>

    <div id="modalImport" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl flex flex-col overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="text-lg font-bold text-slate-800"><i class="fas fa-file-excel text-green-600 mr-2"></i> Import Excel</h3>
                <button onclick="tutupModalImport()" class="text-slate-400 hover:text-red-500"><i class="fas fa-times text-xl"></i></button>
            </div>
            
            <div class="p-6">
                <div class="mb-6 p-4 bg-blue-50 rounded-xl border border-blue-100">
                    <a href="../../proses/download_template.php" class="text-blue-600 hover:text-blue-700 font-medium text-sm flex items-center gap-2">
                        <i class="fas fa-download"></i> Download Template Excel
                    </a>
                </div>

                <form action="proses_import_excel.php" method="POST" enctype="multipart/form-data" id="formImport">
    
    <input type="hidden" name="id_ujian" value="<?= isset($_GET['id']) ? $_GET['id'] : ''; ?>">
    
    <input type="hidden" name="id_mapel" value="<?= isset($id_mapel) ? $id_mapel : '6'; ?>">
    
    <div class="mb-4">
        <label class="block text-sm font-bold text-slate-700 mb-2">Upload File (.xls / .xlsx)</label>
        <input type="file" name="file_excel" accept=".xls,.xlsx" required
               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-green-500 outline-none bg-slate-50">
    </div>
    
    <div class="bg-amber-50 text-amber-700 p-3 rounded-xl border border-amber-200 text-xs font-medium">
        <i class="fas fa-exclamation-triangle mr-1"></i> Pastikan format kolom Excel sesuai dengan template.
    </div>
    
    </form>
            </div>
            
            <div class="p-6 border-t border-slate-100 flex justify-end gap-3 bg-white">
                <button onclick="tutupModalImport()" type="button" class="px-6 py-2.5 text-slate-500 font-bold hover:bg-slate-50 rounded-xl transition">Batal</button>
                <button type="submit" form="formImport" class="bg-green-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-green-700 shadow-lg shadow-green-200 transition">
                    <i class="fas fa-upload mr-1"></i> Import Data
                </button>
            </div>
        </div>
    </div>

    <script>
        // Fungsi Modal Bank
        function bukaModalBank() { document.getElementById('modalBank').classList.replace('hidden', 'flex'); }
        function tutupModalBank() { document.getElementById('modalBank').classList.replace('flex', 'hidden'); }
        
        // Fungsi Modal Import
        function bukaModalImport() { document.getElementById('modalImport').classList.replace('hidden', 'flex'); }
        function tutupModalImport() { document.getElementById('modalImport').classList.replace('flex', 'hidden'); }
        
        // Checkbox All Bank Soal
        document.getElementById('checkAllBank').addEventListener('change', function() {
            document.querySelectorAll('.cb-bank').forEach(cb => cb.checked = this.checked);
        });

        // SweetAlert Keluarkan Soal
        function keluarkanSoal(idUjian, idSoal) {
            Swal.fire({
                title: 'Hapus Soal?', text: "Soal hanya ditarik dari ujian ini, tidak terhapus dari bank soal.", icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
            }).then((res) => { if (res.isConfirmed) window.location.href = "?id=" + idUjian + "&keluarkan=" + idSoal; });
        }

        // SweetAlert Kosongkan Ujian
        function kosongkanUjian(idUjian) {
            Swal.fire({
                title: 'Kosongkan Ujian?', text: "Semua soal akan dibersihkan dari ujian ini!", icon: 'error',
                showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Kosongkan', cancelButtonText: 'Batal'
            }).then((res) => { if (res.isConfirmed) window.location.href = "?id=" + idUjian + "&kosongkan=1"; });
        }
    </script>
</body>
</html>