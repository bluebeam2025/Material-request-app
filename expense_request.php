<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
  header('Location: dashboard.php');
  exit();
}
include 'php/db_connect.php';

$user_id = (int)$_SESSION['user_id'];

// Get assigned projects with expense summary
$projects = $conn->query("SELECT p.id, p.project_name,
    COALESCE(SUM(e.cash_in),0) as cash_in,
    COALESCE(SUM(e.cash_out),0) as cash_out,
    COALESCE(SUM(e.cash_in - e.cash_out),0) as balance
 FROM projects p
 JOIN project_users pu ON pu.project_id = p.id
 LEFT JOIN expense_requests e ON e.project_id = p.id AND e.user_id = $user_id
 WHERE pu.user_id = $user_id
 GROUP BY p.id
 ORDER BY p.project_name")
->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Expense Request – Bluebeam Infra</title>
<link rel="stylesheet" href="css/style.css">
<style>
table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 12px }
th, td { padding: 10px 12px; border-bottom: 1px solid #ccc; font-size: 0.92rem; color: #000 }
th { background: #0d47a1; color: #fff }
.open-btn { background: #1565c0; color: #fff; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: .85rem; }
.open-btn:hover { opacity: 0.85; }
</style>
</head><body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/header.php'; ?>

<div class="main-content">
  <h2>Expense Requests</h2>

  <table>
    <thead>
      <tr><th>SN</th><th>Project</th><th>Cash In</th><th>Cash Out</th><th>Balance</th><th>Sheet</th></tr>
    </thead>
    <tbody>
      <?php if ($projects): $i = 1; foreach($projects as $p): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($p['project_name']) ?></td>
          <td><?= number_format($p['cash_in'], 2) ?></td>
          <td><?= number_format($p['cash_out'], 2) ?></td>
          <td><?= number_format($p['balance'], 2) ?></td>
          <td>
            <a href="expense_sheet.php?project_id=<?= $p['id'] ?>" class="open-btn">Open</a>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="6">No assigned projects or expenses yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body></html>
