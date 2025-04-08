<?php
session_start();
// Redirect ke login jika belum login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Koneksi ke database
$host = "localhost"; 
$username = "rfiduser";
$password = "Subhan@123";
$database = "rfid_db";
$mysqli = new mysqli($host, $username, $password, $database);
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Jika request adalah untuk stream data (SSE)
if(isset($_GET['stream']) && $_GET['stream'] == 'data') {
    // Penting: Matikan output buffering
    if (ob_get_level()) ob_end_clean();
    
    // Set header untuk SSE
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no'); // Untuk Nginx
    
    // Fungsi untuk mengirim data
    function sendSSEData($mysqli) {
        $sql = "SELECT * FROM kehadiran ORDER BY id DESC";
        $result = $mysqli->query($sql);
        
        $data = array();
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Tambahkan format Biji Kakao
                if (is_numeric($row['uid'])) {
                    $row['display_uid'] = "Biji Kakao " . $row['uid'];
                } else {
                    $row['display_uid'] = $row['uid'];
                }
                $data[] = $row;
            }
        }
        
        echo "id: " . time() . "\n";
        echo "data: " . json_encode($data) . "\n\n";
        
        // Flush output
        flush();
    }
    
    // Kirim data pertama kali
    sendSSEData($mysqli);
    
    // Exit untuk mencegah output tambahan
    exit();
}

// Halaman utama
$sql = "SELECT * FROM kehadiran ORDER BY id DESC";
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
        .refresh-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        .manual-refresh {
            margin-right: 20px;
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
        .offline {
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
        <div class="status-bar">
            <span class="status-indicator online" id="status">Terhubung</span>
            <span id="timer"></span>
        </div>
    </div>
    <div>
        <span class="manual-refresh">
            <button id="refresh-btn" class="refresh-btn">Refresh Data</button>
        </span>
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
            <th>Biji Kakao</th>
            <th>Waktu Masuk</th>
            <th>Waktu Keluar</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row["id"]) ?></td>
                    <td><?= is_numeric($row["uid"]) ? "Biji Kakao " . htmlspecialchars($row["uid"]) : htmlspecialchars($row["uid"]) ?></td>
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
    let eventSource = null;
    let lastData = [];
    let reconnectAttempts = 0;
    let maxReconnectAttempts = 5;
    let reconnectInterval = 3000; // 3 detik
    let manualRefreshMode = false;
    let autoRefreshInterval;
    
    // Fungsi untuk memuat data dengan AJAX
    function loadData() {
        $.ajax({
            url: window.location.pathname,
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                // Ekstrak HTML tabel dari respons
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = response;
                const newTableBody = $(tempDiv).find('#data-table tbody').html();
                
                // Update tabel
                $('#data-table tbody').html(newTableBody);
                
                // Update waktu pembaruan
                $("#last-update").text("Terakhir diperbarui: " + new Date().toLocaleTimeString());
            }
        });
    }
    
    // Fungsi untuk memulai koneksi SSE
    function startSSE() {
        if (manualRefreshMode) return;
        
        // Tutup koneksi sebelumnya jika ada
        if (eventSource) {
            eventSource.close();
            eventSource = null;
        }
        
        try {
            // Buat koneksi SSE baru
            eventSource = new EventSource('?stream=data');
            
            // Event listener untuk menerima data
            eventSource.onmessage = function(event) {
                const data = JSON.parse(event.data);
                updateTable(data);
                $("#status").removeClass("offline").addClass("online").text("Terhubung");
                $("#last-update").text("Terakhir diperbarui: " + new Date().toLocaleTimeString());
                reconnectAttempts = 0; // Reset counter jika berhasil
            };
            
            // Event listener untuk terhubung
            eventSource.onopen = function() {
                $("#status").removeClass("offline").addClass("online").text("Terhubung");
                reconnectAttempts = 0; // Reset counter jika berhasil
            };
            
            // Event listener untuk error
            eventSource.onerror = function() {
                eventSource.close();
                eventSource = null;
                $("#status").removeClass("online").addClass("offline").text("Terputus");
                
                // Coba sambungkan kembali dengan interval yang bertambah
                reconnectAttempts++;
                if (reconnectAttempts <= maxReconnectAttempts) {
                    setTimeout(startSSE, reconnectInterval * reconnectAttempts);
                } else {
                    // Jika gagal beberapa kali, aktifkan mode refresh manual
                    manualRefreshMode = true;
                    $("#status").text("Mode Manual - Koneksi Real-time Gagal");
                    startAutoRefresh();
                }
            };
        } catch (e) {
            console.error("Error starting SSE:", e);
            manualRefreshMode = true;
            $("#status").removeClass("online").addClass("offline").text("Mode Manual - Error");
            startAutoRefresh();
        }
    }
    
    // Fungsi untuk update tabel dari data JSON
    function updateTable(data) {
        let tbody = '';
        let newRows = [];
        
        if (data.length > 0) {
            // Identifikasi baris baru
            const existingIds = lastData.map(item => item.id);
            newRows = data.filter(item => !existingIds.includes(item.id)).map(item => item.id);
            
            // Buat HTML untuk setiap baris
            $.each(data, function(i, row) {
                const isNew = newRows.includes(row.id);
                tbody += '<tr' + (isNew ? ' class="highlight"' : '') + '>';
                tbody += '<td>' + escapeHTML(row.id) + '</td>';
                tbody += '<td>' + escapeHTML(row.display_uid) + '</td>';
                tbody += '<td>' + escapeHTML(row.waktu_masuk) + '</td>';
                tbody += '<td>' + escapeHTML(row.waktu_keluar || '') + '</td>';
                tbody += '</tr>';
            });
        } else {
            tbody = '<tr><td colspan="4">Belum ada data.</td></tr>';
        }
        
        // Update tabel
        $('#data-table tbody').html(tbody);
        
        // Update data terakhir
        lastData = data;
    }
    
    // Fungsi untuk escape HTML
    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // Fungsi untuk aktifkan refresh otomatis sebagai fallback
    function startAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
        autoRefreshInterval = setInterval(loadData, 3000); // Refresh setiap 3 detik
    }
    
    // Tombol refresh manual
    $("#refresh-btn").click(function() {
        loadData();
    });
    
    // Deteksi apakah browser mendukung SSE
    if (typeof(EventSource) !== "undefined") {
        // Mulai SSE
        startSSE();
    } else {
        // Fallback untuk browser yang tidak mendukung SSE
        manualRefreshMode = true;
        $("#status").removeClass("online").addClass("offline").text("Mode Manual - Browser Tidak Mendukung SSE");
        startAutoRefresh();
    }
});
</script>
</body>
</html>
