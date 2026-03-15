<?php
session_start();
include __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Barangay') {
    header("Location: ../login.php");
    exit;
}

$barangay = $_SESSION['barangay'] ?? '';
if (!$barangay) exit("Invalid barangay session.");

$household_id = intval($_GET['id']);

// Fetch Household from beneficiaries
$stmt = $conn->prepare("SELECT * FROM beneficiaries WHERE beneficiary_id = ?");
$stmt->bind_param("i", $household_id);
$stmt->execute();
$householdResult = $stmt->get_result();
$household = $householdResult->fetch_assoc();

if (!$household) die("Household not found.");

// Security check
if ($household['barangay'] !== $barangay) {
    die("Access denied. This household is not in your barangay.");
}

// Fetch Members from family_composition
$membersStmt = $conn->prepare("SELECT * FROM family_composition WHERE beneficiary_id = ?");
$membersStmt->bind_param("i", $household_id);
$membersStmt->execute();
$membersResult = $membersStmt->get_result();

// Count family size
$family_size = $membersResult->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Household</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/bootstrap-icons-1.11.0/bootstrap-icons.css">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="content">
<?php include '../barangay/layout/topbar.php'; ?>
<?php include '../barangay/layout/sidebar.php'; ?>

<div class="container mt-4">
<h4>Household Details</h4>
<div class="card shadow p-3 mb-3">
    <p><strong>Head of Family:</strong> 
        <?php 
            echo htmlspecialchars(
                trim($household['first_name'] . ' ' . $household['middle_name'] . ' ' . $household['last_name'] . ' ' . $household['ext'])
            ); 
        ?>
    </p>
    <p><strong>Barangay:</strong> <?php echo htmlspecialchars($household['barangay']); ?></p>
    <p><strong>Family Size:</strong> <?php echo $family_size; ?></p>
    <p><strong>Monthly Income:</strong> ₱<?php echo number_format($household['monthly_income'],2); ?></p>
    <?php if(isset($household['priority'])): ?>
    <p><strong>Priority:</strong> <?php echo htmlspecialchars($household['priority']); ?></p>
    <?php endif; ?>
</div>

<h5>Household Members</h5>
<div class="table-responsive">
<table class="table table-sm table-bordered">
    <thead class="table-light">
        <tr>
            <th>#</th>
            <th>Full Name</th>
            <th>Age</th>
            <th>Sex</th>
            <th>Occupation</th>
            <th>Income</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if($membersResult->num_rows > 0): 
            $i = 1;
            while($member = $membersResult->fetch_assoc()): ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                <td><?php echo $member['age']; ?></td>
                <td>-</td> <!-- Sex not in family_composition table -->
                <td><?php echo htmlspecialchars($member['occupation']); ?></td>
                <td>₱<?php echo number_format($member['monthly_income'],2); ?></td>
                <td>
                    <a href="edit_member.php?id=<?php echo $member['family_member_id']; ?>" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a href="delete_member.php?id=<?php echo $member['family_member_id']; ?>" class="btn btn-danger btn-sm" 
                       onclick="return confirm('Delete this member?')">
                        <i class="bi bi-trash"></i>
                    </a>
                </td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="7" class="text-center">No members added yet.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<a href="barangay_households.php" class="btn btn-secondary mt-2">Back</a>
</div>

</div>
</body>
</html>