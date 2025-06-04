<?php
/* ── security & db ────────────────────────────────────────── */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

include 'php/db_connect.php';

/* ── flash messages ───────────────────────────────────────── */
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error']   ?? '';
unset($_SESSION['success'], $_SESSION['error']);

/* ── add new product ─────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='add') {
    $cat   = $conn->real_escape_string($_POST['category']);
    $name  = $conn->real_escape_string($_POST['product_name']);
    $unit  = $conn->real_escape_string($_POST['unit']);

    if ($conn->query("INSERT INTO products (category,product_name,unit) VALUES ('$cat','$name','$unit')")) {
        $_SESSION['success']='Product added.';
    } else {
        $_SESSION['error']='Failed to add product.';
    }
    header('Location: products.php'); exit();
}

/* ── delete product ───────────────────────────────────────── */
if (isset($_GET['delete'])) {
    $id=(int)$_GET['delete'];
    $conn->query("DELETE FROM products WHERE id=$id");
    header('Location: products.php'); exit();
}

/* ── fetch lists ─────────────────────────────────────────── */
$products = $conn->query("SELECT * FROM products ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT DISTINCT category FROM products ORDER BY category ASC")
                   ->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Products – Bluebeam Infra</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/header.php'; ?>

<div class="main-content">
  <?php if($success):?><div class="alert success"><?=$success?></div><?php endif;?>
  <?php if($error):  ?><div class="alert error"><?=$error?></div><?php endif;?>

  <div class="user-header">
    <h2>Products</h2>
    <button class="add-user-btn" onclick="openAdd()">+ Add Product</button>
    <button class="add-user-btn" style="margin-left:10px;" onclick="openUpload()">↑ Bulk CSV</button>
  </div>

  <table class="user-table">
    <thead>
      <tr><th>SN</th><th>Category</th><th>Product Description</th><th>Unit</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php $sn=1; foreach($products as $p): 
        $cat=htmlspecialchars($p['category'],ENT_QUOTES);
        $prod=htmlspecialchars($p['product_name'],ENT_QUOTES);
        $unit=htmlspecialchars($p['unit'],ENT_QUOTES);?>
        <tr>
          <td><?=$sn++?></td>
          <td><?=$cat?></td>
          <td><?=$prod?></td>
          <td><?=$unit?></td>
          <td>
            <button class="edit-btn" onclick="openEdit(<?=$p['id']?>,'<?=$cat?>','<?=$prod?>','<?=$unit?>')">Edit</button>
            <a class="delete-btn" href="?delete=<?=$p['id']?>" onclick="return confirm('Delete product?')">Delete</a>
          </td>
        </tr>
      <?php endforeach;?>
    </tbody>
  </table>
</div>

<!-- ── Add Product Modal ─────────────────────────────────── -->
<div class="modal" id="addModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeAdd()">&times;</span>
    <h3>Add Product</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label>Category (choose or type new)</label>
        <input list="catList" name="category" required>
        <datalist id="catList">
          <?php foreach($categories as $c): ?>
            <option value="<?=htmlspecialchars($c['category'])?>">
          <?php endforeach;?>
        </datalist>
      </div>
      <div class="form-group">
        <label>Product Description</label>
        <input type="text" name="product_name" required>
      </div>
      <div class="form-group">
        <label>Unit</label>
        <input type="text" name="unit" required>
      </div>
      <button class="submit-btn">Save</button>
    </form>
  </div>
</div>

<!-- ── Edit Product Modal ────────────────────────────────── -->
<div class="modal" id="editModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeEdit()">&times;</span>
    <h3>Edit Product</h3>
    <form method="POST" action="php/edit_product.php">
      <input type="hidden" name="product_id" id="eid">
      <div class="form-group">
        <label>Category</label>
        <input list="catList" name="category" id="ecat" required>
      </div>
      <div class="form-group">
        <label>Product Description</label>
        <input type="text" name="product_name" id="ename" required>
      </div>
      <div class="form-group">
        <label>Unit</label>
        <input type="text" name="unit" id="eunit" required>
      </div>
      <button class="submit-btn">Update</button>
    </form>
  </div>
</div>

<!-- ── Bulk Upload Modal ─────────────────────────────────── -->
<div class="modal" id="uploadModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeUpload()">&times;</span>
    <h3>Upload Products (CSV)</h3>
    <p><small>CSV columns order: <strong>Category, Product Name, Unit</strong></small></p>
    <form method="POST" action="php/upload_products.php" enctype="multipart/form-data">
      <input type="file" name="csv_file" accept=".csv" required>
      <button class="submit-btn">Upload</button>
    </form>
  </div>
</div>

<script>
function openAdd(){document.getElementById('addModal').style.display='flex';}
function closeAdd(){document.getElementById('addModal').style.display='none';}

function openEdit(id,cat,name,unit){
  eid.value=id; ecat.value=cat; ename.value=name; eunit.value=unit;
  document.getElementById('editModal').style.display='flex';
}
function closeEdit(){document.getElementById('editModal').style.display='none';}

function openUpload(){document.getElementById('uploadModal').style.display='flex';}
function closeUpload(){document.getElementById('uploadModal').style.display='none';}

window.onclick=e=>{if(e.target.classList.contains('modal')) e.target.style.display='none';};

const eid=document.getElementById('eid'),
      ecat=document.getElementById('ecat'),
      ename=document.getElementById('ename'),
      eunit=document.getElementById('eunit');
</script>
</body>
</html>
