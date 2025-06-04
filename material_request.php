<?php
/*  material_request.php  –  Normal USER panel (Excel-style v4)
   ------------------------------------------------------------
   • Excel-like input grid (Category | Product | Unit | Qty | Date | Remarks)
   • Only shows projects assigned to the logged-in user
   • Product list filtered by chosen category
   • CSV bulk-upload, View, Edit, Delete, Status (place-holder pages)
   • History table text is always black, blue header retained
   • Blue theme, mobile-first, ONE file only
*/

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: dashboard.php'); exit();
}
include 'php/db_connect.php';

$user_id = (int)$_SESSION['user_id'];

/* ── Assigned projects ────────────────────────────────────── */
$projects = $conn->query(
  "SELECT p.id, p.project_name
     FROM projects p
     JOIN project_users pu ON pu.project_id = p.id
    WHERE pu.user_id = $user_id
 ORDER BY p.project_name"
)->fetch_all(MYSQLI_ASSOC);

$projectIdsList = $projects ? implode(',', array_column($projects,'id')) : '0';

/* ── Datalist data  (for JS filter) ───────────────────────── */
$cats = $conn->query("SELECT DISTINCT category FROM products")->fetch_all(MYSQLI_ASSOC);
$prods= $conn->query("SELECT category, product_name, unit FROM products ORDER BY product_name")
            ->fetch_all(MYSQLI_ASSOC);

