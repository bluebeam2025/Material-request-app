<?php
/*──────────────────────────────────────────────────────────────
  edit_request.php   (place this INSIDE the  /php  folder)

  • Only the request-owner may load the page.
  • Request must have status = 'Pending'   (trimmed / case-insensitive).
  • Form is pre-filled with the same Excel-like grid you use when creating
    a request, including dynamic Category → Product filtering + auto-unit.
──────────────────────────────────────────────────────────────*/
session_start();
include __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../dashboard.php');   // NOT authorised
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$reqNo   = $_GET['req'] ?? '';
if (!$reqNo) {
    $_SESSION['error'] = 'Missing request number.';
    header('Location: ../material_request.php');
    exit();
}

/*── Load all rows for this request — restricted to the owner ──────────*/
$stmt = $conn->prepare(
    "SELECT * FROM material_requests
     WHERE request_number = ? AND user_id = ?"
);
$stmt->bind_param('si', $reqNo, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $_SESSION['error'] = 'Invalid or unauthorised request.';
    header('Location: ../material_request.php');
    exit();
}
$rows  = $result->fetch_all(MYSQLI_ASSOC);
$first = $rows[0];                   // all rows share same meta fields



/*── Only editable when status is strictly “Pending” (trim + lower) ─────*/
$editable_statuses = ['pending', 'pending-l1'];
if (!in_array(strtolower(trim($first['status'])), $editable_statuses)) {
  $_SESSION['error'] = "Only requests in Pending, Pending-L1 or Pending-L2 status can be edited.";
  header("Location: material_request.php");
  exit();
}



/*── Build dropdown of projects the user is assigned to ─────────────────*/
$projects = $conn->query(
    "SELECT p.id, p.project_name
     FROM projects p
     JOIN project_users pu ON p.id = pu.project_id
     WHERE pu.user_id = $user_id
     ORDER BY p.project_name"
)->fetch_all(MYSQLI_ASSOC);

/*── Datalists for category / product / unit ────────────────────────────*/
$categories = $conn->query(
    "SELECT DISTINCT category FROM products ORDER BY category"
)->fetch_all(MYSQLI_ASSOC);

$products = $conn->query(
    "SELECT category, product_name, unit FROM products ORDER BY product_name"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Edit Request – Bluebeam Infra</title>
<link rel="stylesheet" href="../css/style.css">

<style>
body{background:#f4f4f4;padding:20px}
.container{background:#fff;padding:22px;border-radius:10px;max-width:1100px;margin:auto}
.form-row{display:flex;flex-wrap:wrap;gap:15px;margin-bottom:10px}
.form-group{flex:1;min-width:150px}
.form-group.small{max-width:90px}
.form-group.medium{max-width:170px}
.form-group label{display:block;margin-bottom:4px;font-weight:600}
.form-group input,.form-group select,.form-group textarea{
  width:100%;padding:7px;border:1px solid #ccc;border-radius:4px}
textarea{resize:vertical;height:35px}
.submit-btn{background:#0d47a1;color:#fff;padding:10px 22px;border:none;border-radius:6px;cursor:pointer}
.add-btn{background:#1565c0;color:#fff;margin-bottom:8px;padding:6px 14px;border:none;border-radius:4px;cursor:pointer}
</style>
</head>
<body>
<div class="container">
  <h2>Edit Material Request – <?= htmlspecialchars($reqNo) ?></h2>

  <form method="POST" action="update_request.php">
    <input type="hidden" name="request_number" value="<?= htmlspecialchars($reqNo) ?>">

    <!-- ── Project selector (only assigned projects) ───────────────── -->
    <div class="form-row">
      <div class="form-group" style="flex:1 1 100%">
        <label>Project</label>
        <select name="project_id" required>
          <?php foreach ($projects as $p): ?>
            <option value="<?= $p['id'] ?>"
              <?= $p['id'] == $first['project_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['project_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- ── Dynamic Excel-style grid ──────────────────────────────── -->
    <div id="productWrap">
      <?php foreach ($rows as $r): ?>
      <div class="prod-row">
        <div class="form-row">
          <div class="form-group">
            <label>Category</label>
            <input list="catList" name="category[]" value="<?= htmlspecialchars($r['category']) ?>"
                   onchange="filterProducts(this)" required>
          </div>
          <div class="form-group">
            <label>Product</label>
            <input list="prodList" name="product[]" value="<?= htmlspecialchars($r['product']) ?>"
                   oninput="autoUnit(this)" required>
          </div>
          <div class="form-group small">
            <label>Unit</label>
            <input type="text" name="unit[]" value="<?= htmlspecialchars($r['unit']) ?>">
          </div>
          <div class="form-group small">
            <label>Qty</label>
            <input type="number" step="0.01" name="quantity[]" value="<?= htmlspecialchars($r['quantity']) ?>" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group medium">
            <label>Required Date</label>
            <input type="date" name="required_date[]" value="<?= htmlspecialchars($r['required_date']) ?>" required>
          </div>
          <div class="form-group">
            <label>Remarks</label>
            <textarea name="remarks[]"><?= htmlspecialchars($r['remarks']) ?></textarea>
          </div>
        </div>
        <hr>
      </div>
      <?php endforeach; ?>
    </div>

    <button type="button" class="add-btn" onclick="addRow()">+ Add Product</button><br>
    <button class="submit-btn">Update Request</button>
  </form>
</div>

<!-- ── Datalists (single copies) ───────────────────────────── -->
<datalist id="catList">
  <?php foreach ($categories as $c): ?>
    <option value="<?= htmlspecialchars($c['category']) ?>">
  <?php endforeach; ?>
</datalist>

<datalist id="prodList">
  <?php foreach ($products as $p): ?>
    <option value="<?= htmlspecialchars($p['product_name']) ?>"
            data-cat="<?= htmlspecialchars($p['category']) ?>"
            data-unit="<?= htmlspecialchars($p['unit']) ?>">
  <?php endforeach; ?>
</datalist>

<script>
/* ========= helper arrays ========= */
const prodOpts = [...document.querySelectorAll('#prodList option')];

/* ========= add row ========= */
function addRow(){
  const tpl = document.querySelector('.prod-row').cloneNode(true);
  tpl.querySelectorAll('input, textarea').forEach(el=> el.value = '');
  document.getElementById('productWrap').appendChild(tpl);
}

/* ========= category → product filter ========= */
function filterProducts(catInput){
  const cat = catInput.value.trim();
  const prodInput = catInput.closest('.prod-row').querySelector('input[list="prodList"]');
  prodInput.value = '';
  prodInput.setAttribute('data-filter', cat);
}

/* ========= auto-unit ========= */
function autoUnit(inp){
  const unitIn = inp.closest('.prod-row').querySelector('input[name="unit[]"]');
  const match  = prodOpts.find(o => o.value === inp.value);
  unitIn.value = match ? match.dataset.unit : '';
}

/* ========= disable products from other categories ========= */
document.addEventListener('input', e=>{
  if(!e.target.matches('input[list="prodList"]')) return;
  const cat = e.target.getAttribute('data-filter')||'';
  prodOpts.forEach(o => { o.disabled = cat && o.dataset.cat !== cat; });
});
</script>
</body>
</html>
