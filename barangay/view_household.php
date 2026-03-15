<?php
session_start();
include __DIR__ . '/../config/db.php';

if(!isset($_SESSION['id']) || $_SESSION['role'] !== 'Barangay'){
    header("Location: ../login.php");
    exit;
}

$user_barangay_id = $_SESSION['barangay_id'] ?? null;

if(!$user_barangay_id){
    die("Barangay ID not set in session.");
}

if(!isset($_GET['id'])){
    header("Location: barangay_households.php");
    exit;
}

$household_id = intval($_GET['id']);

// Fetch Household safely
$stmt = $conn->prepare("SELECT h.*, b.barangay_name FROM households h 
                        JOIN barangays b ON h.barangay_id = b.id 
                        WHERE h.id = ?");
$stmt->bind_param("i", $household_id);
$stmt->execute();
$householdResult = $stmt->get_result();
$household = $householdResult->fetch_assoc();

if(!$household){
    die("Household not found.");
}

// Security check: ensure Barangay can only view their own households
if($household['barangay_id'] != $user_barangay_id){
    die("Access denied. This household is not in your barangay.");
}

// Fetch Members
$membersStmt = $conn->prepare("SELECT * FROM household_members WHERE household_id = ?");
$membersStmt->bind_param("i", $household_id);
$membersStmt->execute();
$membersResult = $membersStmt->get_result();
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
    <p><strong>Head of Family:</strong> <?php echo htmlspecialchars($household['household_head']); ?></p>
    <p><strong>Barangay:</strong> <?php echo htmlspecialchars($household['barangay_name']); ?></p>
    <p><strong>Family Size:</strong> <?php echo $household['family_size']; ?></p>
    <p><strong>Monthly Income:</strong> ₱<?php echo number_format($household['income'],2); ?></p>
    <p><strong>Priority:</strong> <?php echo $household['priority']; ?></p>
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
            <th>PWD</th>
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
                <td><?php echo $member['sex']; ?></td>
                <td><?php echo htmlspecialchars($member['occupation']); ?></td>
                <td>₱<?php echo number_format($member['income'],2); ?></td>
                <td><?php echo $member['pwd'] ? 'Yes' : 'No'; ?></td>
                <td>
                    <a href="edit_member.php?id=<?php echo $member['id']; ?>" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a href="delete_member.php?id=<?php echo $member['id']; ?>" class="btn btn-danger btn-sm" 
                       onclick="return confirm('Delete this member?')">
                        <i class="bi bi-trash"></i>
                    </a>
                </td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="8" class="text-center">No members added yet.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<a href="barangay_households.php" class="btn btn-secondary mt-2">Back</a>
</div>

</div>
</body>
</html>