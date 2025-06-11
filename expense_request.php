<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: dashboard.php'); exit();
}
include 'php/db_connect.php';

$user_id = (int)$_SESSION['user_id'];

// Fetch expense sheets
$expenses = $conn->query("
  SELECT er.id, p.project_name,
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

// Dropdown data
$projects = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name")
    ->fetch_all(MYSQLI_ASSOC);

$expenseSheets = $conn->query("SELECT er.id, p.project_name FROM expense_requests er JOIN projects p ON p.id = er.project_id WHERE er.user_id = $user_id")
    ->fetch_all(MYSQLI_ASSOC);

$approvers = $conn->query("SELECT id, name FROM users WHERE user_type <> 'admin' AND id <> $user_id ORDER BY name")
    ->fetch_all(MYSQLI_ASSOC);

$flash = $_SESSION['success'] ?? ($_SESSION['error'] ?? '');
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Expense Requests – Bluebeam Infra</title>
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .tabs { display:flex; gap:10px; margin-bottom:16px }
    .tab-btn { padding:8px 14px; border:none; border-radius:4px; cursor:pointer; font-size:.9rem }
    .tab-active { background:#0d47a1; color:#fff }
    .tab-inactive { background:#ccc; color:#000 }
    table { width:100%; border-collapse:collapse; margin-top:15px; background:#fff }
    th, td { padding:10px 12px; border-bottom:1px solid #ccc; font-size:.92rem; color:#000; text-align:center }
    th { background:#0d47a1; color:#fff }
    .open-btn, .delete-btn, .print-btn {
      padding:6px 10px; border:none; border-radius:4px; font-size:.85rem; cursor:pointer; text-decoration:none;
    }
    .open-btn { background:#1565c0; color:#fff }
    .delete-btn { background:#c62828; color:#fff; margin-left:4px }
    .print-btn { background:#4e342e; color:#fff; margin-left:4px }
    .submit-btn { background:#2e7d32; color:#fff; padding:8px 18px; border:none; border-radius:6px; margin-top:12px; cursor:pointer }
    .submit-btn:hover { opacity:.9 }
    .modal{display:none;position:fixed;inset:0;z-index:9999;justify-content:center;align-items:center;background:rgba(0,30,60,.75)}
    .modal-content{background:#003366;color:#fff;padding:22px;border-radius:10px;width:95%;max-width:600px;max-height:90vh;overflow-y:auto}
    .close-btn{float:right;font-size:22px;color:#4fc3f7;cursor:pointer}
    label{display:block;margin-top:10px;font-weight:600;font-size:.9rem}
    input,select,textarea{width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;margin-top:3px;box-sizing:border-box;color:#000}
  </style>
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/header.php'; ?>

<div class="main-content">
  <?php if($flash): ?><div class="alert success"><?= $flash ?></div><?php endif; ?>

  <div class="user-header">
    <h2>Expense Management</h2>
    <div class="tabs">
      <button class="tab-btn tab-active" onclick="showTab('sheet')">Expense Sheet</button>
      <button class="tab-btn tab-inactive" onclick="showTab('request')">Expense Request</button>
    </div>
  </div>

  <!-- Expense Sheets -->
  <div id="sheet" class="tab-content active">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <h3>Expense Sheets</h3>
      <div>
        <a href="php/add_expense.php" class="add-user-btn">+ Add Expense Sheet</a>
        <button class="add-user-btn" onclick="openRequestModal()">+ Add Expense Request</button>
      </div>
    </div>
    <table>
      <thead><tr>
        <th>SN</th><th>Project Name</th><th>Cash In</th><th>Cash Out</th><th>Balance</th><th>Action</th>
      </tr></thead>
      <tbody>
        <?php if ($expenses): $sn=1; foreach ($expenses as $e): ?>
          <tr>
            <td><?= $sn++ ?></td>
            <td><?= htmlspecialchars($e['project_name']) ?></td>
            <td><?= number_format($e['total_in'],2) ?></td>
            <td><?= number_format($e['total_out'],2) ?></td>
            <td><?= number_format($e['balance'],2) ?></td>
            <td>
              <a class="open-btn" href="php/view_expense.php?id=<?= $e['id'] ?>">Open</a>
              <a class="print-btn" href="php/view_expense.php?id=<?= $e['id'] ?>" target="_blank">Print</a>
              <a class="delete-btn" href="php/delete_expense_sheet.php?id=<?= $e['id'] ?>" onclick="return confirm('Delete this expense sheet?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="6">No expense sheets yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Expense Request Tab -->
  <div id="request" class="tab-content" style="display:none">
    <h3>Submit Expense Request</h3>
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
      <select name="sheet_id">
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

<!-- Modal (optional, if you prefer popup form later) -->
<div class="modal" id="requestModal"><div class="modal-content">
  <span class="close-btn" onclick="closeRequestModal()">&times;</span>
  <h3>Add Expense Request</h3>
  <!-- You can duplicate the form here if you want popup style -->
</div></div>

<script>
function showTab(tab) {
  document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('tab-active'));
  if (tab === 'sheet') {
    document.getElementById('sheet').style.display = 'block';
    document.querySelectorAll('.tab-btn')[0].classList.add('tab-active');
  } else {
    document.getElementById('request').style.display = 'block';
    document.querySelectorAll('.tab-btn')[1].classList.add('tab-active');
  }
}
function openRequestModal(){document.getElementById('requestModal').style.display='flex';}
function closeRequestModal(){document.getElementById('requestModal').style.display='none';}
window.onclick=e=>{if(e.target.classList.contains('modal'))e.target.style.display='none';};
</script>
</body>
</html>
