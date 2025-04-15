<?php
session_start();

// Cek login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Koneksi database
$host = "localhost";
$username = "rfiduser";
$password = "Subhan@123";
$database = "rfid_db";

$mysqli = new mysqli($host, $username, $password, $database);
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Ambil semua data produk
$sql = "SELECT uid, waktu_masuk FROM kehadiran ORDER BY waktu_masuk DESC";
$result = $mysqli->query($sql);

// Hitung kapasitas gudang
$jumlahBarang = $result->num_rows;
$kapasitasMaksimum = 160;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gudang Kakao</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #f4f4f4;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
        }
        .kapasitas {
            font-size: 18px;
            color: #007BFF;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
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
    </style>
</head>
<body>

<div class="top-bar">
    <h1>Data Gudang Kakao</h1>
    <div class="kapasitas">Kapasitas: <?= $jumlahBarang ?>/<?= $kapasitasMaksimum ?></div>
</div>

<table>
    <tr>
        <th>UID</th>
        <th>Tanggal Masuk</th>
        <th>Deadline (60 Hari)</th>
    </tr>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): 
            $waktuMasuk = new DateTime($row['waktu_masuk']);
            $deadline = clone $waktuMasuk;
            $deadline->modify('+60 days');
        ?>
            <tr>
                <td><?= htmlspecialchars($row['uid']) ?></td>
                <td><?= $waktuMasuk->format('Y-m-d') ?></td>
                <td><?= $deadline->format('Y-m-d') ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="3">Belum ada data masuk.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
