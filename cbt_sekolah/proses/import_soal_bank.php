<?php
session_start();
// Pastikan folder vendor sudah ada hasil composer tadi
require '../vendor/autoload.php';
require_once '../config/koneksi.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;

if (isset($_POST['import_bank'])) {
    $id_mapel = $_POST['id_mapel'];

    try {
        $file_name = $_FILES['file_excel']['name'];
        $file_temp = $_FILES['file_excel']['tmp_name'];

        $reader = new Xlsx();
        $spreadsheet = $reader->load($file_temp);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // 1. SIAPKAN FOLDER UNTUK MENYIMPAN GAMBAR
        $upload_dir = '../uploads/'; 
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // 2. EKSTRAK SEMUA GAMBAR DARI EXCEL
        $gambarMap = []; // Array untuk menyimpan nama gambar berdasarkan baris
        $drawings = $worksheet->getDrawingCollection();

        foreach ($drawings as $drawing) {
            $koordinat = $drawing->getCoordinates(); // Cth: 'E2'
            
            // Ambil angka barisnya saja (misal dari 'E2' jadi '2')
            preg_match('/\d+/', $koordinat, $matches);
            $baris = $matches[0] ?? null;

            if ($baris) {
                if ($drawing instanceof Drawing) {
                    // Jika gambar berasal dari file beneran (Insert > Picture)
                    $zipReader = fopen($drawing->getPath(), 'r');
                    $imageContents = '';
                    while (!feof($zipReader)) {
                        $imageContents .= fread($zipReader, 1024);
                    }
                    fclose($zipReader);
                    $extension = $drawing->getExtension();
                } elseif ($drawing instanceof MemoryDrawing) {
                    // Jika gambar hasil copy-paste (clipboard)
                    ob_start();
                    call_user_func(
                        $drawing->getRenderingFunction(),
                        $drawing->getImageResource()
                    );
                    $imageContents = ob_get_contents();
                    ob_end_clean();
                    
                    switch ($drawing->getMimeType()) {
                        case MemoryDrawing::MIMETYPE_PNG:  $extension = 'png'; break;
                        case MemoryDrawing::MIMETYPE_GIF:  $extension = 'gif'; break;
                        case MemoryDrawing::MIMETYPE_JPEG: 
                        default: $extension = 'jpg'; break;
                    }
                }

                if (isset($imageContents) && !empty($imageContents)) {
                    // Buat nama unik untuk gambar
                    $namaGambarBaru = 'soal_' . time() . '_' . uniqid() . '.' . $extension;
                    
                    // Simpan gambar ke folder server
                    file_put_contents($upload_dir . $namaGambarBaru, $imageContents);
                    
                    // Masukkan ke array (baris -> nama gambar)
                    $gambarMap[$baris] = $namaGambarBaru;
                }
            }
        }

        // 3. PROSES TEKS DAN SIMPAN KE DATABASE
        $data = $worksheet->toArray();
        $berhasil = 0;
        
        foreach ($data as $key => $row) {
            // Lewati baris pertama (Header Berwarna)
            if ($key < 1) continue;

            // Jika kolom pertanyaan (Index 6 / Kolom G) kosong, lewati
            if (empty($row[6])) continue;

            // Baris asli di Excel (karena array dimulai dari 0, baris 1 di excel = array ke-0)
            $baris_excel = $key + 1; 
            
            // Cek apakah di baris ini ada gambar (yang sudah diekstrak di langkah 2)
            $file_gambar = isset($gambarMap[$baris_excel]) ? $gambarMap[$baris_excel] : '';

            // Masukkan data ke tb_soal (Kolom disesuaikan dengan database Anda)
            $sql = "INSERT INTO tb_soal (id_mapel, jenis_soal, bobot, materi, pertanyaan, gambar, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $id_mapel,
                $row[1] ?? '1', // Jenis Soal
                $row[2] ?? '1', // Bobot Nilai
                $row[3] ?? '',  // Materi/KD
                $row[6],        // Pertanyaan
                $file_gambar,   // Kolom 'gambar'
                $row[8],        // Opsi A
                $row[9],        // Opsi B
                $row[10],       // Opsi C
                $row[11],       // Opsi D
                $row[12],       // Opsi E
                strtolower($row[14] ?? '') // Kunci Jawaban
            ]);
            $berhasil++;
        }

        $_SESSION['notif'] = [
            'type' => 'success',
            'title' => 'Import Berhasil',
            'text' => "$berhasil soal telah dimasukkan ke dalam Gudang Soal."
        ];
    } catch (Exception $e) {
        $_SESSION['notif'] = [
            'type' => 'error',
            'title' => 'Gagal Import',
            'text' => $e->getMessage()
        ];
    }

    header("Location: ../pages/admin/soal_list.php?id_mapel=" . $id_mapel);
    exit;
}