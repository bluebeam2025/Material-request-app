<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_type = $_SESSION['user_type'] ?? null;
?>

<aside class="sidebar"> 
  <div class="logo">
    <img src="images/logo.png" alt="Company Logo" class="logo-img" />
  </div>

  <nav class="menu">
    <a href="dashboard.php">Dashboard</a>

    <?php if ($user_type === 'user'): ?>
      <a href="material_request.php">Material Requests</a>
      <a href="leave_request.php">Leave Request</a>
      <a href="expense_request.php">Expense Request</a>
    <?php endif; ?>

    <?php if (in_array($user_type, ['approver1', 'approver2', 'admin'])): ?>
      <a href="#">Material Requests</a>
      <a href="#">Leave Requests</a>
      <a href="#">Expense Requests</a>
      <a href="projects.php">Projects</a>
    <?php endif; ?>

    <?php if ($user_type === 'admin'): ?>
      <a href="#">Products</a>
      <a href="users.php">Users</a>
    <?php endif; ?>
  </nav>
</aside>
