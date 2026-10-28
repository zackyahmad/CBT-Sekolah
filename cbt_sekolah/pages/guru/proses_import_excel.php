<?php
session_start();
// Menggunakan path absolut
include_once dirname(__DIR__, 2) . '/config/koneksi.php'; 
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

if(isset($_FILES['file_excel']['name']) && isset($_POST['id_ujian']) && isset($_POST['id_mapel'])) { 
    $id_ujian  = $_POST['id_ujian']; 
    $id_mapel  = $_POST['id_mapel']; 
    $nama_file = $_FILES['file_excel']['name'];
    $tmp_file  = $_FILES['file_excel']['tmp_name'];
    $error     = $_FILES['file_excel']['error'];

    if($error == 0) {
        $ekstensi = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

        if($ekstensi == 'xlsx') {
            try {
                $reader = new Xlsx();
                // Set read data only agar proses import sangat cepat dan mengabaikan format/gambar
                $reader->setReadDataOnly(true); 
                $spreadsheet = $reader->load($tmp_file);
                $worksheet = $spreadsheet->getActiveSheet();

                $data = $worksheet->toArray();
                $berhasil = 0;
                $gagal = 0;
                $pesan_error = "";

                $query = "INSERT INTO tb_soal (
                            id_ujian, id_mapel, jenis_soal, nilai, materi, 
                            pertanyaan, gambar, opsi_a, opsi_b, 
                            opsi_c, opsi_d, opsi_e, kunci_jawaban, bobot
                         ) VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($query);

                foreach ($data as $index => $row) {
                    if ($index == 0) continue; // Lewati header
                    
                    $pertanyaan = trim($row[6] ?? '');
                    if (empty($pertanyaan)) continue; 

                    $jenis_soal    = !empty($row[1]) ? $row[1] : '1'; 
                    $bobot         = !empty($row[2]) ? $row[2] : 1;   
                    $materi        = $row[3] ?? '';  
                    $opsi_a        = $row[8] ?? '';  
                    $opsi_b        = $row[9] ?? '';  
                    $opsi_c        = $row[10] ?? ''; 
                    $opsi_d        = $row[11] ?? ''; 
                    $opsi_e        = $row[12] ?? ''; 
                    $kunci_jawaban = strtolower(trim($row[14] ?? '')); 

                    try {
                        // Insert dengan kolom gambar = NULL (dikosongkan dulu)
                        $simpan = $stmt->execute([
                            $id_ujian, $id_mapel, $jenis_soal, 0, $materi, 
                            $pertanyaan, $opsi_a, $opsi_b, 
                            $opsi_c, $opsi_d, $opsi_e, $kunci_jawaban, $bobot
                        ]);
                        
                        if($simpan) {
                            $id_soal_baru = $pdo->lastInsertId();
                            $stmt_relasi = $pdo->prepare("INSERT INTO tb_ujian_soal (id_ujian, id_soal) VALUES (?, ?)");
                            $stmt_relasi->execute([$id_ujian, $id_soal_baru]);
                            $berhasil++;
                        }
                    } catch (PDOException $e) {
                        $gagal++;
                        $pesan_error = $e->getMessage();
                    }
                }

                if ($gagal > 0) {
                    echo "<script>alert('Perhatian! Berhasil: $berhasil. GAGAL: $gagal.\\nError: " . addslashes($pesan_error) . "'); window.location.href='kelola_soal_ujian.php?id=$id_ujian';</script>";
                } else {
                    echo "<script>alert('Sukses! $berhasil soal teks berhasil diimport.'); window.location.href='kelola_soal_ujian.php?id=$id_ujian';</script>";
                }

            } catch (Exception $e) {
                echo "<script>alert('Gagal mengekstrak Excel: ".addslashes($e->getMessage())."'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Format file harus .xlsx'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Gagal upload file!'); window.history.back();</script>";
    }
}
?>