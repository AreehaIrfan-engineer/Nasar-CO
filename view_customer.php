<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Invalid Request");
}
$id = intval($_GET['id']);

// ✅ Fetch customer
$stmt = $conn->prepare("SELECT * FROM co_clients WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if (!$customer) {
    die("Customer not found.");
}

// ✅ Fetch services
$stmt = $conn->prepare("SELECT * FROM co_client_services WHERE client_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer Receipt — Nasar & Co</title>
  <link rel="icon" href="nasar.jpg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
    .receipt-card {
      max-width: 900px;
      margin: auto;
      background: #fff;
      border: 2px solid #000;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    }
    .receipt-header {
      text-align: center;
      margin-bottom: 30px;
      border-bottom: 2px solid #dee2e6;
      padding-bottom: 10px;
    }
    .receipt-header img {
      height: 60px;
      margin-bottom: 10px;
    }
    .info-table th {
      width: 35%;
      background: #f8f9fa;
      color: #333;
      font-weight: 600;
      padding: 12px;
      border-right: 1px solid #dee2e6;
      vertical-align: middle;
    }
    .info-table td {
      padding: 12px;
      background: #ffffff;
      color: #444;
      vertical-align: middle;
    }
    .info-table {
      border-radius: 8px;
      overflow: hidden;
    }
    .info-table tr:hover td { background-color: #f1f1f1; }
    .services-table th {
      background: #343a40;
      color: #fff;
      text-align: center;
    }
    .services-table td {
      text-align: center;
    }
    .btn-print { float: right; }
    @media print {
      .no-print { display: none !important; }
      .receipt-card { border: none; box-shadow: none; }
    }
  </style>
</head>
<body>

<div class="receipt-card mt-5">
  <div class="receipt-header">
    <img src="nasar.jpg" alt="Logo" class="rounded-circle border border-secondary">
    <h4 class="mt-2">Nasar & Co</h4>
    <h5>Customer Receipt</h5>
  </div>

  <!-- Customer Info -->
  <table class="table table-bordered info-table align-middle">
    <tbody>
      <tr><th>Name</th><td><?= htmlspecialchars($customer['customer_name']) ?></td></tr>
      <tr><th>Father Name</th><td><?= htmlspecialchars($customer['father_name']) ?></td></tr>
      <tr><th>CNIC</th><td><?= htmlspecialchars($customer['cnic']) ?></td></tr>
      <tr><th>Phone</th><td><?= htmlspecialchars($customer['phone']) ?></td></tr>
      <tr><th>Address</th><td><?= htmlspecialchars($customer['address']) ?></td></tr>
      <tr><th>Email</th><td><?= htmlspecialchars($customer['email']) ?></td></tr>
      <tr><th>Document Details</th><td><?= nl2br(htmlspecialchars($customer['document_details'])) ?></td></tr>
      <?php if ($customer['document_file']): ?>
        <tr><th>Document File</th><td><a href="<?= htmlspecialchars($customer['document_file']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View PDF</a></td></tr>
      <?php endif; ?>
      <tr><th>Date</th><td><?= date('Y-m-d', strtotime($customer['timestamp'])) ?></td></tr>
    </tbody>
  </table>

  <!-- Services Availed -->
  <h5 class="mt-4">Services Availed</h5>
  <table class="table table-bordered services-table">
    <thead>
      <tr>
        <th>Provider</th>
        <th>Service</th>
        <th>Sub-Service</th>
        <th>Charges (Rs)</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($services): ?>
        <?php foreach ($services as $srv): ?>
          <tr>
            <td><?= htmlspecialchars($srv['provider']) ?></td>
            <td><?= htmlspecialchars($srv['service']) ?></td>
            <td><?= htmlspecialchars($srv['sub_service']) ?></td>
            <td><?= number_format($srv['charges'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="4" class="text-center">No services added</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Payment Summary -->
  <h5 class="mt-4">Payment Summary</h5>
  <table class="table table-bordered">
    <tr><th>Total Amount</th><td>Rs <?= number_format($customer['total_amount'], 2) ?></td></tr>
    <tr><th>Paid Amount</th><td>Rs <?= number_format($customer['paid_amount'], 2) ?></td></tr>
    <tr><th>Remaining Amount</th><td>Rs <?= number_format($customer['remaining_amount'], 2) ?></td></tr>
  </table>

  <div class="text-end mt-4">
    <p><strong>Signature: ________________________</strong></p>
  </div>
</div>

<!-- Action Buttons -->
<div class="container mt-3 no-print text-end">
  <a href="client.php" class="btn btn-secondary">Back</a>
  <a href="edit_customer.php?id=<?= $customer['id'] ?>" class="btn btn-warning">Edit</a>
  <a href="client.php?delete=<?= $customer['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this customer?')">Delete</a>
  <button onclick="window.print()" class="btn btn-dark btn-print">Print Receipt</button>
</div>

</body>
</html>
