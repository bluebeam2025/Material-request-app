<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'php/db_connect.php';
if (!$conn) { die("Database connection failed."); }

$user_type = $_SESSION['user_type'];
$totalUsers = 0;
$totalProjects = 0;

if (in_array($user_type, ['admin', 'approver1', 'approver2'])) {
    $user_result = $conn->query("SELECT COUNT(*) AS total FROM users");
    $totalUsers = $user_result->fetch_assoc()['total'];

    $project_result = $conn->query("SELECT COUNT(*) AS total FROM projects");
    $totalProjects = $project_result->fetch_assoc()['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard – Bluebeam Infra</title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo">
      <img src="images/logo.png" alt="Company Logo" class="logo-img" />
    </div>
    <nav class="menu">
      <a href="dashboard.php">Dashboard</a>

      <?php if ($user_type === 'user'): ?>
        <a href="material_request.php">Material Request</a>
        <a href="leave_request.php">Leave Request</a>
        <a href="expense_request.php">Expense Request</a>
      <?php endif; ?>

      <?php if (in_array($user_type, ['approver1', 'approver2', 'admin'])): ?>
        <a href="material_requests.php">Material Requests</a>
        <a href="leave_requests.php">Leave Requests</a>
        <a href="expense_requests.php">Expense Requests</a>
        <a href="suppliers.php">Suppliers</a>
      <?php endif; ?>

      <?php if ($user_type === 'admin'): ?>
        <a href="projects.php">Projects</a>
        <a href="products.php">Products</a>
        <a href="users.php">Users</a>
      <?php endif; ?>
    </nav>
  </aside>

  <!-- Main Content -->
  <div class="main-content">
    <header class="top-header">
      <div class="profile">
        <img src="img/user.png" alt="User" class="profile-pic">
        <span><?= htmlspecialchars($_SESSION['name']) ?> (<?= htmlspecialchars($user_type) ?>)</span>
        <form method="POST" action="logout.php" style="display:inline;">
          <button type="submit" class="logout-btn">Logout</button>
        </form>
      </div>
    </header>

    <main class="dashboard">
      <div class="tile"><div class="tile-title">Material Requests</div><div class="tile-count">Coming Soon</div></div>
      <div class="tile"><div class="tile-title">Leave Requests</div><div class="tile-count">Coming Soon</div></div>
      <div class="tile"><div class="tile-title">Expense Requests</div><div class="tile-count">Coming Soon</div></div>

      <?php if (in_array($user_type, ['admin', 'approver1', 'approver2'])): ?>
        <div class="tile"><div class="tile-title">Projects</div><div class="tile-count"><?= $totalProjects ?></div></div>
        <div class="tile"><div class="tile-title">Products</div><div class="tile-count">Coming Soon</div></div>
        <div class="tile"><div class="tile-title">Suppliers</div><div class="tile-count">Coming Soon</div></div>
        <div class="tile"><div class="tile-title">Users</div><div class="tile-count"><?= $totalUsers ?></div></div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
