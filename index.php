<?php
session_start();

// Redirect ke login jika belum login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Koneksi ke database di DigitalOcean
$host = "localhost"; // karena file PHP ada di server yang sama dengan MySQL
$username = "rfiduser"; // ganti sesuai user yang kamu buat di MySQL DigitalOcean
$password = "Subhan@123"; // ganti sesuai password yang kamu set
$database = "rfid_db"; // ganti sesuai nama database kamu

$mysqli = new mysqli($host, $username, $password, $database);
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Ambil data dari tabel kehadiran
$sql = "SELECT * FROM kehadiran ORDER BY id";
$result = $mysqli->query($sql);

// Jika ada permintaan AJAX untuk menambahkan UID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uid'])) {
    $uid = htmlspecialchars($_POST['uid']);
    $waktu_masuk = date('Y-m-d H:i:s'); // Waktu saat UID diterima

    // Simpan UID ke database
    $stmt = $mysqli->prepare("INSERT INTO kehadiran (uid, waktu_masuk) VALUES (?, ?)");
    $stmt->bind_param("ss", $uid, $waktu_masuk);
    if ($stmt->execute()) {
        echo "UID berhasil ditambahkan: " . $uid;
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    exit; // Keluar setelah menangani permintaan AJAX
}
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
        #hasil {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <h1>Data Biji Kakao RFID</h1>
    <div>
        <span style="margin-right: 10px;">👤 <?= htmlspecialchars($_SESSION["username"]) ?></span>
        <form action="logout.php" method="post" style="display:inline;">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</div>

<!-- Form untuk input UID -->
<input type="text" id="uid" placeholder="Masukkan UID">
<button id="submit">Kirim</button>
<div id="hasil"></div>

<table>
    <tr>
        <th>ID</th>
        <th>UID</th>
        <th>Waktu Masuk</th>
        <th>Waktu Keluar</th>
    </tr>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row["id"]) ?></td>
                <td><?= htmlspecialchars($row["uid"]) ?></td>
                <td><?= htmlspecialchars($row["waktu_masuk"]) ?></td>
                <td><?= htmlspecialchars($
