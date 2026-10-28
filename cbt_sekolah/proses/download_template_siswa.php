<?php
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (ob_get_contents()) ob_end_clean();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header Kolom tetap 8 kolom sesuai update terakhir
$headers = ['NISN', 'NAMA LENGKAP', 'JK (L/P)', 'TEMPAT LAHIR', 'TGL LAHIR (YYYY-MM-DD)', 'NIS (USERNAME)', 'TINGKAT', 'NAMA KELAS'];

foreach ($headers as $key => $value) {
    $kolom = $key + 1;
    $sheet->setCellValue([$kolom, 1], $value);
    $sheet->getStyle([$kolom, 1])->getFont()->setBold(true);
    $sheet->getStyle([$kolom, 1])->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('3498DB');
}

// CONTOH PENGISIAN AGAR SINKRON DENGAN SISTEM PREFIX
$sheet->setCellValue('A2', '00812345');
$sheet->setCellValue('B2', 'MAYUKA REFORIKA');
$sheet->setCellValue('C2', 'P');
$sheet->setCellValue('D2', 'Bandar Lampung');
$sheet->setCellValue('E2', '2008-05-20');
$sheet->setCellValue('F2', '22456');
$sheet->setCellValue('G2', 'XI'); // Kolom Tingkat
$sheet->setCellValue('H2', 'PPLG 1'); //CUKUP tulis KAN PPLG 1

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Template_Siswa_SKAPAN.xlsx"');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
