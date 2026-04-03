<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';
$id = intval($_GET['id'] ?? 0);
$p = $conn->query("SELECT * FROM co_payments WHERE id=$id")->fetch_assoc();
if (!$p) { echo "Invalid payment"; exit; }
?>
<!DOCTYPE html>
<html><head>
  <meta charset="UTF‑8"><title>Payment Slip</title>
  <link rel="icon" type="image/png" href="nasar.jpg">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <style>
    body {
      background: #f7f8fb;
      font-family: 'Segoe UI', sans-serif;
      padding: 30px;
    }
    .slip {
      max-width: 700px;
      margin: 0 auto 40px;
      border: 2px solid #002244;
      border-radius: 8px;
      background: #fff;
      padding: 20px;
      page-break-inside: avoid;
    }
    .header {
      text-align: center;
      border-bottom: 2px solid #002244;
      margin-bottom: 15px;
      padding-bottom: 10px;
    }
    .info-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
    }
    .label {
      font-weight: 600;
    }
    .signature {
      margin-top: 50px;
      text-align: right;
    }
    .signature hr {
      width: 150px;
      margin-left: auto;
    }
    @media print {
  .no-print { display: none !important; }
  body {
    padding: 0;
    margin: 0;
  }
  .slip {
    page-break-after: avoid;
    margin-bottom: 20px;
  }
}

  </style>
</head>
<body>

<?php foreach (['Person Copy', 'Office Copy'] as $label): ?>
  <div class="slip">
   <div class="header">
  <img src="nasar.jpg" alt="MSN Leathers Logo" style="height: 60px; display: block; margin: 0 auto 10px;">
  <h4>Nasar & Co</h4>
  <small>Payment Slip – <?= $label ?></small>
</div>

    <div class="info-row"><div class="label">Name:</div><div><?= htmlspecialchars($p['name']) ?></div></div>
    <div class="info-row"><div class="label">Phone:</div><div><?= htmlspecialchars($p['phone']) ?></div></div>
    <div class="info-row"><div class="label">Amount (Rs):</div><div>Rs <?= number_format($p['amount'],2) ?></div></div>
    <div class="info-row"><div class="label">In Words:</div><div><?= htmlspecialchars($p['amount_words']) ?></div></div>
    <div class="info-row"><div class="label">Payment Type:</div><div><?= ucfirst($p['pay_type']) ?></div></div>
    <div class="info-row"><div class="label">Reason:</div><div><?= htmlspecialchars($p['reason']) ?></div></div>
    <div class="info-row"><div class="label">Date:</div><div><?= htmlspecialchars($p['pay_date']) ?></div></div>
    <div class="signature"><hr><p>Authorized Signature</p></div>
  </div>
<?php endforeach; ?>

<div class="text-center mt-4 no-print">
  <button onclick="window.print()" class="btn btn-dark">Print Both Copies</button>
  <a href="payment-slip.php" class="btn btn-secondary">Back</a>
</div>

</body>
</html>
