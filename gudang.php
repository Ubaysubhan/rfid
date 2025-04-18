<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

$host = "localhost";
$username = "rfiduser";
$password = "Subhan@123";
$database = "rfid_db";

$mysqli = new mysqli($host, $username, $password, $database);
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

$sql = "SELECT id, uid, waktu_masuk FROM kehadiran ORDER BY waktu_masuk ";
$result = $mysqli->query($sql);

$jumlahBarang = $result->num_rows;
$kapasitasMaksimum = 160;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gudang Kakao</title>
    <meta http-equiv="refresh" content="5">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #f4f4f4;
        }
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .header img {
            width: 70px;
            margin-right: 20px;
        }
        h1 {
            margin: 0;
            color: #333;
        }
        .buttons {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .buttons a {
            text-decoration: none;
            background-color: #007BFF;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
        }
        .buttons a.logout {
            background-color: crimson;
        }
        .buttons span {
            display: flex;
            align-items: center;
            color: #333;
            font-weight: bold;
        }
        .kapasitas {
            font-size: 16px;
            color: #007BFF;
            font-weight: bold;
            margin-top: 5px;
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
        .warning {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="header">
    <img src="cocoa.png" alt="Logo Kakao">
    <div>
        <h1>Data Gudang Kakao</h1>
        <div class="kapasitas">Kapasitas: <?= $jumlahBarang ?>/<?= $kapasitasMaksimum ?></div>
    </div>
    <div class="buttons">
        <a href="index.php">🏠 Home</a>
        <span>👤 admin</span>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</div>

<table>
    <tr>
        <th>Nomor</th>
        <th>UID</th>
        <th>Tanggal Masuk</th>
        <th>Deadline (60 Hari)</th>
        <th>Sisa Hari</th>
    </tr>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php 
            $today = new DateTime(); 
            while($row = $result->fetch_assoc()): 
                $waktuMasuk = new DateTime($row['waktu_masuk']);
                $deadline = clone $waktuMasuk;
                $deadline->modify('+60 days');
                $interval = $today->diff($deadline);
                $sisaHari = (int)$interval->format('%r%a'); // %r untuk hasil bisa negatif
        ?>
            <tr>
                <td><?= htmlspecialchars($row["id"]) ?></td>
                <td><?= is_numeric($row["uid"]) ? "Biji Kakao " . htmlspecialchars($row["uid"]) : htmlspecialchars($row["uid"]) ?></td>
                <td><?= $waktuMasuk->format('Y-m-d') ?></td>
                <td><?= $deadline->format('Y-m-d') ?></td>
                <td class="<?= $sisaHari <= 0 ? 'warning' : '' ?>">
                    <?= $sisaHari > 0 ? "$sisaHari hari lagi" : "Sudah lewat" ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="4">Belum ada data masuk.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
