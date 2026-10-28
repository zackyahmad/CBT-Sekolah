<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../../index.php"); exit;
}
require_once '../../config/koneksi.php';

// Set timezone ke Indonesia/Jakarta
date_default_timezone_set('Asia/Jakarta');

$id_user = $_SESSION['id_user'];
$id_ujian = isset($_GET['id_ujian']) ? (int)$_GET['id_ujian'] : 0;

// 1. Ambil detail ujian
$stmt = $pdo->prepare("SELECT * FROM tb_ujian WHERE id_ujian = ?");
$stmt->execute([$id_ujian]);
$ujian = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ujian) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Ujian tidak ditemukan.</h2><a href='ujian.php'>Kembali</a></div>");
}

// -------------------------------------------------------------
// PENGATURAN WAKTU UJIAN (TIMER) ANTI-CHEAT
// -------------------------------------------------------------
// Catat waktu mulai ujian di sesi agar tidak ter-reset walau di-refresh
$session_key = "start_ujian_" . $id_ujian . "_user_" . $id_user;
if (!isset($_SESSION[$session_key])) {
    $_SESSION[$session_key] = time(); // Simpan detik saat ini
}
$waktu_mulai_ujian = $_SESSION[$session_key];

// Hitung batas waktu berdasarkan Durasi Menit
$durasi_detik = (int)$ujian['durasi_menit'] * 60;
$waktu_berakhir_durasi = $waktu_mulai_ujian + $durasi_detik;

// Hitung batas waktu dari Jam Selesai yang disetel guru
$jam_selesai_db = $ujian['jam_selesai'] ?? '23:59:59';
$waktu_berakhir_jadwal = strtotime($ujian['tanggal_ujian'] . ' ' . $jam_selesai_db);

// Sisa waktu diambil dari yang PALING CEPAT HABIS (antara durasi habis ATAU jam tutup ujian)
$waktu_berakhir_final = min($waktu_berakhir_durasi, $waktu_berakhir_jadwal);
$sisa_waktu = $waktu_berakhir_final - time();

// Keamanan: Jika waktu sudah minus (siswa coba curang mematikan JS dll), tolak render soal
if ($sisa_waktu <= 0) {
    echo "<script>alert('Waktu ujian Anda telah habis!'); window.location.href='ujian.php';</script>";
    exit;
}
// -------------------------------------------------------------

function isPG($jenis) {
    if ($jenis === null || $jenis === '') return true; 
    $j = strtolower(trim((string)$jenis));
    return in_array($j, ['1', 'pg', 'pilihan ganda', 'pilihan_ganda']);
}

