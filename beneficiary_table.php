<?php
session_start();
include __DIR__ . '/config/db.php';

/* RBAC: Only MSWDO can access this page */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'MSWDO') {
    echo "<tr><td colspan='8' class='text-center'>Access Denied</td></tr>";
    exit();
}

/* FILTERS */

$search = $_GET['search'] ?? '';
$barangay = $_GET['barangay'] ?? '';
$sector = $_GET['sector'] ?? [];
$income_from = ($_GET['income_from'] ?? '') !== '' ? (int)$_GET['income_from'] : '';
$income_to   = ($_GET['income_to'] ?? '') !== '' ? (int)$_GET['income_to'] : '';
$fourps = $_GET['fourps'] ?? '';
$philhealth = $_GET['philhealth'] ?? '';
$sort = $_GET['sort'] ?? '';

$page = max(1,(int)($_GET['page'] ?? 1));

$limit = 8;
$offset = ($page-1)*$limit;

/* WHERE */

$where=[];
$params=[];
$types='';

/* ALWAYS SHOW ONLY ACTIVE BENEFICIARIES */

$where[]="b.status='active'";

/* SEARCH */

if($search !== ''){

$where[]="(
b.first_name LIKE ? 
OR b.middle_name LIKE ? 
OR b.last_name LIKE ? 
OR b.barangay LIKE ?
)";

$params[]="%$search%";
$params[]="%$search%";
$params[]="%$search%";
$params[]="%$search%";

$types.="ssss";

}

/* BARANGAY */

if($barangay !== ''){

$where[]="b.barangay=?";
$params[]=$barangay;
$types.="s";

}

if(!empty($sector)){

$placeholders = implode(',', array_fill(0,count($sector),'?'));

$where[] = "(
    EXISTS(
        SELECT 1
        FROM beneficiary_sectors s
        WHERE s.beneficiary_id = b.beneficiary_id
        AND s.sector_name IN ($placeholders)
    )
    OR
    EXISTS(
        SELECT 1
        FROM beneficiary_subcategories sc
        WHERE sc.beneficiary_id = b.beneficiary_id
        AND sc.subcategory_name IN ($placeholders)
    )
)";

foreach($sector as $s){
$params[]=$s;
$types.="s";
}

foreach($sector as $s){
$params[]=$s;
$types.="s";
}

}

/* INCOME RANGE */

if($income_from !== '' && $income_to !== ''){

$where[] = "b.monthly_income BETWEEN ? AND ?";
$params[] = $income_from;
$params[] = $income_to;
$types .= "ii";

}
elseif($income_from !== ''){

$where[] = "b.monthly_income >= ?";
$params[] = $income_from;
$types .= "i";

}
elseif($income_to !== ''){

$where[] = "b.monthly_income <= ?";
$params[] = $income_to;
$types .= "i";

}

/* 4PS FILTER */

if($fourps !== ''){

if($fourps == 1){

$where[]="EXISTS(
SELECT 1 FROM beneficiary_sectors s
WHERE s.beneficiary_id=b.beneficiary_id
AND s.sector_name='4Ps Beneficiary'
)";

}
else{

$where[]="NOT EXISTS(
SELECT 1 FROM beneficiary_sectors s
WHERE s.beneficiary_id=b.beneficiary_id
AND s.sector_name='4Ps Beneficiary'
)";

}

}
$whereSQL = "WHERE " . implode(" AND ", $where);

/* COUNT QUERY */

$countSQL="

SELECT COUNT(DISTINCT b.beneficiary_id) AS total

FROM beneficiaries b

LEFT JOIN beneficiary_sectors bs
ON b.beneficiary_id=bs.beneficiary_id

LEFT JOIN beneficiary_subcategories bc
ON b.beneficiary_id=bc.beneficiary_id

$whereSQL

";

$countStmt=$conn->prepare($countSQL);

if($params){
$countStmt->bind_param($types,...$params);
}

$countStmt->execute();

$totalRows=$countStmt->get_result()->fetch_assoc()['total'];
$totalPages=ceil($totalRows/$limit);

$countStmt->close();

/* SORTING */

$order = "b.last_name ASC";

if($sort == "az"){
    $order = "b.last_name ASC";
}
elseif($sort == "za"){
    $order = "b.last_name DESC";
}
elseif($sort == "high_priority"){
    $order = "b.priority_score DESC";
}
elseif($sort == "low_priority"){
    $order = "b.priority_score ASC";
}

/* MAIN QUERY */

$query="

SELECT

b.beneficiary_id,

CONCAT(
b.last_name,', ',
b.first_name,' ',
IFNULL(b.middle_name,''),' ',
IFNULL(b.ext,'')
) AS full_name,