/* ── History (1 row per request) ──────────────────────────── */
$history = $conn->query(
  "SELECT  r.request_number,
           MAX(r.created_at)  AS created_at,
           MAX(r.status)      AS status,
           p.project_name
      FROM material_requests r
      JOIN projects p ON p.id = r.project_id
     WHERE r.project_id IN ($projectIdsList) AND r.user_id = $user_id
  GROUP BY r.request_number, p.project_name
  ORDER BY created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$flash = $_SESSION['success'] ?? ($_SESSION['error'] ?? '');
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Material Request – Bluebeam Infra</title>
<link rel="stylesheet" href="css/style.css">

<style>
/* ───  Layout tweaks for this screen only  ────────────────── */
body { color:#fff }                     /* theme text */
table{width:100%;border-collapse:collapse;border-radius:8px;overflow:hidden;margin-top:10px}
th,td{padding:10px 13px;border-bottom:1px solid #d0d0d0;font-size:.92rem}
th{background:#0d47a1;color:#fff}
td{background:#ffffff;color:#000}       /* history text always black */

/* buttons */
.action-btn{padding:5px 11px;border:none;border-radius:4px;font-size:.78rem;cursor:pointer;transition:opacity .15s}
.action-btn:hover{opacity:.85}
.view-btn{background:#2e7d32;color:#fff}
.edit-btn{background:#1565c0;color:#fff}
.del-btn {background:#c62828;color:#fff}
.sts-btn {background:#4e342e;color:#fff}

/* modal basics */
.modal{display:none;position:fixed;inset:0;z-index:9999;justify-content:center;align-items:center;background:rgba(0,30,60,.75)}
.modal-content{background:#003366;color:#fff;max-width:1100px;width:96%;max-height:92vh;overflow-y:auto;padding:24px;border-radius:10px}
.close-btn{float:right;font-size:24px;color:#4fc3f7;cursor:pointer}

/* Excel-style input table */
#entryTable{width:100%;border-collapse:collapse;margin:10px 0}
#entryTable th,#entryTable td{border:1px solid #88a; padding:6px 5px;background:#e0e5ff;color:#000;font-size:.88rem}
#entryTable th{background:#1565c0;color:#fff;text-align:center}
input.excel,select.excel,textarea.excel{width:100%;border:none;background:#fff;height:30px;font-size:.85rem;padding:3px 4px;box-sizing:border-box;color:#000}
input.excel.small{width:70px}           /* Unit & Qty inputs */

/* mobile */
@media (max-width:768px){
  td,th{font-size:13px}
  input.excel.small{width:56px}
}
</style>
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/header.php'; ?>

<div class="main-content">
  <?php if ($flash): ?><div class="alert success"><?= $flash ?></div><?php endif; ?>

  <div class="user-header" style="display:flex;justify-content:space-between;align-items:center">
    <h2>Material Requests</h2>
    <div>
      <button class="add-user-btn" onclick="openModal()">+ Request Material</button>
      <button class="add-user-btn" onclick="openCsv()">↑ Bulk CSV</button>
    </div>
  </div>

  <table>
    <thead><tr>
      <th>Request No</th><th>Project</th><th>Date</th><th>Status</th><th>Action</th>
    </tr></thead>
    <tbody>
    <?php if($history): foreach($history as $h): ?>
      <tr>
        <td><?= htmlspecialchars($h['request_number']) ?></td>
        <td><?= htmlspecialchars($h['project_name']) ?></td>
        <td><?= date('d M Y',strtotime($h['created_at'])) ?></td>
        <td><?= htmlspecialchars($h['status']) ?></td>
        <td>
          <button class="action-btn view-btn" onclick='viewReq("<?= $h['request_number'] ?>")'>View</button>
          <a class="action-btn edit-btn" href="php/edit_request.php?req=<?= urlencode($h['request_number']) ?>">Edit</a>
          <a class="action-btn del-btn"  href="delete_request.php?req=<?= urlencode($h['request_number']) ?>" onclick="return confirm('Delete this request?')">Delete</a>
          <button class="action-btn sts-btn" onclick='statusReq("<?= $h['request_number'] ?>")'>Status</button>
        </td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="5">No requests yet.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- ─────────────────────────  NEW REQUEST MODAL  -->
<div class="modal" id="mrModal"><div class="modal-content">
  <span class="close-btn" onclick="mrClose()">&times;</span>
  <h3>Create Material Request</h3>

  <form method="POST" action="php/add_request.php" id="mrForm">
    <!-- project dropdown -->
    <label style="font-weight:600;font-size:.9rem">Project:</label>
    <select name="project_id" required style="margin-bottom:8px;width:100%;padding:7px">
      <option value="">Select project</option>
      <?php foreach($projects as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['project_name']) ?></option>
      <?php endforeach; ?>
    </select>

    <!-- Excel-style table -->
    <table id="entryTable">
      <thead>
        <tr>
          <th style="width:160px">Category</th>
          <th>Product</th>
          <th style="width:70px">Unit</th>
          <th style="width:70px">Qty</th>
          <th style="width:140px">Required&nbsp;Date</th>
          <th>Remarks</th>
          <th style="width:45px"></th>
        </tr>
      </thead>
      <tbody id="tblBody">
        <!-- one default line -->
        <tr>
          <td><input list="catList"      class="excel"         name="category[]" onchange="filterProd(this)" required></td>
          <td><input list="prodList"     class="excel"         name="product[]"  oninput="autoUnit(this)" required></td>
          <td><input                    class="excel small"   name="unit[]"></td>
          <td><input type="number" step="0.01" class="excel small" name="quantity[]" required></td>
          <td><input type="date"          class="excel medium" name="required_date[]" required></td>
          <td><textarea                  class="excel"         name="remarks[]"></textarea></td>
          <td style="text-align:center"><button type="button" onclick="delRow(this)">✖</button></td>
        </tr>
      </tbody>
    </table>

    <button type="button" class="add-user-btn" onclick="addRow()">+ Row</button><br><br>
    <button class="submit-btn">Submit Request</button>
  </form>
</div></div>

<!-- CSV upload -->
<div class="modal" id="csvModal"><div class="modal-content">
  <span class="close-btn" onclick="csvClose()">&times;</span>
  <h3>Upload Requests (CSV)</h3>
  <p style="font-size:.85rem">CSV columns: project_id, category, product, unit, quantity, required_date, remarks</p>
  <form method="POST" action="php/upload_requests.php" enctype="multipart/form-data">
    <input type="file" name="csv_file" accept=".csv" required>
    <button class="submit-btn">Upload</button>
  </form>
</div></div>

<!-- Status modal -->
<div class="modal" id="stsModal"><div class="modal-content">
  <span class="close-btn" onclick="stsClose()">&times;</span>
  <h3>Update Delivery Status</h3>
  <form method="POST" action="update_status.php">
    <input type="hidden" name="request_number" id="stsReq">
    <label>Status:</label>
    <select name="status" required style="padding:6px;width:100%;margin:6px 0">
      <option value="Received">Received</option>
      <option value="Partially Received">Partially Received</option>
      <option value="Damaged">Damaged</option>
    </select>
    <label>Comment:</label>
    <textarea name="comment" style="width:100%;height:60px"></textarea><br>
    <button class="submit-btn">Save</button>
  </form>
</div></div>

<!-- View modal -->
<div class="modal" id="viewModal"><div class="modal-content" id="viewWrap"></div></div>

<!-- datalists -->
<datalist id="catList"><?php foreach($cats as $c): ?><option value="<?= htmlspecialchars($c['category']) ?>"><?php endforeach; ?></datalist>
<datalist id="prodList"><?php foreach($prods as $p): ?>
  <option data-cat="<?= htmlspecialchars($p['category']) ?>" data-unit="<?= htmlspecialchars($p['unit']) ?>" value="<?= htmlspecialchars($p['product_name']) ?>">
<?php endforeach; ?></datalist>

<script>
/* ========= helper references ========== */
const mrModal=document.getElementById('mrModal'),
      csvModal=document.getElementById('csvModal'),
      viewModal=document.getElementById('viewModal'),
      stsModal=document.getElementById('stsModal'),
      prodOpts=[...document.querySelectorAll('#prodList option')],
      tblBody=document.getElementById('tblBody');

/* ========= open / close ========= */
function openModal(){mrModal.style.display='flex';}
function mrClose(){mrModal.style.display='none';}
function openCsv(){csvModal.style.display='flex';}
function csvClose(){csvModal.style.display='none';}
function statusReq(no){document.getElementById('stsReq').value=no;stsModal.style.display='flex';}
function stsClose(){stsModal.style.display='none';}

/* ========= dynamic rows ========= */
function addRow(){
  const tr=tblBody.rows[0].cloneNode(true);
  tr.querySelectorAll('input,textarea').forEach(e=>e.value='');
  tblBody.appendChild(tr);
}
function delRow(btn){
  if(tblBody.rows.length===1){addRow();}         /* keep at least one row */
  btn.parentElement.parentElement.remove();
}

/* ========= category → filter product list ========= */
function filterProd(catInp){
  const cat=catInp.value.trim();
  const prodInp=catInp.parentElement.nextElementSibling.firstElementChild;
  prodInp.value='';prodInp.dataset.cat=cat;autoUnit(prodInp);
}

/* ========= product → auto-fill unit, respect filter ========= */
function autoUnit(prodInp){
  /* filter list options */
  const cat=prodInp.dataset.cat||'';
  prodOpts.forEach(o=>o.disabled=cat && o.dataset.cat!==cat);
  /* fill unit */
  const unitCell=prodInp.parentElement.nextElementSibling.firstElementChild;
  const match=prodOpts.find(o=>o.value===prodInp.value);
  unitCell.value=match?match.dataset.unit:'';
}

/* ========= view request (AJAX) ========= */
function viewReq(no){
  fetch('php/view_request.php?req='+encodeURIComponent(no))
    .then(r=>r.text())
    .then(h=>{document.getElementById('viewWrap').innerHTML=h;viewModal.style.display='flex';});
}

/* ========= backdrop close ========= */
window.onclick=e=>{if(e.target.classList.contains('modal'))e.target.style.display='none';};
</script>
</body>
</html>
