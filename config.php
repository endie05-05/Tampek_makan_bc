<?php
// config.php - Enhanced Database Configuration
$host = 'localhost';
$username = 'root'; 
$password = '';
$database = 'tempat_makan';

try {
    // Konfigurasi PDO dengan options yang lebih robust
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password, $options);
    
} catch(PDOException $e) {
    // Log error dan tampilkan pesan user-friendly
    error_log("Database connection failed: " . $e->getMessage());
    die("Koneksi database gagal. Silakan coba lagi nanti.");
}

// Fungsi untuk generate ID transaksi yang lebih unik
function generateTransaksiId() {
    return 'TRX' . date('YmdHis') . sprintf('%03d', rand(100, 999));
}

// Fungsi untuk generate ID pelanggan
function generatePelangganId() {
    // Menambahkan Jam, Menit, Detik (His) dan 4 digit mikrodetik untuk memastikan ID selalu unik
    return 'PLG' . date('YmdHis') . substr(microtime(), 2, 4);
}
// File: config.php

// ... (kode yang sudah ada) ...

// Fungsi untuk generate ID pelanggan

// TAMBAHKAN FUNGSI BARU DI BAWAH INI
// Fungsi untuk generate ID item yang unik
function generateItemId() {
    return 'ITM' . strtoupper(uniqid());
}

// ... (sisa kode) ...

// Fungsi untuk format rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Fungsi untuk validasi input
function validateInput($data, $type = 'string') {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    
    switch($type) {
        case 'number':
            return is_numeric($data) ? floatval($data) : 0;
        case 'int':
            return is_numeric($data) ? intval($data) : 0;
        default:
            return $data;
    }
}

// Fungsi untuk mengecek koneksi database
function testDatabaseConnection() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT 1");
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Konstanta aplikasi
define('APP_NAME', 'Food Court System');
define('APP_VERSION', '1.0.0');
define('MAX_CART_ITEMS', 50);
define('MIN_ORDER_AMOUNT', 1000);
?>