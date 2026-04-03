<?php
include 'db.php';

// Clean any previous output
if (ob_get_level()) {
    ob_end_clean();
}

$type = $_GET['type'] ?? '';
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

if (!in_array($type, ['capital', 'income', 'expense'])) exit;

// Force download headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'. $type .'_report_'. $from .'_to_'. $to .'.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Date', 'Type', 'Name', 'Amount']);

$stmt = $conn->prepare("SELECT id, DATE(created_at) as date, type, name, amount 
                        FROM co_funds 
                        WHERE type=? AND DATE(created_at) BETWEEN ? AND ?");
$stmt->bind_param('sss', $type, $from, $to);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['date'],
        ucfirst($row['type']),
        $row['name'],
        $row['amount']
    ]);
}

fclose($output);
exit;