// 2. Ambil LANGSUNG semua soal beserta jenis dan gambarnya
$stmt_soal = $pdo->prepare("
    SELECT s.id_soal, s.pertanyaan, s.gambar, s.jenis_soal, s.opsi_a, s.opsi_b, s.opsi_c, s.opsi_d, s.opsi_e 
    FROM tb_ujian_soal us 
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
    <title>Kerjakan Ujian - <?= htmlspecialchars($ujian['judul_ujian']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Sembunyikan semua soal kecuali yang aktif */
        .soal-item { display: none; }
        .soal-item.active { display: block; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Warna untuk Navigasi Bagan Soal */
        .nav-kosong { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; } /* Slate */
        .nav-terjawab { background-color: #22c55e; color: white; border: 1px solid #16a34a; box-shadow: 0 4px 6px -1px rgba(34, 197, 94, 0.4); } /* Green */
        .nav-ragu { background-color: #f97316; color: white; border: 1px solid #ea580c; box-shadow: 0 4px 6px -1px rgba(249, 115, 22, 0.4); } /* Orange */
    </style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans min-h-screen">
    
    <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 md:px-8 py-4 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-black text-slate-800 uppercase tracking-wide"><?= htmlspecialchars($ujian['judul_ujian']) ?></h1>
                <p class="text-sm font-bold text-blue-600"><i class="fas fa-user-graduate mr-1"></i> <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? 'Siswa') ?></p>
            </div>
            <div class="bg-blue-50 border border-blue-200 px-4 py-2 rounded-xl text-center min-w-[120px]">
                <p class="text-[10px] uppercase font-bold text-blue-500 tracking-wider">Sisa Waktu</p>
                <p class="text-xl font-black text-blue-700" id="timer">--:--:--</p>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 md:px-8 py-6 flex flex-col lg:flex-row gap-6">
        
        <div class="w-full lg:w-3/4">
            <form action="proses_ujian.php" method="POST" id="formUjian">
                <input type="hidden" name="id_ujian" value="<?= $id_ujian ?>">

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-10 relative min-h-[400px]">
                    <?php $no = 1; foreach ($list_soal as $index => $soal): ?>
                        
                        <div id="soal_<?= $no ?>" class="soal-item <?= $no == 1 ? 'active' : '' ?>">
                            
                            <input type="hidden" id="id_soal_<?= $no ?>" value="<?= $soal['id_soal'] ?>">
                            <input type="hidden" id="jenis_soal_<?= $no ?>" value="<?= isPG($soal['jenis_soal']) ? 'PG' : 'ESSAY' ?>">

                            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
                                <h2 class="text-xl font-black text-slate-800">SOAL NO. <?= $no ?></h2>
                                
                                <label class="flex items-center gap-2 cursor-pointer bg-orange-50 hover:bg-orange-100 border border-orange-200 px-4 py-2 rounded-xl transition">
                                    <input type="checkbox" id="ragu_<?= $no ?>" onchange="updateBagan(<?= $no ?>)" class="w-5 h-5 accent-orange-500 cursor-pointer">
                                    <span class="text-orange-700 font-bold text-sm">Ragu-ragu</span>
                                </label>
                            </div>

                            <div class="text-lg text-slate-700 font-medium leading-relaxed break-words prose max-w-none mb-8">
                                <?= $soal['pertanyaan'] ?>
                                
                                <?php if (!empty($soal['gambar'])): ?>
                                    <div class="mt-6 mb-6">
                                        <img src="../../uploads/soal/<?= htmlspecialchars($soal['gambar']) ?>" alt="Gambar Soal" class="max-w-full md:max-w-xl h-auto rounded-xl border-2 border-slate-200 shadow-sm">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="space-y-3">
                                <?php if (isPG($soal['jenis_soal'])): ?>
                                    <?php
                                    $opsi = ['A' => $soal['opsi_a'], 'B' => $soal['opsi_b'], 'C' => $soal['opsi_c'], 'D' => $soal['opsi_d'], 'E' => $soal['opsi_e']];
                                    foreach ($opsi as $huruf => $teks_opsi): 
                                        if (trim((string)$teks_opsi) !== ''): 
                                    ?>
                                        <label class="flex items-start p-4 border-2 border-slate-100 rounded-xl cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition group has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500">
                                            <input type="radio" name="jawaban[<?= $soal['id_soal'] ?>]" value="<?= $huruf ?>" onchange="updateBagan(<?= $no ?>)" class="w-5 h-5 mt-0.5 text-blue-600 focus:ring-blue-500 border-gray-300 cursor-pointer">
                                            <span class="ml-4 text-slate-700 font-medium group-hover:text-blue-800 break-words text-lg">
                                                <strong class="text-blue-700 mr-1"><?= $huruf ?>.</strong> <?= htmlspecialchars($teks_opsi) ?>
                                            </span>
                                        </label>
                                    <?php endif; endforeach; ?>
                                <?php else: ?>
                                    <textarea name="jawaban[<?= $soal['id_soal'] ?>]" rows="8" placeholder="Ketik jawaban lengkap Anda di sini..." oninput="updateBagan(<?= $no ?>)"
                                              class="w-full p-5 border-2 border-slate-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition bg-slate-50 text-slate-800 text-lg"></textarea>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php $no++; endforeach; ?>
                </div>

                <div class="flex justify-between items-center mt-6">
                    <button type="button" onclick="gantiSoal('prev')" id="btnPrev" class="hidden bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold py-3 px-6 rounded-xl shadow-sm transition items-center gap-2">
                        <i class="fas fa-arrow-left"></i> Soal Sebelumnya
                    </button>
                    
                    <button type="button" onclick="gantiSoal('next')" id="btnNext" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-blue-200 transition items-center gap-2 ml-auto">
                        Soal Selanjutnya <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="w-full lg:w-1/4">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-24">
                <h3 class="font-black text-slate-800 mb-4 text-center border-b border-slate-100 pb-3">NAVIGASI SOAL</h3>
                
                <div class="flex justify-center gap-4 mb-5 text-[10px] font-bold uppercase tracking-wider">
                    <div class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-500 block"></span> Dijawab</div>
                    <div class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-orange-500 block"></span> Ragu</div>
                    <div class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-slate-200 block border border-slate-300"></span> Kosong</div>
                </div>

                <div class="grid grid-cols-5 gap-2 mb-6" id="gridNavigasi">
                    <?php for($i = 1; $i <= $total_soal; $i++): ?>
                        <button type="button" id="btn_nav_<?= $i ?>" onclick="lompatKe(<?= $i ?>)" class="nav-kosong w-full aspect-square rounded-lg font-black text-sm flex items-center justify-center transition hover:opacity-80">
                            <?= $i ?>
                        </button>
                    <?php endfor; ?>
                </div>

                <button type="button" onclick="validasiKumpul()" class="w-full bg-slate-800 hover:bg-black text-white font-black py-4 rounded-xl shadow-lg transition flex justify-center items-center gap-2">
                    <i class="fas fa-paper-plane"></i> KUMPULKAN UJIAN
                </button>
            </div>
        </div>

    </div>

    <script>
        // ==========================================
        // LOGIKA TIMER DAN AUTO-SUBMIT
        // ==========================================
        let sisaWaktu = <?= $sisa_waktu ?>;
        const timerElement = document.getElementById('timer');

        const hitungMundur = setInterval(() => {
            if (sisaWaktu <= 0) {
                clearInterval(hitungMundur); // Hentikan timer
                timerElement.innerHTML = "00:00:00";
                
                // Muncullkan peringatan lalu paksa kumpul
                Swal.fire({
                    title: 'WAKTU HABIS!',
                    text: 'Waktu ujian Anda telah berakhir. Jawaban Anda akan dikumpulkan secara otomatis.',
                    icon: 'warning',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    timer: 3500 // Tunggu 3,5 detik
                }).then(() => {
                    document.getElementById('formUjian').submit(); // SUBMIT OTOMATIS
                });
                return;
            }
            
            sisaWaktu--;
            
            let jam = Math.floor(sisaWaktu / 3600);
            let menit = Math.floor((sisaWaktu % 3600) / 60);
            let detik = sisaWaktu % 60;
            
            timerElement.innerHTML = 
                (jam < 10 ? "0" + jam : jam) + ":" + 
                (menit < 10 ? "0" + menit : menit) + ":" + 
                (detik < 10 ? "0" + detik : detik);
                
            // Ubah warna text menjadi merah berkedip jika sisa waktu < 5 menit (300 detik)
            if (sisaWaktu < 300) {
                timerElement.classList.remove('text-blue-700');
                timerElement.classList.add('text-red-600', 'animate-pulse');
            }
        }, 1000);


        // ==========================================
        // LOGIKA NAVIGASI SOAL
        // ==========================================
        let soalSekarang = 1;
        const totalSoal = <?= $total_soal ?>;

        function gantiSoal(arah) {
            if (arah === 'next' && soalSekarang < totalSoal) {
                lompatKe(soalSekarang + 1);
            } else if (arah === 'prev' && soalSekarang > 1) {
                lompatKe(soalSekarang - 1);
            }
        }

        function lompatKe(nomor) {
            document.getElementById('soal_' + soalSekarang).classList.remove('active');
            soalSekarang = nomor;
            document.getElementById('soal_' + soalSekarang).classList.add('active');
            
            document.getElementById('btnPrev').style.display = (soalSekarang === 1) ? 'none' : 'flex';
            document.getElementById('btnNext').style.display = (soalSekarang === totalSoal) ? 'none' : 'flex';
        }

        function updateBagan(nomor) {
            let id_soal = document.getElementById('id_soal_' + nomor).value;
            let jenis = document.getElementById('jenis_soal_' + nomor).value;
            let isRagu = document.getElementById('ragu_' + nomor).checked;
            let isTerjawab = false;

            if (jenis === 'PG') {
                let radios = document.getElementsByName('jawaban[' + id_soal + ']');
                for (let i = 0; i < radios.length; i++) {
                    if (radios[i].checked) { isTerjawab = true; break; }
                }
            } else {
                let text = document.getElementsByName('jawaban[' + id_soal + ']')[0].value;
                if (text.trim() !== '') isTerjawab = true;
            }

            let btnNav = document.getElementById('btn_nav_' + nomor);
            btnNav.className = 'w-full aspect-square rounded-lg font-black text-sm flex items-center justify-center transition hover:opacity-80'; 
            
            if (isRagu) {
                btnNav.classList.add('nav-ragu');
            } else if (isTerjawab) {
                btnNav.classList.add('nav-terjawab');
            } else {
                btnNav.classList.add('nav-kosong');
            }
        }

        function validasiKumpul() {
            let belumTerjawab = [];
            let adaRagu = false;

            for (let i = 1; i <= totalSoal; i++) {
                let id_soal = document.getElementById('id_soal_' + i).value;
                let jenis = document.getElementById('jenis_soal_' + i).value;
                let isRagu = document.getElementById('ragu_' + i).checked;
                let isTerjawab = false;

                if (isRagu) adaRagu = true;

                if (jenis === 'PG') {
                    let radios = document.getElementsByName('jawaban[' + id_soal + ']');
                    for (let r of radios) { if (r.checked) isTerjawab = true; }
                } else {
                    let text = document.getElementsByName('jawaban[' + id_soal + ']')[0].value;
                    if (text.trim() !== '') isTerjawab = true;
                }

                if (!isTerjawab) belumTerjawab.push(i);
            }

            if (belumTerjawab.length > 0) {
                Swal.fire({
                    title: 'Ujian Belum Selesai!',
                    text: 'Anda belum menjawab soal nomor: ' + belumTerjawab.join(', '),
                    icon: 'error',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }

            if (adaRagu) {
                Swal.fire({
                    title: 'Masih Ada yang Ragu!',
                    text: 'Anda masih menandai beberapa soal dengan status "Ragu-ragu". Silakan hilangkan centang Ragu-ragu sebelum mengumpulkan.',
                    icon: 'warning',
                    confirmButtonColor: '#f97316'
                });
                return;
            }

            Swal.fire({
                title: 'Sudah Yakin Selesai?',
                text: "Anda tidak bisa kembali mengubah jawaban setelah mengumpulkan ujian ini!",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#22c55e',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Kumpulkan Ujian!',
                cancelButtonText: 'Cek Kembali'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formUjian').submit();
                }
            })
        }

        window.onload = function() {
            for(let i = 1; i <= totalSoal; i++) { updateBagan(i); }
            lompatKe(1); 
        };
    </script>
</body>
</html>