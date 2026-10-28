<?php
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (ob_get_contents()) ob_end_clean();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 1. Definisikan Nama Header
$headers = [
    'No', 'Jenis Soal (1=PG)', 'Bobot Nilai', 'Materi/KD', 
    'Media Gambar (Letakkan Gambar di sini)', // Diperjelas
    'Media Audio', 'Pertanyaan (Teks)', 'Tingkat Kesulitan', 
    'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Opsi E', 'Indikator', 'Kunci Jawaban'
];

// 2. Terapkan Header dan Styling Warna
foreach ($headers as $key => $value) {
    $kolom = $key + 1;
    $sheet->setCellValue([$kolom, 1], $value);
}

// STYLING HEADER
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true // Agar teks panjang turun ke bawah
    ],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3498DB']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];

$sheet->getStyle('A1:O1')->applyFromArray($headerStyle);
$sheet->getRowDimension('1')->setRowHeight(35);

// 3. Set Lebar Kolom
foreach (range('A', 'O') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}
// KHUSUS KOLOM GAMBAR, KITA BUAT LEBAR MANUAL
$sheet->getColumnDimension('E')->setAutoSize(false);
$sheet->getColumnDimension('E')->setWidth(40); // Lebar kolom gambar

// 4. Tambah Contoh Data
$sheet->setCellValue('A2', '1');
$sheet->setCellValue('B2', '1');
$sheet->setCellValue('C2', '2');
$sheet->setCellValue('D2', 'Sistem Operasi');
// Beri petunjuk ke guru di sel E2
$sheet->setCellValue('E2', '[TUMPUK/INSERT GAMBAR ANDA DI KOTAK INI]'); 
$sheet->setCellValue('G2', 'Sebutkan perangkat keras komputer yang berfungsi sebagai unit pemrosesan utama?');
$sheet->setCellValue('I2', 'CPU');
$sheet->setCellValue('J2', 'RAM');
$sheet->setCellValue('K2', 'Harddisk');
$sheet->setCellValue('L2', 'Monitor');
$sheet->setCellValue('M2', 'Keyboard');
$sheet->setCellValue('O2', 'A');

// BUAT BARIS 2 LEBIH TINGGI AGAR GAMBAR MUAT
$sheet->getRowDimension('2')->setRowHeight(100); 
$sheet->getStyle('E2')->getAlignment()->setWrapText(true);

// 5. Proses Download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Template_Soal_SKAPAN.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;