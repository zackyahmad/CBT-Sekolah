<?php
date_default_timezone_set('Asia/Jakarta');
// konfigurasi database
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'db_ujian_sekolah';

try {
    // mencoba menghubungkan pake PDO 
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);

    // setting agar kalau ada eror php ngasih tau detailnya 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal" . $e->getMessage());
}