b.sex,
b.birthdate,
b.civil_status,
b.barangay,

GROUP_CONCAT(DISTINCT bs.sector_name SEPARATOR ', ') AS sectors,
GROUP_CONCAT(DISTINCT bc.subcategory_name SEPARATOR ', ') AS subcategories,

MAX(
CASE
WHEN bc.subcategory_name LIKE 'Indigenous:%'
THEN bc.subcategory_name
END
) AS indigenous

FROM beneficiaries b

LEFT JOIN beneficiary_sectors bs
ON b.beneficiary_id=bs.beneficiary_id

LEFT JOIN beneficiary_subcategories bc
ON b.beneficiary_id=bc.beneficiary_id

$whereSQL

GROUP BY b.beneficiary_id

ORDER BY $order

LIMIT ?,?

";

/* PREPARE */

$stmt=$conn->prepare($query);
if(!$stmt){
    die("SQL ERROR: ".$conn->error);
}

/* MERGE PARAMS */

$bindParams=$params;
$bindTypes=$types;

$bindParams[]=$offset;
$bindParams[]=$limit;
$bindTypes.="ii";

if(!empty($bindParams)){
    $stmt->bind_param($bindTypes,...$bindParams);
}

$stmt->execute();

$result=$stmt->get_result();
?>
<head>
    <style>
        .scroll-box{
            max-width:6rem;
            overflow-x:auto;
            white-space:nowrap;
        }

        .scroll-box::-webkit-scrollbar{
            height:6px;
        }

        .scroll-box::-webkit-scrollbar-thumb{
            background:#bbb;
            border-radius:3px;
        }
    </style>
</head>
<table class="table table-sm table-hover mt-2">

<thead class="table-light">

<tr>
<th>Full Name</th>
<th>Sex</th>
<th>Age</th>
<th>Civil Status</th>
<th>Sectors</th>
<th>Subcategories</th>
<th>Indigenous Member</th>
<th>Barangay</th>
<th>Action</th>
</tr>

</thead>

<tbody>

<?php if($result->num_rows>0): ?>

<?php while($row=$result->fetch_assoc()): ?>

<?php

/* AGE */

$age="";

if(!empty($row['birthdate'])){
$birth=new DateTime($row['birthdate']);
$today=new DateTime();
$age=$today->diff($birth)->y;
}

/* INDIGENOUS */

$indigenous="No";

if(!empty($row['indigenous'])){
$indigenous=str_replace("Indigenous: ","",$row['indigenous']);
}

/* SECTORS */

$sectors=$row['sectors'] ?: "—";

?>

<tr class="beneficiary-row pointer" data-id="<?= $row['beneficiary_id'] ?>">

<td><?= htmlspecialchars(trim($row['full_name'])) ?></td>

<td><?= htmlspecialchars($row['sex']) ?></td>

<td><?= $age ?></td>

<td><?= htmlspecialchars($row['civil_status']) ?></td>

<td>
<div class="scroll-box">
<?= htmlspecialchars($sectors) ?>
</div>
</td>

<td>
<div class="scroll-box">
<?= !empty($row['subcategories']) 
? htmlspecialchars($row['subcategories']) 
: "—" ?>
</div>
</td>

<td><?= htmlspecialchars($indigenous) ?></td>

<td><?= htmlspecialchars($row['barangay']) ?></td>

<td onclick="event.stopPropagation();">

<a href="edit_beneficiary.php?id=<?= $row['beneficiary_id'] ?>"
class="btn btn-warning btn-sm">

<i class="bi bi-pencil"></i>

</a>

<a href="archive_beneficiary.php?id=<?= $row['beneficiary_id'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Archive this beneficiary record?');">

<i class="bi bi-archive"></i>

</a>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="9" class="text-center">No beneficiaries found</td>
</tr>

<?php endif; ?>

</tbody>

</table>

<?php if($totalPages>1): ?>

<nav>

<ul class="pagination justify-content-center">

<?php for($i=1;$i<=$totalPages;$i++): ?>

<li class="page-item <?= ($i==$page)?'active':'' ?>">

<a href="#" class="page-link" data-page="<?= $i ?>">
<?= $i ?>
</a>

</li>

<?php endfor; ?>

</ul>

</nav>

<?php endif; ?>

<script>

/* ROW CLICK → VIEW PAGE */

document.querySelectorAll(".beneficiary-row").forEach(row=>{

row.addEventListener("click",function(){

let id=this.dataset.id;

window.location.href="view_beneficiary_info.php?id="+id;

});

});

</script>