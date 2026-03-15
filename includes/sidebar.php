<div class="sidebar bg-light vh-100 p-3">

    <h4 class="mb-4">APSystem</h4>

    <?php
    $currentPage = basename($_SERVER['PHP_SELF']);
    ?>

    <a href="dashboard.php" class="d-flex align-items-center mb-2 text-decoration-none <?= $currentPage == 'dashboard.php' ? 'fw-bold' : '' ?>">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard
    </a>


    <a href="beneficiary.php" class="d-flex align-items-center mb-2 text-decoration-none <?= ($currentPage == 'beneficiary.php' || $currentPage == 'view_beneficiary.php' || $currentPage == 'edit_beneficiary.php') ? 'fw-bold' : '' ?>">
        <i class="bi bi-people me-2"></i> Beneficiaries
    </a>


    <a href="add_beneficiary.php" class="d-flex align-items-center mb-2 text-decoration-none <?= $currentPage == 'add_beneficiary.php' ? 'fw-bold' : '' ?>">
        <i class="bi bi-person-plus me-2"></i> Add Beneficiary
    </a>


    <a href="archived_beneficiaries.php" class="d-flex align-items-center mb-2 text-decoration-none <?= $currentPage == 'archived_beneficiaries.php' ? 'fw-bold' : '' ?>">
        <i class="bi bi-archive me-2"></i> Archived Beneficiaries
    </a>


    <a href="logout.php" class="d-flex align-items-center mt-4 text-decoration-none">
        <i class="bi bi-box-arrow-right me-2"></i> Logout
    </a>

</div>