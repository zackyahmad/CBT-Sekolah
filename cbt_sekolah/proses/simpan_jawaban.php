<?php
session_start();
require_once '../config/koneksi.php';

// Matikan error display biar tidak merusak respon AJAX
error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_SESSION['id_user'])) {
    echo "error_session";
    exit;
}

$id_siswa = $_SESSION['id_user'];
$id_ujian = $_POST['id_ujian'] ?? null;
$aksi     = $_POST['aksi'] ?? null;

// AMBIL ID NILAI DULU (Jembatan Penting)
$q_nilai = $pdo->prepare("SELECT id_nilai FROM tb_nilai WHERE id_ujian=? AND id_siswa=?");
$q_nilai->execute([$id_ujian, $id_siswa]);
$d_nilai = $q_nilai->fetch();

if (!$d_nilai) {
    echo "error_data_nilai";
    exit;
}
$id_nilai = $d_nilai['id_nilai'];


// 1. SIMPAN JAWABAN (Realtime)
if ($aksi == 'simpan') {
    $id_soal = $_POST['id_soal'];
    $jawaban = $_POST['jawaban'];

    // Cek jawaban pakai id_nilai (Lebih Aman)
    $cek = $pdo->prepare("SELECT id_jawaban FROM tb_jawaban WHERE id_nilai=? AND id_soal=?");
    $cek->execute([$id_nilai, $id_soal]);

    if ($cek->rowCount() > 0) {
        // Update
        $sql = "UPDATE tb_jawaban SET jawaban_siswa = ?, ragu_ragu = '0' WHERE id_nilai=? AND id_soal=?";
        $pdo->prepare($sql)->execute([$jawaban, $id_nilai, $id_soal]);
    } else {
        // Insert Baru
        // Kita masukkan id_ujian dan id_siswa juga jika kolomnya ada, kalau tidak ada biarkan default/null
        // Tapi kuncinya adalah id_nilai wajib masuk
        $sql = "INSERT INTO tb_jawaban (id_nilai, id_soal, jawaban_siswa, id_ujian, id_siswa) VALUES (?, ?, ?, ?, ?)";
        // Gunakan try-catch agar kalau kolom id_ujian/id_siswa gak ada, dia tetap simpan id_nilai
        try {
            $pdo->prepare($sql)->execute([$id_nilai, $id_soal, $jawaban, $id_ujian, $id_siswa]);
        } catch (Exception $e) {
            // Fallback: Jika gagal (misal kolom id_ujian gak ada), simpan yg wajib aja
            $pdo->prepare("INSERT INTO tb_jawaban (id_nilai, id_soal, jawaban_siswa) VALUES (?, ?, ?)")
                ->execute([$id_nilai, $id_soal, $jawaban]);
        }
    }
    echo "saved";
}

// 2. RAGU-RAGU
if ($aksi == 'ragu') {
    $id_soal = $_POST['id_soal'];
    $status  = ($_POST['status'] == 1) ? '1' : '0';
    $pdo->prepare("UPDATE tb_jawaban SET ragu_ragu = ? WHERE id_nilai=? AND id_soal=?")
        ->execute([$status, $id_nilai, $id_soal]);
}

// 3. CATAT PELANGGARAN
if ($aksi == 'curang') {
    $pdo->prepare("UPDATE tb_nilai SET pelanggaran = pelanggaran + 1 WHERE id_nilai=?")
        ->execute([$id_nilai]);
}

// 4. HITUNG NILAI AKHIR (PENTING!)
if ($aksi == 'selesai') {
    // A. Ambil Kunci Jawaban
    $stmt_kunci = $pdo->prepare("SELECT id_soal, kunci_jawaban FROM tb_soal WHERE id_ujian = ?");
    $stmt_kunci->execute([$id_ujian]);
    $kunci_data = $stmt_kunci->fetchAll(PDO::FETCH_KEY_PAIR); // [id_soal => kunci]

    // B. Ambil Jawaban Siswa (Pakai id_nilai biar pasti ketemu)
    $stmt_jwb = $pdo->prepare("SELECT id_soal, jawaban_siswa FROM tb_jawaban WHERE id_nilai = ?");
    $stmt_jwb->execute([$id_nilai]);
    $jawaban_data = $stmt_jwb->fetchAll(PDO::FETCH_KEY_PAIR); // [id_soal => jawaban_siswa]

    // C. Hitung Skor
    $benar = 0;
    $salah = 0;
    $total_soal = count($kunci_data);

    foreach ($kunci_data as $id_soal => $kunci) {
        $jwb_siswa = $jawaban_data[$id_soal] ?? '';
        // Bandingkan (Case Insensitive)
        if (strtoupper(trim($jwb_siswa)) == strtoupper(trim($kunci))) {
            $benar++;
        } else {
            $salah++;
        }
    }

    // Rumus Nilai
    $nilai_akhir = ($total_soal > 0) ? ($benar / $total_soal) * 100 : 0;
    $nilai_fix = number_format($nilai_akhir, 2);

    // D. Update ke Database tb_nilai
    // Pastikan status berubah jadi 'selesai' dan total_nilai terisi
    $sql_update = "UPDATE tb_nilai SET 
                   jml_benar = ?, 
                   jml_salah = ?, 
                   total_nilai = ?, 
                   status_pengerjaan = 'selesai', 
                   waktu_selesai = NOW() 
                   WHERE id_nilai = ?";

    try {
        $pdo->prepare($sql_update)->execute([$benar, $salah, $nilai_fix, $id_nilai]);
        echo "ok";
    } catch (Exception $e) {
        // Kalau error, kirim pesan error ke browser
        echo "error_db: " . $e->getMessage();
    }
}
