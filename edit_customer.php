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

// ✅ Fetch existing services
$stmt = $conn->prepare("SELECT * FROM co_client_services WHERE client_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ✅ Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name    = $_POST['customer_name'];
    $father_name      = $_POST['father_name'];
    $cnic             = $_POST['cnic'];
    $phone            = $_POST['phone'];
    $address          = $_POST['address'];
    $email            = $_POST['email'];
    $paid_amount      = $_POST['paid_amount'] ?: 0;
    $total_amount     = $_POST['total_amount'] ?: 0;
    $remaining_amount = $_POST['remaining_amount'] ?: 0;
    $document_details = $_POST['document_details'];
    $document_file    = $customer['document_file'];

    // ✅ File upload
    if (!empty($_FILES['document_file']['name'])) {
        $upload_dir = __DIR__ . "/uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_tmp = $_FILES['document_file']['tmp_name'];
        $file_name = uniqid("doc_", true) . ".pdf";
        if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
            $document_file = "uploads/" . $file_name;
        }
    }

    // ✅ Update nasar_clients
    $stmt = $conn->prepare("UPDATE co_clients SET 
        customer_name=?, father_name=?, cnic=?, phone=?, address=?, email=?, 
        total_amount=?, paid_amount=?, remaining_amount=?, 
        document_details=?, document_file=? 
        WHERE id=?");
    $stmt->bind_param("ssssssdddssi",
        $customer_name, $father_name, $cnic, $phone, $address, $email,
        $total_amount, $paid_amount, $remaining_amount,
        $document_details, $document_file, $id
    );
    $stmt->execute();
    $stmt->close();

    // ✅ Delete old services & re-insert new ones
    $conn->query("DELETE FROM co_client_services WHERE client_id=$id");

    if (!empty($_POST['services'])) {
        foreach ($_POST['services'] as $srv) {
            $service = $srv['service'];
            if ($service === "Other" && !empty($srv['other_service'])) {
                $service = $srv['other_service'];
            }
            if (!empty($srv['provider']) || !empty($service)) {
                $stmt = $conn->prepare("INSERT INTO co_client_services (client_id, provider, service, sub_service, charges) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isssd", $id, $srv['provider'], $service, $srv['sub_service'], $srv['charges']);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    header("Location: view_customer.php?id=" . $id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Customer — Nasar Law Firm</title>
  <link rel="icon" href="nasar.jpg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">

<h3>Edit Customer</h3>
<form method="POST" enctype="multipart/form-data" class="row g-3">

  <!-- Customer Info -->
  <div class="col-md-6">
    <label class="form-label">Name</label>
    <input name="customer_name" class="form-control" value="<?= htmlspecialchars($customer['customer_name']) ?>" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Father Name</label>
    <input name="father_name" class="form-control" value="<?= htmlspecialchars($customer['father_name']) ?>" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">CNIC</label>
    <input name="cnic" class="form-control" value="<?= htmlspecialchars($customer['cnic']) ?>" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Phone</label>
    <input name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone']) ?>" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Address</label>
    <input name="address" class="form-control" value="<?= htmlspecialchars($customer['address']) ?>" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($customer['email']) ?>">
  </div>

  <!-- Services -->
  <h5 class="mt-4">Services Availed</h5>
  <div id="servicesWrapper">
    <?php foreach ($services as $i => $srv): ?>
      <div class="row g-3 align-items-end border p-3 mb-2 rounded">
        <div class="col-md-3">
          <label class="form-label">Provider</label>
          <input type="text" name="services[<?= $i ?>][provider]" class="form-control" value="<?= htmlspecialchars($srv['provider']) ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Service</label>
          <input type="text" name="services[<?= $i ?>][service]" class="form-control" value="<?= htmlspecialchars($srv['service']) ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Sub-Service</label>
          <input type="text" name="services[<?= $i ?>][sub_service]" class="form-control" value="<?= htmlspecialchars($srv['sub_service']) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Charges (Rs)</label>
          <input type="number" name="services[<?= $i ?>][charges]" class="form-control charges" value="<?= htmlspecialchars($srv['charges']) ?>" oninput="calculateTotal()" required>
        </div>
        <div class="col-md-1">
          <button type="button" class="btn btn-danger btn-sm mt-4" onclick="this.closest('.row').remove(); calculateTotal()">X</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-success btn-sm mt-2" onclick="addService()">+ Add Service</button>

  <!-- Payment Summary -->
  <div class="row mt-4">
    <div class="col-md-4">
      <label class="form-label">Total Amount (Rs)</label>
      <input type="number" name="total_amount" id="total_amount" class="form-control" value="<?= htmlspecialchars($customer['total_amount']) ?>" readonly>
    </div>
    <div class="col-md-4">
      <label class="form-label">Paid Amount (Rs)</label>
      <input type="number" name="paid_amount" id="paid_amount" class="form-control" value="<?= htmlspecialchars($customer['paid_amount']) ?>" oninput="calculateRemaining()">
    </div>
    <div class="col-md-4">
      <label class="form-label">Remaining Amount (Rs)</label>
      <input type="number" name="remaining_amount" id="remaining_amount" class="form-control" value="<?= htmlspecialchars($customer['remaining_amount']) ?>" readonly>
    </div>
  </div>

  <!-- Document -->
  <div class="col-12 mt-3">
    <label class="form-label">Document Details</label>
    <textarea name="document_details" class="form-control"><?= htmlspecialchars($customer['document_details']) ?></textarea>
  </div>
  <div class="col-12">
    <label class="form-label">Upload Document (PDF)</label>
    <input type="file" name="document_file" accept="application/pdf" class="form-control">
    <?php if ($customer['document_file']): ?>
      <p class="mt-2">Current: <a href="<?= htmlspecialchars($customer['document_file']) ?>" target="_blank">View PDF</a></p>
    <?php endif; ?>
  </div>

  <!-- Buttons -->
  <div class="col-12">
    <button class="btn btn-success">Update</button>
    <a href="view_customer.php?id=<?= $customer['id'] ?>" class="btn btn-secondary">Cancel</a>
  </div>
</form>

<script>
let serviceCount = <?= count($services) ?>;

function addService() {
  serviceCount++;
  const wrapper = document.getElementById("servicesWrapper");
  const serviceRow = document.createElement("div");
  serviceRow.classList.add("row","g-3","align-items-end","border","p-3","mb-2","rounded");
  serviceRow.innerHTML = `
    <div class="col-md-3">
      <label class="form-label">Provider</label>
      <input type="text" name="services[${serviceCount}][provider]" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Service</label>
      <input type="text" name="services[${serviceCount}][service]" class="form-control" required>
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
</script>
</body>
</html>
