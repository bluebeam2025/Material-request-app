if($_SESSION['user_type']!=='admin'){ header('Location: dashboard.php'); exit(); }
$leaves = $conn->query("
    SELECT lr.*, ru.name requester, a1.name a1_name, a2.name a2_name
      FROM leave_requests lr
 LEFT JOIN users ru ON ru.id = lr.user_id
 LEFT JOIN users a1 ON a1.id = lr.approver1_id
 LEFT JOIN users a2 ON a2.id = lr.approver2_id
 ORDER BY lr.created_at DESC")->fetch_all(MYSQLI_ASSOC);
/* ... render same table, but without Edit/Delete columns ... */
