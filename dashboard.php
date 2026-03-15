<?php
session_start();
include 'config/db.php';

// RBAC: Only MSWDO can access
/*if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'MSWDO') {
    header("Location: login.php");
    exit();
}
*/

// Total beneficiaries
$totalResult = mysqli_query($conn,"SELECT COUNT(*) AS total FROM beneficiaries");
$total = mysqli_fetch_assoc($totalResult)['total'];

// Male / Female counts
$genderQuery = "
    SELECT 
        SUM(sex='Male') AS male,
        SUM(sex='Female') AS female
    FROM beneficiaries
";
$genderResult = mysqli_query($conn,$genderQuery);
$gender = mysqli_fetch_assoc($genderResult);

// Barangays covered
$barangayResult = mysqli_query($conn,"
    SELECT COUNT(DISTINCT barangay) AS total 
    FROM beneficiaries
");
$barangays = mysqli_fetch_assoc($barangayResult)['total'];

include 'includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard</title>

<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/bootstrap-icons-1.11.0/bootstrap-icons.css">
<link rel="stylesheet" href="css/style.css">

</head>
<body>

<div class="content">

<?php include 'includes/topbar.php'; ?>

<div class="container-fluid mt-4">

<h4 class="mb-4">Dashboard</h4>

<div class="row g-3">

<div class="col-md-3">
<div class="card shadow p-4 text-center">
<h6>Total Beneficiaries</h6>
<h2><?= $total ?></h2>
<p class="text-muted">Registered individuals</p>
</div>
</div>

<div class="col-md-3">
<div class="card shadow p-4 text-center">
<h6>Male Beneficiaries</h6>
<h2><?= $gender['male'] ?? 0 ?></h2>
<p class="text-muted">Male individuals</p>
</div>
</div>

<div class="col-md-3">
<div class="card shadow p-4 text-center">
<h6>Female Beneficiaries</h6>
<h2><?= $gender['female'] ?? 0 ?></h2>
<p class="text-muted">Female individuals</p>
</div>
</div>

<div class="col-md-3">
<div class="card shadow p-4 text-center">
<h6>Barangays Covered</h6>
<h2><?= $barangays ?></h2>
<p class="text-muted">Municipality barangays</p>
</div>
</div>

</div>


<div class="row mt-4 g-3">

<div class="col-md-6">
<div class="card shadow p-5">
<h6>System Information</h6>
<p>
This system helps the Municipal Social Welfare and Development Office (MSWDO)
prioritize beneficiaries eligible for financial assistance or relief support
based on socio-economic indicators and vulnerability factors.
</p>
</div>
</div>


<div class="col-md-6">
<div class="card shadow p-5">
<h6>Quick Actions</h6>

<a href="add_beneficiary.php" class="btn btn-primary mt-3 w-100">
<i class="bi bi-person-plus"></i> Add New Beneficiary
</a>

<a href="beneficiary.php" class="btn btn-secondary mt-3 w-100">
<i class="bi bi-table"></i> View Beneficiary List
</a>

</div>
</div>

</div>

</div>
</div>

</body>
</html>