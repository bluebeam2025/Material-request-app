<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}
include 'php/db_connect.php';

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error']   ?? '';
unset($_SESSION['success'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='add') {
    $cat      = $conn->real_escape_string($_POST['category']);
    $company  = $conn->real_escape_string($_POST['company']);
    $contact  = $conn->real_escape_string($_POST['contact']);
    $address  = $conn->real_escape_string($_POST['address']);
    $email    = $conn->real_escape_string($_POST['email']);
    $phone    = $conn->real_escape_string($_POST['phone']);
    $mobile   = $conn->real_escape_string($_POST['mobile']);

    $sql = "INSERT INTO suppliers (category,company,contact,address,email,phone,mobile)
            VALUES ('$cat','$company','$contact','$address','$email','$phone','$mobile')";
    if ($conn->query($sql)) {
        $_SESSION['success'] = 'Supplier added.';
    } else {
        $_SESSION['error'] = 'Failed to add supplier.';
    }
    header('Location: suppliers.php'); exit();
}

if (isset($_GET['delete'])) {
    $id=(int)$_GET['delete'];
    $conn->query("DELETE FROM suppliers WHERE id=$id");
    header('Location: suppliers.php'); exit();
}

$suppliers = $conn->query("SELECT * FROM suppliers ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT DISTINCT category FROM suppliers ORDER BY category ASC")
                   ->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Suppliers – Bluebeam Infra</title>
  <link rel="stylesheet" href="css/style.css">
<style>
  .form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 15px;
  }
  .form-group {
    flex: 1;
    min-width: 220px;
  }
  .form-group textarea {
    resize: vertical;
    min-height: 38px;
    padding: 8px;
  }
  .modal-content {
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 20px;
  }
  .modal h3 {
    margin-top: 0;
  }
  .submit-btn {
    margin-top: 10px;
    padding: 10px 20px;
  }
  .user-table td:nth-child(5) {
    max-width: 150px;
    word-wrap: break-word;
    white-space: pre-wrap;
  }
  .user-table td:last-child {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }
  .edit-btn, .delete-btn {
    width: 70px;
  }
</style>

</head>
<body>

<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/header.php'; ?>

<div class="main-content">
  <?php if($success):?><div class="alert success"><?=$success?></div><?php endif;?>
  <?php if($error):  ?><div class="alert error"><?=$error?></div><?php endif;?>

  <div class="user-header">
    <h2>Suppliers</h2>
    <button class="add-user-btn" onclick="openAdd()">+ Add Supplier</button>
    <button class="add-user-btn" style="margin-left:10px;" onclick="openUpload()">↑ Bulk CSV</button>
  </div>

  <table class="user-table">
    <thead>
      <tr>
        <th>SN</th><th>Category</th><th>Company</th><th>Contact</th>
        <th>Address</th><th>Email</th><th>Phone</th><th>Mobile</th><th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php $sn=1; foreach($suppliers as $s): ?>
        <tr>
          <td><?=$sn++?></td>
          <td><?=htmlspecialchars($s['category'])?></td>
          <td><?=htmlspecialchars($s['company'])?></td>
          <td><?=htmlspecialchars($s['contact'])?></td>
          <td><?=htmlspecialchars($s['address'])?></td>
          <td><?=htmlspecialchars($s['email'])?></td>
          <td><?=htmlspecialchars($s['phone'])?></td>
          <td><?=htmlspecialchars($s['mobile'])?></td>
          <td>
            <button class="edit-btn"
              onclick="openEdit(<?=$s['id']?>,'<?=htmlspecialchars($s['category'], ENT_QUOTES)?>','<?=htmlspecialchars($s['company'], ENT_QUOTES)?>','<?=htmlspecialchars($s['contact'], ENT_QUOTES)?>','<?=htmlspecialchars($s['address'], ENT_QUOTES)?>','<?=htmlspecialchars($s['email'], ENT_QUOTES)?>','<?=htmlspecialchars($s['phone'], ENT_QUOTES)?>','<?=htmlspecialchars($s['mobile'], ENT_QUOTES)?>')">Edit</button>
            <a class="delete-btn" href="?delete=<?=$s['id']?>" onclick="return confirm('Delete this supplier?')">Delete</a>
          </td>
        </tr>
      <?php endforeach;?>
    </tbody>
  </table>
</div>

<!-- Add Supplier Modal -->
<div class="modal" id="addModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeAdd()">&times;</span>
    <h3>Add Supplier</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <div class="form-group">
          <label>Category</label>
          <input list="catList" name="category" required>
          <datalist id="catList">
            <?php foreach($categories as $c): ?><option value="<?=htmlspecialchars($c['category'])?>"><?php endforeach;?>
          </datalist>
        </div>
        <div class="form-group">
          <label>Company Name</label>
          <input type="text" name="company" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Contact Person</label>
          <input type="text" name="contact" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone">
        </div>
        <div class="form-group">
          <label>Mobile</label>
          <input type="text" name="mobile">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group" style="width: 100%;">
          <label>Address</label>
          <textarea name="address" required></textarea>
        </div>
      </div>
      <button class="submit-btn">Save</button>
    </form>
  </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal" id="editModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeEdit()">&times;</span>
    <h3>Edit Supplier</h3>
    <form method="POST" action="php/edit_supplier.php">
      <input type="hidden" name="supplier_id" id="eid">
      <div class="form-row">
        <div class="form-group">
          <label>Category</label>
          <input list="catList" name="category" id="ecat" required>
        </div>
        <div class="form-group">
          <label>Company</label>
          <input type="text" name="company" id="ecmp" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Contact</label>
          <input type="text" name="contact" id="ectc" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" id="eeml">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" id="eph">
        </div>
        <div class="form-group">
          <label>Mobile</label>
          <input type="text" name="mobile" id="emob">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group" style="width: 100%;">
          <label>Address</label>
          <textarea name="address" id="eadd" required></textarea>
        </div>
      </div>
      <button class="submit-btn">Update</button>
    </form>
  </div>
</div>

<!-- Bulk Upload Modal -->
<div class="modal" id="uploadModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeUpload()">&times;</span>
    <h3>Upload Suppliers (CSV)</h3>
    <p><small>Columns: Category, Company, Contact, Address, Email, Phone, Mobile</small></p>
    <form method="POST" action="php/upload_suppliers.php" enctype="multipart/form-data">
      <input type="file" name="csv_file" accept=".csv" required>
      <button class="submit-btn">Upload</button>
    </form>
  </div>
</div>

<script>
function openAdd(){document.getElementById('addModal').style.display='flex';}
function closeAdd(){document.getElementById('addModal').style.display='none';}

function openEdit(id,cat,cmp,ctc,add,eml,ph,mob){
  eid.value=id; ecat.value=cat; ecmp.value=cmp; ectc.value=ctc;
  eadd.value=add; eeml.value=eml; eph.value=ph; emob.value=mob;
  document.getElementById('editModal').style.display='flex';
}
function closeEdit(){document.getElementById('editModal').style.display='none';}

function openUpload(){document.getElementById('uploadModal').style.display='flex';}
function closeUpload(){document.getElementById('uploadModal').style.display='none';}

window.onclick=e=>{if(e.target.classList.contains('modal')) e.target.style.display='none';};

const eid=document.getElementById('eid'),
      ecat=document.getElementById('ecat'),
      ecmp=document.getElementById('ecmp'),
      ectc=document.getElementById('ectc'),
      eadd=document.getElementById('eadd'),
      eeml=document.getElementById('eeml'),
      eph =document.getElementById('eph'),
      emob=document.getElementById('emob');
</script>
</body>
</html>
