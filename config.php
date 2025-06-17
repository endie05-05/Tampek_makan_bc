<?php
// config.php - Konfigurasi Database
$host = 'localhost';
$username = 'root'; 
$password = '';
$database = 'tempat_makan';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Fungsi untuk generate ID transaksi
function generateTransaksiId() {
    return 'TRX' . date('YmdHis') . rand(100, 999);
}

// Fungsi untuk generate ID pelanggan
function generatePelangganId() {
    return 'PLG' . date('Ymd') . rand(100, 999);
}
?>