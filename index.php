<?php
session_start();

// Redirect ke login jika belum login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Koneksi ke database InfinityFree
$host = "sql301.infinityfree.com";
$username = "if0_38686148";
$password = "4zG9vRBLnaOlE8";
$database = "if0_38686148_defaultdb";

$mysqli = new mysqli($host, $username, $password, $database);
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Ambil data dari tabel kehadiran
$sql = "SELECT * FROM kehadiran ORDER BY id";
$result = $mysqli->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Kehadiran</title>
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
    </style>
</head>
<body>

<div class="top-bar">
    <h1>Data Kehadiran RFID</h1>
    <div>
        <span style="margin-right: 10px;">👤 <?= htmlspecialchars($_SESSION["username"]) ?></span>
        <form action="logout.php" method="post" style="display:inline;">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>UID</th>
        <th>Waktu Masuk</th>
        <th>Waktu Keluar</th>
    </tr>

    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row["id"] ?></td>
                <td><?= $row["uid"] ?></td>
                <td><?= $row["waktu_masuk"] ?></td>
                <td><?= $row["waktu_keluar"] ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="4">Belum ada data.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
