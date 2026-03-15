<?php

$barangay_id = $_SESSION['barangay_id'] ?? null;

$user_barangay_name = 'Barangay';

if($barangay_id){
    include __DIR__ . '/../../config/db.php';
    $stmt = $conn->prepare("SELECT barangay_name FROM barangays WHERE id = ?");
    $stmt->bind_param("i", $barangay_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $user_barangay_name = $row['barangay_name'];
    }
}
?>

<div class="topbar d-flex justify-content-between align-items-center p-2 shadow-sm bg-white">
    <div class="topbar-left">
        <h5 class="mb-0">Barangay: <?php echo htmlspecialchars($user_barangay_name); ?></h5>
    </div>
    <div class="topbar-right">
        <a href="../logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>
</div>