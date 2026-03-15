<?php
session_start();
include __DIR__ . '/config/db.php';

/* FETCH BARANGAYS */
$barangays = [];

$bQuery = "SELECT DISTINCT barangay
FROM users
WHERE role_id = 2
AND barangay IS NOT NULL
AND barangay != ''
ORDER BY barangay ASC";

$bResult = mysqli_query($conn,$bQuery);

while($row = mysqli_fetch_assoc($bResult)){
$barangays[] = $row['barangay'];
}

/* RBAC */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'MSWDO') {
echo "Access Denied";
exit();
}

if(!isset($_GET['id'])){
die("Beneficiary ID missing.");
}

$id=(int)$_GET['id'];

/* BENEFICIARY */
$stmt=$conn->prepare("SELECT * FROM beneficiaries WHERE beneficiary_id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$beneficiary=$stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$beneficiary){
die("Beneficiary not found.");
}

/* CLIENT */
$client=null;
if(!empty($beneficiary['client_id'])){
$stmt=$conn->prepare("SELECT * FROM clients WHERE client_id=?");
$stmt->bind_param("i",$beneficiary['client_id']);
$stmt->execute();
$client=$stmt->get_result()->fetch_assoc();
$stmt->close();
}

/* FAMILY */
$stmt=$conn->prepare("SELECT * FROM family_composition WHERE beneficiary_id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$family=$stmt->get_result();
$stmt->close();

/* INTAKE */
$stmt=$conn->prepare("SELECT * FROM general_intake WHERE beneficiary_id=? ORDER BY intake_date DESC LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$intake=$stmt->get_result()->fetch_assoc();
$stmt->close();

/* SECTORS */
$sectorList=[];
$stmt=$conn->prepare("SELECT sector_name FROM beneficiary_sectors WHERE beneficiary_id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$res=$stmt->get_result();
while($row=$res->fetch_assoc()){
$sectorList[]=$row['sector_name'];
}
$stmt->close();

/* SUBCATEGORIES */
$subList=[];
$stmt=$conn->prepare("SELECT subcategory_name FROM beneficiary_subcategories WHERE beneficiary_id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$res=$stmt->get_result();

while($row=$res->fetch_assoc()){
$subList[]=$row['subcategory_name'];
}

$stmt->close();


/* DSWD ASSISTANCE */
$stmt=$conn->prepare("
SELECT assistance_id, assistance_type, date_received
FROM dswd_assistance
WHERE beneficiary_id=?
");
$stmt->bind_param("i",$id);
$stmt->execute();
$assistance=$stmt->get_result();
$stmt->close();

/* INDIGENOUS GROUP */
$indigenous="";
foreach($subList as $s){
if(strpos($s,"Indigenous:")===0){
$indigenous=str_replace("Indigenous: ","",$s);
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Beneficiary</title>

<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/bootstrap-icons-1.11.0/bootstrap-icons.css">
<link rel="stylesheet" href="css/style.css">

<style>
.step{display:none;}
.step.active{display:block;}
.step-buttons{margin-top:20px;}
.step-indicator{font-weight:bold;margin-bottom:10px;}

.form-check{
display:flex;
align-items:center;
gap:6px;
margin-bottom:6px;
}

input[type="checkbox"].form-check-input{
width:16px!important;
height:16px!important;
}
</style>

</head>

<body>

<?php include 'includes/sidebar.php'; ?>

<div class="content">
<?php include 'includes/topbar.php'; ?>

<div class="container-fluid mt-4">

<div class="d-flex justify-content-between align-items-center mb-4">
<h4>Edit Beneficiary Intake</h4>

<a href="beneficiary.php" class="btn btn-secondary">
<i class="bi bi-arrow-left"></i> Back
</a>

</div>

<div class="card shadow p-4">

<div class="step-indicator">
Step <span id="stepNumber">1</span> of 6
</div>

<form method="POST" action="update_beneficiary.php">

<input type="hidden" name="beneficiary_id" value="<?= $id ?>">

<!-- STEP 1 CLIENT -->
<div class="step active">

<h5>Client Information</h5>

<div class="row">

<div class="col-md-3 mb-3">
<label>Last Name</label>
<input type="text" name="c_last" class="form-control"
value="<?= $client['last_name'] ?? '' ?>">
</div>

<div class="col-md-3 mb-3">
<label>First Name</label>
<input type="text" name="c_first" class="form-control"
value="<?= $client['first_name'] ?? '' ?>">
</div>

<div class="col-md-3 mb-3">
<label>Middle Name</label>
<input type="text" name="c_middle" class="form-control"
value="<?= $client['middle_name'] ?? '' ?>">
</div>

<div class="col-md-3 mb-3">
<label>Ext</label>
<input type="text" name="c_ext" class="form-control"
value="<?= $client['ext'] ?? '' ?>">
</div>

<div class="col-md-4 mb-3">
<label>Street</label>
<input type="text" name="c_street" class="form-control"
value="<?= $client['street_address'] ?? '' ?>">
</div>

<div class="col-md-4 mb-3">
<label>Barangay</label>

<select name="c_barangay" class="form-control">

<option value="">Select Barangay</option>

<?php foreach($barangays as $b): ?>

<option value="<?= htmlspecialchars($b) ?>"
<?= (($client['barangay'] ?? '') == $b ? 'selected' : '') ?>>

<?= htmlspecialchars($b) ?>

</option>

<?php endforeach; ?>

</select>
</div>

<div class="col-md-4 mb-3">
<label>City</label>
<input type="text" name="c_city" class="form-control"
value="<?= $client['city'] ?? '' ?>">
</div>

<div class="col-md-4 mb-3">
<label>Province</label>
<input type="text" name="c_province" class="form-control"
value="<?= $client['province'] ?? '' ?>">
</div>

<div class="col-md-4 mb-3">
<label>Region</label>
<input type="text" name="c_region" class="form-control"
value="<?= $client['region'] ?? '' ?>">
</div>

<div class="col-md-4 mb-3">
<label>Mobile</label>
<input type="text" name="c_mobile" class="form-control"
value="<?= $client['mobile_number'] ?? '' ?>">
</div>

<div class="col-md-4 mb-3">
<label>Birthdate</label>
<input type="date" name="c_birthdate" id="c_birthdate"
class="form-control"
value="<?= $client['birthdate'] ?? '' ?>">
</div>

<div class="col-md-2 mb-3">
<label>Age</label>
<input type="number" name="c_age" id="c_age"
class="form-control"
value="<?= $client['age'] ?? '' ?>">
</div>

<div class="col-md-3 mb-3">
<label>Sex</label>
<select name="c_sex" class="form-control">
<option value="Male" <?= ($client['sex']??'')=='Male'?'selected':'' ?>>Male</option>
<option value="Female" <?= ($client['sex']??'')=='Female'?'selected':'' ?>>Female</option>
</select>
</div>

<div class="col-md-3 mb-3">
<label>Civil Status</label>
<input type="text" name="c_civil" class="form-control"
value="<?= $client['civil_status'] ?? '' ?>">
</div>

<div class="col-md-4 mb-3">
<label>Occupation</label>
<input type="text" name="c_occupation" class="form-control"
value="<?= $client['occupation'] ?? '' ?>">
</div>

<div class="col-md-4 mb-3">
<label>Monthly Income</label>
<input type="number" name="c_income" class="form-control"
value="<?= $client['monthly_income'] ?? '' ?>">
</div>

<div class="col-md-4 mb-3">
<label>Relationship to Beneficiary</label>
<input type="text" name="c_relationship" class="form-control"
value="<?= $client['relationship_to_beneficiary'] ?? '' ?>">
</div>

</div>
</div>

<!-- STEP 2 BENEFICIARY -->
<div class="step">

<h5>Beneficiary Information</h5>

<div class="row">

<div class="col-md-3 mb-3">
<label>Last Name</label>
<input type="text" name="b_last" class="form-control"
value="<?= $beneficiary['last_name'] ?>" required>
</div>

<div class="col-md-3 mb-3">
<label>First Name</label>
<input type="text" name="b_first" class="form-control"
value="<?= $beneficiary['first_name'] ?>" required>
</div>

<div class="col-md-3 mb-3">
<label>Middle Name</label>
<input type="text" name="b_middle" class="form-control"
value="<?= $beneficiary['middle_name'] ?>">
</div>

<div class="col-md-3 mb-3">
<label>Ext</label>
<input type="text" name="b_ext" class="form-control"
value="<?= $beneficiary['ext'] ?>">
</div>

<div class="col-md-4 mb-3">
<label>Street</label>
<input type="text" name="b_street" class="form-control"
value="<?= $beneficiary['street_address'] ?>">
</div>

<div class="col-md-4 mb-3">
<label>Barangay</label>

<select name="b_barangay" class="form-control">

<option value="">Select Barangay</option>

<?php foreach($barangays as $b): ?>

<option value="<?= htmlspecialchars($b) ?>"
<?= ($beneficiary['barangay'] == $b ? 'selected' : '') ?>>

<?= htmlspecialchars($b) ?>

</option>

<?php endforeach; ?>

</select>
</div>

<div class="col-md-4 mb-3">
<label>City</label>
<input type="text" name="b_city" class="form-control"
value="<?= $beneficiary['city'] ?>">
</div>

<div class="col-md-4 mb-3">
<label>Province</label>
<input type="text" name="b_province" class="form-control"
value="<?= $beneficiary['province'] ?>">
</div>

<div class="col-md-4 mb-3">
<label>Region</label>
<input type="text" name="b_region" class="form-control"
value="<?= $beneficiary['region'] ?>">
</div>

<div class="col-md-4 mb-3">
<label>Mobile</label>
<input type="text" name="b_mobile" class="form-control"
value="<?= $beneficiary['mobile_number'] ?>">
</div>

<div class="col-md-4 mb-3">
<label>Birthdate</label>
<input type="date" name="b_birthdate" id="b_birthdate"
class="form-control"
value="<?= $beneficiary['birthdate'] ?>">
</div>

<div class="col-md-2 mb-3">
<label>Age</label>
<input type="number" name="b_age" id="b_age"
class="form-control"
value="<?= $beneficiary['age'] ?>">
</div>

<div class="col-md-3 mb-3">
<label>Sex</label>
<select name="b_sex" class="form-control">
<option value="Male" <?= $beneficiary['sex']=='Male'?'selected':'' ?>>Male</option>
<option value="Female" <?= $beneficiary['sex']=='Female'?'selected':'' ?>>Female</option>
</select>
</div>

<div class="col-md-3 mb-3">
<label>Civil Status</label>
<input type="text" name="b_civil" class="form-control"
value="<?= $beneficiary['civil_status'] ?>">
</div>

<div class="col-md-4 mb-3">
<label>Occupation</label>
<input type="text" name="b_occupation" class="form-control"
value="<?= $beneficiary['occupation'] ?>">
</div>

<div class="col-md-4 mb-3">
<label>Monthly Income</label>
<input type="number" name="b_income" class="form-control"
value="<?= $beneficiary['monthly_income'] ?>">
</div>

</div>
</div>

<!-- STEP 3 FAMILY -->
<div class="step">

<h5>Family Composition</h5>

<table class="table table-bordered">

<thead>
<tr>
<th>Name</th>
<th>Relationship</th>
<th>Age</th>
<th>Occupation</th>
<th>Income</th>
<th></th>
</tr>
</thead>

<tbody id="family_table">

<?php while($row=$family->fetch_assoc()): ?>

<tr>

<input type="hidden" name="fam_id[]" value="<?= $row['family_id'] ?>">

<td>
<input name="fam_name[]" class="form-control"
value="<?= $row['full_name'] ?>">
</td>

<td>
<input name="fam_relation[]" class="form-control"
value="<?= $row['relationship_to_beneficiary'] ?>">
</td>

<td>
<input name="fam_age[]" class="form-control"
value="<?= $row['age'] ?>">
</td>

<td>
<input name="fam_occupation[]" class="form-control"
value="<?= $row['occupation'] ?>">
</td>

<td>
<input name="fam_income[]" class="form-control"
value="<?= $row['monthly_income'] ?>">
</td>

<td>
<button type="button" class="btn btn-danger btn-sm"
onclick="this.closest('tr').remove()">
<i class="bi bi-trash"></i>
</button>
</td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

<button type="button" class="btn btn-info" onclick="addFamily()">Add Member</button>

</div>

<!-- STEP 4 ASSISTANCE -->

<div class="step">

<h5>Previous Assistance from DSWD</h5>

<table class="table table-bordered">

<thead>
<tr>
<th>Assistance Type</th>
<th>Date Received</th>
<th></th>
</tr>
</thead>

<tbody id="assist_table">

<?php while($row=$assistance->fetch_assoc()): ?>

<tr>

<input type="hidden"
name="assist_id[]"
value="<?= $row['assistance_id'] ?>">

<td>
<input type="text"
name="assist_type[]"
class="form-control"
value="<?= $row['assistance_type'] ?>">
</td>

<td>
<input type="date"
name="assist_date[]"
class="form-control"
value="<?= $row['date_received'] ?>">
</td>

<td>
<button type="button"
class="btn btn-danger btn-sm"
onclick="this.closest('tr').remove()">
<i class="bi bi-trash"></i>
</button>
</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

<button type="button"
class="btn btn-info"
onclick="addAssist()">
Add Assistance
</button>

</div>

<!-- STEP 4 INTAKE -->
<div class="step">

<h5>General Intake</h5>

<div class="form-check">
<input class="form-check-input" type="checkbox"
name="aics" <?= !empty($intake['aics'])?'checked':'' ?>>
<label class="form-check-label">AICS</label>
</div>

<div class="form-check">
<input class="form-check-input" type="checkbox"
name="akap" <?= !empty($intake['akap'])?'checked':'' ?>>
<label class="form-check-label">AKAP</label>
</div>

<input type="text" name="others_program"
class="form-control mt-2"
value="<?= $intake['others_program'] ?? '' ?>"
placeholder="Other Program">

<select name="visit_type" class="form-control mt-2">
<option value="New" <?= ($intake['visit_type']??'')=='New'?'selected':'' ?>>New</option>
<option value="Returning" <?= ($intake['visit_type']??'')=='Returning'?'selected':'' ?>>Returning</option>
</select>

<select name="client_source" class="form-control mt-2">
<option value="Walk-in" <?= ($intake['client_source']??'')=='Walk-in'?'selected':'' ?>>Walk-in</option>
<option value="Referral" <?= ($intake['client_source']??'')=='Referral'?'selected':'' ?>>Referral</option>
</select>

<input type="date" name="intake_date"
class="form-control mt-2"
value="<?= $intake['intake_date'] ?? '' ?>">

<textarea name="purpose"
class="form-control mt-2"><?= $intake['purpose_of_assistance'] ?? '' ?></textarea>

<input type="number" name="amount"
class="form-control mt-2"
value="<?= $intake['amount_needed'] ?? '' ?>">

</div>

<!-- STEP 5 SECTOR -->
<div class="step">

<h5>Client Sector</h5>

<?php
$allSectors=["FHONA","WEDC","PWD","CNSP","SC","YNSP","PLHIV"];
foreach($allSectors as $s):
?>

<div class="form-check">
<input class="form-check-input"
type="checkbox"
name="sector[]"
value="<?= $s ?>"
<?= in_array($s,$sectorList)?'checked':'' ?>>
<label class="form-check-label"><?= $s ?></label>
</div>

<?php endforeach; ?>

<hr>

<h6>Sub Category</h6>

<div class="form-check">
<input class="form-check-input"
type="checkbox"
name="subcategory[]"
value="Solo Parent"
<?= in_array("Solo Parent",$subList)?'checked':'' ?>>
<label class="form-check-label">Solo Parent</label>
</div>

<div class="form-check">
<input class="form-check-input"
type="checkbox"
name="indigenous_check"
id="indigenousCheck"
<?= $indigenous ? 'checked':'' ?>>
<label class="form-check-label">Indigenous People</label>
</div>

<input type="text"
name="indigenous_group"
id="indigenousInput"
class="form-control mt-2"
value="<?= $indigenous ?>"
placeholder="Specify Tribe"
<?= $indigenous ? '' : 'disabled' ?>>

<div class="form-check mt-2">
<input class="form-check-input"
type="checkbox"
name="subcategory[]"
value="Street Dwellers"
<?= in_array("Street Dwellers",$subList)?'checked':'' ?>>
<label class="form-check-label">Street Dwellers</label>
</div>

<div class="form-check">
<input class="form-check-input"
type="checkbox"
name="subcategory[]"
value="4Ps Beneficiary"
<?= in_array("4Ps Beneficiary",$subList)?'checked':'' ?>>
<label class="form-check-label">4Ps Beneficiary</label>
</div>

</div>

<div class="step-buttons">

<button type="button" class="btn btn-secondary" onclick="prevStep()">Previous</button>

<button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()">Next</button>

<button type="submit" class="btn btn-success" id="submitBtn" style="display:none;">
Update Beneficiary
</button>

</div>

</form>

</div>
</div>
</div>

<script>

let currentStep=0;
const steps=document.querySelectorAll(".step");

function showStep(index){

steps.forEach((step,i)=>{
step.classList.remove("active");
if(i===index)step.classList.add("active");
});

document.getElementById("stepNumber").innerText=index+1;

let last=index===steps.length-1;

document.getElementById("submitBtn").style.display=last?"inline-block":"none";
document.getElementById("nextBtn").style.display=last?"none":"inline-block";

}

function nextStep(){
if(currentStep<steps.length-1){
currentStep++;
showStep(currentStep);
}
}

function prevStep(){
if(currentStep>0){
currentStep--;
showStep(currentStep);
}
}

function addAssist(){

let row=`<tr>

<td>
<input name="assist_type[]" class="form-control">
</td>

<td>
<input type="date" name="assist_date[]" class="form-control">
</td>

<td>
<button type="button" class="btn btn-danger btn-sm"
onclick="this.closest('tr').remove()">
<i class="bi bi-trash"></i>
</button>
</td>

</tr>`;

document.getElementById("assist_table")
.insertAdjacentHTML("beforeend",row);

}


function addFamily(){

let row=`<tr>

<td><input name="fam_name[]" class="form-control"></td>
<td><input name="fam_relation[]" class="form-control"></td>
<td><input name="fam_age[]" class="form-control"></td>
<td><input name="fam_occupation[]" class="form-control"></td>
<td><input name="fam_income[]" class="form-control"></td>

<td>
<button type="button" class="btn btn-danger btn-sm"
onclick="this.closest('tr').remove()">
<i class="bi bi-trash"></i>
</button>
</td>

</tr>`;

document.getElementById("family_table")
.insertAdjacentHTML("beforeend",row);

}

/* AGE AUTO CALC */

function calculateAge(birthInput,ageInput){

let birthdate=new Date(birthInput.value);
let today=new Date();

let age=today.getFullYear()-birthdate.getFullYear();
let m=today.getMonth()-birthdate.getMonth();

if(m<0 || (m===0 && today.getDate()<birthdate.getDate())){
age--;
}

if(age<0) age=0;

ageInput.value=age;

}

document.getElementById("c_birthdate").addEventListener("change",function(){
calculateAge(this,document.getElementById("c_age"));
});

document.getElementById("b_birthdate").addEventListener("change",function(){
calculateAge(this,document.getElementById("b_age"));
});

/* AUTO AGE WHEN PAGE LOADS */

window.addEventListener("load", function(){

let cBirth = document.getElementById("c_birthdate");
let cAge = document.getElementById("c_age");

if(cBirth.value){
calculateAge(cBirth,cAge);
}

let bBirth = document.getElementById("b_birthdate");
let bAge = document.getElementById("b_age");

if(bBirth.value){
calculateAge(bBirth,bAge);
}

});

/* INDIGENOUS */

document.getElementById("indigenousCheck").addEventListener("change",function(){
document.getElementById("indigenousInput").disabled=!this.checked;
});

</script>

</body>
</html>