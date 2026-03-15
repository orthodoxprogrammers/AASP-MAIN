<?php
session_start();
include __DIR__ . '/../config/db.php';

// Only Barangay users can access
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Barangay') {
    header("Location: ../login.php");
    exit;
}

// Get barangay name
$barangay_id = $_SESSION['barangay_id'] ?? 0;
$user_barangay = '';

if ($barangay_id) {
    $stmt = $conn->prepare("SELECT barangay_name FROM barangays WHERE id=?");
    $stmt->bind_param("i", $barangay_id);
    $stmt->execute();
    $stmt->bind_result($user_barangay);
    $stmt->fetch();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($user_barangay) ?> Households</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/bootstrap-icons-1.11.0/bootstrap-icons.css">
<link rel="stylesheet" href="../css/style.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
/* Search/filter row */
.search-filter-row {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-bottom: 10px;
}
.search-filter-row input { flex: 1; height: 2rem; font-size: .85rem; }
.search-filter-row select, .search-filter-row button { width: 150px; height: 2rem; font-size: .85rem; }

/* Table styling */
.table th, .table td { padding: .35rem .5rem; font-size: .85rem; }
.pagination { font-size: .8rem; margin: 5px 0; }
.pagination .page-link { padding: .25rem .5rem; }
</style>
</head>
<body>

<div class="content">

    <?php include __DIR__ . '/layout/topbar.php'; ?>
    <?php include __DIR__ . '/layout/sidebar.php'; ?>

    <div class="container-fluid mt-3">

        <!-- Header & Actions -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4><?= htmlspecialchars($user_barangay) ?> Households</h4>
            <div>
                <a href="add_household_barangay.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-person-plus"></i> Add Household
                </a>
                <button id="btnPrint" class="btn btn-secondary btn-sm">
                    <i class="bi bi-printer"></i> Print List
                </button>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card shadow tableCard p-2">
            <div class="search-filter-row">
                <input type="text" id="search" placeholder="Search household..." class="form-control">
                <button id="btnSearch" class="btn btn-primary btn-sm">
                    <i class="bi bi-search"></i> Search
                </button>
                <select id="sortBy" class="form-control">
                    <option value="">Sort</option>
                    <option value="az">Name A-Z</option>
                    <option value="za">Name Z-A</option>
                    <option value="high_priority">High Priority</option>
                    <option value="low_priority">Low Priority</option>
                    <option value="highest_income">Highest Income</option>
                    <option value="lowest_income">Lowest Income</option>
                </select>
            </div>

            <div class="table-responsive">
                <div id="householdTable">
                    <?php include 'barangay_household_table.php'; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
$(function() {

    // Load households with optional filters and pagination
    function loadHouseholds(search = '', sort = '', page = 1) {
        $.ajax({
            url: 'barangay_household_table.php',
            type: 'GET',
            data: { search, sort, page },
            success: function(data) {
                $('#householdTable').html(data);
            }
        });
    }

    // Search & Sort events
    $('#search').on('input', () => loadHouseholds($('#search').val(), $('#sortBy').val(), 1));
    $('#sortBy').on('change', () => loadHouseholds($('#search').val(), $('#sortBy').val(), 1));
    $('#btnSearch').on('click', () => loadHouseholds($('#search').val(), $('#sortBy').val(), 1));

    // Pagination click
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        loadHouseholds($('#search').val(), $('#sortBy').val(), page);
    });

    // Confirm deletion
    $(document).on('click', '.deleteBtn', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        if (confirm("Delete this household?") && confirm("This action is permanent. Continue?")) {
            window.location = url;
        }
    });

    // Print button
    $('#btnPrint').on('click', function(e) {
        e.preventDefault();
        const search = $('#search').val();
        const sort = $('#sortBy').val();
        const url = `print_households_barangay.php?search=${encodeURIComponent(search)}&sort=${encodeURIComponent(sort)}`;
        window.open(url, '_blank');
    });

});
</script>

</body>
</html>