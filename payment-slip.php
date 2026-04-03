<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Handle form submission:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
  $stmt = $conn->prepare("
    INSERT INTO co_payments
      (name, phone, amount, amount_words, pay_type, reason, pay_date)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  ");
  $stmt->bind_param(
    "ssdssss",
    $_POST['name'], $_POST['phone'], $_POST['amount'],
    $_POST['amount_words'], $_POST['pay_type'], $_POST['reason'], $_POST['pay_date']
  );
  $stmt->execute();
  header("Location: payment-slip.php?msg=ok");
  exit;
}

// Handle filters:
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
$where = "WHERE DATE(pay_date) BETWEEN '$from' AND '$to'";
$payments = $conn->query("SELECT * FROM co_payments $where ORDER BY pay_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF‑8"><title>Payment Slips | Nasar & Co</title>
  <link rel="icon" type="image/png" href="nasar.jpg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    
    body { background: #f7f8fb;  font-family: 'Segoe UI', sans-serif; }
    .slip-card { max-width: 500px; margin: auto; background: white; border: 2px solid rgb(0, 0, 0); border-radius: 8px; padding: 20px; }
    .slip-header { text-align: center; border-bottom: 2px solid rgb(239, 93, 8); margin-bottom: 15px; }
    .info-row { display:flex; justify-content: space-between; margin-bottom: 8px; }
    .label { font-weight:600; }
    .signature { margin-top: 50px; text-align: right; }
    .signature hr { width:150px; margin-left:auto; }
    @media print { .no-print { display:none!important; } }
  </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg shadow-sm mb-4" style="background-color:#8e949c;">
  <div class="container-fluid">
    <!-- Logo + Brand -->
    <a class="navbar-brand d-flex align-items-center text-white fw-bold" href="#">
      <img src="nasar.jpg" alt="Logo" width="40" height="40" class="me-2 rounded-circle border border-light">
      Nasar & Co
    </a>

    <!-- Mobile toggle -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Menu -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold" href="dashboard.php">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold" href="funds.php">Add Funds</a>
        </li>
         <li class="nav-item">
          <a class="nav-link text-white fw-semibold" href="funds_record.php">Funds Record</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold" href="client.php">Clients</a>
        </li>
         <li class="nav-item">
          <a class="nav-link text-white fw-semibold" href="all_client.php"> All Clients</a>
        </li>
       
       
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white fw-semibold" href="#" id="navbarDropdown" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            Slips
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="payment-slip.php">Payment Slip</a></li>
           
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>


<div class="container">
  <!-- Payment Form -->
  <div class="card mb-5 p-4 shadow-sm">
    <h4>Add Payment</h4>
    <form method="POST" class="row g-3 mt-2">
      <input type="hidden" name="add_payment" value="1">
      <div class="col-md-4"><label>Name</label><input name="name" class="form-control" required></div>
      <div class="col-md-4"><label>Phone</label><input name="phone" class="form-control" required></div>
      <div class="col-md-4"><label>Amount (Rs)</label><input type="number" name="amount" step="0.01" class="form-control" required></div>
      <div class="col-md-8"><label>Amount in Words</label><input name="amount_words" class="form-control" required></div>
      <div class="col-md-4"><label>Payment Type</label>
        <select name="pay_type" class="form-select">
          <option value="cash">Cash</option><option value="bank">Bank</option>
        </select>
      </div>
      <div class="col-md-8"><label>Reason</label><input name="reason" class="form-control" required></div>
      <div class="col-md-4"><label>Date</label><input type="date" name="pay_date" value="<?= date('Y-m-d') ?>" class="form-control" required></div>
      <div class="col-12"><button class="btn btn-dark">Save Payment</button></div>
    </form>
  </div>

  <!-- Filter and table -->
  <div class="card p-4 shadow-sm">
    <h4>Payments</h4>
    <form method="GET" class="row g-3 align-items-end mb-3">
      <div class="col-md-3"><label>From</label><input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>"></div>
      <div class="col-md-3"><label>To</label><input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>"></div>
      <div class="col-md-3"><button class="btn btn-primary">Filter</button> 
        <a href="payment-slip.php" class="btn btn-secondary">Reset</a>
      </div>
    </form>

    <table class="table table-bordered">
      <thead class="table-dark"><tr>
        <th>Name</th><th>Phone</th><th>Date</th><th>Amount (Rs)</th><th>Action</th>
      </tr></thead>
      <tbody>
        <?php if ($payments->num_rows): while ($p = $payments->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['phone']) ?></td>
            <td><?= htmlspecialchars($p['pay_date']) ?></td>
            <td>Rs <?= number_format($p['amount'],2) ?></td>
            <td><a href="view_payment.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-dark text-white">View</a></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="5" class="text-center">No Payments</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
