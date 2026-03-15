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

// Check household ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if(!$id) header("Location: barangay_households.php");

// Fetch household
$stmt = $conn->prepare("SELECT * FROM households WHERE id=? AND barangay_id=?");
$stmt->bind_param("ii", $id, $barangay_id);
$stmt->execute();
$household = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$household) die("Household not found or access denied.");

// Fetch members
$stmt = $conn->prepare("SELECT * FROM household_members WHERE household_id=? ORDER BY id ASC");
$stmt->bind_param("i", $id);
$stmt->execute();
$members_result = $stmt->get_result();
$stmt->close();

if(isset($_POST['update'])){
    $head = trim($_POST['household_head']);
    $income = (float)($_POST['income'] ?? 0);
    $family_size = (int)($_POST['family_size'] ?? 0);

    // Update household
    $stmt = $conn->prepare("UPDATE households SET household_head=?, income=?, family_size=? WHERE id=? AND barangay_id=?");
    $stmt->bind_param("siiii", $head, $income, $family_size, $id, $barangay_id);
    $stmt->execute();
    $stmt->close();

    // Delete old members
    $stmt = $conn->prepare("DELETE FROM household_members WHERE household_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Insert new members
    if(!empty($_POST['member_name'])){
        $stmt_member = $conn->prepare("INSERT INTO household_members (household_id, full_name, age, sex, occupation, income, pwd) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $names = $_POST['member_name'];
        $ages = $_POST['member_age'];
        $sexes = $_POST['member_sex'];
        $occupations = $_POST['member_occupation'];
        $incomes = $_POST['member_income'];
        $pwds = $_POST['member_pwd'] ?? [];

        foreach($names as $index => $name){
            $age = (int)($ages[$index] ?? 0);
            $sex = $sexes[$index] ?? 'Male';
            $occupation = $occupations[$index] ?? '';
            $member_income = (float)($incomes[$index] ?? 0);
            $is_pwd = in_array($index+1, $pwds) ? 1 : 0;

            $stmt_member->bind_param("isissdi", $id, $name, $age, $sex, $occupation, $member_income, $is_pwd);
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