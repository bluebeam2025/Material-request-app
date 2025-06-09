// ✅ FILE: expense_request.php (User Panel)
<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
  header('Location: dashboard.php');
  exit();
}
include 'php/db_connect.php';

$user_id = (int)$_SESSION['user_id'];

// Get all expense sheets for the user
$expenses = $conn->query("SELECT er.id, p.project_name,
  SUM(CASE WHEN ee.cash_in > 0 THEN ee.cash_in ELSE 0 END) AS total_in,
  SUM(CASE WHEN ee.cash_out > 0 THEN ee.cash_out ELSE 0 END) AS total_out,
  (SUM(CASE WHEN ee.cash_in > 0 THEN ee.cash_in ELSE 0 END) -
   SUM(CASE WHEN ee.cash_out > 0 THEN ee.cash_out ELSE 0 END)) AS balance
  FROM expense_requests er
  JOIN projects p ON p.id = er.project_id
  LEFT JOIN expense_entries ee ON ee.request_id = er.id
  WHERE er.user_id = $user_id
  GROUP BY er.id, p.project_name
  ORDER BY er.id DESC")
  ->fetch_all(MYSQLI_ASSOC);

// Dropdown data
$projects = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name")
  ->fetch_all(MYSQLI_ASSOC);
$expenseSheets = $conn->query("SELECT er.id, p.project_name FROM expense_requests er JOIN projects p ON p.id = er.project_id WHERE er.user_id = $user_id")
  ->fetch_all(MYSQLI_ASSOC);
$approvers = $conn->query("SELECT id, name FROM users WHERE user_type <> 'admin' AND id <> $user_id ORDER BY name")
  ->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Expense Requests – Bluebeam Infra</title>
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .tabs { display: flex; gap: 10px; margin-bottom: 16px; }
    .tab-btn {
      background: #0d47a1; color: #fff; padding: 8px 16px;
      border: none; border-radius: 6px; cursor: pointer;
    }
    .tab-btn.active { background: #1565c0; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
    th, td { padding: 10px 12px; border-bottom: 1px solid #ccc; font-size: 0.92rem; color: #000; text-align: center }
    th { background: #0d47a1; color: #fff; }
    .open-btn {
      background: #1565c0; color: #fff; padding: 6px 12px;
      border: none; border-radius: 4px; font-size: 0.85rem; cursor: pointer;
      text-decoration: none;
    }
    .open-btn:hover { opacity: 0.9; }
    .submit-btn {
      background: #2e7d32; color: #fff; padding: 8px 18px;
      border: none; border-radius: 6px; margin-top: 12px;
      cursor: pointer;
    }
    .submit-btn:hover { opacity: .9 }
  </style>
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/header.php'; ?>

<div class="main-content">
  <div class="user-header">
    <h2>Expense Management</h2>
    <div class="tabs">
      <button class="tab-btn active" onclick="showTab('sheet')">Expense Sheet</button>
      <button class="tab-btn" onclick="showTab('request')">Expense Request</button>
    </div>
  </div>

  <!-- Expense Sheets -->
  <div id="sheet" class="tab-content active">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <h3>Expense Sheets</h3>
      <a href="php/add_expense.php" class="add-user-btn">+ Add Expense Sheet</a>
    </div>
    <table>
      <thead><tr>
        <th>SN</th><th>Project Name</th><th>Cash In</th><th>Cash Out</th><th>Balance</th><th>Action</th>
      </tr></thead>
      <tbody>
        <?php if ($expenses): $sn = 1; foreach ($expenses as $e): ?>
          <tr>
            <td><?= $sn++ ?></td>
            <td><?= htmlspecialchars($e['project_name']) ?></td>
            <td><?= number_format($e['total_in'], 2) ?></td>
            <td><?= number_format($e['total_out'], 2) ?></td>
            <td><?= number_format($e['balance'], 2) ?></td>
            <td><a class="open-btn" href="php/view_expense.php?id=<?= $e['id'] ?>">Open</a></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="6">No expense sheets yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Expense Request Tab -->
  <div id="request" class="tab-content">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <h3>Submit Expense Request</h3>
    </div>
    <form method="POST" action="php/add_expense_request.php">
      <label>Project</label>
      <select name="project_id" required>
        <option value="">Select Project</option>
        <?php foreach ($projects as $p): ?>
          <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['project_name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Amount Required</label>
      <input type="number" name="amount" step="0.01" required>

      <label>Request Date</label>
      <input type="date" name="request_date" required>

      <label>Required By</label>
      <input type="date" name="required_date" required>

      <label>Related Expense Sheet</label>
      <select name="related_request_id">
        <option value="">None</option>
        <?php foreach ($expenseSheets as $s): ?>
          <option value="<?= $s['id'] ?>">Sheet #<?= $s['id'] ?> (<?= htmlspecialchars($s['project_name']) ?>)</option>
        <?php endforeach; ?>
      </select>

      <label>Approver 1</label>
      <select name="approver1_id" required>
        <option value="">Select Approver</option>
        <?php foreach ($approvers as $a): ?>
          <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Approver 2</label>
      <select name="approver2_id" required>
        <option value="">Select Approver</option>
        <?php foreach ($approvers as $a): ?>
          <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <button class="submit-btn">Submit Request</button>
    </form>
  </div>
</div>

<script>
function showTab(tab) {
  document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById(tab).classList.add('active');
  event.target.classList.add('active');
}
</script>
</body>
</html>
