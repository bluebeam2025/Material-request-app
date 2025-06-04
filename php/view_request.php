<?php
include 'db_connect.php';

$reqNo = $_GET['req'] ?? '';
$stmt = $conn->prepare("SELECT mr.*, p.project_name, p.project_address, u.name AS requested_by 
                        FROM material_requests mr 
                        JOIN projects p ON mr.project_id = p.id 
                        JOIN users u ON mr.user_id = u.id 
                        WHERE mr.request_number = ?");
$stmt->bind_param("s", $reqNo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>Request not found.</p>";
    exit;
}

$rows = $result->fetch_all(MYSQLI_ASSOC);
$first = $rows[0];
?>

<style>
  body {
    background: #f0f0f0;
    margin: 0;
  }

  .print-wrap {
    font-family: Arial, sans-serif;
    background: #fff;
    color: #000;
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    padding: 25mm 20mm 20mm;
    box-sizing: border-box;
    box-shadow: 0 0 8px rgba(0,0,0,0.15);
  }

  .print-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
  }

  .print-header h2 {
    margin: 0;
    font-size: 22px;
    color: #0d47a1;
  }

  .print-sub {
    margin-top: 0;
    font-size: 14px;
    line-height: 1.5;
  }

  .print-details {
    margin: 10px 0 20px;
    font-size: 14px;
  }

  .print-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
  }

  .print-table th, .print-table td {
    border: 1px solid #000;
    padding: 8px;
    font-size: 13px;
    vertical-align: top;
  }

  .print-table th {
    background-color: #e3f2fd;
    text-align: left;
  }

  .no-print {
    margin-top: 25px;
    text-align: center;
  }

  @media print {
    body {
      background: #fff !important;
      margin: 0;
    }
    .no-print {
      display: none !important;
    }
    .print-wrap {
      box-shadow: none !important;
      width: 100% !important;
      padding: 20mm 15mm 15mm;
      page-break-after: auto;
    }
  }
</style>

<div class="print-wrap" id="printDiv">
  <div class="print-header">
    <div>
      <h2>Material Request: <?= htmlspecialchars($first['request_number']) ?></h2>
      <p class="print-sub">
        <strong>Project:</strong> <?= htmlspecialchars($first['project_name']) ?><br>
        <strong>Address:</strong> <?= htmlspecialchars($first['project_address']) ?>
      </p>
    </div>
    <div style="text-align:right;">
      <p class="print-sub">
        <strong>Requested Date:</strong><br>
        <?= date('d M Y', strtotime($first['created_at'])) ?>
      </p>
    </div>
  </div>

  <div class="print-details">
    <strong>Requested By:</strong> <?= htmlspecialchars($first['requested_by']) ?>
  </div>

  <table class="print-table">
    <thead>
      <tr>
        <th>SN</th>
        <th>Category</th>
        <th>Product</th>
        <th>Unit</th>
        <th>Qty</th>
        <th>Required Date</th>
        <th>Remarks</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($rows as $i => $r): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($r['category']) ?></td>
        <td><?= htmlspecialchars($r['product']) ?></td>
        <td><?= htmlspecialchars($r['unit']) ?></td>
        <td><?= htmlspecialchars($r['quantity']) ?></td>
        <td><?= date('d M Y', strtotime($r['required_date'])) ?></td>
        <td><?= nl2br(htmlspecialchars($r['remarks'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="no-print">
    <button onclick="window.print()">🖨️ Print This Page</button>
  </div>
</div>
