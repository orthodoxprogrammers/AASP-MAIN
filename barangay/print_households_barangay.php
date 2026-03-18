<?php
session_start();
include __DIR__ . '/../config/db.php';

// LOGIN CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Barangay') {
    die("Access Denied.");
}

// BARANGAY CHECK
$barangay = $_SESSION['barangay'] ?? null;
if (!$barangay) {
    die("Barangay not set for this user.");
}

// FETCH HOUSEHOLDS WITH BENEFICIARIES
$stmt = $conn->prepare("
    SELECT 
        c.client_id,
        c.last_name AS client_last,
        c.first_name AS client_first,
        c.middle_name AS client_middle,
        c.ext AS client_ext,
        c.street_address AS client_street,
        c.barangay AS client_barangay,
        c.city AS client_city,
        c.province AS client_province,
        c.mobile_number AS client_mobile,
        c.age AS client_age,
        c.sex AS client_sex,
        c.civil_status AS client_civil_status,
        b.beneficiary_id,
        b.last_name AS ben_last,
        b.first_name AS ben_first,
        b.middle_name AS ben_middle,
        b.ext AS ben_ext,
        b.street_address AS ben_street,
        b.barangay AS ben_barangay,
        b.city AS ben_city,
        b.province AS ben_province,
        b.mobile_number AS ben_mobile,
        b.age AS ben_age,
        b.sex AS ben_sex,
        b.civil_status AS ben_civil_status,
        b.priority_score
    FROM beneficiaries b
    LEFT JOIN clients c ON b.client_id = c.client_id
    WHERE TRIM(LOWER(b.barangay)) = TRIM(LOWER(?))
    ORDER BY COALESCE(b.priority_score,0) DESC
");

$stmt->bind_param("s", $barangay);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Barangay Households</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/bootstrap-icons-1.11.0/bootstrap-icons.css">
<link rel="stylesheet" href="../css/style.css">
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { text-align: center; margin-bottom: 15px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid black; padding: 5px; font-size: 12px; text-align: center; }
th { background-color: #f2f2f2; }
.left { text-align: left; }
.controls { margin-bottom: 15px; }
@media print {
    .controls { display: none; }
    @page { size: landscape; margin: 10px; }
}
</style>
<script>
function printPage(){ window.print(); }
function closePage(){ window.close(); }
</script>
</head>
<body>

<div class="controls d-flex justify-content-end gap-2">
    <button class="btn btn-primary" onclick="printPage()"><i class="bi bi-printer"></i> Print</button>
    <button class="btn btn-secondary" onclick="closePage()"><i class="bi bi-x-circle"></i> Close</button>
</div>

<h2>HOUSEHOLDS IN <?= htmlspecialchars($barangay) ?></h2>

<table>
<thead>
<tr>
<th>#</th>
<th>Client Name</th>
<th>Beneficiary Name</th>
<th>Priority Score</th>
<th>Mobile Number</th>
<th>Address</th>
<th>Age</th>
<th>Sex</th>
<th>Civil Status</th>
</tr>
</thead>
<tbody>
<?php
$count = 1;
if($result->num_rows > 0):
    while($row = $result->fetch_assoc()):
        $client_name = $row['client_last'] . ', ' . $row['client_first'] . ' ' . $row['client_middle'] . ' ' . ($row['client_ext'] ?? '');
        $ben_name = $row['ben_last'] ? $row['ben_last'] . ', ' . $row['ben_first'] . ' ' . $row['ben_middle'] . ' ' . ($row['ben_ext'] ?? '') : 'N/A';
        $mobile = $row['ben_mobile'] ?? $row['client_mobile'];
        $address = ($row['ben_street'] ?? $row['client_street']) . ', ' . ($row['ben_barangay'] ?? $row['client_barangay']) . ', ' . ($row['ben_city'] ?? $row['client_city']);
        $age = $row['ben_age'] ?? $row['client_age'];
        $sex = $row['ben_sex'] ?? $row['client_sex'];
        $civil = $row['ben_civil_status'] ?? $row['client_civil_status'];
        $priority = $row['priority_score'] ?? 'N/A';
?>
<tr>
<td><?= $count++ ?></td>
<td class="left"><?= htmlspecialchars($client_name) ?></td>
<td class="left"><?= htmlspecialchars($ben_name) ?></td>
<td><?= htmlspecialchars($priority) ?></td>
<td><?= htmlspecialchars($mobile) ?></td>
<td class="left"><?= htmlspecialchars($address) ?></td>
<td><?= htmlspecialchars($age) ?></td>
<td><?= htmlspecialchars($sex) ?></td>
<td><?= htmlspecialchars($civil) ?></td>
</tr>
<?php
    endwhile;
else:
?>
<tr>
<td colspan="9">No households found in this barangay.</td>
</tr>
<?php endif; ?>
</tbody>
</table>

</body>
</html>