<?php

$user_barangay_name = $_SESSION['barangay'] ?? 'Barangay';

?>

<div class="topbar d-flex justify-content-between align-items-center p-2 shadow-sm bg-white">
    <div class="topbar-left">
        <h5 class="mb-0">Barangay: <?php echo htmlspecialchars($user_barangay_name); ?></h5>
    </div>
    <div class="topbar-right">
        <a href="../logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>
</div>