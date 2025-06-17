<?php
require_once 'config.php';

// Handle CRUD operations
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_toko':
            $stmt = $pdo->prepare("INSERT INTO toko (id_toko, nama_toko, jenis_toko) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['id_toko'], $_POST['nama_toko'], $_POST['jenis_toko']]);
            $message = "Toko berhasil ditambahkan!";
            break;
            
        case 'edit_toko':
            $stmt = $pdo->prepare("UPDATE toko SET nama_toko = ?, jenis_toko = ? WHERE id_toko = ?");
            $stmt->execute([$_POST['nama_toko'], $_POST['jenis_toko'], $_POST['id_toko']]);
            $message = "Toko berhasil diupdate!";
            break;
            
        case 'delete_toko':
            $stmt = $pdo->prepare("DELETE FROM toko WHERE id_toko = ?");
            $stmt->execute([$_POST['id_toko']]);
            $message = "Toko berhasil dihapus!";
            break;
            
        case 'add_item':
            $stmt = $pdo->prepare("INSERT INTO item (id_item, nama_item, harga_item, id_toko) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['id_item'], $_POST['nama_item'], $_POST['harga_item'], $_POST['id_toko']]);
            $message = "Menu berhasil ditambahkan!";
            break;
            
        case 'edit_item':
            $stmt = $pdo->prepare("UPDATE item SET nama_item = ?, harga_item = ?, id_toko = ? WHERE id_item = ?");
            $stmt->execute([$_POST['nama_item'], $_POST['harga_item'], $_POST['id_toko'], $_POST['id_item']]);
            $message = "Menu berhasil diupdate!";
            break;
            
        case 'delete_item':
            $stmt = $pdo->prepare("DELETE FROM item WHERE id_item = ?");
            $stmt->execute([$_POST['id_item']]);
            $message = "Menu berhasil dihapus!";
            break;
            
        case 'update_status':
            $stmt = $pdo->prepare("UPDATE transaksi SET status_transaksi = ? WHERE id_transaksi = ?");
            $stmt->execute([$_POST['status'], $_POST['id_transaksi']]);
            $message = "Status transaksi berhasil diupdate!";
            break;
    }
}

// Fetch data
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: #8B7355;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .nav-tabs {
            display: flex;
            background: white;
            border-radius: 10px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-tab {
            flex: 1;
            padding: 15px;
            background: #e9ecef;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .nav-tab.active {
            background: #8B7355;
            color: white;
        }
        
        .nav-tab:hover {
            background: #8B7355;
            color: white;
        }
        
        .tab-content {
            display: none;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #8B7355;
            color: white;
        }
        
        .btn-primary:hover {
            background: #704A3A;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .table tr:hover {
            background: #f5f5f5;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
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
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Admin Dashboard - Food Court</h1>
            <p>Kelola toko, menu, dan transaksi</p>
        </div>
        
        <?php if (isset($message)): ?>
            <div class="alert"><?= $message ?></div>
        <?php endif; ?>
        
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('transaksi')">Transaksi</button>
            <button class="nav-