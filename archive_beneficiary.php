<?php
session_start();
include __DIR__.'/config/db.php';

/* RBAC */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'MSWDO') {
    die("Access Denied");
}

if(!isset($_GET['id'])){
    die("Invalid request.");
}

$id=(int)$_GET['id'];

/* GET BENEFICIARY NAME */

$stmt=$conn->prepare("
SELECT CONCAT(last_name,', ',first_name,' ',IFNULL(middle_name,'')) AS name
FROM beneficiaries
WHERE beneficiary_id=? AND status='active'
");

$stmt->bind_param("i",$id);
$stmt->execute();
$result=$stmt->get_result();
$beneficiary=$result->fetch_assoc();
$stmt->close();

if(!$beneficiary){
    die("Beneficiary not found or already archived.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>Archive Beneficiary</title>

<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/bootstrap-icons-1.11.0/bootstrap-icons.css">
</head>

<body class="bg-light">
    <div class="container mt-5">

        <div class="card shadow">

            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-archive"></i> Archive Beneficiary</h5>
            </div>

            <div class="card-body">

                <p>You are about to archive: <strong><?= htmlspecialchars($beneficiary['name']) ?></strong></p>

                <form method="POST" action="process_archive_beneficiary.php">

                    <input type="hidden" name="beneficiary_id" value="<?= $id ?>">

                    <div class="mb-3">
                        <label class="form-label">Reason for Archiving</label>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="Enter reason for archiving this beneficiary record..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-archive"></i> Archive
                    </button>

                    <a href="beneficiary.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>