<?php
require_once 'config.php';

$transaksi_id = $_GET['id'] ?? '';

if (!$transaksi_id) {
    header('Location: index.php');
    exit;
}

// Ambil data transaksi lengkap
$stmt = $pdo->prepare("
    SELECT t.*, p.nama_pembeli, tk.nama_toko, ao.order_line, ao.nomor_urut
    FROM transaksi t
    JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
    JOIN toko tk ON t.id_toko = tk.id_toko
    LEFT JOIN antrian_order ao ON t.id_transaksi = ao.id_transaksi
    WHERE t.id_transaksi = ?
");
$stmt->execute([$transaksi_id]);
$transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaksi) {
    header('Location: index.php');
    exit;
}

// Ambil detail items
$stmt_detail = $pdo->prepare("
    SELECT dt.*, i.nama_item, i.harga_item
    FROM detail_transaksi dt
    JOIN item i ON dt.id_item = i.id_item
    WHERE dt.id_transaksi = ?
    ORDER BY i.nama_item
");
$stmt_detail->execute([$transaksi_id]);
$detail_items = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pesanan - <?= $transaksi['order_line'] ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #8B7355, #D4C4A8);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #333;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .receipt-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .order-number {
            font-size: 32px;
            font-weight: bold;
            color: #8B7355;
            margin: 15px 0;
        }
        
        .receipt-info {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .items-section {
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
            padding: 15px 0;
            margin: 20px 0;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: flex-start;
        }
        
        .item-name {
            font-weight: bold;
            flex: 1;
        }
        
        .item-details {
            text-align: right;
            flex: 0 0 auto;
        }
        
        .item-qty-price {
            font-size: 14px;
            color: #666;
        }
        
        .item-subtotal {
            font-weight: bold;
        }
        
        .total-section {
            border-top: 2px solid #333;
            padding-top: 15px;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .grand-total {
            font-size: 20px;
            font-weight: bold;
            border-top: 1px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .payment-info {
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        
        .notes-section {
            margin: 20px 0;
            padding: 15px;
            background: #fff9e6;
            border-radius: 8px;
            border-left: 4px solid #8B7355;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed #333;
            font-size: 14px;
            color: #666;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #8B7355;
            color: white;
        }
        
        .btn-primary:hover {
            background: #704A3A;
        }
        
        .btn-secondary {
            background: #CCCCCC;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #BBBBBB;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-diproses {
            background: #ffeaa7;
            color: #2d3436;
        }
        
        .status-selesai {
            background: #00b894;
            color: white;
        }
        
        .status-dibatalkan {
            background: #e17055;
            color: white;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                max-width: none;
                padding: 20px;
            }
            
            .action-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="receipt-header">
            <div class="receipt-title">BC FOOD NET</div>
            <div style="font-size: 14px; margin-bottom: 10px;">STRUK PESANAN</div>
            <div class="order-number"><?= $transaksi['order_line'] ?></div>
            <div class="status-badge status-<?= strtolower($transaksi['status_transaksi']) ?>">
                <?= $transaksi['status_transaksi'] ?>
            </div>
        </div>
        
        <div class="receipt-info">
            <div class="info-row">
                <span>Tanggal</span>
                <span><?= date('d/m/Y H:i', strtotime($transaksi['waktu_transaksi'])) ?></span>
            </div>
            <div class="info-row">
                <span>No. Meja</span>
                <span><?= $transaksi['no_meja'] ?></span>
            </div>
            <div class="info-row">
                <span>Nama</span>
                <span><?= htmlspecialchars($transaksi['nama_pembeli']) ?></span>
            </div>
            <div class="info-row">
                <span>Toko</span>
                <span><?= htmlspecialchars($transaksi['nama_toko']) ?></span>
            </div>
            <div class="info-row">
                <span>ID Transaksi</span>
                <span><?= $transaksi['id_transaksi'] ?></span>
            </div>
        </div>
        
        <div class="items-section">
            <h3 style="margin-bottom: 15px; text-align: center;">RINCIAN PESANAN</h3>
            <?php foreach($detail_items as $item): ?>
                <div class="item-row">
                    <div class="item-name"><?= htmlspecialchars($item['nama_item']) ?></div>
                    <div class="item-details">
                        <div class="item-qty-price">
                            <?= $item['jumlah_item'] ?> x Rp <?= number_format($item['harga_item'], 0, ',', '.') ?>
                        </div>
                        <div class="item-subtotal">
                            Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="total-section">
            <?php
            $total_items = 0;
            foreach($detail_items as $item) {
                $total_items += $item['jumlah_item'];
            }
            ?>
            <div class="total-row">
                <span>Total Item:</span>
                <span><?= $total_items ?></span>
            </div>
            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span>Rp <?= number_format($transaksi['total_harga'], 0, ',', '.') ?></span>
            </div>
        </div>
        
        <div class="payment-info">
            <div class="info-row">
                <span><strong>Metode Pembayaran:</strong></span>
                <span><strong><?= $transaksi['jenis_transaksi'] ?></strong></span>
            </div>
        </div>
        
        <?php if (!empty($transaksi['catatan'])): ?>
            <div class="notes-section">
                <strong>CATATAN:</strong><br>
                <?= htmlspecialchars($transaksi['catatan']) ?>
            </div>
        <?php endif; ?>
        
        <div class="receipt-footer">
            <p>Terima kasih atas kunjungan Anda!</p>
            <p>Simpan struk ini sebagai bukti pembayaran</p>
            <p style="margin-top: 10px; font-size: 12px;">
                Dicetak pada: <?= date('d/m/Y H:i:s') ?>
            </p>
        </div>
        
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="window.print()">🖨️ Cetak</button>
            <a href="index.php" class="btn btn-secondary">🏠 Beranda</a>
            <button class="btn btn-primary" onclick="window.location.reload()">🔄 Refresh</button>
        </div>
    </div>
    
    <script>
        // Auto refresh status setiap 30 detik
        setTimeout(function() {
            window.location.reload();
        }, 30000);
        
        // Konfirmasi sebelum meninggalkan halaman
        window.addEventListener('beforeunload', function(e) {
            if (!confirm('Yakin ingin meninggalkan halaman struk?')) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>