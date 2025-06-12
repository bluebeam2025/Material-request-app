<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: dashboard.php');
    exit();
}
include 'db_connect.php';

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
    echo "<p>Invalid request or access denied.</p>";
    exit();
}

// Fetch expense entries
$entries = $conn->query("SELECT * FROM expense_entries WHERE request_id = $request_id ORDER BY id ASC")
    ->fetch_all(MYSQLI_ASSOC);

// Totals
$totalIn = $totalOut = 0;
foreach ($entries as $e) {
    $totalIn += (float)$e['cash_in'];
    $totalOut += (float)$e['cash_out'];
}
$balance = $totalIn - $totalOut;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Expense Sheet – <?= htmlspecialchars($sheet['project_name']) ?></title>
<link rel="stylesheet" href="../css/style.css" />
<style>
table { width: 100%; border-collapse: collapse; margin-top: 12px; background: #fff; }
th, td {
    border: 1px solid #ccc;
    padding: 12px 10px;
    font-size: 0.95rem;
    color: #000;
}
th {
    background: #0d47a1;
    color: #fff;
}
input[type="text"], input[type="number"], input[type="file"], textarea {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
    font-size: 0.9rem;
}
textarea {
    resize: vertical;
    height: 60px;
}
button {
    background: #0d47a1;
    color: #fff;
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 12px;
}
button:hover {
    opacity: 0.9;
}
.add-btn {
    background: #1565c0;
    margin-bottom: 10px;
}
.del-btn {
    background: #c62828;
}
.print-btn {
    background: #2e7d32;
    float: right;
    margin-bottom: 10px;
}
.invoice-preview {
    font-size: 0.85rem;
}
.row-checkbox {
    transform: scale(1.2);
}
</style>
</head>
<body>
<?php include '../partials/sidebar.php'; ?>
<?php include '../partials/header.php'; ?>

<div class="main-content">
    <h2>Expense Sheet – <?= htmlspecialchars($sheet['project_name']) ?></h2>

    <button type="button" class="print-btn" onclick="window.print()">🖨️ Print PDF</button>

    <form method="POST" action="update_expense.php" enctype="multipart/form-data">
        <input type="hidden" name="request_id" value="<?= $sheet['id'] ?>" />

        <table>
            <thead>
                <tr>
                    <th>SN</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Cash In</th>
                    <th>Cash Out</th>
                    <th>Remarks</th>
                    <th>Invoice Upload</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody id="expenseBody">
                <?php $sn = 1; foreach ($entries as $index => $e): ?>
                    <tr>
                        <td><?= $sn++ ?></td>
                        <td>
                            <input type="hidden" name="entry_id[]" value="<?= $e['id'] ?>">
                            <input type="text" name="description[]" value="<?= htmlspecialchars($e['description']) ?>">
                        </td>
                        <td>
                            <input type="text" name="category[]" value="<?= htmlspecialchars($e['category']) ?>">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="cash_in[]" value="<?= $e['cash_in'] ?>">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="cash_out[]" value="<?= $e['cash_out'] ?>">
                        </td>
                        <td>
                            <textarea name="remarks[]"><?= htmlspecialchars($e['remarks']) ?></textarea>
                        </td>
                        <td>
                            <?php if (!empty($e['invoice_file'])): ?>
                                <div class="invoice-preview">
                                    <a href="../<?= htmlspecialchars($e['invoice_file']) ?>" target="_blank">View Invoice</a>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="invoice_file_<?= $index ?>" accept=".pdf,.jpg,.jpeg,.png" />
                        </td>
                        <td style="text-align: center;">
                            <input type="checkbox" name="delete_entry[]" value="<?= $e['id'] ?>" class="row-checkbox">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">Total</th>
                    <th><?= number_format($totalIn, 2) ?></th>
                    <th><?= number_format($totalOut, 2) ?></th>
                    <th><?= number_format($balance, 2) ?></th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>

        <button type="button" class="add-btn" onclick="addRow()">+ Add Row</button>
        <button type="submit">Save Changes</button>
    </form>
</div>

<script>
function addRow() {
    const tbody = document.getElementById('expenseBody');
    const index = tbody.rows.length;
    const row = document.createElement('tr');

    row.innerHTML = `
        <td>${index + 1}</td>
        <td>
            <input type="hidden" name="entry_id[]" value="0">
            <input type="text" name="description[]">
        </td>
        <td>
            <input type="text" name="category[]">
        </td>
        <td>
            <input type="number" step="0.01" name="cash_in[]" value="0">
        </td>
        <td>
            <input type="number" step="0.01" name="cash_out[]" value="0">
        </td>
        <td>
            <textarea name="remarks[]"></textarea>
        </td>
        <td>
            <input type="file" name="invoice_file_${index}" accept=".pdf,.jpg,.jpeg,.png" />
        </td>
        <td style="text-align: center;">
            <input type="checkbox" name="delete_entry[]" value="0" class="row-checkbox">
        </td>
    `;

    tbody.appendChild(row);
}
</script>
</body>
</html>
