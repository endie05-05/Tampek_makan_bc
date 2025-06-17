<?php
require_once 'config.php';

$toko_id = $_GET['toko'] ?? '';
$no_meja = $_GET['meja'] ?? '';
$nama_pembeli = $_GET['nama'] ?? '';

if (!$toko_id || !$no_meja || !$nama_pembeli) {
    header('Location: index.php');
    exit;
}

// Ambil data toko
$stmt_toko = $pdo->prepare("SELECT * FROM toko WHERE id_toko = ?");
$stmt_toko->execute([$toko_id]);
$toko = $stmt_toko->fetch(PDO::FETCH_ASSOC);

// Ambil menu dari toko
$stmt_menu = $pdo->prepare("SELECT * FROM item WHERE id_toko = ? ORDER BY nama_item");
$stmt_menu->execute([$toko_id]);
$menu_list = $stmt_menu->fetchAll(PDO::FETCH_ASSOC);

if (!$toko) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - <?= htmlspecialchars($toko['nama_toko']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #8B7355, #D4C4A8);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #F5E6A3;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .toko-name {
            font-size: 28px;
            font-weight: bold;
            color: #2C2C2C;
            margin-bottom: 10px;
        }
        
        .order-info {
            background: #8B7355;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .menu-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .menu-info h3 {
            color: #2C2C2C;
            margin-bottom: 5px;
        }
        
        .menu-price {
            color: #8B7355;
            font-weight: bold;
            font-size: 18px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .qty-btn {
            width: 35px;
            height: 35px;
            border: none;
            background: #8B7355;
            color: white;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qty-btn:hover {
            background: #704A3A;
        }
        
        .qty-input {
            width: 50px;
            text-align: center;
            border: 2px solid #8B7355;
            border-radius: 5px;
            padding: 5px;
            font-size: 16px;
        }
        
        .cart-summary {
            background: #8B7355;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: center;
        }
        
        .total-items {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .total-price {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #F5E6A3;
            color: #2C2C2C;
        }
        
        .btn-primary:hover {
            background: #E6D072;
        }
        
        .btn-secondary {
            background: #704A3A;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5A3A2A;
        }
        
        .back-btn {
            background: #CCCCCC;
            color: #2C2C2C;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .back-btn:hover {
            background: #BBBBBB;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #F5E6A3;
            margin: 10% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            right: 20px;
            top: 15px;
        }
        
        .close:hover {
            color: #000;
        }
        
        .order-summary {
            margin: 20px 0;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #8B7355;
        }
        
        .payment-method {
            margin: 20px 0;
        }
        
        .payment-method label {
            display: block;
            margin: 10px 0;
            cursor: pointer;
        }
        
        .payment-method input[type="radio"] {
            margin-right: 10px;
        }
        
        .notes-section {
            margin: 20px 0;
        }
        
        .notes-section textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #8B7355;
            border-radius: 8px;
            resize: vertical;
            min-height: 80px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-btn">← Kembali</a>
        
        <div class="header">
            <div class="toko-name"><?= htmlspecialchars($toko['nama_toko']) ?></div>
            <div class="order-info">
                <strong>Meja: <?= htmlspecialchars($no_meja) ?></strong> | 
                <strong>Nama: <?= htmlspecialchars($nama_pembeli) ?></strong>
            </div>
        </div>
        
        <div class="menu-list">
            <?php foreach($menu_list as $menu): ?>
                <div class="menu-item">
                    <div class="menu-info">
                        <h3><?= htmlspecialchars($menu['nama_item']) ?></h3>
                        <div class="menu-price">Rp <?= number_format($menu['harga_item'], 0, ',', '.') ?></div>
                    </div>
                    <div class="quantity-controls">
                        <button class="qty-btn" onclick="changeQuantity('<?= $menu['id_item'] ?>', -1)">-</button>
                        <input type="number" class="qty-input" id="qty_<?= $menu['id_item'] ?>" 
                               value="0" min="0" onchange="updateCart()" 
                               data-price="<?= $menu['harga_item'] ?>" 
                               data-name="<?= htmlspecialchars($menu['nama_item']) ?>">
                        <button class="qty-btn" onclick="changeQuantity('<?= $menu['id_item'] ?>', 1)">+</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="cart-summary" id="cartSummary" style="display: none;">
            <div class="total-items" id="totalItems">Total Item: 0</div>
            <div class="total-price" id="totalPrice">Total: Rp 0</div>
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="showOrderSummary()">Rincian Pesanan</button>
                <button class="btn btn-secondary" onclick="clearCart()">Bersihkan</button>
            </div>
        </div>
    </div>
    
    <!-- Modal Rincian Pesanan -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 style="text-align: center; color: #2C2C2C; margin-bottom: 20px;">RINCIAN PESANAN</h2>
            
            <div class="order-summary" id="orderSummaryContent">
                <!-- Konten akan diisi oleh JavaScript -->
            </div>
            
            <div class="notes-section">
                <label for="catatan"><strong>CATATAN:</strong></label>
                <textarea id="catatan" placeholder="Tambahkan catatan pesanan..."></textarea>
            </div>
            
            <div class="payment-method">
                <strong>METODE PEMBAYARAN:</strong>
                <label>
                    <input type="radio" name="jenis_transaksi" value="TUNAI" checked> TUNAI
                </label>
                <label>
                    <input type="radio" name="jenis_transaksi" value="NON-TUNAI"> NON-TUNAI
                </label>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <div style="margin-bottom: 15px;">
                    <strong>Jumlah: <span id="modalTotalItems">0</span></strong><br>
                    <strong>Total: <span id="modalTotalPrice">Rp 0</span></strong><br>
                    <strong>Jenis Transaksi: <span id="modalPaymentType">TUNAI</span></strong>
                </div>
                <button class="btn btn-primary" onclick="processOrder()" style="padding: 15px 30px; font-size: 18px;">
                    Pesan Sekarang
                </button>
            </div>
        </div>
    </div>

    <script>
        let cart = {};
        
        function changeQuantity(itemId, change) {
            const qtyInput = document.getElementById('qty_' + itemId);
            let currentQty = parseInt(qtyInput.value) || 0;
            let newQty = Math.max(0, currentQty + change);
            qtyInput.value = newQty;
            updateCart();
        }
        
        function updateCart() {
            cart = {};
            let totalItems = 0;
            let totalPrice = 0;
            
            const qtyInputs = document.querySelectorAll('.qty-input');
            qtyInputs.forEach(input => {
                const qty = parseInt(input.value) || 0;
                if (qty > 0) {
                    const itemId = input.id.replace('qty_', '');
                    const price = parseFloat(input.dataset.price);
                    const name = input.dataset.name;
                    
                    cart[itemId] = {
                        name: name,
                        price: price,
                        quantity: qty,
                        subtotal: price * qty
                    };
                    
                    totalItems += qty;
                    totalPrice += price * qty;
                }
            });
            
            const cartSummary = document.getElementById('cartSummary');
            if (totalItems > 0) {
                cartSummary.style.display = 'block';
                document.getElementById('totalItems').textContent = 'Total Item: ' + totalItems;
                document.getElementById('totalPrice').textContent = 'Total: Rp ' + totalPrice.toLocaleString('id-ID');
            } else {
                cartSummary.style.display = 'none';
            }
        }
        
        function clearCart() {
            const qtyInputs = document.querySelectorAll('.qty-input');
            qtyInputs.forEach(input => {
                input.value = 0;
            });
            updateCart();
        }
        
        function showOrderSummary() {
            if (Object.keys(cart).length === 0) {
                alert('Keranjang kosong!');
                return;
            }
            
            let summaryHTML = '';
            let totalItems = 0;
            let totalPrice = 0;
            
            for (let itemId in cart) {
                const item = cart[itemId];
                summaryHTML += `
                    <div class="summary-item">
                        <div>
                            <strong>${item.name}</strong><br>
                            ${item.quantity} x Rp ${item.price.toLocaleString('id-ID')}
                        </div>
                        <div><strong>Rp ${item.subtotal.toLocaleString('id-ID')}</strong></div>
                    </div>
                `;
                totalItems += item.quantity;
                totalPrice += item.subtotal;
            }
            
            document.getElementById('orderSummaryContent').innerHTML = summaryHTML;
            document.getElementById('modalTotalItems').textContent = totalItems;
            document.getElementById('modalTotalPrice').textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');
            
            document.getElementById('orderModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('orderModal').style.display = 'none';
        }
        
        function processOrder() {
            if (Object.keys(cart).length === 0) {
                alert('Keranjang kosong!');
                return;
            }
            
            const jenis_transaksi = document.querySelector('input[name="jenis_transaksi"]:checked').value;
            const catatan = document.getElementById('catatan').value;
            
            // Update payment type display
            document.getElementById('modalPaymentType').textContent = jenis_transaksi;
            
            // Kirim data ke server
            const formData = new FormData();
            formData.append('action', 'create_order');
            formData.append('toko_id', '<?= $toko_id ?>');
            formData.append('no_meja', '<?= $no_meja ?>');
            formData.append('nama_pembeli', '<?= $nama_pembeli ?>');
            formData.append('jenis_transaksi', jenis_transaksi);
            formData.append('catatan', catatan);
            formData.append('cart_data', JSON.stringify(cart));
            
            fetch('process_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect ke halaman struk
                    window.location.href = 'struk.php?id=' + data.transaksi_id;
                } else {
                    alert('Gagal memproses pesanan: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memproses pesanan');
            });
        }
        
        // Update payment type when radio button changes
        document.querySelectorAll('input[name="jenis_transaksi"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('modalPaymentType').textContent = this.value;
            });
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('orderModal');
            if (event.target == modal) {
                closeModal();
            }
        }