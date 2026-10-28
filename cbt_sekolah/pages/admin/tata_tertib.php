<?php
// File: tata_tertib.php
require_once '../../config/koneksi.php';

// Proses simpan perubahan (jika ada POST dari form edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan'])) {
    $judul = $_POST['judul'];
    $deskripsi = $_POST['deskripsi'];
    $kategori = isset($_POST['kategori']) ? $_POST['kategori'] : 'Ujian';
    $status = isset($_POST['status']) ? $_POST['status'] : 'Aktif';
    
    try {
        // Cek apakah sudah ada data
        $check = $pdo->query("SELECT COUNT(*) FROM tb_tata_tertib")->fetchColumn();
        
        if ($check > 0) {
            // Update data yang ada (ambil id pertama/terakhir)
            $sql = "UPDATE tb_tata_tertib 
                    SET judul = :judul, 
                        deskripsi = :deskripsi, 
                        kategori = :kategori, 
                        status = :status, 
                        updated_at = NOW() 
                    WHERE id = (SELECT id FROM (SELECT id FROM tb_tata_tertib LIMIT 1) AS temp)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':judul' => $judul,
                ':deskripsi' => $deskripsi,
                ':kategori' => $kategori,
                ':status' => $status
            ]);
        } else {
            // Insert data baru
            $sql = "INSERT INTO tb_tata_tertib (judul, deskripsi, kategori, status, created_at) 
                    VALUES (:judul, :deskripsi, :kategori, :status, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':judul' => $judul,
                ':deskripsi' => $deskripsi,
                ':kategori' => $kategori,
                ':status' => $status
            ]);
        }
        
        echo "<script>
            alert('Data tata tertib berhasil disimpan!');
            window.location.href='?page=tata_tertib';
        </script>";
        exit;
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil data tata tertib dari database
try {
    // Ambil data terbaru (atau satu-satunya data)
    $stmt = $pdo->query("SELECT * FROM tb_tata_tertib ORDER BY id DESC LIMIT 1");
    $tata_tertib = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Jika belum ada data, buat default
    if (!$tata_tertib) {
        $default_isi = "1. Peserta Sumatif Tengah Semester (STS) Genap memasuki ruangan 15 menit sebelum pelaksanaan ujian.\n2. Peserta ujian membawa Nomor Peserta Sumatif Tengah Semester (STS) Genap.\n3. Peserta ujian Login dengan menggunakan User yang tertera dikartu Peserta.\n4. Peserta ujian:\n   - Dilarang Membuka Tab lain selain PIJAR.\n   - Nonaktifkan Mode Sleep.\n   - Nonaktifkan Notif SMS.\n   - Hindari menyentuh tombol kembali.\n   - 3 kali keluar dari PIJAR/terblokir, tidak dapat melanjutkan ujian.\n5. Peserta ujian mengisi daftar hadir menggunakan Bolpoint.\n6. Bagi peserta ujian yang tidak memiliki Handphone dapat mengerjakan di Ruang Guru.\n7. Selama Sumatif Tengah Semester (STS) Genap berlangsung, peserta hanya dapat meninggalkan ruangan dengan izin dan pengawasan dari pengawas ruang.\n8. Peserta STS Genap dapat menyampaikan kepihak panitia terkait kendala teknis.\n9. Peserta STS Genap yang telah selesai mengerjakan soal ujian sebelum waktu STS berakhir tidak diperbolehkan meninggalkan ruangan sebelum berakhirnya waktu ujian.\n10. Peserta ujian yang tidak hadir, bisa mengikuti susulan dengan menghubungi guru mata pelajaran masing-masing.";
        
        // Insert default data
        $insert = $pdo->prepare("INSERT INTO tb_tata_tertib (judul, deskripsi, kategori, status, created_at) 
                                 VALUES (:judul, :deskripsi, :kategori, :status, NOW())");
        $insert->execute([
            ':judul' => 'Tata Tertib STS Genap 2025/2026',
            ':deskripsi' => $default_isi,
            ':kategori' => 'Ujian',
            ':status' => 'Aktif'
        ]);
        
        // Ambil kembali data yang baru diinsert
        $stmt = $pdo->query("SELECT * FROM tb_tata_tertib ORDER BY id DESC LIMIT 1");
        $tata_tertib = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("<div style='background:#fee; border:1px solid #f99; color:#c00; padding:15px; margin:15px; border-radius:5px;'>
            <strong>Error Database:</strong> " . $e->getMessage() . "
         </div>");
}

// Pastikan $tata_tertib tidak null
if (!$tata_tertib) {
    $tata_tertib = [
        'id' => 0,
        'judul' => 'Tata Tertib STS Genap 2025/2026',
        'deskripsi' => "1. Peserta Sumatif Tengah Semester (STS) Genap memasuki ruangan 15 menit sebelum pelaksanaan ujian.\n2. Peserta ujian membawa Nomor Peserta Sumatif Tengah Semester (STS) Genap.\n3. Peserta ujian Login dengan menggunakan User yang tertera dikartu Peserta.\n4. Peserta ujian:\n   - Dilarang Membuka Tab lain selain PIJAR.\n   - Nonaktifkan Mode Sleep.\n   - Nonaktifkan Notif SMS.\n   - Hindari menyentuh tombol kembali.\n   - 3 kali keluar dari PIJAR/terblokir, tidak dapat melanjutkan ujian.\n5. Peserta ujian mengisi daftar hadir menggunakan Bolpoint.\n6. Bagi peserta ujian yang tidak memiliki Handphone dapat mengerjakan di Ruang Guru.\n7. Selama Sumatif Tengah Semester (STS) Genap berlangsung, peserta hanya dapat meninggalkan ruangan dengan izin dan pengawasan dari pengawas ruang.\n8. Peserta STS Genap dapat menyampaikan kepihak panitia terkait kendala teknis.\n9. Peserta STS Genap yang telah selesai mengerjakan soal ujian sebelum waktu STS berakhir tidak diperbolehkan meninggalkan ruangan sebelum berakhirnya waktu ujian.\n10. Peserta ujian yang tidak hadir, bisa mengikuti susulan dengan menghubungi guru mata pelajaran masing-masing.",
        'kategori' => 'Ujian',
        'status' => 'Aktif'
    ];
}

// Hapus nomor dari string untuk tampilan
$isi_bersih = preg_replace('/^\d+\.\s*/m', '', $tata_tertib['deskripsi']);
$isi_list = explode("\n", $isi_bersih);
?>

<style>
/* TATA TERTIB CARD */
.tata-tertib-container {
    max-width: 1000px;
    margin: 0 auto;
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid #e5e7eb;
    padding: 50px;
    position: relative;
}

/* KOP SURAT DENGAN LOGO KIRI KANAN */
.kop-surat {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 3px double #1e293b;
}

.logo-kiri, .logo-kanan {
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-kiri img, .logo-kanan img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.teks-kop {
    flex: 1;
    text-align: center;
    padding: 0 20px;
}

.teks-kop h2 {
    font-size: 20px;
    font-weight: 800;
    color: #1e293b;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0 0 5px 0;
}

.teks-kop h3 {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    margin: 5px 0;
}

.teks-kop .alamat {
    font-size: 14px;
    color: #4b5563;
    margin: 5px 0;
}

.teks-kop .kontak {
    font-size: 13px;
    color: #6b7280;
}

/* JUDUL DOKUMEN - RATA TENGAH */
.judul-dokumen {
    text-align: center;
    margin: 30px 0 40px;
}

.judul-dokumen h1 {
    font-size: 28px;
    font-weight: 800;
    color: #1e293b;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 5px;
}

.judul-dokumen h2 {
    font-size: 22px;
    font-weight: 700;
    color: #2d3748;
    margin: 5px 0;
}

.judul-dokumen .tahun {
    font-size: 16px;
    color: #6b7280;
    font-style: italic;
}

/* ISI TATA TERTIB */
.isi-container {
    margin: 40px 0;
    padding: 0 20px;
}

.poin-utama {
    list-style-type: decimal;
    padding-left: 25px;
}

.poin-utama li {
    margin-bottom: 15px;
    font-size: 15px;
    line-height: 1.6;
    color: #2d3748;
    text-align: justify;
}

.poin-cabang {
    list-style-type: disc;
    margin-left: 30px;
    margin-top: 5px;
    margin-bottom: 10px;
}

.poin-cabang li {
    margin-bottom: 5px;
    color: #4a5568;
    font-size: 14px;
}

/* TANDA TANGAN */
.ttd-container {
    margin-top: 60px;
    display: flex;
    justify-content: flex-end;
}

.ttd-box {
    text-align: center;
    width: 250px;
}

.ttd-box .jabatan {
    font-size: 14px;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 40px;
}

.ttd-box .nama {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    min-height: 30px;
    margin-top: 5px;
}

.ttd-box .nip {
    font-size: 13px;
    color: #6b7280;
    min-height: 25px;
    margin-top: 5px;
}

/* BUTTON ACTION */
.action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-bottom: 20px;
}

.btn-edit {
    background: #3b82f6;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-edit:hover {
    background: #2563eb;
}

.btn-print {
    background: #10b981;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-print:hover {
    background: #059669;
}

/* MODAL STYLES */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 50px auto;
    padding: 30px;
    border-radius: 8px;
    width: 90%;
    max-width: 700px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e5e7eb;
}

.modal-header h3 {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
}

.close-modal {
    font-size: 24px;
    font-weight: bold;
    color: #6b7280;
    cursor: pointer;
}

.close-modal:hover {
    color: #1e293b;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    ring: 2px solid #93c5fd;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e5e7eb;
}

.btn-batal {
    background-color: #6b7280;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
}

.btn-batal:hover {
    background-color: #4b5563;
}

.btn-simpan {
    background-color: #3b82f6;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
}

.btn-simpan:hover {
    background-color: #2563eb;
}

/* PRINT STYLES */
@media print {
    .action-buttons, .modal, .btn-print, .btn-edit {
        display: none !important;
    }
    .tata-tertib-container {
        box-shadow: none;
        border: none;
        padding: 20px;
    }
    
    /* Pastikan logo tercetak dengan baik */
    .logo-kiri img, .logo-kanan img {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
}
</style>

<!-- ACTION BUTTONS -->
<div class="action-buttons">
    <button onclick="bukaModalEdit()" class="btn-edit">
        <i class="fas fa-edit"></i> Edit Tata Tertib
    </button>
    <button onclick="window.print()" class="btn-print">
        <i class="fas fa-print"></i> Print / PDF
    </button>
</div>

<!-- TATA TERTIB CONTAINER -->
<div class="tata-tertib-container">
    <!-- KOP SURAT DENGAN LOGO KIRI KANAN -->
    <div class="kop-surat">
        <!-- Logo Kiri (LPNG) -->
        <div class="logo-kiri">
            <img src="../../assets/img/lpng.jpg" alt="Logo Provinsi Lampung" onerror="this.src='https://via.placeholder.com/100?text=Logo+Lampung'">
        </div>
        
        <!-- Teks Kop Surat -->
        <div class="teks-kop">
            <h2>PEMERINTAH PROVINSI LAMPUNG</h2>
            <h3>SMK NEGERI 8 BANDAR LAMPUNG</h3>
            <div class="alamat">
                Jl. Imam Bonjol No. 52 Kemiling Bandar Lampung, Lampung 35159
            </div>
            <div class="kontak">
                Telp: 0721 – 340 3709 | Website: smkn8bl.sch.id | E-mail: smkn8bl@yahoo.co.id
            </div>
        </div>
        
        <!-- Logo Kanan (Logo Sekolah) -->
        <div class="logo-kanan">
            <img src="../../assets/img/logo_sekolah.png" alt="Logo SMK N 8 Bandar Lampung" onerror="this.src='https://via.placeholder.com/100?text=Logo+Sekolah'">
        </div>
    </div>

    <!-- JUDUL DOKUMEN -->
    <div class="judul-dokumen">
        <h1>TATA TERTIB</h1>
        <h2>PELAKSANAAN SUMATIF TENGAH SEMESTER (STS) GENAP</h2>
        <div class="tahun">Tahun Pelajaran 2025/2026</div>
    </div>

    <!-- ISI TATA TERTIB -->
    <div class="isi-container">
        <ol class="poin-utama" start="1">
            <?php 
            $no = 1;
            $in_sub = false;
            foreach($isi_list as $item): 
                $item = trim($item);
                if(!empty($item)):
                    // Cek apakah ini sub-point (diawali dengan -)
                    if(strpos($item, '-') === 0):
                        // Ini adalah sub-point
                        if (!$in_sub) {
                            echo '<ul class="poin-cabang">';
                            $in_sub = true;
                        }
                        echo '<li>' . htmlspecialchars(substr($item, 1)) . '</li>';
                    else:
                        // Ini adalah poin utama
                        if ($in_sub) {
                            echo '</ul>';
                            $in_sub = false;
                        }
                        echo '<li value="' . $no . '">' . htmlspecialchars($item) . '</li>';
                        $no++;
                    endif;
                endif;
            endforeach; 
            if ($in_sub) echo '</ul>';
            ?>
        </ol>
    </div>

    <!-- TANDA TANGAN -->
    <div class="ttd-container">
        <div class="ttd-box">
            <div class="jabatan">PANITIA STS GENAP</div>
            <div class="nama"></div>
            <div class="nip"></div>
        </div>
    </div>
</div>

<!-- MODAL EDIT TATA TERTIB -->
<div id="modalEdit" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Tata Tertib</h3>
            <span class="close-modal" onclick="tutupModal()">&times;</span>
        </div>
        <form method="POST">
            <div class="form-group">
                <label>Judul</label>
                <input type="text" name="judul" value="<?= htmlspecialchars($tata_tertib['judul'] ?? 'Tata Tertib STS Genap 2025/2026') ?>" required>
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <select name="kategori">
                    <option value="Siswa" <?= ($tata_tertib['kategori'] ?? '') == 'Siswa' ? 'selected' : '' ?>>Siswa</option>
                    <option value="Guru" <?= ($tata_tertib['kategori'] ?? '') == 'Guru' ? 'selected' : '' ?>>Guru</option>
                    <option value="Ujian" <?= ($tata_tertib['kategori'] ?? 'Ujian') == 'Ujian' ? 'selected' : '' ?>>Ujian</option>
                    <option value="Umum" <?= ($tata_tertib['kategori'] ?? '') == 'Umum' ? 'selected' : '' ?>>Umum</option>
                </select>
            </div>
            <div class="form-group">
                <label>Isi Tata Tertib</label>
                <textarea name="deskripsi" rows="15" required><?= htmlspecialchars($tata_tertib['deskripsi'] ?? '') ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Gunakan nomor 1., 2., 3. untuk poin utama. Gunakan tanda - untuk sub-point.</p>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="Aktif" <?= ($tata_tertib['status'] ?? 'Aktif') == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="Nonaktif" <?= ($tata_tertib['status'] ?? '') == 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="tutupModal()" class="btn-batal">Batal</button>
                <button type="submit" name="simpan" class="btn-simpan">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModalEdit() {
    document.getElementById('modalEdit').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function tutupModal() {
    document.getElementById('modalEdit').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Tutup modal jika klik di luar modal
window.onclick = function(event) {
    var modal = document.getElementById('modalEdit');
    if (event.target == modal) {
        tutupModal();
    }
}

// Tutup modal dengan tombol ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        var modal = document.getElementById('modalEdit');
        if (modal.style.display === 'block') {
            tutupModal();
        }
    }
});
</script>