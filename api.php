<?php
date_default_timezone_set("Asia/Jakarta");

$uid = $_POST['uid'] ?? null;

if (!$uid || strlen($uid) < 4) {
    http_response_code(400);
    echo "UID tidak valid";
    exit;
}

$now = date("Y-m-d H:i:s");

// Koneksi ke database InfinityFree
$conn = new mysqli("sql301.infinityfree.com", "if0_38686148", "PASSWORD_KAMU", "if0_38686148_defaultdb");

if ($conn->connect_error) {
    http_response_code(500);
    echo "DB error";
    exit;
}

// Cek apakah UID sudah masuk dan belum keluar
$stmt = $conn->prepare("SELECT id FROM kehadiran WHERE uid=? AND waktu_keluar IS NULL ORDER BY waktu_masuk DESC LIMIT 1");
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update waktu_keluar
    $row = $result->fetch_assoc();
    $stmt = $conn->prepare("UPDATE kehadiran SET waktu_keluar=? WHERE id=?");
    $stmt->bind_param("si", $now, $row["id"]);
    $stmt->execute();
    echo "Waktu keluar dicatat: $now";
} else {
    // Masukkan waktu_masuk baru
    $stmt = $conn->prepare("INSERT INTO kehadiran (uid, waktu_masuk) VALUES (?, ?)");
    $stmt->bind_param("ss", $uid, $now);
    $stmt->execute();
    echo "Waktu masuk dicatat: $now";
}
?>
