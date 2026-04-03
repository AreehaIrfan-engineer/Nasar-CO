<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Handle new customer insert
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name    = $_POST['customer_name'];
    $father_name      = $_POST['father_name'];
    $cnic             = $_POST['cnic'];
    $phone            = $_POST['phone'];
    $address          = $_POST['address'];
    $email            = $_POST['email'];
    $document_details = $_POST['document_details'];
    $document_file    = null;

    $paid_amount      = $_POST['paid_amount'] ?: 0;
    $total_amount     = $_POST['total_amount'] ?: 0;
    $remaining_amount = $_POST['remaining_amount'] ?: 0;

    // ✅ File Upload
    if (!empty($_FILES['document_file']['name'])) {
        $upload_dir = __DIR__ . "/uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_tmp = $_FILES['document_file']['tmp_name'];
        $file_name = uniqid("doc_", true) . ".pdf";
        if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
            $document_file = "uploads/" . $file_name;
        }
    }

    // ✅ Insert into nasar_clients first
    $stmt = $conn->prepare("INSERT INTO co_clients 
        (customer_name, father_name, cnic, phone, address, email, total_amount, paid_amount, remaining_amount, document_details, document_file)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssddsss",
        $customer_name, $father_name, $cnic, $phone, $address, $email,
        $total_amount, $paid_amount, $remaining_amount,
        $document_details, $document_file
    );

    if ($stmt->execute()) {
        $client_id = $conn->insert_id;
    } else {
        die("Customer insert failed: " . $stmt->error);
    }
    $stmt->close();

    // ✅ Insert multiple services AFTER customer is created
    if (!empty($_POST['services'])) {
        foreach ($_POST['services'] as $srv) {
            $service = $srv['service'];
            if ($service === "Other" && !empty($srv['other_service'])) {
                $service = $srv['other_service'];
            }
            if (!empty($srv['provider']) || !empty($service)) {
                $stmt = $conn->prepare("INSERT INTO co_client_services (client_id, provider, service, sub_service, charges) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isssd", $client_id, $srv['provider'], $service, $srv['sub_service'], $srv['charges']);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    header("Location: client.php?msg=Customer+Added+Successfully");
    exit;
}

// ✅ Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM co_clients WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// ✅ Date filter
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end'] ?? date('Y-m-d');

// ✅ Fetch customers
$stmt = $conn->prepare("SELECT * FROM co_clients WHERE DATE(timestamp) BETWEEN ? AND ? ORDER BY timestamp DESC");
$stmt->bind_param("ss", $start, $end);
$stmt->execute();
$result = $stmt->get_result();
$customers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer Management — Nasar Law Firm</title>
  <link rel="icon" type="image/png" href="nasar.jpg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f5f7fa; font-family: 'Segoe UI', Roboto, Arial, sans-serif; }
    .navbar { background-color: #0d6efd; }
    .navbar-brand { font-weight: 700; color: #fff !important; }
    .navbar-brand img { height: 40px; margin-right: 8px; }
    .form-section, .table-section {
      background: #fff; border-radius: 12px; padding: 24px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.08); margin-bottom: 30px;
    }
    .btn-primary-custom {
      background: linear-gradient(90deg, #0077cc, #0a3d62);
      color: #fff; border: none; border-radius: 8px;
      padding: 10px 20px; font-weight: 600;
    }
    .btn-primary-custom:hover { background: linear-gradient(90deg, #005fa3, #0a3d62); }
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

<div class="container my-4">

  <!-- Customer Form -->
 

  <!-- Date Filter -->
  <div class="form-section">
    <form method="get" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label">From</label>
        <input type="date" name="start" value="<?= $start ?>" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">To</label>
        <input type="date" name="end" value="<?= $end ?>" class="form-control">
      </div>
      <div class="col-md-4">
        <button class="btn-primary-custom w-100">Filter</button>
      </div>
    </form>
  </div>

  <!-- Customers Table -->
  <div class="table-section">
    <h4 class="mb-3">Customer Records</h4>
    <table class="table table-striped table-bordered align-middle">
      <thead class="table-dark">
        <tr>
          <th>Name</th><th>Phone</th><th>Total</th><th>Paid</th><th>Remaining</th><th>Date</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['customer_name']) ?></td>
          <td><?= htmlspecialchars($c['phone']) ?></td>
          <td><?= number_format($c['total_amount'], 2) ?></td>
          <td><?= number_format($c['paid_amount'], 2) ?></td>
          <td><?= number_format($c['remaining_amount'], 2) ?></td>
          <td><?= date('Y-m-d', strtotime($c['timestamp'])) ?></td>
          <td>
            <a href="view_customer.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-info text-white">View</a>
            <a href="edit_customer.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this customer?')">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>

<script>
let serviceCount = 0;

function addService() {
  serviceCount++;
  const wrapper = document.getElementById("servicesWrapper");

  const serviceRow = document.createElement("div");
  serviceRow.classList.add("row","g-3","align-items-end","border","p-3","mb-2","rounded");
  serviceRow.innerHTML = `
    <div class="col-md-3">
      <label class="form-label">Service Provider</label>
      <input type="text" name="services[${serviceCount}][provider]" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Service</label>
      <select name="services[${serviceCount}][service]" class="form-select service-select" onchange="toggleOtherService(this, ${serviceCount})" required>
        <option value="">-- Select Service --</option>
        <option value="Taxation">Taxation</option>
        <option value="Revenue Record">Revenue Record</option>
        <option value="Registry">Registry</option>
        <option value="Civil Law">Civil Law</option>
        <option value="Criminal Law">Criminal Law</option>
        <option value="Corporate">Corporate</option>
        <option value="Other">Other</option>
      </select>
      <input type="text" name="services[${serviceCount}][other_service]" 
             class="form-control mt-2 d-none" 
             placeholder="Enter other service">
    </div>
    <div class="col-md-3">
      <label class="form-label">Sub-Service</label>
      <input type="text" name="services[${serviceCount}][sub_service]" class="form-control">
    </div>
    <div class="col-md-2">
      <label class="form-label">Charges (Rs)</label>
      <input type="number" name="services[${serviceCount}][charges]" class="form-control charges" oninput="calculateTotal()" required>
    </div>
    <div class="col-md-1">
      <button type="button" class="btn btn-danger btn-sm mt-4" onclick="this.closest('.row').remove(); calculateTotal()">X</button>
    </div>
  `;
  wrapper.appendChild(serviceRow);
}

function calculateTotal() {
  let total = 0;
  document.querySelectorAll(".charges").forEach(input => {
    total += parseFloat(input.value) || 0;
  });
  document.getElementById("total_amount").value = total;
  calculateRemaining();
}

function calculateRemaining() {
  let total = parseFloat(document.getElementById("total_amount").value) || 0;
  let paid = parseFloat(document.getElementById("paid_amount").value) || 0;
  document.getElementById("remaining_amount").value = total - paid;
}

function toggleOtherService(select, index) {
  const input = select.parentNode.querySelector("input[name='services[" + index + "][other_service]']");
  if (select.value === "Other") {
    input.classList.remove("d-none");
    input.required = true;
  } else {
    input.classList.add("d-none");
    input.required = false;
    input.value = "";
  }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
