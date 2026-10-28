<?php
session_start();
session_destroy();
session_start(); // Mulai sesi baru cuma buat nyimpen pesan flash

$_SESSION['notif'] = [
    'type' => 'success',
    'title' => 'Logout Berhasil',
    'text' => 'Sampai jumpa lagi!'
];

header("Location: index.php");
exit;
