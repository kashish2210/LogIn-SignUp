<?php
require "DataBase.php";
$db = new DataBase();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['fullname'], $_POST['email'], $_POST['username'], $_POST['password'])) {
        if ($db->dbConnect()) {
            if ($db->signUp("users", $_POST['fullname'], $_POST['email'], $_POST['username'], $_POST['password'])) {
                $success = "Account created! 🎉 <a href='login.php'>Login here</a>";
            } else {
                $error = "Sign up failed. Try again.";
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
  <title>Sign Up</title>
  <style>
    body { margin:0; height:100vh; display:flex; justify-content:center; align-items:center;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #11998e, #38ef7d);
    }
    .glass {
      background: rgba(255,255,255,0.15);
      border-radius: 20px;
      padding: 40px; width: 340px; text-align: center;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.2);
      box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    }
    h2 { color: #fff; margin-bottom: 20px; }
    input, button { width: 100%; padding: 12px; margin: 10px 0; border-radius: 10px; border:none; outline:none; }
    input { font-size: 16px; }
    button { background: rgba(255,255,255,0.25); color: #fff; cursor: pointer; transition:0.3s; }
    button:hover { background: rgba(255,255,255,0.4); }
    .error { color: #ffcccc; font-size: 14px; }
    .success { color: #caffca; font-size: 14px; }
    a { color: #fff; text-decoration: none; display:block; margin-top:10px; }
  </style>
</head>
<body>
  <div class="glass">
    <h2>📝 Sign Up</h2>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>
    <form method="post">
      <input type="text" name="fullname" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Sign Up</button>
    </form>
    <a href="login.php">← Back to Login</a>
  </div>
</body>
</html>
