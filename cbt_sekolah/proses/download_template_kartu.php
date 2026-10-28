<?php
// Bersihkan output buffer agar file excel tidak korup
if (ob_get_level()) {
    ob_end_clean();
}

// Berikan header agar browser mendeteksi ini sebagai file Excel yang didownload
header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Template_Import_Kartu_Ujian.xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
<table border="1">
    <thead>
        <tr>
            <th style="background-color: #27ae60; color: white; font-weight: bold;">NIS (Wajib)</th>
            <th style="background-color: #27ae60; color: white; font-weight: bold;">Nama Siswa (Wajib)</th>
            <th style="background-color: #27ae60; color: white; font-weight: bold;">Kelas</th>
            <th style="background-color: #27ae60; color: white; font-weight: bold;">Jurusan</th>
            <th style="background-color: #27ae60; color: white; font-weight: bold;">SID / NISN</th>
            <th style="background-color: #27ae60; color: white; font-weight: bold;">Tempat Lahir</th>
            <th style="background-color: #27ae60; color: white; font-weight: bold;">Tanggal Lahir (YYYY-MM-DD)</th>
            <th style="background-color: #27ae60; color: white; font-weight: bold;">Username Ujian</th>
            <th style="background-color: #27ae60; color: white; font-weight: bold;">Password Ujian</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>100123</td>
            <td>Ahmad Budi Santoso</td>
            <td>XII RPL 1</td>
            <td>Rekayasa Perangkat Lunak</td>
            <td>9988776655</td>
            <td>Bandar Lampung</td>
            <td>2006-08-15</td>
            <td>100123</td>
            <td>123456</td>
        </tr>
        <tr>
            <td>100124</td>
            <td>Siti Aminah</td>
            <td>XII TKJ 2</td>
            <td>Teknik Komputer Jaringan</td>
            <td>9988776656</td>
            <td>Jakarta</td>
            <td>2006-11-20</td>
            <td>siti_aminah</td>
            <td>123456</td>
        </tr>
    </tbody>
</table>