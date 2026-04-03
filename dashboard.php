<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ✅ Date filters
$from = $_GET['from'] ?? '2000-01-01';
$to   = $_GET['to'] ?? date('Y-m-d');

// ✅ Funds summary
$capital = $conn->query("
  SELECT SUM(amount) as total 
  FROM co_funds 
  WHERE LOWER(type)='capital' 
    AND DATE(created_at) BETWEEN '$from' AND '$to'
")->fetch_assoc()['total'] ?? 0;

$income = $conn->query("
  SELECT SUM(amount) as total 
  FROM co_funds
  WHERE LOWER(type)='income' 
    AND DATE(created_at) BETWEEN '$from' AND '$to'
")->fetch_assoc()['total'] ?? 0;

$expense = $conn->query("
  SELECT SUM(amount) as total 
  FROM co_funds 
  WHERE LOWER(type)='expense' 
    AND DATE(created_at) BETWEEN '$from' AND '$to'
")->fetch_assoc()['total'] ?? 0;

$profit  = $income - $expense;

// ✅ Pagination
$limit = 10;

// --- Clients ---
$pageCli   = max(1, intval($_GET['cli_page'] ?? 1));
$offsetCli = ($pageCli - 1) * $limit;
$cliStmt = $conn->prepare("
    SELECT c.id, c.customer_name, c.timestamp, 
           GROUP_CONCAT(s.service SEPARATOR ', ') AS services
    FROM nasar_clients c
    LEFT JOIN nasar_client_services s ON c.id = s.client_id
    WHERE DATE(c.timestamp) BETWEEN ? AND ?
    GROUP BY c.id, c.customer_name, c.timestamp
    ORDER BY c.timestamp DESC 
    LIMIT $limit OFFSET $offsetCli
");
$cliStmt->bind_param("ss", $from, $to);
$cliStmt->execute();
$clients = $cliStmt->get_result();

$cliTotal = $conn->query("
    SELECT COUNT(*) as total 
    FROM nasar_clients 
    WHERE DATE(timestamp) BETWEEN '$from' AND '$to'
")->fetch_assoc()['total'];

$cliPages = ceil($cliTotal / $limit);

// --- Employees ---
$pageEmp   = max(1, intval($_GET['emp_page'] ?? 1));
$offsetEmp = ($pageEmp - 1) * $limit;
$empStmt = $conn->prepare("SELECT employee_id, name, designation, joining_date, salary 
                           FROM nasar_employees 
                           WHERE DATE(joining_date) BETWEEN ? AND ? 
                           ORDER BY joining_date DESC 
                           LIMIT $limit OFFSET $offsetEmp");
$empStmt->bind_param("ss", $from, $to);
$empStmt->execute();
$employees = $empStmt->get_result();
$empTotal = $conn->query("SELECT COUNT(*) as total FROM nasar_employees WHERE DATE(joining_date) BETWEEN '$from' AND '$to'")->fetch_assoc()['total'];
$empPages = ceil($empTotal / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Nasar Dashboard</title>
 <link rel="icon" type="image/png" href="nasar.jpg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body { background:#f8f9fa; font-family: 'Segoe UI', sans-serif; }
    .summary-box { color:white; padding:20px; border-radius:10px; text-align:center; }
    .capital { background-color:black; }
    .income  { background-color:rgb(33,65,9); }
    .expense { background-color:rgb(230,34,16); }
    .profit  { background-color:rgb(99,104,108); }
    .table-box { background:#fff; padding:20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
    .pagination .page-link { background-color:rgb(239,93,8); color:white; }
    .pagination .active .page-link { background-color:#333; border:none; }
  </style>
</head>
<body>
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
<div class="container mt-5">

  <!-- Date Filter -->
  <form method="GET" class="row g-3 mb-4">
    <div class="col-md-3">
      <input type="date" name="from" value="<?= $from ?>" class="form-control">
    </div>
    <div class="col-md-3">
      <input type="date" name="to" value="<?= $to ?>" class="form-control">
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-primary w-100">Filter</button>
      <a href="?reset=1" class="btn btn-secondary w-100">Reset</a>
    </div>
  </form>

  <!-- Summary Cards -->
  <div class="row text-white mb-4">
    <div class="col-md-3">
      <div class="summary-box capital">
        <div class="d-flex justify-content-between align-items-center">
          <h4>Capital</h4>
          <a href="download_funds.php?type=capital&from=<?= $from ?>&to=<?= $to ?>" target="_blank" class="text-white">
            <i class="bi bi-download"></i>
          </a>
        </div>
        <div><?= number_format($capital, 2) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="summary-box income">
        <div class="d-flex justify-content-between align-items-center">
          <h4>Income</h4>
          <a href="download_funds.php?type=income&from=<?= $from ?>&to=<?= $to ?>" target="_blank" class="text-white">
            <i class="bi bi-download"></i>
          </a>
        </div>
        <div><?= number_format($income, 2) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="summary-box expense">
        <div class="d-flex justify-content-between align-items-center">
          <h4>Expense</h4>
          <a href="download_funds.php?type=expense&from=<?= $from ?>&to=<?= $to ?>" target="_blank" class="text-white">
            <i class="bi bi-download"></i>
          </a>
        </div>
        <div><?= number_format($expense, 2) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="summary-box profit">
        <h4>Profit / Loss</h4>
        <div><?= ($profit >= 0 ? '+' : '-') . number_format(abs($profit), 2) ?></div>
      </div>
    </div>
  </div>

 
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
