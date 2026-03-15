<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include __DIR__ . '/../config/db.php';

// Only Barangay users
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Barangay') {
    exit("Access denied");
}

$barangay_id = $_SESSION['barangay_id'] ?? 0;
if (!$barangay_id) exit("Invalid barangay session.");

// Get AJAX params
$search = trim($_GET['search'] ?? '');
$sort   = $_GET['sort'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));

$limit  = 8;
$offset = ($page - 1) * $limit;

// Base query
$sql = "SELECT * FROM households WHERE barangay_id = ?";
$params = [$barangay_id];
$types  = "i";

// Apply search filter
if ($search !== '') {
    $sql .= " AND household_head LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

// Apply sorting
switch ($sort) {
    case 'az': $sql .= " ORDER BY household_head ASC"; break;
    case 'za': $sql .= " ORDER BY household_head DESC"; break;
    case 'high_priority': $sql .= " ORDER BY FIELD(priority,'High','Medium','Low') ASC"; break;
    case 'low_priority': $sql .= " ORDER BY FIELD(priority,'Low','Medium','High') ASC"; break;
    case 'highest_income': $sql .= " ORDER BY income DESC"; break;
    case 'lowest_income': $sql .= " ORDER BY income ASC"; break;
    default: $sql .= " ORDER BY id DESC"; break;
}

// Count total rows for pagination
$count_sql = "SELECT COUNT(*) FROM households WHERE barangay_id = ?";
$count_params = [$barangay_id];
$count_types = "i";

if ($search !== '') {
    $count_sql .= " AND household_head LIKE ?";
    $count_params[] = "%$search%";
    $count_types .= "s";
}

$stmt_count = $conn->prepare($count_sql);
$stmt_count->bind_param($count_types, ...$count_params);
$stmt_count->execute();
$stmt_count->bind_result($totalRows);
$stmt_count->fetch();
$stmt_count->close();

$totalPages = ceil($totalRows / $limit);

// Add LIMIT for pagination
$sql .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $limit;
$types .= "ii";

// Execute main query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<table class="table table-sm table-hover mt-2">
<thead>
<tr>
    <th>ID</th>
    <th>Household Head</th>
    <th>Income</th>
    <th>Dependents</th>
    <th>Priority</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr class="household-row" style="cursor:pointer;" onclick="window.location='view_household.php?id=<?= $row['id'] ?>'">
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['household_head']) ?></td>
        <td>₱<?= number_format($row['income'], 2) ?></td>
        <td><?= $row['family_size'] ?></td>
        <td><?= $row['priority'] ?></td>
        <td onclick="event.stopPropagation();">
            <a href="edit_household_barangay.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil"></i>
            </a>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="6" class="text-center">No households found.</td>
</tr>
<?php endif; ?>
</tbody>
</table>

<?php if ($totalPages > 1): ?>
<nav>
<ul class="pagination justify-content-center mt-2">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
        <a href="#" class="page-link" data-page="<?= $i ?>"><?= $i ?></a>
    </li>
    <?php endfor; ?>
</ul>
</nav>
<?php endif; ?>