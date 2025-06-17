<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_POST['action'] !== 'create_order') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    $toko_id = $_POST['toko_id'];
    $no_meja = $_POST['no_meja'];
    $nama_pembeli = $_POST['nama_pembeli'];
    $jenis_transaksi = $_POST['jenis_transaksi'];
    $catatan = $_POST['catatan'] ?? '';
    $cart_data = json_decode($_POST['cart_data'], true);
    
    if (empty($cart_data)) {
        throw new Exception('Keranjang kosong');
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Generate IDs
    $transaksi_id = generateTransaksiId();
    $pelanggan_id = generatePelangganId();
    
    // Insert pelanggan
    $stmt_pelanggan = $pdo->prepare("INSERT INTO pelanggan (id_pelanggan, nama_pembeli) VALUES (?, ?)");
    $stmt_pelanggan->execute([$pelanggan_id, $nama_pembeli]);
    
    // Calculate total
    $total_harga = 0;
    foreach ($cart_data as $item) {
        $total_harga += $item['subtotal'];
    }
    
    // Insert transaksi
    $stmt_transaksi = $pdo->prepare("
        INSERT INTO transaksi (id_transaksi, no_meja, id_toko, jenis_transaksi, total_harga, id_pelanggan, catatan) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_transaksi->execute([
        $transaksi_id, 
        $no_meja, 
        $toko_id, 
        $jenis_transaksi, 
        $total_harga, 
        $pelanggan_id, 
        $catatan
    ]);
    
    // Insert detail transaksi
    $stmt_detail = $pdo->prepare("
        INSERT INTO detail_transaksi (id_transaksi, id_item, jumlah_item, subtotal) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($cart_data as $item_id => $item) {
        $stmt_detail->execute([
            $transaksi_id,
            $item_id,
            $item['quantity'],
            $item['subtotal']
        ]);
    }
    
    // Generate nomor antrian
    $stmt_count = $pdo->prepare("SELECT COUNT(*) as count FROM antrian_order WHERE DATE(waktu_masuk_antrian) = CURDATE()");
    $stmt_count->execute();
    $count_result = $stmt_count->fetch(PDO::FETCH_ASSOC);
    $nomor_urut = $count_result['count'] + 1;
    
    $order_line = 'ORD' . str_pad($nomor_urut, 3, '0', STR_PAD_LEFT);
    
    // Insert antrian order
    $stmt_antrian = $pdo->prepare("
        INSERT INTO antrian_order (order_line, id_transaksi, nomor_urut) 
        VALUES (?, ?, ?)
    ");
    $stmt_antrian->execute([$order_line, $transaksi_id, $nomor_urut]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'transaksi_id' => $transaksi_id,
        'order_line' => $order_line
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>