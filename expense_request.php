<?php
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
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
      text-decoration: none;
    }
    .open-btn:hover {
      opacity: 0.9;
    }
    .tab-btn {
      padding: 8px 14px; border: none; border-radius: 4px; margin-right: 8px;
      cursor: pointer; font-size: 0.88rem;
    }
    .tab-active { background: #0d47a1; color: #fff; }
    .tab-inactive { background: #ccc; color: #000; }
  </style>
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/header.php'; ?>

<div class="main-content">
  <div class="user-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <button class="tab-btn tab-active" onclick="toggleTab('sheet')">Expense Sheet</button>
      <button class="tab-btn tab-inactive" onclick="toggleTab('request')">Expense Request</button>
    </div>
    <div>
      <a href="php/add_expense.php" class="add-user-btn">+ Add Expense Sheet</a>
      <button class="add-user-btn" onclick="openRequestModal()">+ Add Expense Request</button>
    </div>
  </div>

  <!-- Expense Sheet Section -->
  <div id="sheet-section">
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
              <a class="open-btn" href="php/view_expense.php?id=<?= $e['id'] ?>">Open</a>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="6">No expense requests yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Expense Request Section -->
  <div id="request-section" style="display:none">
    <!-- Placeholder for Expense Request List (optional) -->
    <p style="margin-top:20px">Coming soon: List of expense requests...</p>
  </div>
</div>

<!-- EXPENSE REQUEST MODAL -->
<div class="modal" id="requestModal"><div class="modal-content">
  <span class="close-btn" onclick="closeRequestModal()">&times;</span>
  <h3>Add Expense Request</h3>
  <form method="POST" action="php/add_expense_request.php">
    <label>Project</label>
    <select name="project_id" required>
      <option value="">Select Project</option>
      <?php
      $projects = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name")->fetch_all(MYSQLI_ASSOC);
      foreach ($projects as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['project_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Amount Required</label>
    <input type="number" name="amount" required step="0.01">
    <label>Request Date</label>
    <input type="date" name="request_date" required>
    <label>Required By</label>
    <input type="date" name="required_date" required>
    <label>Related Expense Sheet</label>
    <select name="sheet_id">
      <option value="">Select (optional)</option>
      <?php foreach ($expenses as $e): ?>
        <option value="<?= $e['id'] ?>">Sheet #<?= $e['id'] ?> – <?= htmlspecialchars($e['project_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Approver 1</label>
    <select name="approver1_id" required>
      <option value="">Select</option>
      <?php
      $approvers = $conn->query("SELECT id, name FROM users WHERE user_type <> 'admin' AND id <> $user_id")->fetch_all(MYSQLI_ASSOC);
      foreach ($approvers as $a): ?>
        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Approver 2</label>
    <select name="approver2_id" required>
      <option value="">Select</option>
      <?php foreach ($approvers as $a): ?>
        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="submit-btn">Submit Request</button>
  </form>
</div></div>

<script>
function toggleTab(tab) {
  const sheet = document.getElementById('sheet-section');
  const req = document.getElementById('request-section');
  document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('tab-active'));
  if (tab === 'sheet') {
    sheet.style.display = 'block'; req.style.display = 'none';
    document.querySelectorAll('.tab-btn')[0].classList.add('tab-active');
  } else {
    req.style.display = 'block'; sheet.style.display = 'none';
    document.querySelectorAll('.tab-btn')[1].classList.add('tab-active');
  }
}
function openRequestModal(){document.getElementById('requestModal').style.display='flex';}
function closeRequestModal(){document.getElementById('requestModal').style.display='none';}
window.onclick = e => { if (e.target.classList.contains('modal')) e.target.style.display='none'; };
</script>
</body>
</html>
