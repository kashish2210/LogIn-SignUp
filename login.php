<?php
session_start();
require "DataBase.php";

$db = new DataBase();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        if ($db->dbConnect()) {
            if ($db->logIn("users", $_POST['username'], $_POST['password'])) {
                $_SESSION['username'] = $_POST['username'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Username or Password wrong";
            }
        } else {
            $error = "Error: Database connection";
        }
    } else {
        $error = "All fields are required";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <style>
    body {
      margin:0; height:100vh; display:flex; justify-content:center; align-items:center;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #1e3c72, #2a5298);
    }
    .glass {
      background: rgba(255,255,255,0.15);
      border-radius: 20px;
      padding: 40px;
      width: 320px;
      text-align: center;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.2);
      box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    }
    h2 { color: #fff; margin-bottom: 20px; }
    input {
      width: 100%; padding: 12px; margin: 10px 0;
      border: none; border-radius: 10px;
      outline: none; font-size: 16px;
    }
    button {
      width: 100%; padding: 12px;
      border: none; border-radius: 10px;
      background: rgba(255,255,255,0.25);
      color: #fff; font-size: 16px; cursor: pointer;
      transition: 0.3s;
    }
    button:hover { background: rgba(255,255,255,0.4); }
    .error { color: #ffcccc; font-size: 14px; }
    a { color: #fff; text-decoration: none; display:block; margin-top:10px; }
  </style>
</head>
<body>
  <div class="glass">
    <h2>🔑 Login</h2>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="post">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <a href="signup.php">Create an account →</a>
  </div>
</body>
</html>
