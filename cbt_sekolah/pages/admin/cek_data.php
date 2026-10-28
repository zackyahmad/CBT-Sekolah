<?php
session_start();
require_once '../../config/koneksi.php';

echo "<h2>Debug Data Ujian</h2>";

// Cek semua data ujian
$query = "SELECT u.*, m.nama_mapel, g.nama_lengkap 
          FROM tb_ujian u 
          LEFT JOIN tb_mapel m ON u.id_mapel = m.id_mapel 
          LEFT JOIN tb_users g ON u.id_guru = g.id_user
          ORDER BY u.id_ujian DESC";

$stmt = $pdo->query($query);
$data = $stmt->fetchAll();

echo "<h3>Total Data: " . count($data) . "</h3>";

if (count($data) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr>
            <th>ID</th>
            <th>Judul</th>
            <th>ID Mapel</th>
            <th>Nama Mapel</th>
            <th>ID Guru</th>
            <th>Nama Guru</th>
            <th>Tanggal</th>
            <th>Jam Mulai</th>
            <th>Durasi</th>
            <th>Status</th>
          </tr>";
    
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>" . $row['id_ujian'] . "</td>";
        echo "<td>" . ($row['judul_ujian'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['id_mapel'] . "</td>";
        echo "<td>" . ($row['nama_mapel'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['id_guru'] . "</td>";
        echo "<td>" . ($row['nama_lengkap'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['tanggal_ujian'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['jam_mulai'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['durasi_menit'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['status_ujian'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>TIDAK ADA DATA UJIAN DI DATABASE!</p>";
}

// Cek struktur tabel
echo "<h3>Struktur Tabel tb_ujian:</h3>";
$struktur = $pdo->query("DESCRIBE tb_ujian");
echo "<pre>";
while ($row = $struktur->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
} 
echo "</pre>";

// Cek apakah foreign key berfungsi
echo "<h3>Cek Foreign Key:</h3>";
try {
    $fk_check = $pdo->query("SELECT 
        TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, 
        REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'tb_ujian' 
        AND REFERENCED_TABLE_NAME IS NOT NULL");
    
    $foreign_keys = $fk_check->fetchAll();
    if (count($foreign_keys) > 0) {
        echo "<pre>";
        print_r($foreign_keys);
        echo "</pre>";
    } else {
        echo "Tidak ada foreign key yang terdefinisi";
    }
} catch (Exception $e) {
    echo "Error cek foreign key: " . $e->getMessage();
}
?>