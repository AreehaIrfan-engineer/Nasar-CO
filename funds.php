<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php'; 

if (isset($_POST["submit_fund"])) {
    $type = $_POST["type"];
    $reason = $_POST["expense_reason"];
    
    $nameIndex = $_POST["name_index"];

    if ($nameIndex === "other" && !empty($_POST["other_name"])) {
        $name = $_POST["other_name"];
    } else {
        $namesList = ["Sheraz Hassan", "Nasir Sura", "Tahir Javed"];
        $name = $namesList[$nameIndex] ?? '';
    }

  $amount = $_POST["amount"];
$expense_date = $_POST["expense_date"];

$stmt = $conn->prepare("INSERT INTO co_funds (type, name, reason, amount, expense_date) VALUES (?, ?, ?, ?, ?)");
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("sssss", $type, $name, $reason, $amount, $expense_date);

    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    header("Location: funds.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Fund | NASAR & Co</title>
 <link rel="icon" type="image/png" href="nasar.jpg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: white;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .popup-form {
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      width: 100%;
      max-width: 500px;
      box-shadow: 0 0 20px rgb(26, 19, 15);
    }
    .form-title {
      background-color:rgb(17, 12, 9);
      color: white;
      padding: 10px 15px;
      border-radius: 10px 10px 0 0;
      margin: -30px -30px 20px -30px;
      text-align: center;
    }
    #otherNameInput {
      display: none;
    }
    #nameGroup, #expenseGroup {
      display: none;
    }
  </style>
</head>
<body>

<form method="POST" class="popup-form">
  <div class="form-title">Add Fund</div>
  <button type="button" onclick="window.close()" class="btn-close position-absolute end-0 m-3" aria-label="Close"></button>

  <div class="mb-3">
    <label for="type">Fund Type</label>
    <select name="type" class="form-select" id="fundType" onchange="toggleFields()" required>
      <option value="capital">Capital</option>
      <option value="income">Income</option>
      <option value="expense">Expense</option>
    </select>
  </div>

  <div class="mb-3">
    <label for="name">Name</label>
   <select id="nameSelect" class="form-select" onchange="toggleOther()" name="name_index" required>
        <option >--Select--</option>
  <option value="0">Sheraz Hassan</option>
  <option value="1">Nasir Sura</option>
  <option value="2">Tahir Javed</option>
  <option value="other">Other</option>
</select>

    <input type="text" name="other_name" id="otherNameInput" class="form-control mt-2" placeholder="Enter other name">
  </div>


 <div class="mb-3">
  <label for="expense_reason">Reason</label>
  <input type="text" name="expense_reason" class="form-control" required>
</div>


  <div class="mb-3">
    <label for="amount">Amount</label>
    <input type="number" name="amount" class="form-control" required>
  </div>
<div class="mb-3">
  <label for="expense_date"> Date</label>
  <input type="date" name="expense_date" class="form-control" required>
</div>

  <div class="d-grid">
    <button type="submit" name="submit_fund" class="btn btn-dark">Submit</button>
  </div>
</form>

<script>
function toggleFields() {
  const type = document.getElementById("fundType").value;
  document.getElementById("nameGroup").style.display = (type === "capital" || type === "income") ? "block" : "none";
  document.getElementById("expenseGroup").style.display = (type === "expense") ? "block" : "none";
  toggleOther(); // update other field visibility based on current name
}

function toggleOther() {
  const nameSelect = document.getElementById("nameSelect");
  const otherInput = document.getElementById("otherNameInput");
  if (nameSelect && nameSelect.value === "other") {
    otherInput.style.display = "block";
    otherInput.required = true;
  } else {
    otherInput.style.display = "none";
    otherInput.required = false;
  }
}

// initialize on load
window.onload = toggleFields;


</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
