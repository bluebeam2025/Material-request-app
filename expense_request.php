<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
  header('Location: dashboard.php');
  exit();
}
include 'php/db_connect.php';

$user_id = (int)$_SESSION['user_id'];

$expenses = $conn->query("
  SELECT 
    er.id,
    p.project_name,
    SUM(CASE WHEN ee.cash_in > 0 THEN ee.cash_in ELSE 0 END) AS total_in,
    SUM(CASE WHEN ee.cash_out > 0 THEN ee.cash_out ELSE 0 END) AS total_out,
    (SUM(CASE WHEN ee.cash_in > 0 THEN ee.cash_in ELSE 0 END) -
     SUM(CASE WHEN ee.cash_out > 0 THEN ee.cash_out ELSE 0 END)) AS balance
  FROM expense_requests er
  JOIN projects p ON p.id = er.project_id
  LEFT JOIN expense_entries ee ON ee.request_id = er.id
  WHERE er.user_id = $user_id
  GROUP BY er.id, p.project_name
  ORDER BY er.id DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Expense Requests – Bluebeam Infra</title>
  <link rel="stylesheet" href="css/style.css" />
  <style>
    table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
    th, td { padding: 10px 12px; border-bottom: 1px solid #ccc; font-size: 0.92rem; color: #000; text-align: center }
    th { background: #0d47a1; color: #fff; }
    .open-btn {
      background: #1565c0;
      color: #fff;
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      font-size: 0.85rem;
      cursor: pointer;
    }
    .open-btn:hover {
      opacity: 0.9;
    }
  </style>
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/header.php'; ?>

<div class="main-content">
  <div class="user-header" style="display: flex; justify-content: space-between; align-items: center;">
    <h2>Expense Requests</h2>
    <a href="add_expense_sheet.php" class="add-user-btn">+ Add Expense Sheet</a>
  </div>

  <table>
    <thead>
      <tr>
        <th>SN</th>
        <th>Project Name</th>
        <th>Cash In</th>
        <th>Cash Out</th>
        <th>Balance</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($expenses): $sn = 1; foreach ($expenses as $e): ?>
        <tr>
          <td><?= $sn++ ?></td>
          <td><?= htmlspecialchars($e['project_name']) ?></td>
          <td><?= number_format($e['total_in'], 2) ?></td>
          <td><?= number_format($e['total_out'], 2) ?></td>
          <td><?= number_format($e['balance'], 2) ?></td>
          <td>
            <a class="open-btn" href="view_expense.php?id=<?= $e['id'] ?>">Open</a>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="6">No expense requests yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
