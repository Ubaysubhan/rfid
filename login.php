<?php
session_start();

// Koneksi ke database DigitalOcean
$host = "localhost"; // karena PHP & MySQL di server yang sama
$username = "rfiduser"; // sesuaikan dengan user yang kamu buat di MySQL
$password = "Subhan@123"; // ganti ke password yang kamu set
$database = "rfid_db"; // nama database kamu

$mysqli = new mysqli($host, $username, $password, $database);
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_user = trim($_POST["username"]);
    $input_pass = hash("sha256", $_POST["password"]);

    $stmt = $mysqli->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $input_user, $input_pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $_SESSION["loggedin"] = true;
        $_SESSION["username"] = $input_user;
        header("Location: index.php");
        exit;
    } else {
        $error = "Username atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Admin</title>
    <style>
        body {
            font-family: Arial;
            background: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-box {
            background: white;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            border-radius: 8px;
            width: 300px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 8px 0 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="submit"] {
            background: #007BFF;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
        }
        .error {
            color: red;
            margin-bottom: 12px;
        }
        h2 {
            text-align: center;
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Login Admin</h2>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <input type="submit" value="Login">
    </form>
</div>

</body>
</html>
