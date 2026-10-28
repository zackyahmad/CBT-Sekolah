<?php
session_start();
require_once '../../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = $_SESSION['id_user'];
    $id_ujian = (int)$_POST['id_ujian'];
    $jawaban_siswa = $_POST['jawaban'] ?? []; 

    // 1. Ambil data soal yang diujikan
    $stmt = $pdo->prepare("SELECT s.id_soal, s.kunci_jawaban, s.jenis_soal FROM tb_ujian_soal us JOIN tb_soal s ON us.id_soal = s.id_soal WHERE us.id_ujian = ?");
    $stmt->execute([$id_ujian]);
    $list_soal = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cek apakah sudah pernah mengerjakan (mencegah double submit)
    $cek = $pdo->prepare("SELECT id_hasil FROM tb_hasil_ujian WHERE id_user = ? AND id_ujian = ?");
    $cek->execute([$id_user, $id_ujian]);
    if ($cek->rowCount() > 0) {
        header("Location: ujian.php"); exit;
    }

    // 2. Buat record awal di tb_hasil_ujian (Nilai awal 0)
    $ins_hasil = $pdo->prepare("INSERT INTO tb_hasil_ujian (id_user, id_ujian, nilai) VALUES (?, ?, 0)");
    $ins_hasil->execute([$id_user, $id_ujian]);
    $id_hasil = $pdo->lastInsertId();

    $benar = 0; 
    $salah = 0;
    $total_pg = 0;
    
    // 3. Simpan rincian ke tb_jawaban
    $ins_jwb = $pdo->prepare("INSERT INTO tb_jawaban (id_hasil, id_user, id_ujian, id_soal, jawaban) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($list_soal as $s) {
        $id_s = $s['id_soal'];
        $jwb = $jawaban_siswa[$id_s] ?? '';
        
        $ins_jwb->execute([$id_hasil, $id_user, $id_ujian, $id_s, $jwb]);

        // Hitung skor hanya untuk PG (jenis_soal = 1)
        if ($s['jenis_soal'] == '1') {
            $total_pg++;
            if (strtoupper(trim($jwb)) === strtoupper(trim($s['kunci_jawaban']))) {
                $benar++;
            } else {
                $salah++;
            }
        }
    }

    // 4. Update nilai sementara berdasarkan persentase PG
    $nilai_sementara = ($total_pg > 0) ? ($benar / $total_pg) * 100 : 0;
    $pdo->prepare("UPDATE tb_hasil_ujian SET jml_benar = ?, jml_salah = ?, nilai = ? WHERE id_hasil = ?")->execute([$benar, $salah, $nilai_sementara, $id_hasil]);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Ujian berhasil diselesaikan!'];
    header("Location: ujian.php"); 
    exit;
}