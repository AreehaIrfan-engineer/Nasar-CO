<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$host = "localhost";
$username = "u617641804_tjtechsoftware";
$password = "@Tjtech2025";
$database = "u617641804_software";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM co_funds WHERE id=$id");
    header("Location: funds_record.php");
    exit;
}

// Filters
$type_filter = $_GET['type_filter'] ?? '';
$from_date   = $_GET['from_date'] ?? '';
$to_date     = $_GET['to_date'] ?? '';

$where = [];
if ($type_filter !== '') {
    $where[] = "type = '" . $conn->real_escape_string($type_filter) . "'";
}
if ($from_date !== '') {
    $where[] = "DATE(created_at) >= '" . $conn->real_escape_string($from_date) . "'";
}
if ($to_date !== '') {
    $where[] = "DATE(created_at) <= '" . $conn->real_escape_string($to_date) . "'";
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// ================== SUMMARY CALCULATIONS ==================

// Opening balance = all before from_date
$opening_balance = 0;
if ($from_date !== '') {
    $opening_sql = "
        SELECT
            SUM(CASE WHEN type IN ('income','capital') THEN amount ELSE 0 END) AS credit,
            SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS debit
        FROM co_funds
        WHERE DATE(created_at) < '" . $conn->real_escape_string($from_date) . "'
    ";
    $op = $conn->query($opening_sql)->fetch_assoc();
    $opening_balance = floatval($op['credit']) - floatval($op['debit']);
}

// Totals within filter
$totals_sql = "
    SELECT
        SUM(CASE WHEN type IN ('income','capital') THEN amount ELSE 0 END) AS total_credit,
        SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS total_debit
    FROM co_funds
    $where_sql
";
$totals = $conn->query($totals_sql)->fetch_assoc();
$total_credit = floatval($totals['total_credit']);
$total_debit  = floatval($totals['total_debit']);
$closing_balance = $opening_balance + $total_credit - $total_debit;

// ================== CSV EXPORT ==================
if (isset($_GET['export_csv']) && $_GET['export_csv'] == '1') {
    $sql = "
      SELECT id, type, name, reason, amount, expense_date, created_at
      FROM co_funds
      $where_sql
      ORDER BY id DESC
    ";
    $res = $conn->query($sql);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="co_fund_records.csv"');

    echo "ID,Date,Type,Name,Reason,Amount\n";
    while ($row = $res->fetch_assoc()) {
        $line = [
            $row['id'],
            $row['expense_date'] ?: date('Y-m-d', strtotime($row['created_at'])),
            $row['type'],
            $row['name'],
            $row['reason'],
            $row['amount']
        ];
        $escaped = array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $line);
        echo implode(",", $escaped) . "\n";
    }

    // Add summary to CSV
    echo "\nSummary\n";
    echo "Opening Balance,," . $opening_balance . "\n";
    echo "Total Credit,," . $total_credit . "\n";
    echo "Total Debit,," . $total_debit . "\n";
    echo "Closing Balance,," . $closing_balance . "\n";
    exit;
}

// Fetch filtered records
$sql = "
    SELECT id, type, name, reason, amount, expense_date, created_at
    FROM co_funds
    $where_sql
    ORDER BY id DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fund Records | Nasar & Co</title>
<link rel="icon" type="image/png" href="nasar.jpg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8f9fa; }
.table-section { background: white; padding: 20px; margin-top: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
.summary-box { background:#fff3cd; padding:15px; margin-bottom:15px; border-left:5px solid #ffc107; }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg shadow-sm mb-4" style="background-color:#8e949c;">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center text-white fw-bold" href="#">
      <img src="nasar.jpg" alt="Logo" width="40" height="40" class="me-2 rounded-circle border border-light">
      Nasar & Co
    </a>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link text-white fw-semibold" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white fw-semibold" href="funds.php">Add Funds</a></li>
        <li class="nav-item"><a class="nav-link text-white fw-semibold" href="funds_record.php">Funds Record</a></li>
        <li class="nav-item"><a class="nav-link text-white fw-semibold" href="client.php">Clients</a></li>
        <li class="nav-item"><a class="nav-link text-white fw-semibold" href="all_client.php">All Clients</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white fw-semibold" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Slips</a>
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
    <h2 class="mt-4 mb-4 text-center">Fund Records — Nasar & Co</h2>

    <!-- Filter Form -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
            <label>From Date</label>
            <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label>To Date</label>
            <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label>Type</label>
            <select name="type_filter" class="form-select">
                <option value="">All</option>
                <option value="capital" <?= $type_filter == 'capital' ? 'selected' : '' ?>>Capital</option>
                <option value="income" <?= $type_filter == 'income' ? 'selected' : '' ?>>Income</option>
                <option value="expense" <?= $type_filter == 'expense' ? 'selected' : '' ?>>Expense</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-primary me-2">Apply Filters</button>
            <a href="?<?= http_build_query(array_merge($_GET, ['export_csv' => '1'])) ?>" class="btn btn-success">Export CSV</a>
        </div>
    </form>

    <!-- Summary -->
    <div class="summary-box">
        <strong>Opening Balance:</strong> <?= number_format($opening_balance, 2) ?> |
        <strong>Total Credit:</strong> <?= number_format($total_credit, 2) ?> |
        <strong>Total Debit:</strong> <?= number_format($total_debit, 2) ?> |
        <strong>Closing Balance:</strong> <?= number_format($closing_balance, 2) ?>
    </div>

    <!-- Table -->
    <div class="table-section">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Reason</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['expense_date'] ?: date('Y-m-d', strtotime($row['created_at']))) ?></td>
                    <td><?= ucfirst($row['type']) ?></td>
                    <td><?= htmlspecialchars($row['name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['reason'] ?? '-') ?></td>
                    <td><?= number_format($row['amount'], 2) ?></td>
                    <td>
                        <a href="edit_fund.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this record?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">No records found</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
