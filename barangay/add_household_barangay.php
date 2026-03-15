<?php
session_start();
include __DIR__ . '/../config/db.php';

// RBAC: Only Barangay users
if(!isset($_SESSION['id']) || $_SESSION['role'] !== 'Barangay'){
    header("Location: ../login.php");
    exit;
}

// Barangay info from session
$barangay_id = $_SESSION['barangay_id'] ?? 0;
$barangay_name = $_SESSION['barangay_name'] ?? '';

if(!$barangay_id) exit("Invalid barangay session.");

if(isset($_POST['submit'])){

    $name = trim($_POST['household_head']);
    $income = (float)($_POST['income'] ?? 0);
    $family_size = (int)($_POST['family_size'] ?? 0);

    // Simple priority calculation
    $priority_score = 0;
    $priority_score += ($income <= 5000) ? 3 : (($income <= 10000) ? 2 : 1);
    $priority_score += ($family_size >= 6) ? 3 : (($family_size >= 4) ? 2 : 1);

    $priority_level = ($priority_score >= 5) ? "High" : (($priority_score >= 3) ? "Medium" : "Low");

    // Insert household
    $stmt = $conn->prepare("INSERT INTO households (household_head, income, family_size, priority, barangay_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sdisi", $name, $income, $family_size, $priority_level, $barangay_id);
    $stmt->execute();
    $household_id = $stmt->insert_id;
    $stmt->close();

    // Insert members
    if(!empty($_POST['member_name'])){
        $stmt_member = $conn->prepare("INSERT INTO household_members (household_id, full_name, age, sex, occupation, income, pwd) VALUES (?, ?, ?, ?, ?, ?, ?)");

        $names = $_POST['member_name'];
        $ages = $_POST['member_age'];
        $sexes = $_POST['member_sex'];
        $occupations = $_POST['member_occupation'];
        $incomes = $_POST['member_income'];
        $pwds = $_POST['member_pwd'] ?? [];

        foreach($names as $index => $member_name){
            $age = (int)($ages[$index] ?? 0);
            $sex = $sexes[$index] ?? 'Male';
            $occupation = $occupations[$index] ?? '';
            $member_income = (float)($incomes[$index] ?? 0);
            $is_pwd = in_array($index+1, $pwds) ? 1 : 0; // JS index starts at 1

            $stmt_member->bind_param("isissdi", $household_id, $member_name, $age, $sex, $occupation, $member_income, $is_pwd);
            $stmt_member->execute();
        }
        $stmt_member->close();
    }

    header("Location: barangay_households.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Household</title>
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
<div class="card shadow p-3">
<h4>Add Household</h4>

<form method="POST">

<div class="mb-2">
<label>Household Head</label>
<input type="text" name="household_head" class="form-control" required>
</div>

<div class="mb-2">
<label>Barangay</label>
<input type="text" class="form-control" value="<?php echo htmlspecialchars($barangay_name); ?>" disabled>
</div>

<div class="mb-2">
<label>Monthly Income</label>
<input type="number" name="income" class="form-control" required>
</div>

<div class="mb-2">
<label>Number of Dependents</label>
<input type="number" name="family_size" class="form-control" required>
</div>

<hr>
<h5>Household Members</h5>
<div id="members_container"></div>

<div class="mb-3">
<button type="button" class="btn btn-info" onclick="addMember()">
<i class="bi bi-person-plus"></i> Add Member
</button>
</div>

<button type="submit" name="submit" class="btn btn-primary">
<i class="bi bi-save"></i> Save Household
</button>
<a href="barangay_households.php" class="btn btn-secondary">Cancel</a>

</form>
</div>
</div>
</div>

<script>
let memberIndex = 0;

function addMember(){
    memberIndex++;
    const container = document.getElementById('members_container');
    const html = `
    <div class="row mb-2 border p-2" id="member_${memberIndex}">
        <div class="col-md-3">
            <input type="text" name="member_name[]" class="form-control" placeholder="Full Name" required>
        </div>
        <div class="col-md-1">
            <input type="number" name="member_age[]" class="form-control" placeholder="Age" required>
        </div>
        <div class="col-md-2">
            <select name="member_sex[]" class="form-control" required>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" name="member_occupation[]" class="form-control" placeholder="Occupation">
        </div>
        <div class="col-md-2">
            <input type="number" name="member_income[]" class="form-control" placeholder="Income">
        </div>
        <div class="col-md-1 pwd-checkbox">
            <input type="checkbox" name="member_pwd[]" value="${memberIndex}"> <span>PWD</span>
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeMember(${memberIndex})">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function removeMember(idx){
    const row = document.getElementById(`member_${idx}`);
    if(row) row.remove();
}
</script>

</body>
</html>