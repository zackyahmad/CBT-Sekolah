<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../../index.php"); exit;
}
require_once '../../config/koneksi.php';

$id_guru  = $_SESSION['id_user'] ?? 0;
$nama_guru = $_SESSION['nama_lengkap'] ?? 'Guru';
$id_ujian = isset($_GET['ujian_id']) ? (int)$_GET['ujian_id'] : 0;

// 1. Ambil daftar ujian milik guru ini
$stmt_list = $pdo->prepare("SELECT id_ujian, judul_ujian, status_ujian FROM tb_ujian WHERE id_guru = ? ORDER BY tanggal_ujian DESC, id_ujian DESC");
$stmt_list->execute([$id_guru]);
$ujian_list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

if (!$id_ujian && count($ujian_list) > 0) {
    $id_ujian = $ujian_list[0]['id_ujian'];
}

$detail_ujian = null;
$rows         = [];
$cnt          = ['total'=>0, 'belum'=>0, 'selesai'=>0];
$kelas_unik   = []; // Array untuk menyimpan daftar kelas yang spesifik

if ($id_ujian) {
    // 2. Info Detail Ujian
    $stmt_detail = $pdo->prepare("
        SELECT u.*, m.nama_mapel, GROUP_CONCAT(k.nama_kelas SEPARATOR ', ') as kelas_list
        FROM tb_ujian u
        LEFT JOIN tb_mapel m ON u.id_mapel = m.id_mapel
        LEFT JOIN tb_ujian_kelas uk ON u.id_ujian = uk.id_ujian
        LEFT JOIN tb_kelas k ON uk.id_kelas = k.id_kelas
        WHERE u.id_ujian = ? AND u.id_guru = ? GROUP BY u.id_ujian
    ");
    $stmt_detail->execute([$id_ujian, $id_guru]);
    $detail_ujian = $stmt_detail->fetch(PDO::FETCH_ASSOC);

    if ($detail_ujian) {
        // 3. Ambil data Siswa BERDASARKAN KELAS ujian tersebut + Data Hasil
        $stmt_siswa = $pdo->prepare("
            SELECT u.id_user as id, u.nama_lengkap as nama, u.nis, k.nama_kelas as kelas,
                   h.id_hasil, h.nilai, h.waktu_selesai
            FROM tb_ujian_kelas uk
            JOIN tb_users u ON uk.id_kelas = u.id_kelas AND u.role = 'siswa'
            LEFT JOIN tb_kelas k ON u.id_kelas = k.id_kelas
            LEFT JOIN tb_hasil_ujian h ON h.id_user = u.id_user AND h.id_ujian = uk.id_ujian
            WHERE uk.id_ujian = ?
            ORDER BY k.nama_kelas ASC, u.nama_lengkap ASC
        ");
        $stmt_siswa->execute([$id_ujian]);
        
        while ($r = $stmt_siswa->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $r;
            $cnt['total']++;
            if (!empty($r['id_hasil'])) {
                $cnt['selesai']++;
            } else {
                $cnt['belum']++;
            }
            
            // Kumpulkan nama kelas untuk filter dropdown
            if (!empty($r['kelas']) && !in_array($r['kelas'], $kelas_unik)) {
                $kelas_unik[] = $r['kelas'];
            }
        }
        sort($kelas_unik); // Urutkan nama kelas sesuai abjad
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Penilaian & Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta http-equiv="refresh" content="30">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
</head>
<body class="bg-slate-50 min-h-screen p-6">
<div class="max-w-7xl mx-auto">
    
    <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-800"><i class="fas fa-desktop text-blue-600 mr-2"></i> Monitoring Ujian</h1>
            <p class="text-sm text-slate-500">Otomatis muat ulang tiap 30 detik</p>
        </div>
        <a href="rencana_ujian.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl font-bold transition">Kembali</a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-6">
        <form method="GET" class="flex flex-wrap gap-3 items-end justify-between">
            <div class="flex gap-3 items-end w-full md:w-auto">
                <div class="flex-1 md:w-80">
                    <label class="block text-xs font-bold text-slate-500 mb-2 uppercase">Pilih Ujian</label>
                    <select name="ujian_id" onchange="this.form.submit()" class="w-full py-2.5 px-3 border border-slate-200 rounded-xl outline-none font-medium text-slate-700 bg-slate-50">
                        <?php if (count($ujian_list) > 0): foreach ($ujian_list as $u): ?>
                        <option value="<?= $u['id_ujian'] ?>" <?= $u['id_ujian']==$id_ujian ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['judul_ujian']) ?>
                        </option>
                        <?php endforeach; else: ?>
                        <option>Belum ada ujian</option>
                        <?php endif; ?>
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold transition"><i class="fas fa-sync-alt"></i></button>
            </div>
            
            <?php if ($detail_ujian && $cnt['selesai'] > 0): ?>
            <div class="flex gap-2 items-center bg-slate-50 p-2 border border-slate-200 rounded-xl">
                <select id="pilih_kelas_pdf" class="py-1.5 px-3 border border-slate-300 rounded-lg outline-none text-sm font-medium text-slate-700 bg-white">
                    <option value="semua">-- Semua Kelas --</option>
                    <?php foreach($kelas_unik as $ku): ?>
                        <option value="<?= htmlspecialchars($ku) ?>"><?= htmlspecialchars($ku) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" onclick="cetakPDFKelas()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-1.5 rounded-lg text-sm font-bold flex items-center gap-2 transition shadow-sm">
                    <i class="fas fa-file-pdf"></i> Download Rekap
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($detail_ujian): ?>
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 text-center"><p class="text-xs text-slate-500 font-bold">Total Siswa</p><h3 class="text-2xl font-black text-slate-800"><?= $cnt['total'] ?></h3></div>
        <div class="bg-emerald-50 p-5 rounded-xl shadow-sm border border-emerald-100 text-center"><p class="text-xs text-emerald-600 font-bold">Selesai Mengerjakan</p><h3 class="text-2xl font-black text-emerald-700"><?= $cnt['selesai'] ?></h3></div>
        <div class="bg-amber-50 p-5 rounded-xl shadow-sm border border-amber-100 text-center"><p class="text-xs text-amber-600 font-bold">Belum Mengerjakan</p><h3 class="text-2xl font-black text-amber-700"><?= $cnt['belum'] ?></h3></div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 uppercase">
                <tr>
                    <th class="px-6 py-4">Nama Siswa</th>
                    <th class="px-6 py-4 text-center">Kelas</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-center">Nilai Akhir</th>
                    <th class="px-6 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($rows as $r): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4 font-bold text-slate-800"><?= htmlspecialchars($r['nama']) ?></td>
                    <td class="px-6 py-4 text-center"><span class="bg-slate-100 px-3 py-1 rounded-lg text-xs font-bold"><?= htmlspecialchars($r['kelas']) ?></span></td>
                    <td class="px-6 py-4 text-center">
                        <?php if(!empty($r['id_hasil'])): ?>
                            <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-[10px] font-bold">SELESAI</span>
                            <div class="text-[10px] text-slate-400 mt-1"><?= date('H:i', strtotime($r['waktu_selesai'])) ?> WIB</div>
                        <?php else: ?>
                            <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-[10px] font-bold">BELUM</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if(!empty($r['id_hasil'])): ?>
                            <span class="font-black text-lg <?= $r['nilai'] >= 75 ? 'text-emerald-600' : 'text-red-500' ?>"><?= round($r['nilai']) ?></span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if(!empty($r['id_hasil'])): ?>
                            <div class="flex justify-center gap-2">
                                <a href="koreksi_ujian.php?id_hasil=<?= $r['id_hasil'] ?>&ujian_id=<?= $id_ujian ?>" class="bg-blue-100 text-blue-700 hover:bg-blue-600 hover:text-white px-3 py-2 rounded-xl text-xs font-bold transition" title="Koreksi Jawaban">
                                    <i class="fas fa-search"></i>
                                </a>
                                <button onclick="cetakPDFSiswa('<?= addslashes($r['nama']) ?>', '<?= addslashes($r['nis'] ?? '') ?>', '<?= addslashes($r['kelas']) ?>', '<?= round($r['nilai']) ?>')" class="bg-red-100 text-red-700 hover:bg-red-600 hover:text-white px-3 py-2 rounded-xl text-xs font-bold transition" title="Download Nilai Siswa">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                            </div>
                        <?php else: ?>
                            <button disabled class="bg-slate-100 text-slate-400 px-4 py-2 rounded-xl text-xs font-bold">Menunggu</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php if ($detail_ujian): ?>
<script>
    // 1. Fungsi Export PDF Per Kelas / Filter Kelas
    function cetakPDFKelas() {
        // Ambil nilai filter yang dipilih
        const selectedKelas = document.getElementById('pilih_kelas_pdf').value;
        
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        doc.setFontSize(16);
        doc.setFont(undefined, 'bold');
        doc.text('REKAPITULASI NILAI UJIAN', 105, 20, { align: 'center' });
        
        // Tentukan tulisan Kelas di Kop PDF (semua kelas atau kelas spesifik)
        let displayKelas = selectedKelas === 'semua' ? '<?= addslashes($detail_ujian['kelas_list']) ?>' : selectedKelas;

        doc.setFontSize(10);
        doc.setFont(undefined, 'normal');
        doc.text('Mata Pelajaran', 14, 32); doc.text(': <?= addslashes($detail_ujian['nama_mapel']) ?>', 45, 32);
        doc.text('Judul Ujian', 14, 38); doc.text(': <?= addslashes($detail_ujian['judul_ujian']) ?>', 45, 38);
        doc.text('Kelas', 14, 44); doc.text(': ' + displayKelas, 45, 44);
        doc.text('Guru Pengampu', 14, 50); doc.text(': <?= addslashes($nama_guru) ?>', 45, 50);

        // Ambil SEMUA data mentah dari PHP ke Object Javascript
        const allData = [
            <?php foreach($rows as $r): ?>
            {
                nama: '<?= addslashes($r['nama']) ?>',
                nis: '<?= addslashes($r['nis'] ?? '-') ?>',
                kelas: '<?= addslashes($r['kelas']) ?>',
                status: '<?= !empty($r['id_hasil']) ? "Selesai" : "Belum" ?>',
                nilai: '<?= !empty($r['id_hasil']) ? round($r['nilai']) : "-" ?>'
            },
            <?php endforeach; ?>
        ];

        // Saring data berdasarkan Dropdown (jika tidak pilih "semua")
        let filteredData = allData;
        if (selectedKelas !== 'semua') {
            filteredData = allData.filter(item => item.kelas === selectedKelas);
        }

        // Cek jika kosong
        if (filteredData.length === 0) {
            alert('Tidak ada data siswa untuk kelas ' + selectedKelas);
            return;
        }

        // Ubah struktur data menjadi array of array untuk library tabel PDF
        let finalData = [];
        let no = 1;
        filteredData.forEach(item => {
            finalData.push([no++, item.nama, item.nis, item.kelas, item.status, item.nilai]);
        });

        const headers = [['No', 'Nama Siswa', 'NIS', 'Kelas', 'Status', 'Nilai Akhir']];

        // Cetak Tabel
        doc.autoTable({
            head: headers,
            body: finalData,
            startY: 55,
            theme: 'grid',
            headStyles: { fillColor: [43, 58, 103] }, 
            styles: { fontSize: 9 }
        });

        // Simpan File dengan nama dinamis
        let fileName = 'Rekap_Nilai_' + '<?= addslashes($detail_ujian['judul_ujian']) ?>';
        if (selectedKelas !== 'semua') fileName += '_' + selectedKelas;
        doc.save(fileName.replace(/\s+/g, '_') + '.pdf');
    }

    // 2. Fungsi Export PDF Per Siswa (Raport Kecil A5)
    function cetakPDFSiswa(nama, nis, kelas, nilai) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a5' });
        
        doc.setLineWidth(0.5); doc.rect(5, 5, 138, 200);

        doc.setFontSize(14); doc.setFont(undefined, 'bold');
        doc.text('HASIL NILAI UJIAN', 74, 20, { align: 'center' });
        doc.setFontSize(10); doc.setFont(undefined, 'normal');
        doc.text('SMK NEGERI 8 BANDAR LAMPUNG', 74, 26, { align: 'center' });
        
        doc.setLineWidth(0.3); doc.line(10, 32, 138, 32);

        doc.setFontSize(10);
        doc.text('Mata Pelajaran', 10, 42); doc.text(':', 35, 42);
        doc.setFont(undefined, 'bold');
        doc.text('<?= addslashes($detail_ujian['nama_mapel']) ?>', 40, 42);
        
        doc.setFont(undefined, 'normal');
        doc.text('Ujian', 10, 48); doc.text(':', 35, 48);
        doc.text('<?= addslashes($detail_ujian['judul_ujian']) ?>', 40, 48);

        doc.line(10, 54, 138, 54);

        doc.text('Nama Siswa', 10, 65); doc.text(':', 35, 65);
        doc.setFont(undefined, 'bold');
        doc.text(nama, 40, 65);
        
        doc.setFont(undefined, 'normal');
        doc.text('NIS', 10, 72); doc.text(':', 35, 72);
        doc.text(nis ? nis : '-', 40, 72);

        doc.text('Kelas', 10, 79); doc.text(':', 35, 79);
        doc.text(kelas, 40, 79);

        doc.setFillColor(240, 248, 255);
        doc.rect(30, 95, 88, 30, 'FD');
        
        doc.setFontSize(12); doc.text('NILAI AKHIR', 74, 105, { align: 'center' });
        doc.setFontSize(24); doc.setFont(undefined, 'bold');
        
        if (parseInt(nilai) >= 75) { doc.setTextColor(22, 163, 74); } else { doc.setTextColor(220, 38, 38); }
        doc.text(nilai.toString(), 74, 118, { align: 'center' });
        
        doc.setTextColor(0,0,0); doc.setFontSize(10); doc.setFont(undefined, 'normal');
        
        let tgl = new Date().toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'});
        doc.text('Bandar Lampung, ' + tgl, 85, 150);
        doc.text('Guru Mata Pelajaran,', 85, 155);
        doc.setFont(undefined, 'bold');
        doc.text('<?= addslashes($nama_guru) ?>', 85, 180);

        let nama_file = nama.replace(/\s+/g, '_');
        doc.save('Nilai_' + nama_file + '.pdf');
    }
</script>
<?php endif; ?>

</body>
</html>