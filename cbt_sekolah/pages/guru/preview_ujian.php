<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../../index.php"); exit;
}
require_once '../../config/koneksi.php';

$id_guru  = $_SESSION['id_user'];
$id_ujian = isset($_GET['id_ujian']) ? (int)$_GET['id_ujian'] : 0;

// 1. Ambil detail ujian
$stmt = $pdo->prepare("SELECT * FROM tb_ujian WHERE id_ujian = ? AND id_guru = ?");
$stmt->execute([$id_ujian, $id_guru]);
$ujian = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ujian) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Ujian tidak ditemukan atau Anda tidak memiliki akses.</h2><a href='rencana_ujian.php'>Kembali</a></div>");
}

// ==========================================
// PROSES HAPUS SOAL (GET ACTION)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id_soal'])) {
    $id_soal_hapus = (int)$_GET['id_soal'];
    $pdo->beginTransaction();
    try {
        $del_relasi = $pdo->prepare("DELETE FROM tb_ujian_soal WHERE id_ujian = ? AND id_soal = ?");
        $del_relasi->execute([$id_ujian, $id_soal_hapus]);
        $del_soal = $pdo->prepare("DELETE FROM tb_soal WHERE id_soal = ?");
        $del_soal->execute([$id_soal_hapus]);
        $pdo->commit();
        echo "<script>alert('Soal berhasil dihapus permanen!'); window.location.href='preview_ujian.php?id_ujian=$id_ujian';</script>";
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Gagal menghapus soal: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
}

// ==========================================
// PROSES EDIT SOAL & GAMBAR (POST ACTION)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_soal'])) {
    $id_soal_edit = (int)$_POST['id_soal'];
    $pertanyaan   = trim($_POST['pertanyaan']);
    $opsi_a       = trim($_POST['opsi_a']);
    $opsi_b       = trim($_POST['opsi_b']);
    $opsi_c       = trim($_POST['opsi_c']);
    $opsi_d       = trim($_POST['opsi_d']);
    $opsi_e       = trim($_POST['opsi_e']);
    $kunci        = strtolower(trim($_POST['kunci_jawaban']));
    $bobot        = (int)$_POST['bobot'];

    // PROSES GAMBAR
    $upload_dir = '../../uploads/soal/';
    $gambar_lama = $_POST['gambar_lama'] ?? '';
    $nama_gambar_baru = $gambar_lama; // Default gunakan nama lama

    // A. Cek jika guru mencentang "Hapus Gambar Ini"
    if (isset($_POST['hapus_gambar_lama']) && $_POST['hapus_gambar_lama'] == '1') {
        if (!empty($gambar_lama) && file_exists($upload_dir . $gambar_lama)) {
            unlink($upload_dir . $gambar_lama);
        }
        $nama_gambar_baru = null; // Kosongkan
    }

    // B. Cek jika ada gambar baru hasil crop dari Cropper.js
    $base64_image = $_POST['cropped_image_data'] ?? '';
    if (!empty($base64_image)) {
        list($type, $data) = explode(';', $base64_image);
        list(, $data)      = explode(',', $data);
        $decoded_image = base64_decode($data);

        $ext = (strpos($type, 'png') !== false) ? 'png' : 'jpg';
        $nama_gambar_baru = 'soal_' . time() . '_' . uniqid() . '.' . $ext;
        
        // Hapus file lama agar tidak menumpuk di server
        if (!empty($gambar_lama) && file_exists($upload_dir . $gambar_lama)) {
            unlink($upload_dir . $gambar_lama);
        }

        file_put_contents($upload_dir . $nama_gambar_baru, $decoded_image);

        // Kompresi GD Library agar enteng (Opsional)
        if (extension_loaded('gd')) {
            if ($ext === 'jpg' || $ext === 'jpeg') {
                $img = imagecreatefromjpeg($upload_dir . $nama_gambar_baru);
                imagejpeg($img, $upload_dir . $nama_gambar_baru, 80);
                imagedestroy($img);
            } else if ($ext === 'png') {
                $img = imagecreatefrompng($upload_dir . $nama_gambar_baru);
                imagepng($img, $upload_dir . $nama_gambar_baru, 8);
                imagedestroy($img);
            }
        }
    }

    // UPDATE DATABASE
    try {
        $sql_update = "UPDATE tb_soal SET 
                        pertanyaan = ?, opsi_a = ?, opsi_b = ?, opsi_c = ?, opsi_d = ?, opsi_e = ?, kunci_jawaban = ?, bobot = ?, gambar = ? 
                       WHERE id_soal = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $opsi_e, $kunci, $bobot, $nama_gambar_baru, $id_soal_edit]);
        
        echo "<script>alert('Soal & Gambar berhasil diperbarui!'); window.location.href='preview_ujian.php?id_ujian=$id_ujian';</script>";
        exit;
    } catch (PDOException $e) {
        echo "<script>alert('Gagal memperbarui: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
}

