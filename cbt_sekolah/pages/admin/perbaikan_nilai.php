<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/koneksi.php';

$conn = new mysqli("localhost", "root", "", "db_ujian_sekolah");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// =============================================
// FILTER PARAMETER
// =============================================
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$mapel_id = isset($_GET['mapel_id']) ? (int)$_GET['mapel_id'] : 0;
$ujian_id = isset($_GET['ujian_id']) ? (int)$_GET['ujian_id'] : 0;

// =============================================
// PROSES HAPUS NILAI
// =============================================
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $del = $conn->prepare("DELETE FROM tb_nilai WHERE id_nilai = ?");
    $del->bind_param("i", $del_id);
    if ($del->execute()) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Data nilai berhasil dihapus!'];
    }
    header("Location: perbaikan_nilai.php?ujian_id=$ujian_id&kelas_id=$kelas_id&mapel_id=$mapel_id");
    exit;
}

// =============================================
// PROSES EDIT NILAI
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_nilai'])) {
    $id_nilai = (int)$_POST['id_nilai'];
    $nilai_baru = (float)$_POST['nilai'];
    
    // Update total_nilai dan nilai_pg sekaligus
    $update = $conn->prepare("UPDATE tb_nilai SET total_nilai = ?, nilai_pg = ? WHERE id_nilai = ?");
    $update->bind_param("ddi", $nilai_baru, $nilai_baru, $id_nilai);
    
    if ($update->execute()) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Nilai berhasil diperbarui!'];
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal memperbarui nilai!'];
    }
    header("Location: perbaikan_nilai.php?ujian_id=$ujian_id&kelas_id=$kelas_id&mapel_id=$mapel_id");
    exit;
}

// =============================================
// PROSES TAMBAH NILAI BARU
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_nilai'])) {
    $ujian_id_post = (int)$_POST['ujian_id'];
    $siswa_id_post = (int)$_POST['siswa_id'];
    $nilai_baru = (float)$_POST['nilai'];
    
    if ($nilai_baru < 0 || $nilai_baru > 100) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Nilai harus antara 0-100!'];
    } else {
        // Cek apakah siswa sudah punya nilai di ujian ini
        $cek = $conn->prepare("SELECT id_nilai FROM tb_nilai WHERE id_ujian = ? AND id_siswa = ?");
        $cek->bind_param("ii", $ujian_id_post, $siswa_id_post);
        $cek->execute();
        $res_cek = $cek->get_result();
        
        if ($res_cek->num_rows > 0) {
            // Jika sudah ada, langsung Update nilainya
            $row = $res_cek->fetch_assoc();
            $update = $conn->prepare("UPDATE tb_nilai SET total_nilai = ?, nilai_pg = ? WHERE id_nilai = ?");
            $update->bind_param("ddi", $nilai_baru, $nilai_baru, $row['id_nilai']);
            $update->execute();
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Siswa sudah memiliki riwayat, nilai berhasil ditimpa!'];
        } else {
            // Jika belum ada, Insert data baru
            $insert = $conn->prepare("
                INSERT INTO tb_nilai (id_ujian, id_siswa, total_nilai, nilai_pg, status_pengerjaan, waktu_mulai, waktu_selesai)
                VALUES (?, ?, ?, ?, 'selesai', NOW(), NOW())
            ");
            $insert->bind_param("iidd", $ujian_id_post, $siswa_id_post, $nilai_baru, $nilai_baru);
            if ($insert->execute()) {
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Nilai berhasil ditambahkan!'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal menambahkan nilai! Pastikan ujian aktif.'];
            }
        }
    }
    header("Location: perbaikan_nilai.php?ujian_id=$ujian_id_post&kelas_id=$kelas_id&mapel_id=$mapel_id");
    exit;
}

// =============================================
// AMBIL DATA FILTER (KELAS, MAPEL, UJIAN)
// =============================================
$list_kelas = $conn->query("SELECT id_kelas, nama_kelas FROM tb_kelas ORDER BY nama_kelas ASC");
$list_mapel = $conn->query("SELECT id_mapel, nama_mapel FROM tb_mapel ORDER BY nama_mapel ASC");

$list_ujian = [];
if ($mapel_id > 0) {
    $stmt_u = $conn->prepare("SELECT id_ujian, judul_ujian FROM tb_ujian WHERE id_mapel = ? ORDER BY id_ujian DESC");
    $stmt_u->bind_param("i", $mapel_id);
    $stmt_u->execute();
    $res_u = $stmt_u->get_result();
    while($u = $res_u->fetch_assoc()) $list_ujian[] = $u;
} else {
    $res_u = $conn->query("SELECT id_ujian, judul_ujian FROM tb_ujian ORDER BY id_ujian DESC");
    while($u = $res_u->fetch_assoc()) $list_ujian[] = $u;
}

