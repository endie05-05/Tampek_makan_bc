<?php
require_once 'config.php';

// Ambil data meja dan toko
$stmt_meja = $pdo->query("SELECT * FROM meja ORDER BY no_meja");
$meja_list = $stmt_meja->fetchAll(PDO::FETCH_ASSOC);

$stmt_toko = $pdo->query("SELECT * FROM toko ORDER BY nama_toko");
$toko_list = $stmt_toko->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Court System</title>
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
            max-width: 500px;
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
        
        .section-title {
            color: #8B7355;
            font-size: 16px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .website-name {
            font-size: 32px;
            font-weight: bold;
            color: #2C2C2C;
            margin: 20px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2C2C2C;
            font-weight: bold;
        }
        
        .form-group select, .form-group input {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #FFFFFF;
            font-size: 16px;
            color: #2C2C2C;
        }
        
        .meja-display {
            background: #8B7355;
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .date-display {
            background: #8B7355;
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-size: 16px;
            margin: 15px 0;
        }
        
        .toko-list {
            margin-top: 20px;
        }
        
        .toko-item {
            display: flex;
            align-items: center;
            background: #8B7355;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .toko-item:hover {
            background: #704A3A;
            transform: translateY(-2px);
        }
        
        .toko-icon {
            width: 40px;
            height: 40px;
            background: #F5E6A3;
            border-radius: 8px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .toko-name {
            font-size: 18px;
            font-weight: bold;
        }
        
        .start-btn {
            width: 100%;
            padding: 15px;
            background: #8B7355;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .start-btn:hover {
            background: #704A3A;
        }
        
        .start-btn:disabled {
            background: #CCCCCC;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="section-title">SECTION 1</div>
            <div class="website-name">BC FOOD NET</div>
        </div>
        
        <form id="orderForm">
            <div class="form-group">
                <label for="no_meja">Pilih Nomor Meja:</label>
                <select id="no_meja" name="no_meja" required>
                    <option value="">-- Pilih Meja --</option>
                    <?php foreach($meja_list as $meja): ?>
                        <option value="<?= $meja['no_meja'] ?>"><?= $meja['no_meja'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="meja-display" id="mejaDisplay" style="display: none;">
                No. Meja <span id="selectedMeja">-</span>
            </div>
            
            <div class="date-display" id="realtime-clock">
                Memuat jam...
            </div>
            
            <div class="form-group">
                <label for="nama_pembeli">Nama Pembeli:</label>
                <input type="text" id="nama_pembeli" name="nama_pembeli" required>
            </div>
            
            <button type="button" class="start-btn" id="startOrder" disabled>
                Mulai Pesan
            </button>
        </form>
        
        <div class="toko-list" id="tokoList" style="display: none;">
            <h3 style="color: #2C2C2C; margin-bottom: 15px;">Pilih Toko:</h3>
            <?php foreach($toko_list as $toko): ?>
                <a href="menu.php?toko=<?= $toko['id_toko'] ?>&meja=MEJA_PLACEHOLDER&nama=NAMA_PLACEHOLDER" 
                   class="toko-item" 
                   data-toko="<?= $toko['id_toko'] ?>">
                    <div class="toko-icon">🏪</div>
                    <div class="toko-name"><?= $toko['nama_toko'] ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        const noMejaSelect = document.getElementById('no_meja');
        const namaPembeliInput = document.getElementById('nama_pembeli');
        const startOrderBtn = document.getElementById('startOrder');
        const mejaDisplay = document.getElementById('mejaDisplay');
        const selectedMejaSpan = document.getElementById('selectedMeja');
        const tokoList = document.getElementById('tokoList');
        
        function checkFormValid() {
            const isValid = noMejaSelect.value && namaPembeliInput.value.trim();
            startOrderBtn.disabled = !isValid;
            
            if (noMejaSelect.value) {
                mejaDisplay.style.display = 'block';
                selectedMejaSpan.textContent = noMejaSelect.value;
            } else {
                mejaDisplay.style.display = 'none';
            }
        }
        
        noMejaSelect.addEventListener('change', checkFormValid);
        namaPembeliInput.addEventListener('input', checkFormValid);
        
        startOrderBtn.addEventListener('click', function() {
            tokoList.style.display = 'block';
            startOrderBtn.style.display = 'none';
            
            // Update links dengan data sebenarnya
            const tokoLinks = document.querySelectorAll('.toko-item');
            tokoLinks.forEach(link => {
                const href = link.getAttribute('href');
                const newHref = href
                    .replace('MEJA_PLACEHOLDER', noMejaSelect.value)
                    .replace('NAMA_PLACEHOLDER', encodeURIComponent(namaPembeliInput.value));
                link.setAttribute('href', newHref);
            });
        });

        // --- SCRIPT JAM REAL-TIME ---
        function updateClock() {
            const clockElement = document.getElementById('realtime-clock');
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            const dayName = days[now.getDay()];
            const day = now.getDate();
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();
            
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            clockElement.textContent = `${dayName}, ${day} ${monthName} ${year} | ${hours}:${minutes}:${seconds}`;
        }
        
        // Update jam setiap detik
        setInterval(updateClock, 1000);
        
        // Panggil sekali saat halaman dimuat
        updateClock();
        // --- AKHIR SCRIPT JAM REAL-TIME ---

    </script>
</body>
</html>