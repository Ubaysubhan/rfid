<?php
session_start();

$host = "localhost";
$username = "rfiduser";
$password = "Subhan@123";
$database = "rfid_db";

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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #a1c4fd, #c2e9fb);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-box {
            background: #fff;
            padding: 40px 30px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            border-radius: 10px;
            width: 340px;
        }
        .login-box h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        input[type="submit"] {
            width: 100%;
            background: #007bff;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        input[type="submit"]:hover {
            background: #0056b3;
        }
        .error {
            color: #d93025;
            background: #fbeaea;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 14px;
        }
        label {
            font-weight: 500;
            font-size: 14px;
            color: #444;
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
