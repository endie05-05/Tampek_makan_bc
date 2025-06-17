<?php
require_once 'config.php';

/**
 * Fungsi untuk generate ID item yang unik.
 * Diletakkan di sini agar tidak perlu mengubah config.php
 */
function generateItemId() {
    // Membuat ID unik dengan prefix 'ITM' diikuti kode unik berbasis waktu
    return 'ITM' . strtoupper(uniqid());
}

// Handle semua operasi Create, Read, Update, Delete (CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Pesan default jika terjadi error
    $message = "Terjadi kesalahan. Aksi tidak dikenal.";
    $message_type = "error"; // Tipe pesan untuk styling CSS

    try {
        switch ($action) {
            case 'add_toko':
                $stmt = $pdo->prepare("INSERT INTO toko (id_toko, nama_toko, jenis_toko) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['id_toko'], $_POST['nama_toko'], $_POST['jenis_toko']]);
                $message = "Toko berhasil ditambahkan!";
                $message_type = "success";
                break;
                
            case 'edit_toko':
                $stmt = $pdo->prepare("UPDATE toko SET nama_toko = ?, jenis_toko = ? WHERE id_toko = ?");
                $stmt->execute([$_POST['nama_toko'], $_POST['jenis_toko'], $_POST['id_toko']]);
                $message = "Toko berhasil diupdate!";
                $message_type = "success";
                break;
                
            case 'delete_toko':
                $stmt = $pdo->prepare("DELETE FROM toko WHERE id_toko = ?");
                $stmt->execute([$_POST['id_toko']]);
                $message = "Toko berhasil dihapus!";
                $message_type = "success";
                break;
                
            case 'add_item':
                // FIX: Panggil fungsi lokal untuk membuat ID Item baru secara otomatis
                $new_item_id = generateItemId();
                
                // Masukkan ID baru tersebut bersama data dari form
                $stmt = $pdo->prepare("INSERT INTO item (id_item, nama_item, harga_item, id_toko) VALUES (?, ?, ?, ?)");
                $stmt->execute([$new_item_id, $_POST['nama_item'], $_POST['harga_item'], $_POST['id_toko']]);
                
                $message = "Menu berhasil ditambahkan!";
                $message_type = "success";
                break;
                
            case 'edit_item':
                $stmt = $pdo->prepare("UPDATE item SET nama_item = ?, harga_item = ?, id_toko = ? WHERE id_item = ?");
                $stmt->execute([$_POST['nama_item'], $_POST['harga_item'], $_POST['id_toko'], $_POST['id_item']]);
                $message = "Menu berhasil diupdate!";
                $message_type = "success";
                break;
                
            case 'delete_item':
                $stmt = $pdo->prepare("DELETE FROM item WHERE id_item = ?");
                $stmt->execute([$_POST['id_item']]);
                $message = "Menu berhasil dihapus!";
                $message_type = "success";
                break;
                
            case 'update_status':
                $stmt = $pdo->prepare("UPDATE transaksi SET status_transaksi = ? WHERE id_transaksi = ?");
                $stmt->execute([$_POST['status'], $_POST['id_transaksi']]);
                $message = "Status transaksi berhasil diupdate!";
                $message_type = "success";
                break;
        }
    } catch (PDOException $e) {
        // Jika ada error dari database, tampilkan pesan errornya
        $message = "Database Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Ambil semua data terbaru dari database untuk ditampilkan
$toko_list = $pdo->query("SELECT * FROM toko ORDER BY nama_toko")->fetchAll(PDO::FETCH_ASSOC);
$item_list = $pdo->query("SELECT i.*, t.nama_toko FROM item i JOIN toko t ON i.id_toko = t.id_toko ORDER BY t.nama_toko, i.nama_item")->fetchAll(PDO::FETCH_ASSOC);
$transaksi_list = $pdo->query("
    SELECT t.*, p.nama_pembeli, tk.nama_toko, ao.order_line 
    FROM transaksi t 
    JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan 
    JOIN toko tk ON t.id_toko = tk.id_toko 
    LEFT JOIN antrian_order ao ON t.id_transaksi = ao.id_transaksi 
    ORDER BY t.waktu_transaksi DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Food Court</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: #8B7355; color: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; text-align: center; }
        .nav-tabs { display: flex; background: white; border-radius: 10px; margin-bottom: 20px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-tab { flex: 1; padding: 15px; background: #e9ecef; border: none; cursor: pointer; font-size: 16px; font-weight: bold; transition: all 0.3s ease; }
        .nav-tab.active { background: #8B7355; color: white; }
        .tab-content { display: none; background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .tab-content.active { display: block; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: bold; text-decoration: none; display: inline-block; text-align: center; transition: all 0.3s ease; margin-right: 5px; }
        .btn-primary { background: #8B7355; color: white; }
        .btn-primary:hover { background: #704A3A; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; font-weight: bold; }
        .table tr:hover { background: #f5f5f5; }
        .table-actions { display: flex; gap: 5px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid; }
        .alert.success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .alert.error { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; color: white; }
        .status-diproses { background: #ffc107; color: #212529; }
        .status-selesai { background: #28a745; }
        .status-dibatalkan { background: #dc3545; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto; }
        .modal-content { background-color: white; margin: 10% auto; padding: 30px; border-radius: 10px; width: 90%; max-width: 500px; position: relative; }
        .modal-header { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Admin Dashboard - Food Court</h1>
            <p>Kelola toko, menu, dan transaksi</p>
        </div>
        
        <?php if (isset($message)): ?>
            <div class="alert <?= $message_type ?>" onclick="this.style.display='none'"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('transaksi')">Transaksi</button>
            <button class="nav-tab" onclick="showTab('toko')">Kelola Toko</button>
            <button class="nav-tab" onclick="showTab('menu')">Kelola Menu</button>
        </div>

        <div id="menu" class="tab-content">
            <h2>Kelola Menu</h2>
            <button class="btn btn-primary" onclick="openModal('addItemModal')">Tambah Menu Baru</button>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Item</th>
                        <th>Harga</th>
                        <th>Toko</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($item_list)): ?>
                        <tr><td colspan="4" style="text-align:center;">Belum ada menu.</td></tr>
                    <?php else: ?>
                        <?php foreach($item_list as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nama_item']) ?></td>
                            <td><?= formatRupiah($item['harga_item']) ?></td>
                            <td><?= htmlspecialchars($item['nama_toko']) ?></td>
                            <td class="table-actions">
                                <button class="btn btn-warning btn-sm" onclick="openEditItemModal('<?= $item['id_item'] ?>', '<?= htmlspecialchars(addslashes($item['nama_item'])) ?>', '<?= $item['harga_item'] ?>', '<?= $item['id_toko'] ?>')">Edit</button>
                                <form action="admin.php" method="post" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus menu ini?');">
                                    <input type="hidden" name="action" value="delete_item">
                                    <input type="hidden" name="id_item" value="<?= $item['id_item'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="transaksi" class="tab-content active">
            <h2>Daftar Transaksi</h2>
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order Line</th>
                            <th>Waktu</th>
                            <th>Pembeli</th>
                            <th>Toko</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transaksi_list)): ?>
                             <tr><td colspan="7" style="text-align:center;">Belum ada transaksi.</td></tr>
                        <?php else: ?>
                            <?php foreach($transaksi_list as $trx): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($trx['order_line'] ?? 'N/A') ?></strong></td>
                                <td><?= date('d/m/y H:i', strtotime($trx['waktu_transaksi'])) ?></td>
                                <td><?= htmlspecialchars($trx['nama_pembeli']) ?></td>
                                <td><?= htmlspecialchars($trx['nama_toko']) ?></td>
                                <td><?= formatRupiah($trx['total_harga']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($trx['status_transaksi']) ?>">
                                        <?= htmlspecialchars($trx['status_transaksi']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-warning btn-sm" onclick="openStatusModal('<?= $trx['id_transaksi'] ?>', '<?= $trx['status_transaksi'] ?>')">Ubah Status</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="toko" class="tab-content">
            <h2>Kelola Toko</h2>
            <button class="btn btn-primary" onclick="openModal('addTokoModal')">Tambah Toko Baru</button>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID Toko</th>
                        <th>Nama Toko</th>
                        <th>Jenis</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($toko_list)): ?>
                        <tr><td colspan="4" style="text-align:center;">Belum ada toko.</td></tr>
                    <?php else: ?>
                        <?php foreach($toko_list as $toko): ?>
                        <tr>
                            <td><?= htmlspecialchars($toko['id_toko']) ?></td>
                            <td><?= htmlspecialchars($toko['nama_toko']) ?></td>
                            <td><?= htmlspecialchars($toko['jenis_toko']) ?></td>
                            <td class="table-actions">
                                <button class="btn btn-warning btn-sm" onclick="openEditTokoModal('<?= htmlspecialchars(addslashes($toko['id_toko'])) ?>', '<?= htmlspecialchars(addslashes($toko['nama_toko'])) ?>', '<?= htmlspecialchars(addslashes($toko['jenis_toko'])) ?>')">Edit</button>
                                <form action="admin.php" method="post" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus toko ini? Semua menu terkait akan ikut terhapus.');">
                                    <input type="hidden" name="action" value="delete_toko">
                                    <input type="hidden" name="id_toko" value="<?= $toko['id_toko'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="addItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal('addItemModal')">&times;</span>
                <h2>Tambah Menu Baru</h2>
            </div>
            <form action="admin.php" method="POST">
                <input type="hidden" name="action" value="add_item">
                <div class="form-group">
                    <label for="add_nama_item">Nama Item</label>
                    <input type="text" id="add_nama_item" name="nama_item" required>
                </div>
                <div class="form-group">
                    <label for="add_harga_item">Harga</label>
                    <input type="number" id="add_harga_item" name="harga_item" required min="0">
                </div>
                <div class="form-group">
                    <label for="add_id_toko">Toko</label>
                    <select id="add_id_toko" name="id_toko" required>
                        <option value="">-- Pilih Toko --</option>
                        <?php foreach ($toko_list as $toko): ?>
                            <option value="<?= $toko['id_toko'] ?>"><?= htmlspecialchars($toko['nama_toko']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Tambah Menu</button>
            </form>
        </div>
    </div>

    <div id="editItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><span class="close" onclick="closeModal('editItemModal')">&times;</span><h2>Edit Menu</h2></div>
            <form action="admin.php" method="POST">
                <input type="hidden" name="action" value="edit_item"><input type="hidden" id="edit_id_item" name="id_item">
                <div class="form-group"><label for="edit_nama_item">Nama Item</label><input type="text" id="edit_nama_item" name="nama_item" required></div>
                <div class="form-group"><label for="edit_harga_item">Harga</label><input type="number" id="edit_harga_item" name="harga_item" required min="0"></div>
                <div class="form-group"><label for="edit_id_toko">Toko</label><select id="edit_id_toko" name="id_toko" required><option value="">-- Pilih Toko --</option><?php foreach ($toko_list as $toko): ?><option value="<?= $toko['id_toko'] ?>"><?= htmlspecialchars($toko['nama_toko']) ?></option><?php endforeach; ?></select></div>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><span class="close" onclick="closeModal('statusModal')">&times;</span><h2>Ubah Status Transaksi</h2></div>
            <form action="admin.php" method="POST">
                <input type="hidden" name="action" value="update_status"><input type="hidden" id="status_id_transaksi" name="id_transaksi">
                <div class="form-group"><label for="status_select">Status</label><select id="status_select" name="status" required><option value="diproses">Diproses</option><option value="selesai">Selesai</option><option value="dibatalkan">Dibatalkan</option></select></div>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </form>
        </div>
    </div>
    
    <div id="addTokoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><span class="close" onclick="closeModal('addTokoModal')">&times;</span><h2>Tambah Toko Baru</h2></div>
            <form action="admin.php" method="POST">
                <input type="hidden" name="action" value="add_toko">
                <div class="form-group"><label for="id_toko">ID Toko</label><input type="text" id="id_toko" name="id_toko" required></div>
                <div class="form-group"><label for="nama_toko">Nama Toko</label><input type="text" id="nama_toko" name="nama_toko" required></div>
                <div class="form-group"><label for="jenis_toko">Jenis Toko</label><input type="text" id="jenis_toko" name="jenis_toko" placeholder="cth: Makanan, Minuman" required></div>
                <button type="submit" class="btn btn-primary">Tambah</button>
            </form>
        </div>
    </div>

    <div id="editTokoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><span class="close" onclick="closeModal('editTokoModal')">&times;</span><h2>Edit Toko</h2></div>
            <form action="admin.php" method="POST">
                <input type="hidden" name="action" value="edit_toko"><input type="hidden" id="edit_id_toko" name="id_toko">
                <div class="form-group"><label for="edit_nama_toko">Nama Toko</label><input type="text" id="edit_nama_toko" name="nama_toko" required></div>
                <div class="form-group"><label for="edit_jenis_toko">Jenis Toko</label><input type="text" id="edit_jenis_toko" name="jenis_toko" required></div>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tampilkan tab pertama (transaksi) secara default saat halaman dimuat
            showTab('transaksi');
        });

        function showTab(tabId) {
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            // Menemukan tab yang sesuai dengan tabId dan menambahkan kelas active
            document.querySelector('.nav-tab[onclick="showTab(\'' + tabId + '\')"]').classList.add('active');
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function openStatusModal(transaksiId, currentStatus) {
            document.getElementById('status_id_transaksi').value = transaksiId;
            document.getElementById('status_select').value = currentStatus;
            openModal('statusModal');
        }

        function openEditTokoModal(id, nama, jenis) {
            document.getElementById('edit_id_toko').value = id;
            document.getElementById('edit_nama_toko').value = nama;
            document.getElementById('edit_jenis_toko').value = jenis;
            openModal('editTokoModal');
        }

        function openEditItemModal(id, nama, harga, tokoId) {
            document.getElementById('edit_id_item').value = id;
            document.getElementById('edit_nama_item').value = nama;
            document.getElementById('edit_harga_item').value = harga;
            document.getElementById('edit_id_toko').value = tokoId;
            openModal('editItemModal');
        }

        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>