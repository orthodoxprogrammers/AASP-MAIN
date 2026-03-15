<?php
session_start();
include __DIR__ . '/../config/db.php';

// Only Barangay users
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Barangay') {
    header("Location: ../login.php");
    exit;
}

$barangay_id = $_SESSION['barangay_id'] ?? 0;
if (!$barangay_id) die("Invalid barangay session.");

// Get barangay name
$stmt = $conn->prepare("SELECT barangay_name FROM barangays WHERE id=?");
$stmt->bind_param("i", $barangay_id);
$stmt->execute();
$barangay_row = $stmt->get_result()->fetch_assoc();
$barangay_name = $barangay_row['barangay_name'] ?? 'Unknown';
$stmt->close();

// Get search & sort filters
$search = trim($_GET['search'] ?? '');
$sort   = $_GET['sort'] ?? '';

// Build SQL
$sql = "SELECT * FROM households WHERE barangay_id=?";
$params = [$barangay_id];
$types  = "i";

// Search filter
if ($search !== '') {
    $sql .= " AND household_head LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

// Sorting
switch ($sort) {
    case 'az': $sql .= " ORDER BY household_head ASC"; break;
    case 'za': $sql .= " ORDER BY household_head DESC"; break;
    case 'high_priority': $sql .= " ORDER BY FIELD(priority,'High','Medium','Low') ASC"; break;
    case 'low_priority': $sql .= " ORDER BY FIELD(priority,'Low','Medium','High') ASC"; break;
    case 'highest_income': $sql .= " ORDER BY income DESC"; break;
    case 'lowest_income': $sql .= " ORDER BY income ASC"; break;
    default: $sql .= " ORDER BY household_head ASC"; break;
}

// Execute query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print <?= htmlspecialchars($barangay_name) ?> Households</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<style>
body { padding: 40px; font-family: Arial, sans-serif; }
h3 { margin-bottom: 20px; }
table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
table th, table td { border: 1px solid #000; padding: 8px; text-align: left; }
.signature { height: 35px; }
.printBtns { margin-bottom: 20px; }
@media print { .printBtns { display: none; } }
</style>
</head>
<body>

<div class="printBtns">
    <button onclick="window.print()" class="btn btn-primary btn-sm">
        <i class="bi bi-printer"></i> Print
    </button>
    <button onclick="window.location.href='barangay_households.php'" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </button>
</div>

<h3><?= htmlspecialchars($barangay_name) ?> Household Beneficiaries (<?= date('F d, Y') ?>)</h3>

<table>
<thead>
<tr>
    <th style="width:40%">Full Name</th>
    <th style="width:20%">Income</th>
    <th style="width:20%">Priority</th>
    <th style="width:20%">Signature</th>
</tr>
</thead>
<tbody>
<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['household_head']) ?></td>
        <td>₱<?= number_format($row['income'], 2) ?></td>
        <td><?= $row['priority'] ?></td>
        <td class="signature"></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="4" class="text-center">No households found.</td>
</tr>
<?php endif; ?>
</tbody>
</table>

</body>
</html>