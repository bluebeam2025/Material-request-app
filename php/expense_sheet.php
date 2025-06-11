<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: dashboard.php'); exit();
}
include 'php/db_connect.php';

$request_id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// Fetch sheet metadata
$sheet = $conn->query("SELECT er.*, p.project_name FROM expense_requests er JOIN projects p ON p.id = er.project_id WHERE er.id = $request_id AND er.user_id = $user_id")->fetch_assoc();
if (!$sheet) {
    echo "<p>Sheet not found or access denied.</p>";
    exit();
}

// Fetch entries
$entries = $conn->query("SELECT * FROM expense_entries WHERE request_id = $request_id ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Expense Sheet – <?= htmlspecialchars($sheet['project_name']) ?></title>
  <link rel="stylesheet" href="css/style.css" />
  <style>
    table { width: 100%; border-collapse: collapse; margin-top: 12px; background: #fff; }
    th, td { border: 1px solid #ccc; padding: 8px; font-size: 0.92rem; color: #000; vertical-align: middle; }
    th { background: #0d47a1; color: #fff; }
    input[type=text], input[type=number], input[type=date], textarea, input[type=file] {
      width: 100%; padding: 6px; box-sizing: border-box; font-size: 0.9rem;
    }
    textarea { resize: vertical; height: 36px; } /* 2 line height */
    .submit-btn, .print-btn, .delete-btn {
      background: #0d47a1; color: #fff; padding: 8px 14px; border: none; border-radius: 6px; margin-top: 12px; cursor: pointer;
    }
    .submit-btn:hover, .print-btn:hover, .delete-btn:hover { opacity: 0.9; }
    .delete-btn { background: #c62828; float: right; }
    .print-btn { background: #2e7d32; margin-left: 8px; }
    .upload-label {
      display: inline-block;
      background: #1565c0;
      color: #fff;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.85rem;
      text-align: center;
    }
    .upload-label:hover { opacity: 0.9; }
    .file-name { font-size: 0.8rem; color: #444; margin-top: 4px; }
  </style>
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/header.php'; ?>

<div class="main-content">
  <h2>Expense Sheet – <?= htmlspecialchars($sheet['project_name']) ?></h2>

  <form method="POST" action="php/update_expense.php" enctype="multipart/form-data">
    <input type="hidden" name="request_id" value="<?= $sheet['id'] ?>">

    <table>
      <thead>
        <tr>
          <th>SN</th>
          <th>Description</th>
          <th>Category</th>
          <th>Cash In</th>
          <th>Cash Out</th>
          <th>Date</th>
          <th>Invoice</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $total_in = 0; $total_out = 0; $sn = 1;
        foreach ($entries as $e):
          $total_in += $e['cash_in'];
          $total_out += $e['cash_out'];
        ?>
          <tr>
            <td><?= $sn++ ?><input type="hidden" name="entry_id[]" value="<?= $e['id'] ?>"></td>
            <td><input type="text" name="description[]" value="<?= htmlspecialchars($e['description']) ?>"></td>
            <td><input type="text" name="category[]" value="<?= htmlspecialchars($e['category']) ?>"></td>
            <td><input type="number" step="0.01" name="cash_in[]" value="<?= $e['cash_in'] ?>"></td>
            <td><input type="number" step="0.01" name="cash_out[]" value="<?= $e['cash_out'] ?>"></td>
            <td><input type="date" name="entry_date[]" value="<?= $e['entry_date'] ?? '' ?>"></td>
            <td>
              <label class="upload-label">
                Upload
                <input type="file" name="invoice_file[]" accept=".pdf,.jpg,.jpeg,.png" style="display:none" onchange="showFileName(this)">
              </label>
              <?php if (!empty($e['invoice_file'])): ?>
                <div class="file-name">Existing: <a href="uploads/<?= htmlspecialchars($e['invoice_file']) ?>" target="_blank">View</a></div>
              <?php endif; ?>
            </td>
            <td><textarea name="remarks[]"><?= htmlspecialchars($e['remarks']) ?></textarea></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3">Total</th>
          <th><?= number_format($total_in, 2) ?></th>
          <th><?= number_format($total_out, 2) ?></th>
          <th colspan="3"><?= number_format($total_in - $total_out, 2) ?></th>
        </tr>
      </tfoot>
    </table>

    <button type="submit" class="submit-btn">Save Changes</button>
    <button type="button" class="print-btn" onclick="window.print()">Print</button>
    <a href="php/delete_expense_sheet.php?id=<?= $sheet['id'] ?>" class="delete-btn" onclick="return confirm('Delete this expense sheet?')">Delete Sheet</a>
  </form>
</div>

<script>
function showFileName(input) {
  const label = input.parentElement;
  const fileNameDiv = document.createElement('div');
  fileNameDiv.className = 'file-name';
  fileNameDiv.textContent = 'Selected: ' + (input.files[0]?.name || '');
  // remove existing file name display if present
  label.parentElement.querySelectorAll('.file-name').forEach(e => e.remove());
  label.parentElement.appendChild(fileNameDiv);
}
</script>
</body>
</html>
