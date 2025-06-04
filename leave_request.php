<?php
/* leave_request.php  —  user panel */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] === 'admin') {
    header('Location: dashboard.php'); exit();
}
include 'php/db_connect.php';
$user_id = (int)$_SESSION['user_id'];

/* approver dropdown (any non-admin, not me) */
$approvers = $conn->query("
    SELECT id,name FROM users
    WHERE user_type<>'admin' AND id<>$user_id
    ORDER BY name")->fetch_all(MYSQLI_ASSOC);

/* my requests OR requests where I’m approver-1 / approver-2 */
$leaves = $conn->query("
  SELECT lr.*,
         req.name  AS requester_name,
         a1.name   AS approver1_name,
         a2.name   AS approver2_name
    FROM leave_requests lr
    LEFT JOIN users req ON req.id = lr.user_id
    LEFT JOIN users a1  ON a1.id  = lr.approver1_id
    LEFT JOIN users a2  ON a2.id  = lr.approver2_id
   WHERE lr.user_id        = $user_id
      OR lr.approver1_id   = $user_id
      OR lr.approver2_id   = $user_id
   ORDER BY lr.created_at DESC")
  ->fetch_all(MYSQLI_ASSOC);

$flash = $_SESSION['success'] ?? ($_SESSION['error'] ?? '');
unset($_SESSION['success'],$_SESSION['error']);
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Leave Requests – Bluebeam Infra</title>
<link rel="stylesheet" href="css/style.css">
<style>
table{width:100%;border-collapse:collapse;background:#fff;margin-top:10px}
th,td{padding:10px 12px;border-bottom:1px solid #ddd;font-size:.92rem;color:#000}
th{background:#0d47a1;color:#fff}
.action{padding:5px 11px;border:none;border-radius:4px;font-size:.8rem;cursor:pointer;transition:opacity .15s}
.action:hover{opacity:.85}
.edit{background:#1565c0;color:#fff}
.del {background:#c62828;color:#fff;margin-left:4px}
.sub {background:#2e7d32;color:#fff}
.modal{display:none;position:fixed;inset:0;z-index:9999;justify-content:center;align-items:center;background:rgba(0,30,60,.75)}
.modal-content{background:#003366;color:#fff;padding:22px;border-radius:10px;width:95%;max-width:600px;max-height:90vh;overflow-y:auto}
.close-btn{float:right;font-size:22px;color:#4fc3f7;cursor:pointer}
label{display:block;margin-top:10px;font-weight:600;font-size:.9rem}
input,select,textarea{width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;margin-top:3px;box-sizing:border-box;color:#000}
.submit-btn{background:#0d47a1;color:#fff;padding:8px 18px;border:none;border-radius:6px;margin-top:12px;cursor:pointer}
.submit-btn:hover{opacity:.9}
</style></head><body>
<?php include 'partials/sidebar.php'; include 'partials/header.php'; ?>

<div class="main-content">
 <?php if($flash):?><div class="alert success"><?= $flash ?></div><?php endif;?>

 <div class="user-header" style="display:flex;justify-content:space-between;align-items:center">
   <h2>Leave Requests</h2>
   <button class="add-user-btn" onclick="openModal()">+ Request Leave</button>
 </div>

 <table>
  <thead>
   <tr>
    <th>Requested On</th><th>From</th><th>To</th><th>Reason</th>
    <th>Status</th><th>Approver 1</th><th>Approver 2</th>
    <th>Comments</th><th>Requester</th><th style="width:170px">Action</th>
   </tr>
  </thead>
  <tbody>
  <?php if($leaves): foreach($leaves as $l):
        /* convenience flags */
        $isOwner   = ($l['user_id']      == $user_id);
        $isA1      = ($l['approver1_id'] == $user_id);
        $isA2      = ($l['approver2_id'] == $user_id);
        $status    = $l['status'];
  ?>
   <tr>
    <td><?= date('d M Y',strtotime($l['created_at'])) ?></td>
    <td><?= htmlspecialchars($l['start_date']) ?></td>
    <td><?= htmlspecialchars($l['end_date']) ?></td>
    <td><?= htmlspecialchars($l['reason']) ?></td>
    <td><?= htmlspecialchars($status) ?></td>
    <td><?= htmlspecialchars($l['approver1_name'] ?? '—') ?></td>
    <td><?= htmlspecialchars($l['approver2_name'] ?? '—') ?></td>
    <td>
      <?php
        echo $l['l1_comment'] ? 'L1: '.htmlspecialchars($l['l1_comment']).'<br>' : '';
        echo $l['l2_comment'] ? 'L2: '.htmlspecialchars($l['l2_comment'])       : '—';
      ?>
    </td>
    <td><?= htmlspecialchars($l['requester_name']) ?></td>

    <td>
      <!-- owner can edit/delete while still at L1 -->
      <?php if($isOwner && $status==='Pending-L1'): ?>
        <a class="action edit" href="php/edit_leave.php?id=<?= $l['id'] ?>">Edit</a>
        <a class="action del"
           href="php/delete_leave.php?id=<?= $l['id'] ?>"
           onclick="return confirm('Delete this request?')">Delete</a>
           <!-- inside leave_request.php, just after the “Edit / Delete / Approve” buttons -->
        <a class="action pdf"
           href="php/leave_pdf.php?id=<?= $l['id'] ?>" target="_blank">🖨️ PDF</a>

      <?php endif; ?>

      <!-- approver1 form -->
      <?php if($isA1 && $status==='Pending-L1'): ?>
        <?= approverForm($l['id']) ?>
      <?php endif; ?>

      <!-- approver2 form -->
      <?php if($isA2 && $status==='Pending-L2'): ?>
        <?= approverForm($l['id']) ?>
      <?php endif; ?>
    </td>
   </tr>
  <?php endforeach; else: ?>
   <tr><td colspan="10">No leave requests.</td></tr>
  <?php endif;?>
  </tbody>
 </table>
</div>

<!-- =========== Leave modal =========== -->
<div class="modal" id="lvModal"><div class="modal-content">
 <span class="close-btn" onclick="closeModal()">&times;</span>
 <h3>Request Leave</h3>
<form method="POST" action="php/add_leave.php">
  <label>Start Date</label><input type="date" name="start_date" required>
  <label>End Date</label><input type="date" name="end_date" required>
  <label>Reason</label><textarea name="reason" required></textarea>

  <label>Approver (Level-1)</label>
  <select name="approver1_id" required>
    <option value="">Select approver</option>
    <?php foreach($approvers as $a):?>
      <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
    <?php endforeach;?>
  </select>

  <label>Approver (Level-2)</label>
  <select name="approver2_id" required>
    <option value="">Select approver</option>
    <?php foreach($approvers as $a):?>
      <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
    <?php endforeach;?>
  </select>

  <button class="submit-btn">Submit</button>
</form>

</div></div>

<script>
function openModal(){document.getElementById('lvModal').style.display='flex';}
function closeModal(){document.getElementById('lvModal').style.display='none';}
window.onclick=e=>{if(e.target.classList.contains('modal'))e.target.style.display='none';};

/* tiny helper to inject approver form html from PHP */
</script>
<?php
/* ---------- helper to output inline form ---------- */
function approverForm($id){
  return '
  <form method="POST" action="php/leave_action.php" style="margin-top:6px">
    <input type="hidden" name="leave_id" value="'.$id.'">
    <select name="action" required style="width:100%;margin-bottom:4px">
      <option value="">Select</option>
      <option value="Approved">Approve</option>
      <option value="Rejected">Reject</option>
    </select>
    <textarea name="comment" placeholder="Comment (optional)"></textarea>
    <button class="action sub" style="width:100%;margin-top:4px">Submit</button>
  </form>';
}
?>
</body></html>
