<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../dashboard.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$request_id = (int)($_GET['id'] ?? 0);

if (!$request_id) {
    echo "<p>Invalid expense sheet ID.</p>";
    exit();
}

// Fetch sheet header (check ownership)
$sheet = $conn->query("SELECT er.id, p.project_name 
    FROM expense_requests er 
    JOIN projects p ON p.id = er.project_id 
    WHERE er.id = $request_id AND er.user_id = $user_id
")->fetch_assoc();

if (!$sheet) {
    echo "<p>Sheet not found or access denied.</p>";
    exit();
}

// Fetch entries
$entries = $conn->query("SELECT * FROM expense_entries WHERE request_id = $request_id ORDER BY id ASC")
    ->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Expense Sheet – <?= htmlspecialchars($sheet['project_name']) ?></title>
<link rel="stylesheet" href="../css/style.css">
<style>
table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 12px; }
th, td { border: 1px solid #ccc; padding: 16px; font-size: 0.95rem; }
th { background: #0d47a1; color: #fff; text-align: center; }
input[type=text], input[type=number], input[type=file], input[type=date] { width: 100%; padding: 8px; box-sizing: border-box; }
textarea { width: 100%; padding: 8px; box-sizing: border-box; resize: vertical; }
button { padding: 8px 14px; background: #0d47a1; color: #fff; border: none; border-radius: 5px; cursor: pointer; margin-top: 8px; }
button:hover { opacity: 0.9; }
.print-btn { background: #2e7d32; float: right; }
.del-entry-btn { background: #c62828; margin-left: 4px; }
.view-btn { background: #1565c0; margin-right: 4px; }
tfoot th { text-align: center; font-weight: bold; }
</style>
</head>
<body>
<?php include '../partials/sidebar.php'; ?>
<?php include '../partials/header.php'; ?>

<div class="main-content">
  <h2 style="display:flex;justify-content:space-between;align-items:center;">
    Expense Sheet: <?= htmlspecialchars($sheet['project_name']) ?>
    <button class="print-btn" onclick="window.print()">Print Sheet</button>
  </h2>

  <form method="POST" action="update_expense.php" enctype="multipart/form-data">
    <input type="hidden" name="request_id" value="<?= $sheet['id'] ?>">
    <table>
      <thead>
        <tr>
          <th>SN</th>
          <th>Date</th>
          <th>Description</th>
          <th>Category</th>
          <th>Cash In</th>
          <th>Cash Out</th>
          <th>Remarks</th>
          <th>Invoice</th>
          <th>Action</th>
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
          <input type="hidden" name="entry_id[]" value="<?= $e['id'] ?>">
          <td><?= $sn++ ?></td>
          <td><input type="date" name="entry_date[]" value="<?= htmlspecialchars($e['entry_date']) ?>"></td>
          <td><input type="text" name="description[]" value="<?= htmlspecialchars($e['description']) ?>"></td>
          <td><input type="text" name="category[]" value="<?= htmlspecialchars($e['category']) ?>"></td>
          <td><input type="number" step="0.01" name="cash_in[]" value="<?= $e['cash_in'] ?>"></td>
          <td><input type="number" step="0.01" name="cash_out[]" value="<?= $e['cash_out'] ?>"></td>
          <td><textarea name="remarks[]"><?= htmlspecialchars($e['remarks']) ?></textarea></td>
          <td>
            <?php if (!empty($e['invoice_file']) && file_exists("../uploads/invoices/{$e['invoice_file']}")): ?>
              <a class="view-btn" href="../uploads/invoices/<?= urlencode($e['invoice_file']) ?>" target="_blank">View</a>
              <a class="del-entry-btn" href="delete_invoice.php?id=<?= $e['id'] ?>&sheet_id=<?= $sheet['id'] ?>" onclick="return confirm('Delete invoice file?')">Delete</a><br>
            <?php endif; ?>
            <input type="file" name="invoice_file[]">
          </td>
          <td>
            <a class="del-entry-btn" href="delete_expense_entry.php?id=<?= $e['id'] ?>&sheet_id=<?= $sheet['id'] ?>" onclick="return confirm('Delete this entry?')">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <!-- New Entry Row -->
        <tr>
          <input type="hidden" name="entry_id[]" value="0">
          <td>New</td>
          <td><input type="date" name="entry_date[]"></td>
          <td><input type="text" name="description[]"></td>
          <td><input type="text" name="category[]"></td>
          <td><input type="number" step="0.01" name="cash_in[]" value="0"></td>
          <td><input type="number" step="0.01" name="cash_out[]" value="0"></td>
          <td><textarea name="remarks[]"></textarea></td>
          <td><input type="file" name="invoice_file[]"></td>
          <td></td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="4">Total</th>
          <th><?= number_format($total_in, 2) ?></th>
          <th><?= number_format($total_out, 2) ?></th>
          <th colspan="3"><?= number_format($total_in - $total_out, 2) ?></th>
        </tr>
      </tfoot>
    </table>
    <button type="submit">Save Changes</button>
  </form>
</div>
</body>
</html>
