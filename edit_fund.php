<?php
$host = "localhost";
$username = "u617641804_tjtechsoftware";
$password = "@Tjtech2025";
$database = "u617641804_software";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid ID.");
}

// Fetch existing record
$stmt = $conn->prepare("SELECT * FROM co_funds WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$fund = $result->fetch_assoc();
$stmt->close();

if (!$fund) {
    die("Record not found.");
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type         = $_POST['type'] ?? '';
    $name         = $_POST['name'] ?? '';
    $reason       = $_POST['reason'] ?? '';
    $amount       = floatval($_POST['amount'] ?? 0);
    $expense_date = $_POST['expense_date'] ?? null;

    $stmt = $conn->prepare("UPDATE co_funds SET type=?, name=?, reason=?, amount=?, expense_date=? WHERE id=?");
    $stmt->bind_param("sssisi", $type, $name, $reason, $amount, $expense_date, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: funds_record.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Fund | Nasar & Co</title>
 <link rel="icon" type="image/png" href="nasar.jpg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

<h2>Edit Fund — Nasar & Co</h2>

<form method="POST" class="mt-4">
    <div class="mb-3">
        <label for="type" class="form-label">Type</label>
        <select name="type" id="type" class="form-control" required>
            <option value="capital" <?= $fund['type'] === 'capital' ? 'selected' : '' ?>>Capital</option>
            <option value="income" <?= $fund['type'] === 'income' ? 'selected' : '' ?>>Income</option>
            <option value="expense" <?= $fund['type'] === 'expense' ? 'selected' : '' ?>>Expense</option>
        </select>
    </div>

    <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($fund['name']) ?>" required>
    </div>

    <div class="mb-3">
        <label for="reason" class="form-label">Reason</label>
        <input type="text" name="reason" id="reason" class="form-control" value="<?= htmlspecialchars($fund['reason']) ?>">
    </div>

    <div class="mb-3">
        <label for="amount" class="form-label">Amount</label>
        <input type="number" step="0.01" name="amount" id="amount" class="form-control" value="<?= $fund['amount'] ?>" required>
    </div>

    <div class="mb-3">
        <label for="expense_date" class="form-label">Date</label>
        <input type="date" name="expense_date" id="expense_date" class="form-control" value="<?= $fund['expense_date'] ?>">
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="funds_record.php" class="btn btn-secondary">Cancel</a>
</form>

</body>
</html>
