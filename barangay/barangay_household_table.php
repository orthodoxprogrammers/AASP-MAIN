<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../config/db.php';

// Only Barangay users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Barangay') {
    header("Location: ../login.php");
    exit;
}

// Get the logged-in barangay
$barangay = $_SESSION['barangay'] ?? '';
if (!$barangay) exit("Invalid barangay session.");

// Get AJAX params
$search = trim($_GET['search'] ?? '');
$sort   = $_GET['sort'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));

$limit  = 8;
$offset = ($page - 1) * $limit;

// Base query
$sql = "SELECT * FROM beneficiaries WHERE barangay = ?";
$params = [$barangay];
$types  = "s"; // string type

// Apply search filter
if ($search !== '') {
    $sql .= " AND CONCAT(last_name, ' ', first_name) LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

// Apply sorting
switch ($sort) {
    case 'az': $sql .= " ORDER BY last_name ASC"; break;
    case 'za': $sql .= " ORDER BY last_name DESC"; break;
    case 'highest_income': $sql .= " ORDER BY monthly_income DESC"; break;
    case 'lowest_income': $sql .= " ORDER BY monthly_income ASC"; break;
    default: $sql .= " ORDER BY beneficiary_id DESC"; break;
}

// Count total rows for pagination
$count_sql = "SELECT COUNT(*) FROM beneficiaries WHERE barangay = ?";
$count_params = [$barangay];
$count_types = "s";

if ($search !== '') {
    $count_sql .= " AND CONCAT(last_name, ' ', first_name) LIKE ?";
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
    <th>Last Name</th>
    <th>First Name</th>
    <th>Income</th>
    <th>Dependents</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr class="household-row" style="cursor:pointer;" onclick="window.location='view_beneficiary_barangay.php?id=<?= $row['beneficiary_id'] ?>'">
        <td><?= $row['beneficiary_id'] ?></td>
        <td><?= htmlspecialchars($row['last_name']) ?></td>
        <td><?= htmlspecialchars($row['first_name']) ?></td>
        <td>₱<?= number_format($row['monthly_income'], 2) ?></td>
        <td><?= $row['dependents_count'] ?></td>
        <td onclick="event.stopPropagation();">
            <a href="edit_beneficiaries_barangay.php?id=<?= $row['beneficiary_id'] ?>" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil"></i>
            </a>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="6" class="text-center">No beneficiaries found in <?= htmlspecialchars($barangay) ?>.</td>
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