<?php
session_start();
require_once '../../config/koneksi.php';

$id_mapel = $_GET['id_mapel'];
$m = $pdo->prepare("SELECT nama_mapel FROM tb_mapel WHERE id_mapel = ?");
$m->execute([$id_mapel]);
$mapel = $m->fetch();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Soal <?= $mapel['nama_mapel'] ?> - SMKN 8 Bandar Lampung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .input-focus {
            transition: all 0.2s ease;
        }
        .input-focus:focus {
             background-color: #052c46;
;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        .tab-active {
            background-color: #052c46;
            color: white;
        }
    </style>
</head>

<body class="font-sans text-gray-700 p-6">

    <div class="max-w-5xl mx-auto">
        <!-- Header dengan breadcrumb ala dashboard -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6 mb-6 card-hover">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <nav class="text-xs text-gray-400 mb-2">
                        <a href="data_soal.php" class="hover:text-[#2C3E50] transition">Bank Soal</a>
                        <i class="fas fa-chevron-right mx-2 text-[10px]"></i>
                        <a href="soal_list.php?id_mapel=<?= $id_mapel ?>" class="hover:text-[#2C3E50] transition"><?= htmlspecialchars($mapel['nama_mapel']) ?></a>
                        <i class="fas fa-chevron-right mx-2 text-[10px]"></i>
                        <span class="text-[#2C3E50] font-semibold">Tambah Soal Manual</span>
                    </nav>
                    <h1 class="text-3xl font-bold text-[#2C3E50] flex items-center gap-3">
                        <i class="fas fa-plus-circle text-[#3498DB]"></i>
                        Tambah Soal Manual
                    </h1>
                </div>
                <a href="soal_list.php?id_mapel=<?= $id_mapel ?>" 
                   class="text-gray-400 hover:text-[#2C3E50] font-medium text-sm flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-lg border border-gray-100">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Soal
                </a>
            </div>
        </div>

        <!-- Form tambah soal -->
        <form action="../../proses/soal_proses.php" method="POST" enctype="multipart/form-data" id="formSoal">
            <input type="hidden" name="id_mapel" value="<?= $id_mapel ?>">
            <input type="hidden" name="jenis_soal" id="jenisSoal" value="1"> <!-- Default: PG -->

            <!-- Pilihan Tipe Soal -->
            <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden shadow-sm mb-6 card-hover">
                <div class="p-4">
                    <div class="flex items-center gap-4 p-1 bg-gray-100 rounded-xl max-w-md mx-auto">
                        <button type="button" id="tabPG" onclick="pilihTipe('pg')" 
                                class="tab-active flex-1 py-3 px-4 rounded-xl font-semibold text-sm transition-all duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-list"></i> Pilihan Ganda
                        </button>
                        <button type="button" id="tabEssay" onclick="pilihTipe('essay')" 
                                class="flex-1 py-3 px-4 rounded-xl font-semibold text-sm transition-all duration-200 text-gray-500 hover:text-gray-700 flex items-center justify-center gap-2">
                            <i class="fas fa-pen"></i> Essay
                        </button>
                    </div>
                </div>
            </div>

            <!-- Kartu Informasi Dasar -->
            <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden shadow-sm mb-6 card-hover">
                <div class="border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white px-6 py-4">
                    <h2 class="font-bold text-[#2C3E50] flex items-center gap-2">
                        <i class="fas fa-info-circle text-[#3498DB]"></i>
                        Informasi Dasar Soal
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-600 mb-2">
                                <i class="fas fa-tag text-[#3498DB] mr-1"></i> Materi / Kompetensi Dasar
                            </label>
                            <input type="text" name="materi" 
                                   placeholder="Contoh: Teks Prosedur, Persamaan Kuadrat, dll" 
                                   class="w-full border border-gray-200 p-3 rounded-xl outline-none input-focus text-sm"
                                   required>
                            <p class="text-xs text-gray-400 mt-2">Masukkan pokok bahasan atau KD dari soal ini</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-600 mb-2">
                                <i class="fas fa-weight-hanging text-[#3498DB] mr-1"></i> Bobot Nilai
                            </label>
                            <input type="number" name="bobot" value="1" min="1" max="100"
                                   class="w-full border border-gray-200 p-3 rounded-xl outline-none input-focus text-sm"
                                   required>
                            <p class="text-xs text-gray-400 mt-2">Nilai yang diberikan jika soal dijawab benar</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kartu Pertanyaan -->
            <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden shadow-sm mb-6 card-hover">
                <div class="border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white px-6 py-4">
                    <h2 class="font-bold text-[#2C3E50] flex items-center gap-2">
                        <i class="fas fa-question-circle text-[#3498DB]"></i>
                        Pertanyaan
                    </h2>
                </div>
                <div class="p-6">
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-600 mb-2">Isi Pertanyaan</label>
                        <textarea name="pertanyaan" rows="5" 
                                  class="w-full border border-gray-200 p-4 rounded-xl outline-none input-focus text-sm"
                                  placeholder="Tuliskan soal/pertanyaan di sini..." 
                                  required></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-2">
                            <i class="fas fa-image text-[#3498DB] mr-1"></i> Lampiran Gambar (Opsional)
                        </label>
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="flex-1">
                                <input type="file" name="gambar" accept="image/*" 
                                       class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="text-xs text-gray-400 mt-1">Format: JPG, PNG, GIF (Maks. 2MB)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kartu Pilihan Jawaban (PG) - Tampil jika tipe PG -->
            <div id="pgSection" class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden shadow-sm mb-6 card-hover">
                <div class="border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white px-6 py-4">
                    <h2 class="font-bold text-[#2C3E50] flex items-center gap-2">
                        <i class="fas fa-list text-[#3498DB]"></i>
                        Pilihan Jawaban (Pilihan Ganda)
                    </h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php 
                        $opsiHuruf = ['A', 'B', 'C', 'D', 'E'];
                        $warnaOpsi = ['bg-red-100 text-red-600', 'bg-blue-100 text-blue-600', 'bg-green-100 text-green-600', 'bg-yellow-100 text-yellow-600', 'bg-purple-100 text-purple-600'];
                        foreach ($opsiHuruf as $index => $huruf): 
                        ?>
                        <div class="flex items-center gap-4 p-3 rounded-xl border border-gray-100 hover:border-gray-200 transition">
                            <div class="w-10 h-10 rounded-xl <?= $warnaOpsi[$index] ?> flex items-center justify-center font-bold text-sm flex-shrink-0">
                                <?= $huruf ?>
                            </div>
                            <div class="flex-1">
                                <input type="text" 
                                       name="opsi_<?= strtolower($huruf) ?>" 
                                       class="w-full border border-gray-200 p-3 rounded-xl outline-none input-focus text-sm pg-field"
                                       placeholder="Isi pilihan <?= $huruf ?>">
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer bg-gray-50 px-4 py-2 rounded-xl border border-gray-100 hover:bg-gray-100 transition flex-shrink-0">
                                <input type="radio" name="kunci_jawaban" value="<?= strtolower($huruf) ?>" class="w-4 h-4 text-[#3498DB] pg-radio">
                                <span class="text-xs font-semibold text-gray-600">Kunci</span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 p-4 bg-blue-50 rounded-xl border border-blue-100">
                        <p class="text-xs text-blue-600 flex items-center gap-2">
                            <i class="fas fa-info-circle"></i>
                            Pilih salah satu opsi sebagai kunci jawaban dengan mencentang radio button "Kunci"
                        </p>
                    </div>
                </div>
            </div>

            <!-- Kartu Essay - Tampil jika tipe Essay -->
            <div id="essaySection" class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden shadow-sm mb-6 card-hover hidden">
                <div class="border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white px-6 py-4">
                    <h2 class="font-bold text-[#2C3E50] flex items-center gap-2">
                        <i class="fas fa-pen text-[#3498DB]"></i>
                        Essay & Kunci Jawaban
                    </h2>
                </div>
                <div class="p-6">
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-600 mb-2">Petunjuk / Instruksi Essay</label>
                        <textarea name="petunjuk_essay" rows="3" 
                                  class="w-full border border-gray-200 p-4 rounded-xl outline-none input-focus text-sm essay-field"
                                  placeholder="Contoh: Jawablah pertanyaan berikut dengan jelas dan tepat!">Jawablah pertanyaan berikut dengan jelas dan tepat!</textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-2">
                            <i class="fas fa-key text-[#3498DB] mr-1"></i> Kunci Jawaban / Pedoman Penskoran
                        </label>
                        <textarea name="kunci_essay" rows="4" 
                                  class="w-full border border-gray-200 p-4 rounded-xl outline-none input-focus text-sm essay-field"
                                  placeholder="Tuliskan kunci jawaban atau poin-poin penting yang harus ada dalam jawaban siswa..."></textarea>
                        <p class="text-xs text-gray-400 mt-2">Untuk soal essay, kunci jawaban digunakan sebagai pedoman penskoran</p>
                    </div>

                    <div class="mt-4 p-4 bg-orange-50 rounded-xl border border-orange-100">
                        <p class="text-xs text-orange-600 flex items-center gap-2">
                            <i class="fas fa-lightbulb"></i>
                            Tips: Tuliskan poin-poin penting yang harus ada dalam jawaban siswa untuk memudahkan koreksi
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex items-center justify-end gap-4">
                <a href="soal_list.php?id_mapel=<?= $id_mapel ?>" 
                   class="px-8 py-3 text-gray-400 font-medium hover:text-gray-600 transition rounded-xl border border-gray-200 hover:border-gray-300">
                    Batal
                </a>
                <button type="submit" name="add_soal_manual" 
                        class="bg-[#3498DB] hover:bg-[#2980B9] text-white px-10 py-3 rounded-xl font-semibold transition-all duration-200 shadow-lg shadow-blue-200 flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    Simpan Soal Ke Bank Soal
                </button>
            </div>
        </form>

        <!-- Footer kecil -->
        <div class="text-center mt-8">
            <p class="text-xs text-gray-400">
                <i class="far fa-copyright mr-1"></i> SMKN 8 Bandar Lampung - Pusat Kendali
            </p>
        </div>
    </div>

    <script>
        // Fungsi untuk memilih tipe soal
        function pilihTipe(tipe) {
            const tabPG = document.getElementById('tabPG');
            const tabEssay = document.getElementById('tabEssay');
            const pgSection = document.getElementById('pgSection');
            const essaySection = document.getElementById('essaySection');
            const jenisSoal = document.getElementById('jenisSoal');
            
            // Ambil semua field yang diperlukan
            const pgFields = document.querySelectorAll('.pg-field');
            const pgRadios = document.querySelectorAll('.pg-radio');
            const essayFields = document.querySelectorAll('.essay-field');
            
            if (tipe === 'pg') {
                // Aktifkan tab PG
                tabPG.classList.add('tab-active');
                tabPG.classList.remove('text-gray-500', 'hover:text-gray-700');
                tabEssay.classList.remove('tab-active');
                tabEssay.classList.add('text-gray-500', 'hover:text-gray-700');
                
                // Tampilkan section PG, sembunyikan Essay
                pgSection.classList.remove('hidden');
                essaySection.classList.add('hidden');
                
                // Set jenis soal ke PG (1)
                jenisSoal.value = '1';
                
                // Nonaktifkan field essay, aktifkan field PG
                essayFields.forEach(field => {
                    field.removeAttribute('required');
                    field.disabled = true;
                });
                
                pgFields.forEach(field => {
                    field.setAttribute('required', 'required');
                    field.disabled = false;
                });
                
                pgRadios.forEach(radio => {
                    radio.setAttribute('required', 'required');
                    radio.disabled = false;
                });
                
            } else {
                // Aktifkan tab Essay
                tabEssay.classList.add('tab-active');
                tabEssay.classList.remove('text-gray-500', 'hover:text-gray-700');
                tabPG.classList.remove('tab-active');
                tabPG.classList.add('text-gray-500', 'hover:text-gray-700');
                
                // Tampilkan section Essay, sembunyikan PG
                essaySection.classList.remove('hidden');
                pgSection.classList.add('hidden');
                
                // Set jenis soal ke Essay (0)
                jenisSoal.value = '0';
                
                // Nonaktifkan field PG, aktifkan field essay
                pgFields.forEach(field => {
                    field.removeAttribute('required');
                    field.disabled = true;
                });
                
                pgRadios.forEach(radio => {
                    radio.removeAttribute('required');
                    radio.disabled = true;
                });
                
                essayFields.forEach(field => {
                    field.setAttribute('required', 'required');
                    field.disabled = false;
                });
            }
        }

        // Validasi form sebelum submit
        document.getElementById('formSoal').addEventListener('submit', function(e) {
            const jenisSoal = document.getElementById('jenisSoal').value;
            
            if (jenisSoal === '1') { // PG
                // Cek apakah kunci jawaban dipilih
                const kunciTerpilih = document.querySelector('input[name="kunci_jawaban"]:checked');
                if (!kunciTerpilih) {
                    e.preventDefault();
                    alert('Silakan pilih kunci jawaban untuk soal Pilihan Ganda!');
                    return false;
                }
                
                // Cek apakah semua opsi terisi
                const pgFields = document.querySelectorAll('.pg-field');
                let semuaTerisi = true;
                pgFields.forEach(field => {
                    if (!field.value.trim()) {
                        semuaTerisi = false;
                    }
                });
                
                if (!semuaTerisi) {
                    e.preventDefault();
                    alert('Semua pilihan jawaban (A-E) harus diisi!');
                    return false;
                }
            } else { // Essay
                const kunciEssay = document.querySelector('textarea[name="kunci_essay"]');
                if (!kunciEssay.value.trim()) {
                    e.preventDefault();
                    alert('Kunci jawaban essay harus diisi!');
                    return false;
                }
            }
        });

        // Preview gambar sebelum upload
        document.querySelector('input[name="gambar"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran file maksimal 2MB!');
                    this.value = '';
                }
            }
        });
    </script>
</body>

</html>