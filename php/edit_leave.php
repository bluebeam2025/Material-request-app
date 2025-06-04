<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] === 'admin') {
  header("Location: ../dashboard.php"); exit;
}

$user_id = (int)$_SESSION['user_id'];
$leave_id = (int)$_GET['id'];

// Fetch leave record
$stmt = $conn->prepare("SELECT * FROM leave_requests WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $leave_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
  $_SESSION['error'] = "Invalid or unauthorized request.";
  header("Location: ../leave_request.php");
  exit;
}

$leave = $result->fetch_assoc();
if ($leave['status'] !== 'Pending-L1') {
  $_SESSION['error'] = "Only Pending-L1 requests can be edited.";
  header("Location: ../leave_request.php");
  exit;
}

// Get approver list
$approvers = $conn->query("SELECT id, name FROM users WHERE user_type != 'admin' AND id != $user_id ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Leave Request</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { padding: 20px; background: #f5f5f5; font-family: sans-serif; }
    .form-container { max-width: 600px; background: #fff; padding: 20px; border-radius: 10px; margin: auto; }
    label { font-weight: 600; margin-top: 10px; display: block; }
    input, select, textarea {
      width: 100%; padding: 8px; margin-top: 4px;
      border: 1px solid #ccc; border-radius: 4px;
    }
    button {
      margin-top: 15px; background: #0d47a1; color: #fff;
      padding: 10px 16px; border: none; border-radius: 5px;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Edit Leave Request</h2>
    <form method="POST" action="update_leave.php">
      <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
      <label>Start Date</label>
      <input type="date" name="start_date" value="<?= $leave['start_date'] ?>" required>

      <label>End Date</label>
      <input type="date" name="end_date" value="<?= $leave['end_date'] ?>" required>

      <label>Reason</label>
      <textarea name="reason" required><?= htmlspecialchars($leave['reason']) ?></textarea>

      <label>Approver (Level 1)</label>
      <select name="approver1_id" required>
        <?php foreach ($approvers as $a): ?>
          <option value="<?= $a['id'] ?>" <?= $a['id'] == $leave['approver1_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($a['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Approver (Level 2)</label>
      <select name="approver2_id" required>
        <?php foreach ($approvers as $a): ?>
          <option value="<?= $a['id'] ?>" <?= $a['id'] == $leave['approver2_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($a['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="submit">Update Request</button>
    </form>
  </div>
</body>
</html>
