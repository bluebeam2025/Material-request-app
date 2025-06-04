<?php
session_start();
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'php/db_connect.php';

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['name'] = $user['name'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!-- Login HTML -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login - Bluebeam Infra</title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body class="login-body">
  <div class="login-wrapper">
    <h2 class="login-title">Sign In</h2>
    <div class="login-logo">
      <img src="images/logo.png" alt="Bluebeam Infra Logo" />
    </div>
    <?php if ($error): ?>
      <div class="error-msg"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="input-group">
        <label for="username">User ID</label>
        <input type="text" name="username" required />
      </div>
      <div class="input-group">
        <label for="password">Password</label>
        <input type="password" name="password" required />
      </div>
      <div class="forgot-password">
        <a href="#">Forgot Password?</a>
      </div>
      <button type="submit" class="login-btn">Sign In</button>
    </form>
  </div>
</body>
</html>
