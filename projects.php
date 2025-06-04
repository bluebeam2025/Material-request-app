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

/* ── add project + assign users ───────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name    = $conn->real_escape_string($_POST['project_name']);
    $addr    = $conn->real_escape_string($_POST['project_address']);
    $users   = $_POST['assigned_users'] ?? [];

    if ($conn->query("INSERT INTO projects (project_name, project_address) VALUES ('$name','$addr')")) {
        $pid = $conn->insert_id;
        foreach ($users as $uid) {
            $uid = (int)$uid;
            $conn->query("INSERT INTO project_users (project_id,user_id) VALUES ($pid,$uid)");
        }
        $_SESSION['success'] = 'Project added successfully.';
    } else {
        $_SESSION['error'] = 'Failed to add project.';
    }
    header('Location: projects.php'); exit();
}

/* ── delete project ───────────────────────────────────────── */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM projects WHERE id=$id"); // cascade deletes project_users
    header('Location: projects.php'); exit();
}

/* ── fetch projects + assigned users ─────────────────────── */
$projects_q = $conn->query("SELECT id, project_name, project_address FROM projects ORDER BY id DESC");

$projects = [];
while ($row = $projects_q->fetch_assoc()) {
    $pid = $row['id'];
    $user_q = $conn->query("SELECT name FROM users 
                            JOIN project_users ON users.id = project_users.user_id 
                            WHERE project_users.project_id = $pid ORDER BY name ASC");
    $assigned_users = [];
    while ($u = $user_q->fetch_assoc()) {
        $assigned_users[] = $u['name'];
    }
    $row['assigned_users'] = $assigned_users;
    $projects[] = $row;
}

/* ── fetch normal users list ─────────────────────────────── */
$normal_users = $conn->query("SELECT id, name FROM users WHERE user_type='user' ORDER BY name ASC")
                     ->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Projects – Bluebeam Infra</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/header.php'; ?>

<div class="main-content">
  <?php if ($success): ?><div class="alert success"><?= $success ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert error"><?= $error ?></div><?php endif; ?>

  <div class="user-header">
    <h2>Projects</h2>
    <button class="add-user-btn" onclick="openAdd()">+ Add Project</button>
  </div>

  <table class="user-table">
    <thead>
      <tr>
        <th>SN.NO</th>
        <th>Project Name</th>
        <th>Address</th>
        <th>Assigned Users</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php $sn = 1; foreach ($projects as $proj): 
        $name = htmlspecialchars($proj['project_name'], ENT_QUOTES);
        $addr = htmlspecialchars($proj['project_address'], ENT_QUOTES);
        $assignedList = implode(', ', $proj['assigned_users']);
      ?>
        <tr>
          <td><?= $sn++ ?></td>
          <td><?= $name ?></td>
          <td><?= $addr ?></td>
          <td><?= htmlspecialchars($assignedList) ?></td>
          <td>
            <button class="edit-btn" onclick="openEdit(<?= $proj['id'] ?>, '<?= $name ?>', '<?= $addr ?>')">Edit</button>
            <a class="delete-btn" href="?delete=<?= $proj['id'] ?>" onclick="return confirm('Delete this project?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- ── Add Modal ─────────────────────────────────────────── -->
<div class="modal" id="addModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeAdd()">&times;</span>
    <h3>Add New Project</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label>Project Name</label>
        <input type="text" name="project_name" required>
      </div>
      <div class="form-group">
        <label>Project Address</label>
        <textarea name="project_address" required></textarea>
      </div>
      <div class="form-group">
        <label>Assign Users</label>
        <select name="assigned_users[]" multiple>
          <?php foreach ($normal_users as $u): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <small>Ctrl/Cmd-click to select multiple</small>
      </div>
      <button type="submit" class="submit-btn">Add Project</button>
    </form>
  </div>
</div>

<!-- ── Edit Modal ────────────────────────────────────────── -->
<div class="modal" id="editModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeEdit()">&times;</span>
    <h3>Edit Project</h3>
    <form method="POST" action="php/edit_project.php">
      <input type="hidden" name="project_id" id="editId">
      <div class="form-group">
        <label>Project Name</label>
        <input type="text" name="project_name" id="editName" required>
      </div>
      <div class="form-group">
        <label>Project Address</label>
        <textarea name="project_address" id="editAddr" required></textarea>
      </div>
      <div class="form-group">
        <label>Assign Users</label>
        <select name="assigned_users[]" id="editUsers" multiple>
          <?php foreach ($normal_users as $u): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="submit-btn">Update Project</button>
    </form>
  </div>
</div>

<script>
function openAdd(){ document.getElementById('addModal').style.display='flex'; }
function closeAdd(){ document.getElementById('addModal').style.display='none'; }

function openEdit(id, name, addr){
  document.getElementById('editId').value = id;
  document.getElementById('editName').value = name;
  document.getElementById('editAddr').value = addr;

  // Reset selected users
  const options = document.getElementById('editUsers').options;
  for (let i = 0; i < options.length; i++) options[i].selected = false;

  // Load current assigned users
  fetch('php/get_project_users.php?id=' + id)
    .then(res => res.json())
    .then(userIds => {
      for (let i = 0; i < options.length; i++) {
        if (userIds.includes(parseInt(options[i].value))) {
          options[i].selected = true;
        }
      }
    });

  document.getElementById('editModal').style.display = 'flex';
}
function closeEdit(){ document.getElementById('editModal').style.display='none'; }

window.onclick = e => {
  if (e.target.classList.contains('modal')) e.target.style.display = 'none';
};
</script>
</body>
</html>
