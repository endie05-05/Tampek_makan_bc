<?php
require_once 'config.php';

// Set content type untuk JSON response
header('Content-Type: application/json');

// Fungsi untuk log error
function logError($message) {
    error_log(date('Y-m-d H:i:s') . " - " . $message . "\n", 3, "order_errors.log");
}

// Validasi request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

// Validasi action
if (!isset($_POST['action']) || $_POST['action'] !== 'create_order') {
    echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
    exit;
}

try {
    // Ambil dan validasi input data
    $toko_id = $_POST['toko_id'] ?? '';
    $no_meja = $_POST['no_meja'] ?? '';
    $nama_pembeli = $_POST['nama_pembeli'] ?? '';
    $jenis_transaksi = $_POST['jenis_transaksi'] ?? 'TUNAI';
    $catatan = $_POST['catatan'] ?? '';
    $cart_data = $_POST['cart_data'] ?? '';
    
    // Validasi input kosong
    if (empty($toko_id) || empty($no_meja) || empty($nama_pembeli) || empty($cart_data)) {
        throw new Exception('Data input tidak lengkap');
    }
    
    // Parse cart data
    $cart_items = json_decode($cart_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Format data keranjang tidak valid');
    }
    
    if (empty($cart_items)) {
        throw new Exception('Keranjang kosong');
    }
    
    // Validasi toko exists
    $stmt_check_toko = $pdo->prepare("SELECT id_toko FROM toko WHERE id_toko = ?");
    $stmt_check_toko->execute([$toko_id]);
    if (!$stmt_check_toko->fetch()) {
        throw new Exception('Toko tidak ditemukan');
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Generate IDs
    $transaksi_id = generateTransaksiId();
    $pelanggan_id = generatePelangganId();
    
    // Log untuk debugging
    logError("Memulai transaksi: $transaksi_id untuk pelanggan: $nama_pembeli");
    
    // Insert atau update pelanggan
    $stmt_check_pelanggan = $pdo->prepare("SELECT id_pelanggan FROM pelanggan WHERE nama_pembeli = ?");
    $stmt_check_pelanggan->execute([$nama_pembeli]);
    $existing_pelanggan = $stmt_check_pelanggan->fetch();
    
    if ($existing_pelanggan) {
        $pelanggan_id = $existing_pelanggan['id_pelanggan'];
    } else {
        $stmt_pelanggan = $pdo->prepare("INSERT INTO pelanggan (id_pelanggan, nama_pembeli) VALUES (?, ?)");
        if (!$stmt_pelanggan->execute([$pelanggan_id, $nama_pembeli])) {
            throw new Exception('Gagal menyimpan data pelanggan');
        }
    }
    
    // Calculate total dan validasi items
    $total_harga = 0;
    $valid_items = [];
    
    foreach ($cart_items as $item_id => $item) {
        // Validasi item exists dan ambil harga terbaru
        $stmt_item = $pdo->prepare("SELECT id_item, nama_item, harga_item FROM item WHERE id_item = ? AND id_toko = ?");
        $stmt_item->execute([$item_id, $toko_id]);
        $db_item = $stmt_item->fetch(PDO::FETCH_ASSOC);
        
        if (!$db_item) {
            throw new Exception("Item dengan ID $item_id tidak ditemukan");
        }
        
        // Gunakan harga dari database (bukan dari client)
        $harga_satuan = $db_item['harga_item'];
        $quantity = intval($item['quantity']);
        $subtotal = $harga_satuan * $quantity;
        
        $valid_items[] = [
            'id_item' => $item_id,
            'nama_item' => $db_item['nama_item'],
            'quantity' => $quantity,
            'harga_satuan' => $harga_satuan,
            'subtotal' => $subtotal
        ];
        
        $total_harga += $subtotal;
    }
    
    if ($total_harga <= 0) {
        throw new Exception('Total harga tidak valid');
    }
    
    // Insert transaksi
    $stmt_transaksi = $pdo->prepare("
        INSERT INTO transaksi (id_transaksi, no_meja, id_toko, jenis_transaksi, total_harga, id_pelanggan, catatan, status_transaksi) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'diproses')
    ");
    
    if (!$stmt_transaksi->execute([
        $transaksi_id, 
        $no_meja, 
        $toko_id, 
        $jenis_transaksi, 
        $total_harga, 
        $pelanggan_id, 
        $catatan
    ])) {
        throw new Exception('Gagal menyimpan transaksi');
    }
    
    // Insert detail transaksi
$stmt_detail = $pdo->prepare("
    INSERT INTO detail_transaksi (id_transaksi, id_item, jumlah_item, subtotal) 
    VALUES (?, ?, ?, ?)
");

foreach ($valid_items as $item) {
    if (!$stmt_detail->execute([
        $transaksi_id,
        $item['id_item'],
        $item['quantity'],
        // $item['harga_satuan'] <-- Baris ini dihapus
        $item['subtotal']
    ])) {
        throw new Exception('Gagal menyimpan detail transaksi');
    }
}
    
    // Generate nomor antrian
    $stmt_count = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM antrian_order 
        WHERE DATE(waktu_masuk_antrian) = CURDATE()
    ");
    $stmt_count->execute();
    $count_result = $stmt_count->fetch(PDO::FETCH_ASSOC);
    $nomor_urut = ($count_result['count'] ?? 0) + 1;
    
    $order_line = 'ORD' . str_pad($nomor_urut, 3, '0', STR_PAD_LEFT);
    
    // Insert antrian order
 // Insert antrian order
// Menambahkan kolom status_antrian dan menggunakan NOW() untuk waktu
$stmt_antrian = $pdo->prepare("
    INSERT INTO antrian_order (order_line, id_transaksi, nomor_urut, waktu_masuk_antrian, status_antrian) 
    VALUES (?, ?, ?, NOW(), 'menunggu')
");

// Variabel yang di-execute tetap sama karena NOW() dan 'menunggu' ditangani oleh SQL
if (!$stmt_antrian->execute([$order_line, $transaksi_id, $nomor_urut])) {
    throw new Exception('Gagal membuat antrian order');
}
    
    // Update status meja menjadi terisi
    // $stmt_update_meja = $pdo->prepare("UPDATE meja SET status_meja = 'terisi' WHERE no_meja = ?");
    // $stmt_update_meja->execute([$no_meja]);
    
    // Commit transaction
    $pdo->commit();
    
    logError("Transaksi berhasil: $transaksi_id dengan total: $total_harga");
    
    echo json_encode([
        'success' => true, 
        'transaksi_id' => $transaksi_id,
        'order_line' => $order_line,
        'total_harga' => $total_harga,
        'nomor_urut' => $nomor_urut,
        'message' => 'Pesanan berhasil dibuat'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction jika ada error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    logError("Error dalam transaksi: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'error_code' => 'TRANSACTION_FAILED'
    ]);
}
?>