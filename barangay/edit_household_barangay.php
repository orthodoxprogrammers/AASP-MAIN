<?php
session_start();
include __DIR__ . '/../config/db.php';

// Check if user is logged in and has Barangay role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Barangay') {
    echo "<tr><td colspan='8' class='text-center'>Access Denied</td></tr>";
    exit;
}

// Get beneficiary_id from request
$beneficiary_id = isset($_GET['beneficiary_id']) ? intval($_GET['beneficiary_id']) : 0;

// Optional: get Barangay of logged-in user to restrict access
$barangay_user = $_SESSION['barangay'] ?? '';

// Fetch beneficiary info
$sql = "SELECT * FROM beneficiaries WHERE beneficiary_id = ?";
$params = [$beneficiary_id];

if ($barangay_user) {
    $sql .= " AND barangay = ?";
    $params[] = $barangay_user;
}

$stmt = $conn->prepare($sql);
if ($barangay_user) {
    $stmt->bind_param("is", $beneficiary_id, $barangay_user);
} else {
    $stmt->bind_param("i", $beneficiary_id);
}
$stmt->execute();
$result = $stmt->get_result();
$household = $result->fetch_assoc();

if (!$household) {
    echo "<tr><td colspan='8' class='text-center'>No household details found</td></tr>";
    exit;
}

// Fetch family members
$sql_members = "SELECT * FROM family_composition WHERE beneficiary_id = ?";
$stmt_members = $conn->prepare($sql_members);
$stmt_members->bind_param("i", $beneficiary_id);
$stmt_members->execute();
$members_result = $stmt_members->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Household - Barangay</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/bootstrap-icons-1.11.0/bootstrap-icons.css">
<link rel="stylesheet" href="../css/style.css">

<style>
.pwd-checkbox { display: flex; align-items: center; justify-content: center; height: 100%; }
.pwd-checkbox input { margin-right: 4px; }
</style>
</head>
<body>

<div class="content">
<?php include '../barangay/layout/topbar.php'; ?>
<?php include '../barangay/layout/sidebar.php'; ?>

<div class="container mt-3">
<h4>Edit Household</h4>

<div class="card shadow p-3">
<form method="POST">

<div class="mb-2">
<label>Household Head</label>
<input type="text" name="household_head" class="form-control" value="<?php echo htmlspecialchars($household['household_head']); ?>" required>
</div>

<div class="mb-2">
<label>Barangay</label>
<input type="text" class="form-control" value="<?php echo htmlspecialchars($barangay_name); ?>" disabled>
</div>

<div class="mb-2">
<label>Monthly Income</label>
<input type="number" name="income" class="form-control" value="<?php echo $household['income']; ?>" required>
</div>

<div class="mb-2">
<label>Number of Dependents</label>
<input type="number" name="family_size" class="form-control" value="<?php echo $household['family_size']; ?>" required>
</div>

<hr>
<h5>Household Members</h5>
<div id="members_container"></div>

<div class="mb-3">
<button type="button" class="btn btn-info" onclick="addMember()">
<i class="bi bi-person-plus"></i> Add Member
</button>
</div>

<button type="submit" name="update" class="btn btn-success">
<i class="bi bi-save"></i> Update
</button>
<a href="barangay_households.php" class="btn btn-secondary">Cancel</a>

</form>
</div>
</div>
</div>

<script>
let memberIndex = 0;

function addMember(name='', age='', sex='Male', occupation='', income='', pwd=0){
    memberIndex++;
    const checked = pwd ? 'checked' : '';
    const container = document.getElementById('members_container');
    const html = `
    <div class="row mb-2 border p-2" id="member_${memberIndex}">
        <div class="col-md-3">
            <input type="text" name="member_name[]" class="form-control" placeholder="Full Name" value="${name}" required>
        </div>
        <div class="col-md-1">
            <input type="number" name="member_age[]" class="form-control" placeholder="Age" value="${age}" required>
        </div>
        <div class="col-md-2">
            <select name="member_sex[]" class="form-control" required>
                <option value="Male" ${sex=='Male'?'selected':''}>Male</option>
                <option value="Female" ${sex=='Female'?'selected':''}>Female</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" name="member_occupation[]" class="form-control" placeholder="Occupation" value="${occupation}">
        </div>
        <div class="col-md-2">
            <input type="number" name="member_income[]" class="form-control" placeholder="Income" value="${income}">
        </div>
        <div class="col-md-1 pwd-checkbox">
            <input type="checkbox" name="member_pwd[]" value="${memberIndex}" ${checked}> <span>PWD</span>
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeMember(${memberIndex})">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

/* Load existing members from PHP */
<?php while($m = $members_result->fetch_assoc()):
$name = addslashes($m['full_name']);
$age = $m['age'];
$sex = $m['sex'];
$occupation = addslashes($m['occupation']);
$income = $m['income'];
$pwd = $m['pwd'];
echo "addMember('$name','$age','$sex','$occupation','$income','$pwd');\n";
endwhile; ?>

function removeMember(idx){
    const row = document.getElementById(`member_${idx}`);
    if(row) row.remove();
}
</script>

</body>
</html>