function isPG($jenis) {
    if ($jenis === null || $jenis === '') return true; 
    $j = strtolower(trim((string)$jenis));
    return in_array($j, ['1', 'pg', 'pilihan ganda', 'pilihan_ganda']);
}

// 2. Ambil semua data soal ujian
$stmt_soal = $pdo->prepare("
    SELECT s.* FROM tb_ujian_soal us 
    JOIN tb_soal s ON us.id_soal = s.id_soal 
    WHERE us.id_ujian = ? 
    ORDER BY us.ditambahkan_pada ASC
");
$stmt_soal->execute([$id_ujian]);
$list_soal = $stmt_soal->fetchAll(PDO::FETCH_ASSOC);
$total_soal = count($list_soal);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POV Guru (Pratinjau) - <?= htmlspecialchars($ujian['judul_ujian']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <style>
        .animate-pop { animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans min-h-screen">
    
    <header class="bg-amber-500 border-b border-amber-600 shadow-md sticky top-0 z-30 text-white">
        <div class="max-w-7xl mx-auto px-4 md:px-8 py-3.5 flex justify-between items-center">
            <div>
                <span class="bg-amber-700/50 text-xs font-bold px-2 py-1 rounded-md uppercase tracking-wider mb-1 inline-block">MODE PRATINJAU GURU (POV SISWA)</span>
                <h1 class="text-xl font-black uppercase tracking-wide"><?= htmlspecialchars($ujian['judul_ujian']) ?></h1>
            </div>
            <a href="kelola_soal_ujian.php?id=<?= $id_ujian ?>" class="bg-white hover:bg-slate-100 text-amber-700 font-bold px-4 py-2 rounded-xl text-sm transition shadow flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 md:px-8 py-6 flex flex-col lg:flex-row gap-6">
        
        <div class="w-full lg:w-3/4">
            <?php if($total_soal == 0): ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-16 text-center text-slate-400 font-medium shadow-sm">
                    <i class="fas fa-folder-open text-6xl text-slate-300 mb-4 block"></i>
                    Belum ada soal di dalam ujian ini.
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-10 relative min-h-[420px]">
                    <?php $no = 1; foreach ($list_soal as $index => $soal): ?>
                        
                        <div id="soal_<?= $no ?>" class="soal-item hidden <?= $no == 1 ? '!block' : '' ?>">
                            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <h2 class="text-xl font-black text-slate-800">SOAL NO. <?= $no ?></h2>
                                    <span class="bg-blue-50 text-blue-700 border border-blue-100 text-xs px-2 py-0.5 rounded-md font-bold">Bobot: <?= $soal['bobot'] ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($soal)) ?>)" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-2 rounded-xl transition flex items-center gap-1.5 shadow-md">
                                        <i class="fas fa-edit"></i> Edit Soal & Gambar
                                    </button>
                                    <a href="?id_ujian=<?= $id_ujian ?>&action=delete&id_soal=<?= $soal['id_soal'] ?>" onclick="return confirm('Hapus PERMANEN soal ini?')" class="bg-red-500 hover:bg-red-600 text-white text-xs font-bold px-3 py-2 rounded-xl transition flex items-center gap-1.5 shadow-md">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="text-lg text-slate-700 font-medium leading-relaxed break-words mb-8">
                                <?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?>
                                
                                <?php if (!empty($soal['gambar'])): ?>
                                    <div class="mt-6 mb-6">
                                        <img src="../../uploads/soal/<?= htmlspecialchars($soal['gambar']) ?>" alt="Gambar Soal" class="max-w-full md:max-w-xl h-auto rounded-xl border-2 border-slate-200 shadow-sm block">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="space-y-3">
                                <?php if (isPG($soal['jenis_soal'])): ?>
                                    <?php
                                    $opsi = ['a' => $soal['opsi_a'], 'b' => $soal['opsi_b'], 'c' => $soal['opsi_c'], 'd' => $soal['opsi_d'], 'e' => $soal['opsi_e']];
                                    foreach ($opsi as $huruf => $teks_opsi): 
                                        if (trim((string)$teks_opsi) !== ''): 
                                            $isKunci = (strtolower($soal['kunci_jawaban']) === $huruf);
                                    ?>
                                        <div class="flex items-start p-4 border-2 rounded-xl text-lg <?= $isKunci ? 'bg-green-50 border-green-500 text-green-900 font-bold' : 'border-slate-100 bg-white' ?>">
                                            <span class="w-6 h-6 flex-shrink-0 rounded-full flex items-center justify-center mr-3 text-sm <?= $isKunci ? 'bg-green-600 text-white font-black' : 'bg-slate-200 text-slate-700' ?>">
                                                <?= strtoupper($huruf) ?>
                                            </span>
                                            <span class="break-words flex-1">
                                                <?= htmlspecialchars($teks_opsi) ?> 
                                                <?= $isKunci ? ' <span class="text-xs uppercase bg-green-600 text-white px-1.5 py-0.5 rounded ml-2 tracking-wide font-black"><i class="fas fa-check"></i> KUNCI</span>' : '' ?>
                                            </span>
                                        </div>
                                    <?php endif; endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php $no++; endforeach; ?>
                </div>

                <div class="flex justify-between items-center mt-6">
                    <button type="button" onclick="gantiSoal('prev')" id="btnPrev" class="hidden bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold py-3 px-6 rounded-xl shadow-sm transition items-center gap-2"><i class="fas fa-arrow-left"></i> Sebelumnya</button>
                    <button type="button" onclick="gantiSoal('next')" id="btnNext" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition items-center gap-2 ml-auto">Selanjutnya <i class="fas fa-arrow-right"></i></button>
                </div>
            <?php endif; ?>
        </div>

        <div class="w-full lg:w-1/4">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-24">
                <h3 class="font-black text-slate-800 mb-4 text-center border-b border-slate-100 pb-3">NAVIGASI SOAL</h3>
                <div class="grid grid-cols-5 gap-2" id="gridNavigasi">
                    <?php for($i = 1; $i <= $total_soal; $i++): ?>
                        <button type="button" id="btn_nav_<?= $i ?>" onclick="lompatKe(<?= $i ?>)" class="w-full aspect-square rounded-lg font-black text-sm flex items-center justify-center transition border border-slate-300 bg-slate-100 text-slate-700 hover:bg-slate-200"><?= $i ?></button>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="modalEdit" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden border border-slate-200 max-h-[95vh] flex flex-col animate-pop">
            
            <div class="bg-blue-600 p-4 flex justify-between items-center text-white flex-shrink-0">
                <h3 class="font-bold text-lg"><i class="fas fa-edit mr-2"></i>Edit Detail & Gambar Soal</h3>
                <button type="button" onclick="tutupModal()" class="hover:text-blue-200"><i class="fas fa-times text-xl"></i></button>
            </div>
            
            <form action="" method="POST" id="formEditSoal" class="overflow-y-auto p-6 flex-1 bg-slate-50">
                <input type="hidden" name="id_soal" id="modal_id_soal">
                
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-5">
                    <label class="block text-xs uppercase font-bold text-slate-500 mb-2"><i class="fas fa-pen text-blue-500 mr-1"></i> Teks Pertanyaan</label>
                    <textarea name="pertanyaan" id="modal_pertanyaan" rows="3" required class="w-full p-3 border border-slate-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"></textarea>
                    
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-xs uppercase font-bold text-slate-500 mb-1">Bobot Nilai</label>
                            <input type="number" name="bobot" id="modal_bobot" required class="w-full p-2.5 border border-slate-300 rounded-xl outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs uppercase font-bold text-slate-500 mb-1">Kunci (PG)</label>
                            <select name="kunci_jawaban" id="modal_kunci" class="w-full p-2.5 border border-slate-300 rounded-xl outline-none focus:border-blue-500 uppercase font-bold text-blue-700 bg-blue-50">
                                <option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option><option value="e">E</option><option value="">Bukan PG</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-5">
                    <h4 class="text-xs uppercase font-black text-blue-600 tracking-wider mb-3 pb-2 border-b border-slate-100"><i class="fas fa-image mr-1"></i> Kelola Gambar Soal</h4>
                    
                    <input type="hidden" name="cropped_image_data" id="modal_cropped_data">
                    <input type="hidden" name="gambar_lama" id="modal_gambar_lama">

                    <div id="modal_current_image_container" class="mb-4 hidden p-4 bg-slate-50 border border-slate-200 rounded-xl text-center">
                        <p class="text-[10px] uppercase font-bold text-green-600 mb-2"><i class="fas fa-check-circle"></i> Gambar Terpasang Saat Ini</p>
                        <img id="modal_current_image" src="" class="mx-auto max-h-32 rounded border shadow-sm mb-3">
                        <label class="inline-flex items-center justify-center gap-2 text-sm text-red-600 font-bold cursor-pointer hover:bg-red-100 px-3 py-1.5 rounded-lg border border-red-200 transition">
                            <input type="checkbox" name="hapus_gambar_lama" id="modal_hapus_gambar_lama" value="1" class="w-4 h-4 accent-red-600 rounded">
                            Hapus Gambar Ini 
                        </label>
                    </div>

                    <label class="block text-xs font-bold text-slate-500 mb-2">Ganti / Upload Gambar Baru (Opsional)</label>
                    <input type="file" id="modal_input_gambar" accept="image/jpeg, image/png" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-300 rounded-xl p-1 bg-white mb-4 cursor-pointer">
                    
                    <div id="modal_canvas_container" class="hidden w-full h-64 bg-slate-200 border-2 border-dashed border-slate-400 rounded-xl overflow-hidden relative mb-3 items-center justify-center">
                        <img id="modal_image_to_crop" src="" class="max-w-full max-h-full">
                    </div>
                    
                    <div id="modal_toolbar" class="hidden grid grid-cols-4 gap-2">
                        <button type="button" onclick="cropper.zoom(0.1)" class="bg-slate-100 border border-slate-300 text-xs py-2 rounded-lg text-slate-700 hover:bg-slate-200 font-bold shadow-sm transition"><i class="fas fa-search-plus"></i> Zoom In</button>
                        <button type="button" onclick="cropper.zoom(-0.1)" class="bg-slate-100 border border-slate-300 text-xs py-2 rounded-lg text-slate-700 hover:bg-slate-200 font-bold shadow-sm transition"><i class="fas fa-search-minus"></i> Zoom Out</button>
                        <button type="button" onclick="cropper.rotate(-90)" class="bg-slate-100 border border-slate-300 text-xs py-2 rounded-lg text-slate-700 hover:bg-slate-200 font-bold shadow-sm transition"><i class="fas fa-undo"></i> Putar Kiri</button>
                        <button type="button" onclick="cropper.rotate(90)" class="bg-slate-100 border border-slate-300 text-xs py-2 rounded-lg text-slate-700 hover:bg-slate-200 font-bold shadow-sm transition"><i class="fas fa-redo"></i> Putar Kanan</button>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-4">
                    <p class="text-xs uppercase font-black text-blue-600 tracking-wider mb-3"><i class="fas fa-list-ul mr-1"></i> Opsi Jawaban Pilihan Ganda</p>
                    <div class="space-y-3">
                        <?php foreach(['a', 'b', 'c', 'd', 'e'] as $h): ?>
                            <div class="flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-slate-100 border border-slate-300 text-slate-600 font-black flex items-center justify-center uppercase"><?= $h ?></span>
                                <input type="text" name="opsi_<?= $h ?>" id="modal_opsi_<?= $h ?>" class="flex-1 p-2.5 border border-slate-300 rounded-xl outline-none focus:border-blue-500">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex gap-3 sticky bottom-0 bg-slate-50 pt-3 pb-2 border-t border-slate-200 mt-2">
                    <button type="button" onclick="tutupModal()" class="flex-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-600 py-3 rounded-xl font-bold transition shadow-sm">Batal</button>
                    <button type="submit" id="btnSubmitModal" name="update_soal" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-bold shadow-lg shadow-blue-200 transition"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        let soalSekarang = 1;
        const totalSoal = <?= $total_soal ?>;
        let cropper = null;

        // Logika Slider
        function gantiSoal(arah) {
            if (arah === 'next' && soalSekarang < totalSoal) { lompatKe(soalSekarang + 1); } 
            else if (arah === 'prev' && soalSekarang > 1) { lompatKe(soalSekarang - 1); }
        }

        function lompatKe(nomor) {
            if(totalSoal === 0) return;
            document.getElementById('soal_' + soalSekarang).classList.add('hidden');
            document.getElementById('btn_nav_' + soalSekarang).classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
            
            soalSekarang = nomor;
            document.getElementById('soal_' + soalSekarang).classList.remove('hidden');
            document.getElementById('btn_nav_' + soalSekarang).classList.add('bg-blue-600', 'text-white', 'border-blue-600');
            
            document.getElementById('btnPrev').style.display = (soalSekarang === 1) ? 'none' : 'flex';
            document.getElementById('btnNext').style.display = (soalSekarang === totalSoal) ? 'none' : 'flex';
        }

        // BUKA MODAL DAN ISI DATA
        function bukaModalEdit(dataSoal) {
            document.getElementById('modal_id_soal').value = dataSoal.id_soal;
            document.getElementById('modal_pertanyaan').value = dataSoal.pertanyaan;
            document.getElementById('modal_bobot').value = dataSoal.bobot;
            document.getElementById('modal_kunci').value = dataSoal.kunci_jawaban.toLowerCase();
            
            ['a','b','c','d','e'].forEach(h => {
                document.getElementById('modal_opsi_' + h).value = dataSoal['opsi_' + h];
            });

            // RESET CROPPER & UI GAMBAR
            if (cropper) { cropper.destroy(); cropper = null; }
            document.getElementById('modal_input_gambar').value = '';
            document.getElementById('modal_cropped_data').value = '';
            document.getElementById('modal_canvas_container').classList.add('hidden');
            document.getElementById('modal_canvas_container').classList.remove('flex');
            document.getElementById('modal_toolbar').classList.add('hidden');
            document.getElementById('modal_hapus_gambar_lama').checked = false;

            if (dataSoal.gambar && dataSoal.gambar.trim() !== '') {
                document.getElementById('modal_gambar_lama').value = dataSoal.gambar;
                document.getElementById('modal_current_image').src = '../../uploads/soal/' + dataSoal.gambar;
                document.getElementById('modal_current_image_container').classList.remove('hidden');
            } else {
                document.getElementById('modal_gambar_lama').value = '';
                document.getElementById('modal_current_image_container').classList.add('hidden');
            }

            let m = document.getElementById('modalEdit');
            m.classList.remove('hidden'); m.classList.add('flex');
        }

        function tutupModal() {
            let m = document.getElementById('modalEdit');
            m.classList.add('hidden'); m.classList.remove('flex');
        }

        // INIT CROPPER KETIKA GURU MEMILIH FILE GAMBAR BARU
        document.getElementById('modal_input_gambar').addEventListener('change', function(e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                const reader = new FileReader();
                reader.onload = function(event) {
                    if (cropper) { cropper.destroy(); }
                    
                    document.getElementById('modal_image_to_crop').src = event.target.result;
                    document.getElementById('modal_current_image_container').classList.add('hidden');
                    
                    let canvasContainer = document.getElementById('modal_canvas_container');
                    canvasContainer.classList.remove('hidden');
                    canvasContainer.classList.add('flex'); // Agar gambar ke tengah
                    document.getElementById('modal_toolbar').classList.remove('hidden');

                    cropper = new Cropper(document.getElementById('modal_image_to_crop'), {
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        // TANGKAP EVENT SUBMIT FORM UNTUK MEMPROSES HASIL CROP
        document.getElementById('formEditSoal').addEventListener('submit', function(e) {
            if (cropper) {
                e.preventDefault(); // Tahan pengiriman form
                
                // Ambil hasil crop
                const canvas = cropper.getCroppedCanvas({
                    width: 800, // Ukuran ideal
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });
                
                // Ubah ke Base64
                const base64 = canvas.toDataURL('image/jpeg', 0.8);
                document.getElementById('modal_cropped_data').value = base64;
                
                // Matikan cropper & lepas penahan, submit secara paksa
                cropper.destroy();
                cropper = null;
                this.submit(); 
            }
            // Jika tidak ada gambar baru (cropper null), form otomatis langsung terkirim
        });

        window.onload = function() {
            lompatKe(1);
        };
    </script>
</body>
</html>