// =============================================
// AMBIL DATA SISWA DAN NILAI
// =============================================
$data_siswa = [];
if ($kelas_id > 0 && $ujian_id > 0) {
    // Menggunakan n.total_nilai as nilai agar kompatibel dengan tabel di HTML
    $stmt_siswa = $conn->prepare("
        SELECT s.id_user, s.nama_lengkap, s.nis, 
               n.id_nilai, n.total_nilai as nilai, n.status_pengerjaan as status
        FROM tb_users s
        LEFT JOIN tb_nilai n ON s.id_user = n.id_siswa AND n.id_ujian = ?
        WHERE s.id_kelas = ? AND s.role = 'siswa'
        ORDER BY s.nama_lengkap ASC
    ");
    $stmt_siswa->bind_param("ii", $ujian_id, $kelas_id);
    $stmt_siswa->execute();
    $res_siswa = $stmt_siswa->get_result();
    while($row = $res_siswa->fetch_assoc()) {
        $data_siswa[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbaikan Nilai - Admin CBT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto p-6">
        
        <div class="flex items-center gap-4 mb-6">
            <a href="dashboard.php" class="bg-white text-slate-600 hover:bg-slate-100 px-4 py-2 rounded-lg font-medium shadow-sm border border-slate-200 transition">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
            <h1 class="text-2xl font-bold text-slate-800">Manajemen & Perbaikan Nilai</h1>
        </div>

        <?php if (isset($_SESSION['flash'])): ?>
            <script>
                Swal.fire({
                    icon: '<?= $_SESSION['flash']['type'] === 'success' ? 'success' : 'error' ?>',
                    title: '<?= $_SESSION['flash']['type'] === 'success' ? 'Berhasil' : 'Gagal' ?>',
                    text: '<?= $_SESSION['flash']['msg'] ?>',
                    timer: 2500,
                    showConfirmButton: false
                });
            </script>
        <?php unset($_SESSION['flash']); endif; ?>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
            <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Pilih Kelas</label>
                    <select name="kelas_id" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 bg-slate-50" onchange="this.form.submit()">
                        <option value="0">-- Pilih Kelas --</option>
                        <?php while ($k = $list_kelas->fetch_assoc()): ?>
                            <option value="<?= $k['id_kelas'] ?>" <?= $kelas_id == $k['id_kelas'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Pilih Mata Pelajaran</label>
                    <select name="mapel_id" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 bg-slate-50" onchange="this.form.submit()">
                        <option value="0">-- Semua Mapel --</option>
                        <?php while ($m = $list_mapel->fetch_assoc()): ?>
                            <option value="<?= $m['id_mapel'] ?>" <?= $mapel_id == $m['id_mapel'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['nama_mapel']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Pilih Ujian</label>
                    <select name="ujian_id" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 bg-slate-50" onchange="this.form.submit()">
                        <option value="0">-- Pilih Ujian --</option>
                        <?php foreach ($list_ujian as $ujn): ?>
                            <option value="<?= $ujn['id_ujian'] ?>" <?= $ujian_id == $ujn['id_ujian'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ujn['judul_ujian']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-800 text-lg"><i class="fas fa-users text-blue-600 mr-2"></i>Daftar Nilai Siswa</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-100 text-slate-600 uppercase">
                        <tr>
                            <th class="px-6 py-4 font-bold">No</th>
                            <th class="px-6 py-4 font-bold">NIS</th>
                            <th class="px-6 py-4 font-bold">Nama Siswa</th>
                            <th class="px-6 py-4 font-bold text-center">Status</th>
                            <th class="px-6 py-4 font-bold text-center">Nilai</th>
                            <th class="px-6 py-4 font-bold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($kelas_id == 0 || $ujian_id == 0): ?>
                            <tr>
                                <td colspan="6" class="py-16 text-center text-slate-400 font-medium">
                                    <i class="fas fa-info-circle text-4xl mb-3 block text-slate-300"></i>
                                    Silakan pilih Kelas dan Ujian terlebih dahulu.
                                </td>
                            </tr>
                        <?php elseif (empty($data_siswa)): ?>
                            <tr>
                                <td colspan="6" class="py-16 text-center text-slate-400 font-medium">
                                    Tidak ada data siswa di kelas ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($data_siswa as $siswa): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4"><?= $no++ ?></td>
                                <td class="px-6 py-4 font-mono text-slate-500"><?= htmlspecialchars($siswa['nis']) ?></td>
                                <td class="px-6 py-4 font-medium text-slate-800"><?= htmlspecialchars($siswa['nama_lengkap']) ?></td>
                                
                                <td class="px-6 py-4 text-center">
                                    <?php if ($siswa['id_nilai']): ?>
                                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">Selesai</span>
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-600 px-3 py-1 rounded-full text-xs font-bold">Belum</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-6 py-4 text-center">
                                    <?php if ($siswa['id_nilai']): ?>
                                        <span class="text-lg font-bold <?= $siswa['nilai'] >= 75 ? 'text-green-600' : 'text-red-600' ?>">
                                            <?= number_format($siswa['nilai'], 1) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-6 py-4 text-center space-x-2">
                                    <?php if ($siswa['id_nilai']): ?>
                                        <button onclick="bukaModalEdit(<?= $siswa['id_nilai'] ?>, '<?= addslashes($siswa['nama_lengkap']) ?>', <?= $siswa['nilai'] ?>)" class="bg-amber-100 text-amber-700 hover:bg-amber-500 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="hapusNilai(<?= $siswa['id_nilai'] ?>)" class="bg-red-100 text-red-600 hover:bg-red-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button onclick="bukaModalTambah(<?= $siswa['id_user'] ?>, '<?= addslashes($siswa['nama_lengkap']) ?>')" class="bg-blue-100 text-blue-700 hover:bg-blue-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">
                                            <i class="fas fa-plus"></i> Isi Nilai
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modalEdit" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-amber-500 p-4 flex justify-between items-center text-white">
                <h3 class="font-bold text-lg"><i class="fas fa-edit mr-2"></i>Edit Nilai</h3>
                <button onclick="tutupModal('modalEdit')" class="hover:text-amber-200"><i class="fas fa-times text-xl"></i></button>
            </div>
            <form action="" method="POST" class="p-6">
                <input type="hidden" name="id_nilai" id="edit_id_nilai">
                
                <div class="mb-4 text-center">
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wide">Nama Siswa</p>
                    <p class="text-lg font-bold text-slate-800" id="edit_nama_siswa"></p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-bold text-slate-700 mb-2 text-center">Nilai Ujian (0-100)</label>
                    <input type="number" step="0.1" name="nilai" id="edit_input_nilai" required class="w-full text-center text-2xl font-black text-amber-600 border border-slate-300 rounded-xl py-3 outline-none focus:border-amber-500 bg-slate-50">
                </div>
                
                <button type="submit" name="edit_nilai" class="w-full bg-amber-500 text-white font-bold py-3 rounded-xl hover:bg-amber-600 transition shadow-lg shadow-amber-200">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <div id="modalTambah" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-blue-600 p-4 flex justify-between items-center text-white">
                <h3 class="font-bold text-lg"><i class="fas fa-plus-circle mr-2"></i>Input Nilai Manual</h3>
                <button onclick="tutupModal('modalTambah')" class="hover:text-blue-200"><i class="fas fa-times text-xl"></i></button>
            </div>
            <form action="" method="POST" class="p-6">
                <input type="hidden" name="ujian_id" value="<?= $ujian_id ?>">
                <input type="hidden" name="siswa_id" id="tambah_id_siswa">
                
                <div class="mb-4 text-center">
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wide">Nama Siswa</p>
                    <p class="text-lg font-bold text-slate-800" id="tambah_nama_siswa"></p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-bold text-slate-700 mb-2 text-center">Masukkan Nilai (0-100)</label>
                    <input type="number" step="0.1" name="nilai" required class="w-full text-center text-2xl font-black text-blue-600 border border-slate-300 rounded-xl py-3 outline-none focus:border-blue-500 bg-slate-50">
                </div>
                
                <button type="submit" name="tambah_nilai" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-200">Simpan Nilai</button>
            </form>
        </div>
    </div>

    <script>
        function bukaModalEdit(idNilai, namaSiswa, nilai) {
            document.getElementById('edit_id_nilai').value = idNilai;
            document.getElementById('edit_nama_siswa').innerText = namaSiswa;
            document.getElementById('edit_input_nilai').value = nilai;
            document.getElementById('modalEdit').classList.remove('hidden');
            document.getElementById('modalEdit').classList.add('flex');
        }

        function bukaModalTambah(idSiswa, namaSiswa) {
            document.getElementById('tambah_id_siswa').value = idSiswa;
            document.getElementById('tambah_nama_siswa').innerText = namaSiswa;
            document.getElementById('modalTambah').classList.remove('hidden');
            document.getElementById('modalTambah').classList.add('flex');
        }

        function tutupModal(id) {
            document.getElementById(id).classList.remove('flex');
            document.getElementById(id).classList.add('hidden');
        }

        function hapusNilai(id) {
            Swal.fire({
                title: 'Hapus Nilai?',
                text: "Nilai yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "?ujian_id=<?= $ujian_id ?>&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&delete_id=" + id;
                }
            });
        }
    </script>
</body>
</html>