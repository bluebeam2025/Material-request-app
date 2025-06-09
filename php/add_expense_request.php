// ✅ FILE: php/delete_leave.php
<?php
session_start();
include 'db_connect.php';

$id = (int)($_GET['id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);

$stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = ? AND user_id = ? AND status = 'Pending-L1'");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

$_SESSION[$stmt->affected_rows > 0 ? 'success' : 'error'] =
  $stmt->affected_rows > 0 ? "Leave request deleted." : "Delete failed or not allowed.";

header("Location: ../leave_request.php");
exit;


// ✅ FILE: php/edit_leave.php
<?php
session_start();
include 'db_connect.php';
$id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

$leave = $conn->query("SELECT * FROM leave_requests WHERE id = $id")->fetch_assoc();
if (!$leave || $leave['user_id'] != $user_id || $leave['status'] !== 'Pending-L1') {
  $_SESSION['error'] = "You can't edit this request.";
  header("Location: ../leave_request.php"); exit;
}

$approvers = $conn->query("SELECT id,name FROM users WHERE user_type <> 'admin' AND id <> $user_id")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html><html><head><title>Edit Leave</title><link rel="stylesheet" href="../css/style.css"></head><body>
<h2>Edit Leave Request</h2>
<form method="POST" action="update_leave.php">
  <input type="hidden" name="id" value="<?= $id ?>">
  <label>Start Date</label>
  <input type="date" name="start_date" value="<?= $leave['start_date'] ?>" required>
  <label>End Date</label>
  <input type="date" name="end_date" value="<?= $leave['end_date'] ?>" required>
  <label>Reason</label>
  <textarea name="reason" required><?= $leave['reason'] ?></textarea>
  <label>Approver 1</label>
  <select name="approver1_id" required>
    <?php foreach($approvers as $a): ?>
      <option value="<?= $a['id'] ?>" <?= $a['id']==$leave['approver1_id'] ? 'selected' : '' ?>><?= $a['name'] ?></option>
    <?php endforeach; ?>
  </select>
  <label>Approver 2</label>
  <select name="approver2_id" required>
    <?php foreach($approvers as $a): ?>
      <option value="<?= $a['id'] ?>" <?= $a['id']==$leave['approver2_id'] ? 'selected' : '' ?>><?= $a['name'] ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit">Update</button>
</form>
</body></html>


// ✅ FILE: php/update_leave.php
<?php
session_start();
include 'db_connect.php';

$id = (int)$_POST['id'];
$user_id = $_SESSION['user_id'];

$start = $_POST['start_date'];
$end = $_POST['end_date'];
$reason = trim($_POST['reason']);
$a1 = (int)$_POST['approver1_id'];
a2 = (int)$_POST['approver2_id'];

$stmt = $conn->prepare("UPDATE leave_requests SET start_date=?, end_date=?, reason=?, approver1_id=?, approver2_id=? WHERE id=? AND user_id=? AND status='Pending-L1'");
$stmt->bind_param("sssiiii", $start, $end, $reason, $a1, $a2, $id, $user_id);
$stmt->execute();

$_SESSION[$stmt->affected_rows > 0 ? 'success' : 'error'] =
  $stmt->affected_rows > 0 ? "Leave request updated." : "Update failed.";

header("Location: ../leave_request.php");
exit;


// ✅ FILE: php/leave_action.php
<?php
session_start();
include 'db_connect.php';

$id = (int)$_POST['leave_id'];
$action = $_POST['action'];
$comment = trim($_POST['comment'] ?? '');
$user_id = $_SESSION['user_id'];

$leave = $conn->query("SELECT * FROM leave_requests WHERE id = $id")->fetch_assoc();
if (!$leave) {
  $_SESSION['error'] = "Invalid request."; header("Location: ../leave_request.php"); exit;
}

$status = $leave['status'];
if ($leave['approver1_id'] == $user_id && $status === 'Pending-L1') {
  $newStatus = ($action == 'Approved') ? 'Pending-L2' : 'Rejected';
  $stmt = $conn->prepare("UPDATE leave_requests SET status=?, l1_comment=? WHERE id=?");
  $stmt->bind_param("ssi", $newStatus, $comment, $id);
} elseif ($leave['approver2_id'] == $user_id && $status === 'Pending-L2') {
  $newStatus = ($action == 'Approved') ? 'Approved' : 'Rejected';
  $stmt = $conn->prepare("UPDATE leave_requests SET status=?, l2_comment=? WHERE id=?");
  $stmt->bind_param("ssi", $newStatus, $comment, $id);
} else {
  $_SESSION['error'] = "Unauthorized or already processed.";
  header("Location: ../leave_request.php"); exit;
}

$stmt->execute();
$_SESSION['success'] = "Leave $action";
header("Location: ../leave_request.php");
exit;
