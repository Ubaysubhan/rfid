<?php
session_start();
// Redirect ke login jika belum login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Koneksi ke database di DigitalOcean
$host = "localhost"; 
$username = "rfiduser";
$password = "Subhan@123";
$database = "rfid_db";
$mysqli = new mysqli($host, $username, $password, $database);
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Handle SSE (Server-Sent Events) request
if(isset($_GET['sse']) && $_GET['sse'] == 'stream') {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    
    // Mengirimkan ID untuk mengidentifikasi update
    echo "id: " . time() . "\n";
    
    // Query untuk mengambil data terbaru
    $sql = "SELECT * FROM kehadiran ORDER BY id";
    $result = $mysqli->query($sql);
    
    $data = array();
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    // Mengirim data ke klien
    echo "data: " . json_encode($data) . "\n\n";
    
    // Memastikan output langsung dikirim
    ob_flush();
    flush();
    exit;
}

// Handle AJAX request untuk mendapatkan semua data
if(isset($_GET['action']) && $_GET['action'] == 'getdata') {
    $sql = "SELECT * FROM kehadiran ORDER BY id";
    $result = $mysqli->query($sql);
    
    $data = array();
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}



// Tampilkan halaman normal jika bukan AJAX/SSE request
$sql = "SELECT * FROM kehadiran ORDER BY id";
$result = $mysqli->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Biji Kakao</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #f4f4f4;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #007BFF;
            color: white;
        }
        h1 {
            color: #333;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .status-indicator {
            display: inline-block;
            margin-left: 15px;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        .online {
            background-color: #28a745;
            color: white;
        }
        .loading {
            background-color: #ffc107;
            color: black;
        }
        .error {
            background-color: #dc3545;
            color: white;
        }
        .last-update {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
            text-align: right;
        }
        .highlight {
            animation: highlight 3s;
        }
        @keyframes highlight {
            0% { background-color: #ffff99; }
            100% { background-color: transparent; }
        }
    </style>
</head>
<body>
<div class="top-bar">
    <div>
        <h1>Data Biji Kakao RFID</h1>
        <span class="status-indicator online" id="status">Online</span>
    </div>
    <div>
        <span style="margin-right: 10px;">👤 <?= htmlspecialchars($_SESSION["username"]) ?></span>
        <form action="logout.php" method="post" style="display:inline;">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</div>

<table id="data-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>UID</th>
            <th>Waktu Masuk</th>
            <th>Waktu Keluar</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row["id"]) ?></td>
                    <td><p>biji kakao</p><?= htmlspecialchars($row["uid"]) ?></td>
                    <td><?= htmlspecialchars($row["waktu_masuk"]) ?></td>
                    <td><?= htmlspecialchars($row["waktu_keluar"]) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">Belum ada data.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<div class="last-update" id="last-update">Terakhir diperbarui: <?= date('H:i:s') ?></div>

<script>
$(document).ready(function() {
    // Simpan data terakhir untuk perbandingan
    let lastData = [];
    let eventSource;
    
    // Fungsi untuk memulai koneksi SSE
    function startSSE() {
        // Tutup koneksi sebelumnya jika ada
        if (eventSource) {
            eventSource.close();
        }
        
        // Buat koneksi SSE baru
        eventSource = new EventSource('?sse=stream');
        
        // Event listener untuk menerima data
        eventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            updateTableWithHighlight(data);
            $("#status").removeClass("loading error").addClass("online").text("Online");
            $("#last-update").text("Terakhir diperbarui: " + new Date().toLocaleTimeString());
        };
        
        // Event listener untuk error
        eventSource.onerror = function() {
            $("#status").removeClass("loading online").addClass("error").text("Koneksi Terputus");
            eventSource.close();
            
            // Coba sambungkan kembali setelah 5 detik
            setTimeout(startSSE, 5000);
        };
    }
    function formatBijiKakao($uid) {
    // Jika UID berupa angka, langsung konversi ke format "Biji Kakao X"
    if (is_numeric($uid)) {
        return "Biji Kakao " . $uid;
    }
    
    // Fungsi untuk update tabel dan highlight baris baru
    function updateTableWithHighlight(data) {
        let tbody = '';
        let newRows = [];
        
        if (data.length > 0) {
            // Identifikasi baris baru berdasarkan ID
            if (lastData.length > 0) {
                const existingIds = lastData.map(item => item.id);
                newRows = data.filter(item => !existingIds.includes(item.id)).map(item => item.id);
            }

            // Buat HTML untuk semua baris
            $.each(data, function(i, row) {
                const isNew = newRows.includes(row.id);
                tbody += '<tr' + (isNew ? ' class="highlight"' : '') + ' data-id="' + escapeHTML(row.id) + '">';
                tbody += '<td>' + escapeHTML(row.id) + '</td>';
                tbody += '<td>' + escapeHTML(row.uid) + '</td>';
                tbody += '<td>' + escapeHTML(row.waktu_masuk) + '</td>';
                tbody += '<td>' + escapeHTML(row.waktu_keluar) + '</td>';
                tbody += '</tr>';
            });
        } else {
            tbody = '<tr><td colspan="4">Belum ada data.</td></tr>';
        }
        
        // Update tabel
        $('#data-table tbody').html(tbody);
        
        // Update lastData untuk perbandingan berikutnya
        lastData = data;
    }
    
    // Fungsi untuk escape HTML untuk keamanan
    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // Muat data awal dengan AJAX
    function loadInitialData() {
        $("#status").removeClass("online error").addClass("loading").text("Memuat data...");
        $.ajax({
            url: '?action=getdata',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                updateTableWithHighlight(data);
                $("#status").removeClass("loading error").addClass("online").text("Online");
                $("#last-update").text("Terakhir diperbarui: " + new Date().toLocaleTimeString());
                
                // Setelah memuat data awal, mulai SSE
                startSSE();
            },
            error: function(xhr, status, error) {
                console.error("Error: " + error);
                $("#status").removeClass("loading online").addClass("error").text("Error");
                
                // Coba lagi dalam 5 detik
                setTimeout(loadInitialData, 5000);
            }
        });
    }
    
    // Mulai dengan memuat data awal
    loadInitialData();
});

// Tambahkan handler untuk jika halaman di-unload (user pindah halaman)
window.addEventListener('beforeunload', function() {
    if (window.eventSource) {
        window.eventSource.close();
    }
});
</script>
</body>
</html>
