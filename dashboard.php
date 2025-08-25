<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <style>
    body {
      margin:0; height:100vh; display:flex; justify-content:center; align-items:center;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #8e2de2, #4a00e0);
      color: white;
    }
    .glass {
      background: rgba(255,255,255,0.15);
      border-radius: 20px;
      padding: 50px;
      width: 400px; text-align: center;
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255,255,255,0.2);
      box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    }
    h1 { font-size: 28px; margin-bottom: 20px; }
    p { font-size: 16px; margin-bottom: 30px; }
    a {
      display:inline-block;
      padding: 12px 20px;
      border-radius: 10px;
      background: rgba(255,255,255,0.25);
      color: #fff; text-decoration: none;
      transition:0.3s;
    }
    a:hover { background: rgba(255,255,255,0.4); }
  </style>
</head>
<body>
  <div class="glass">
    <h1>👋 Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    <p>You are successfully logged in to your dashboard.</p>
    <a href="logout.php">Logout</a>
  </div>
</body>
</html>
