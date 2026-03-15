<?php
session_start();
include __DIR__.'/config/db.php';

/* RBAC */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'MSWDO') {
    die("Access Denied");
}

$query="

SELECT
beneficiary_id,
CONCAT(last_name,', ',first_name,' ',IFNULL(middle_name,'')) AS name,
barangay,
archived_reason,
archived_at
FROM beneficiaries
WHERE status='archived'
ORDER BY archived_at DESC

";

$result=$conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<title>Archived Beneficiaries</title>

<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/bootstrap-icons-1.11.0/bootstrap-icons.css">
<link rel="stylesheet" href="css/style.css">

</head>

<body>

<?php include 'includes/sidebar.php'; ?>

<div class="content">

<?php include 'includes/topbar.php'; ?>

<div class="container-fluid mt-4">

<div class="d-flex justify-content-between align-items-center mb-4">

<h4><i class="bi bi-archive"></i> Archived Beneficiaries</h4>

<a href="beneficiary.php" class="btn btn-secondary">
<i class="bi bi-arrow-left"></i> Back
</a>

</div>

<div class="card shadow">

<div class="card-body">

<table class="table table-hover">

<thead class="table-light">

<tr>
<th>Name</th>
<th>Barangay</th>
<th>Reason</th>
<th>Date Archived</th>
<th>Action</th>
</tr>

</thead>

<tbody>

<?php if($result->num_rows>0): ?>

<?php while($row=$result->fetch_assoc()): ?>

<tr>

<td><?= htmlspecialchars($row['name']) ?></td>

<td><?= htmlspecialchars($row['barangay']) ?></td>

<td><?= htmlspecialchars($row['archived_reason']) ?></td>

<td><?= date("F d, Y",strtotime($row['archived_at'])) ?></td>

<td>

<a href="view_beneficiary_info.php?id=<?= $row['beneficiary_id'] ?>"
class="btn btn-info btn-sm">

<i class="bi bi-eye"></i>

</a>

<a href="restore_beneficiary.php?id=<?= $row['beneficiary_id'] ?>"
class="btn btn-success btn-sm"
onclick="return confirm('Restore this beneficiary?');">

<i class="bi bi-arrow-counterclockwise"></i>

</a>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="5" class="text-center">No archived beneficiaries</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</body>
</html>