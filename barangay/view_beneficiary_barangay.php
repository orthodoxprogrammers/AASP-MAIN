<?php
session_start();
include __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Barangay') {
    echo "<tr><td colspan='8' class='text-center'>Access Denied</td></tr>";
    exit();
}

if(!isset($_GET['id'])){
    die("Beneficiary ID not found.");
}

$id = intval($_GET['id']);

/* BENEFICIARY */
$stmt = $conn->prepare("SELECT * FROM beneficiaries WHERE beneficiary_id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$beneficiary = $stmt->get_result()->fetch_assoc();

if(!$beneficiary){
    die("Beneficiary not found.");
}

/* CLIENT */
$client = null;
if(!empty($beneficiary['client_id'])){
$clientQuery = $conn->prepare("SELECT * FROM clients WHERE client_id=?");
$clientQuery->bind_param("i",$beneficiary['client_id']);
$clientQuery->execute();
$client = $clientQuery->get_result()->fetch_assoc();
}

/* FAMILY */
$familyQuery = $conn->prepare("
SELECT * FROM family_composition WHERE beneficiary_id=?
");
$familyQuery->bind_param("i",$id);
$familyQuery->execute();
$family = $familyQuery->get_result();

/* INTAKE */
$intakeQuery = $conn->prepare("
SELECT * FROM general_intake WHERE beneficiary_id=? ORDER BY intake_date DESC
");
$intakeQuery->bind_param("i",$id);
$intakeQuery->execute();
$intake = $intakeQuery->get_result();

/* SECTORS */
$sectorQuery = $conn->prepare("
SELECT sector_name FROM beneficiary_sectors WHERE beneficiary_id=?
");
$sectorQuery->bind_param("i",$id);
$sectorQuery->execute();
$sectors = $sectorQuery->get_result();

/* SUBCATEGORY */
$subQuery = $conn->prepare("
SELECT subcategory_name FROM beneficiary_subcategories WHERE beneficiary_id=?
");
$subQuery->bind_param("i",$id);
$subQuery->execute();
$subcategories = $subQuery->get_result();

/* DSWD ASSISTANCE */
$assistQuery = $conn->prepare("
SELECT assistance_type, date_received
FROM dswd_assistance
WHERE beneficiary_id=?
");
$assistQuery->bind_param("i",$id);
$assistQuery->execute();
$assistance = $assistQuery->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Beneficiary Profile</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/bootstrap-icons-1.11.0/bootstrap-icons.css">
<link rel="stylesheet" href="../css/style.css">

<style>

.form-control[readonly]{
background:#f8f9fa;
}

</style>

</head>

<body>

<?php include 'layout/sidebar.php'; ?>

<div class="content">

<?php include 'layout/topbar.php'; ?>

<div class="container-fluid mt-4">

<h4 class="mb-4">Beneficiary Intake Profile</h4>

<div class="card shadow p-4">

<!-- CLIENT INFO -->

<h5>Client Information</h5>

<div class="row">

<div class="col-md-3 mb-3">
<label>Last Name</label>
<input type="text" class="form-control"
value="<?= $client['last_name'] ?? '' ?>" readonly>
</div>

<div class="col-md-3 mb-3">
<label>First Name</label>
<input type="text" class="form-control"
value="<?= $client['first_name'] ?? '' ?>" readonly>
</div>

<div class="col-md-3 mb-3">
<label>Middle Name</label>
<input type="text" class="form-control"
value="<?= $client['middle_name'] ?? '' ?>" readonly>
</div>

<div class="col-md-3 mb-3">
<label>Ext</label>
<input type="text" class="form-control"
value="<?= $client['ext'] ?? '' ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Street</label>
<input type="text" class="form-control"
value="<?= $client['street_address'] ?? '' ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Barangay</label>
<input type="text" class="form-control"
value="<?= $client['barangay'] ?? '' ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>City</label>
<input type="text" class="form-control"
value="<?= $client['city'] ?? '' ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Province</label>
<input type="text" class="form-control"
value="<?= $client['province'] ?? '' ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Region</label>
<input type="text" class="form-control"
value="<?= $client['region'] ?? '' ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Mobile</label>
<input type="text" class="form-control"
value="<?= $client['mobile_number'] ?? '' ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Birthdate</label>
<input type="text" class="form-control"
value="<?= $client['birthdate'] ?? '' ?>" readonly>
</div>

<div class="col-md-2 mb-3">
<label>Age</label>
<input type="text" class="form-control"
value="<?= $client['age'] ?? '' ?>" readonly>
</div>

<div class="col-md-3 mb-3">
<label>Sex</label>
<input type="text" class="form-control"
value="<?= $client['sex'] ?? '' ?>" readonly>
</div>

<div class="col-md-3 mb-3">
<label>Civil Status</label>
<input type="text" class="form-control"
value="<?= $client['civil_status'] ?? '' ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Occupation</label>
<input type="text" class="form-control"
value="<?= $client['occupation'] ?? '' ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Monthly Income</label>
<input type="text" class="form-control"
value="<?= $client['monthly_income'] ?? '' ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Relationship to Beneficiary</label>
<input type="text" class="form-control"
value="<?= $client['relationship_to_beneficiary'] ?? '' ?>" readonly>
</div>

</div>

<hr>

<!-- BENEFICIARY INFO -->

<h5>Beneficiary Information</h5>

<div class="row">

<div class="col-md-3 mb-3">
<label>Last Name</label>
<input type="text" class="form-control"
value="<?= $beneficiary['last_name'] ?>" readonly>
</div>

<div class="col-md-3 mb-3">
<label>First Name</label>
<input type="text" class="form-control"
value="<?= $beneficiary['first_name'] ?>" readonly>
</div>

<div class="col-md-3 mb-3">
<label>Middle Name</label>
<input type="text" class="form-control"
value="<?= $beneficiary['middle_name'] ?>" readonly>
</div>

<div class="col-md-3 mb-3">
<label>Ext</label>
<input type="text" class="form-control"
value="<?= $beneficiary['ext'] ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Street</label>
<input type="text" class="form-control"
value="<?= $beneficiary['street_address'] ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Barangay</label>
<input type="text" class="form-control"
value="<?= $beneficiary['barangay'] ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>City</label>
<input type="text" class="form-control"
value="<?= $beneficiary['city'] ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Province</label>
<input type="text" class="form-control"
value="<?= $beneficiary['province'] ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Region</label>
<input type="text" class="form-control"
value="<?= $beneficiary['region'] ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Mobile</label>
<input type="text" class="form-control"
value="<?= $beneficiary['mobile_number'] ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Birthdate</label>
<input type="text" class="form-control"
value="<?= $beneficiary['birthdate'] ?>" readonly>
</div>

<div class="col-md-2 mb-3">
<label>Age</label>
<input type="text" class="form-control"
value="<?= $beneficiary['age'] ?>" readonly>
</div>

<div class="col-md-3 mb-3">
<label>Sex</label>
<input type="text" class="form-control"
value="<?= $beneficiary['sex'] ?>" readonly>
</div>

<div class="col-md-3 mb-3">
<label>Civil Status</label>
<input type="text" class="form-control"
value="<?= $beneficiary['civil_status'] ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Occupation</label>
<input type="text" class="form-control"
value="<?= $beneficiary['occupation'] ?>" readonly>
</div>

<div class="col-md-4 mb-3">
<label>Monthly Income</label>
<input type="text" class="form-control"
value="<?= $beneficiary['monthly_income'] ?>" readonly>
</div>

<div class="col-md-3 mb-3">
<label>Priority Score</label>
<input type="text" class="form-control"
value="<?= $beneficiary['priority_score'] ?? '' ?>" readonly>
</div>

</div>

<hr>

<h5>Previous Assistance from DSWD</h5>

<?php if($assistance->num_rows > 0): ?>

<table class="table table-bordered">

<thead>
<tr>
<th>Assistance Type</th>
<th>Date Received</th>
</tr>
</thead>

<tbody>

<?php while($row = $assistance->fetch_assoc()): ?>

<tr>
<td><?= htmlspecialchars($row['assistance_type']) ?></td>
<td><?= htmlspecialchars($row['date_received']) ?></td>
</tr>

<?php endwhile; ?>

</tbody>

</table>

<?php else: ?>

<p class="text-muted">No previous assistance recorded.</p>

<?php endif; ?>

<!-- FAMILY -->

<h5>Family Composition</h5>

<table class="table table-bordered">

<thead>
<tr>
<th>Name</th>
<th>Relationship</th>
<th>Age</th>
<th>Occupation</th>
<th>Income</th>
</tr>
</thead>

<tbody>

<?php while($row=$family->fetch_assoc()): ?>

<tr>
<td><?= $row['full_name'] ?></td>
<td><?= $row['relationship_to_beneficiary'] ?></td>
<td><?= $row['age'] ?></td>
<td><?= $row['occupation'] ?></td>
<td><?= $row['monthly_income'] ?></td>
</tr>

<?php endwhile; ?>

</tbody>

</table>

<hr>

<!-- INTAKE -->

<h5>General Intake</h5>

<?php if($intake->num_rows>0):
$row=$intake->fetch_assoc();
?>

<div class="form-check">
<input class="form-check-input" type="checkbox" <?= $row['aics']?'checked':'' ?> disabled>
<label class="form-check-label">AICS</label>
</div>

<div class="form-check">
<input class="form-check-input" type="checkbox" <?= $row['akap']?'checked':'' ?> disabled>
<label class="form-check-label">AKAP</label>
</div>

<input class="form-control mt-2" value="<?= $row['others_program'] ?>" readonly>
<input class="form-control mt-2" value="<?= $row['visit_type'] ?>" readonly>
<input class="form-control mt-2" value="<?= $row['client_source'] ?>" readonly>
<input class="form-control mt-2" value="<?= $row['intake_date'] ?>" readonly>
<textarea class="form-control mt-2" readonly><?= $row['purpose_of_assistance'] ?></textarea>
<input class="form-control mt-2" value="<?= $row['amount_needed'] ?>" readonly>

<?php endif; ?>

<hr>

<!-- SECTORS -->

<h5>Client Sector</h5>

<h6>Target Sector</h6>

<?php while($row=$sectors->fetch_assoc()): ?>
<div class="form-check">
<input class="form-check-input" type="checkbox" checked disabled>
<label class="form-check-label"><?= $row['sector_name'] ?></label>
</div>
<?php endwhile; ?>

<hr>

<h6>Sub Category / Special Tags</h6>

<?php while($row=$subcategories->fetch_assoc()): ?>

<div class="form-check">
<input class="form-check-input" type="checkbox" checked disabled>
<label class="form-check-label">
<?= htmlspecialchars($row['subcategory_name']) ?>
</label>
</div>

<?php endwhile; ?>

<div class="mt-4">
<a href="barangay_households.php" class="btn btn-secondary">
<i class="bi bi-arrow-left"></i> Back
</a>
</div>

</div>
</div>
</div>

</div>

</body>
</html>