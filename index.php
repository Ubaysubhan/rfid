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

// Handle AJAX request untuk mendapatkan data terbaru
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

// Tampilkan halaman normal jika bukan AJAX request
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
        .refresh-rate {
            margin-left: 20px;
            display: inline-flex;
            align-items: center;
        }
        .refresh-rate select {
            margin-left: 5px;
            padding: 5px;
        }
        .last-update {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
            text-align: right;
        }
    </style>
</head>
<body>
<div class="top-bar">
    <div>
        <h1>Data Biji Kakao RFID</h1>
        <span class="status-indicator online" id="status">Online</span>
        <span class="refresh-rate">
            Refresh: 
            <select id="refresh-rate">
                <option value="1000">1 detik</option>
                <option value="3000" selected>3 detik</option>
                <option value="5000">5 detik</option>
                <option value="10000">10 detik</option>
            </select>
        </span>
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
                    <td><?= htmlspecialchars($row["uid"]) ?></td>
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
    let refreshInterval;
    let intervalTime = 3000; // Default 3 detik
    
    // Fungsi untuk memuat data
    function loadData() {
        $("#status").removeClass("online error").addClass("loading").text("Memperbarui...");
        
        $.ajax({
            url: '?action=getdata',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                updateTable(data);
                $("#status").removeClass("loading error").addClass("online").text("Online");
                $("#last-update").text("Terakhir diperbarui: " + new Date().toLocaleTimeString());
            },
            error: function(xhr, status, error) {
                console.error("Error: " + error);
                $("#status").removeClass("loading online").addClass("error").text("Error");
            }
        });
    }
    
    // Fungsi untuk update tabel
    function updateTable(data) {
        let tbody = '';
        
        if (data.length > 0) {
            $.each(data, function(i, row) {
                tbody += '<tr>';
                tbody += '<td>' + escapeHTML(row.id) + '</td>';
                tbody += '<td>' + escapeHTML(row.uid) + '</td>';
                tbody += '<td>' + escapeHTML(row.waktu_masuk) + '</td>';
                tbody += '<td>' + escapeHTML(row.waktu_keluar) + '</td>';
                tbody += '</tr>';
            });
        } else {
            tbody = '<tr><td colspan="4">Belum ada data.</td></tr>';
        }
        
        $('#data-table tbody').html(tbody);
    }
    
    // Fungsi untuk escape HTML untuk keamanan
    function escapeHTML(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // Atur interval refresh berdasarkan pilihan pengguna
    $('#refresh-rate').on('change', function() {
        intervalTime = parseInt($(this).val());
        
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        
        refreshInterval = setInterval(loadData, intervalTime);
    });
    
    // Jalankan interval awal
    refreshInterval = setInterval(loadData, intervalTime);
    
    // Load data pertama kali
    loadData();
});
</script>
</body>
</html>
