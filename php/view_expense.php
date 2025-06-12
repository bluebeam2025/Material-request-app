<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../dashboard.php'); exit();
}

$request_id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// Fetch sheet metadata
$sheet = $conn->query("
    SELECT er.*, p.project_name 
    FROM expense_requests er 
    JOIN projects p ON p.id = er.project_id 
    WHERE er.id = $request_id AND er.user_id = $user_id
")->fetch_assoc();

if (!$sheet) {
    echo "<p>Sheet not found or access denied.</p>";
    exit();
}

// Fetch entries
$entries = $conn->query("
    SELECT * FROM expense_entries 
    WHERE request_id = $request_id 
    ORDER BY id ASC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html><head>
  <meta charset="UTF-8">
  <title>Expense Sheet – <?= htmlspecialchars($sheet['project_name']) ?></title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    table { width: 100%; border-collapse: collapse; margin-top: 12px; background: #fff; }
    th, td { border: 1px solid #ccc; padding: 14px; font-size: 0.92rem; color: #000; }
    th { background: #0d47a1; color: #fff; }
    input[type=text], input[type=number], input[type=file], textarea { width: 100%; padding: 8px; box-sizing: border-box; }
    .submit-btn { background: #0d47a1; color: #fff; padding: 8px 14px; border: none; border-radius: 6px; margin-top: 12px; cursor: pointer; }
    .submit-btn:hover { opacity: 0.9; }
    .delete-btn { background: #c62828; color: #fff; padding: 6px 12px; border-radius: 4px; text-decoration: none; display: inline-block; }
    .delete-btn:hover { opacity: 0.9; }
    .download-link { display: block; margin-top: 4px; font-size: 0.85rem; color: #1565c0; text-decoration: underline; }
    .edit-btn { background: #1565c0; color: #fff; padding: 6px 12px; border-radius: 4px; text-decoration: none; display: inline-block; margin-right: 6px; }
    .edit-btn:hover { opacity: 0.9; }
  </style>
</head>
<body>
<?php include '../partials/sidebar.php'; ?>
<?php include '../partials/header.php'; ?>

<div class="main-content">
  <h2>Expense Sheet – <?= htmlspecialchars($sheet['project_name']) ?></h2>

  <form method="POST" action="update_expense.php" enctype="multipart/form-data">
    <input type="hidden" name="request_id" value="<?= $sheet['id'] ?>">

    <table>
      <thead>
        <tr>
          <th>SN</th>
          <th>Description</th>
          <th>Category</th>
          <th>Cash In</th>
          <th>Cash Out</th>
          <th>Remarks</th>
          <th>Invoice</th>
          <th>Delete</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $sn = 1; 
        foreach ($entries as $e): ?>
          <tr>
            <td><?= $sn++ ?></td>
            <td>
              <input type="hidden" name="entry_id[]" value="<?= $e['id'] ?>">
              <input type="text" name="description[]" value="<?= htmlspecialchars($e['description']) ?>">
            </td>
            <td><input type="text" name="category[]" value="<?= htmlspecialchars($e['category']) ?>"></td>
            <td><input type="number" step="0.01" name="cash_in[]" value="<?= $e['cash_in'] ?>"></td>
            <td><input type="number" step="0.01" name="cash_out[]" value="<?= $e['cash_out'] ?>"></td>
            <td><textarea name="remarks[]"><?= htmlspecialchars($e['remarks']) ?></textarea></td>
            <td>
              <?php if ($e['invoice_file']): ?>
                <a href="../uploads/invoices/<?= htmlspecialchars($e['invoice_file']) ?>" target="_blank" class="download-link">View</a>
              <?php else: ?>
                No file
              <?php endif; ?>
              <input type="file" name="invoice_file_<?= $e['id'] ?>">
            </td>
            <td>
              <a class="delete-btn" href="delete_expense_entry.php?id=<?= $e['id'] ?>&request_id=<?= $sheet['id'] ?>" onclick="return confirm('Delete this entry?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <button class="submit-btn">Save Changes</button>
    <a class="delete-btn" style="margin-left:10px" href="delete_expense_sheet.php?id=<?= $sheet['id'] ?>" onclick="return confirm('Delete entire expense sheet?')">Delete Entire Sheet</a>
  </form>

  <br>
  <button class="submit-btn" onclick="window.print()">Print PDF</button>
</div>
</body>
</html>
