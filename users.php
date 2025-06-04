<?php
session_start();
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'php/db_connect.php';

// Handle new user submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $designation = $_POST['designation'];
    $user_type = $_POST['user_type'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, username, designation, user_type, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $username, $designation, $user_type, $password);

    if ($stmt->execute()) {
        $_SESSION['success'] = "User added successfully.";
    } else {
        $_SESSION['error'] = "Failed to add user.";
    }

    header("Location: users.php");
    exit();
}


// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($conn->query("DELETE FROM users WHERE id = $id")) {
        $_SESSION['success'] = "User deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete user.";
    }

    header("Location: users.php");
    exit();
}


// Fetch users
$users = [];
$result = $conn->query("SELECT id, name, username, designation, user_type FROM users ORDER BY id DESC");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>All Users – Bluebeam Infra</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/header.php'; ?>

<div class="main-content">
  <?php if ($success): ?>
  <div class="alert success"><?= $success ?></div>
<?php elseif ($error): ?>
  <div class="alert error"><?= $error ?></div>
<?php endif; ?>

  <div class="user-header">
    <h2>All Users</h2>
    <button class="add-user-btn" onclick="openModal()">+ Add User</button>
  </div>

  <table class="user-table">
    <thead>
      <tr>
        <th>Name</th>
        <th>User ID</th>
        <th>Designation</th>
        <th>Role</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= htmlspecialchars($user['name']) ?></td>
          <td><?= htmlspecialchars($user['username']) ?></td>
          <td><?= htmlspecialchars($user['designation']) ?></td>
          <td><?= htmlspecialchars($user['user_type']) ?></td>
          <td>
            <button class="edit-btn" 
                    onclick="openEditModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>', 
                                           '<?= htmlspecialchars($user['username']) ?>', 
                                           '<?= htmlspecialchars($user['designation']) ?>', 
                                           '<?= $user['user_type'] ?>')">
              Edit
            </button>
            <a class="delete-btn" href="?delete=<?= $user['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add User Modal -->
<div class="modal" id="addUserModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <h3>Add New User</h3>
    <form method="POST" action="">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" required>
      </div>
      <div class="form-group">
        <label>User ID</label>
        <input type="text" name="username" required>
      </div>
      <div class="form-group">
        <label>Designation</label>
        <input type="text" name="designation">
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="user_type" required>
          <option value="user">User</option>
          <option value="approver1">Approver Level 1</option>
          <option value="approver2">Approver Level 2</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="submit-btn">Add User</button>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal" id="editUserModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeEditModal()">&times;</span>
    <h3>Edit User</h3>
    <form method="POST" action="php/edit_user.php">
      <input type="hidden" name="id" id="editUserId">

      <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" id="editName" required>
      </div>

      <div class="form-group">
        <label>User ID</label>
        <input type="text" name="username" id="editUsername" required>
      </div>

      <div class="form-group">
        <label>Designation</label>
        <input type="text" name="designation" id="editDesignation">
      </div>

      <div class="form-group">
        <label>Role</label>
        <select name="user_type" id="editUserType" required>
          <option value="user">User</option>
          <option value="approver1">Approver Level 1</option>
          <option value="approver2">Approver Level 2</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <div class="form-group">
        <label>Password <small>(leave blank to keep current)</small></label>
        <input type="password" name="password" placeholder="Enter new password (optional)">
      </div>

      <button type="submit" class="submit-btn">Update User</button>
    </form>
  </div>
</div>


<script>
  function openModal() {
    document.getElementById("addUserModal").style.display = "flex";
  }

  function closeModal() {
    document.getElementById("addUserModal").style.display = "none";
  }

  function openEditModal(id, name, username, designation, user_type) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editUsername').value = username;
    document.getElementById('editDesignation').value = designation;
    document.getElementById('editUserType').value = user_type;
    document.getElementById('editUserModal').style.display = 'flex';
  }

  function closeEditModal() {
    document.getElementById("editUserModal").style.display = "none";
  }

  window.onclick = function(e) {
    const addModal = document.getElementById("addUserModal");
    const editModal = document.getElementById("editUserModal");
    if (e.target == addModal) {
      addModal.style.display = "none";
    }
    if (e.target == editModal) {
      editModal.style.display = "none";
    }
  };
</script>

</body>
</html>
