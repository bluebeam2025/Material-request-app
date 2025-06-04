<?php
if (!isset($_SESSION)) session_start();
?>
<header class="top-header">
  <div class="profile">
    <img src="img/user.png" alt="User" class="profile-pic">
    <span>
      <?= htmlspecialchars($_SESSION['name']) ?>
      (<?= htmlspecialchars($_SESSION['user_type']) ?>)
    </span>
    <form method="POST" action="logout.php" style="display:inline;">
      <button type="submit" class="logout-btn">Logout</button>
    </form>
  </div>
</header>