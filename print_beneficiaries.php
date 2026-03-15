<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'MSWDO') {
    die("Access Denied");
}

/* FILTERS */

$search = $_GET['search'] ?? '';
$barangay = $_GET['barangay'] ?? '';
$sector = $_GET['sector'] ?? '';
$income_from = $_GET['income_from'] ?? '';
$income_to = $_GET['income_to'] ?? '';

$where = [];
$params = [];
$types = "";

$where[] = "b.status='active'";

if($search != ''){
$where[]="(b.first_name LIKE ? OR b.last_name LIKE ? OR b.barangay LIKE ?)";
$params[]="%$search%";
$params[]="%$search%";
$params[]="%$search%";
$types.="sss";
}

if($barangay != ''){
$where[]="b.barangay=?";
$params[]=$barangay;
$types.="s";
}

if($sector != ''){
$where[]="bs.sector_name=?";
$params[]=$sector;
$types.="s";
}

/* INCOME RANGE */

if($income_from !== '' && $income_to !== ''){

$where[] = "b.monthly_income BETWEEN ? AND ?";
$params[] = $income_from;
$params[] = $income_to;
$types .= "ii";

}

$whereSQL="WHERE ".implode(" AND ",$where);

$query="

SELECT
b.*,
GROUP_CONCAT(DISTINCT bs.sector_name SEPARATOR ', ') AS sectors,
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
ORDER BY b.last_name ASC

";

$stmt=$conn->prepare($query);

if($params){
$stmt->bind_param($types,...$params);
}

$stmt->execute();
$result=$stmt->get_result();

$count=1;
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>Relief Distribution Sheet</title>

<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/bootstrap-icons-1.11.0/bootstrap-icons.css">
<link rel="stylesheet" href="css/style.css">

<style>

body{
font-family:Arial;
margin:20px;
}

h2{
text-align:center;
margin-bottom:10px;
}

.header-row{
display:flex;
justify-content:space-between;
margin-bottom:10px;
font-size:14px;
}

.controls{
margin-bottom:15px;
}

/* TABLE */

table{
width:100%;
border-collapse:collapse;
}

th,td{
border:1px solid black;
padding:4px;
font-size:11px;
text-align:center;
}

thead th{
background:#f2f2f2;
}

.left{
text-align:left;
}

/* EDITABLE CELLS */

td[contenteditable="true"]{
background:#fffdf2;
cursor:text;
}

td[contenteditable="true"]:focus{
outline:2px solid #4CAF50;
background:white;
}

/* SELECTED ROWS */

tr.selected-row{
background:#ffd6d6 !important;
}

/* PRINT */

@media print{

.controls{
display:none;
}

@page{
size: landscape;
}

body{
margin:0;
}

}

</style>

<script>

let selectedRows=[];

function printPage(){
window.print();
}

function cancelPrint(){
window.close();
}

/* ADD ROW */

function addRow(){

let table=document.getElementById("beneficiaryTable").getElementsByTagName('tbody')[0];

let row=table.insertRow();

for(let i=0;i<21;i++){

let cell=row.insertCell(i);

cell.contentEditable="true";
cell.innerHTML="";

}

attachRowClick(row);

updateNumbers();

}

/* REMOVE SELECTED ROWS */

function removeRow(){

if(selectedRows.length===0){
alert("Please select row(s) to remove.");
return;
}

selectedRows.forEach(row=>{
row.remove();
});

selectedRows=[];

updateNumbers();

}

/* UPDATE NUMBER COLUMN */

function updateNumbers(){

let rows=document.querySelectorAll("#beneficiaryTable tbody tr");

rows.forEach((row,index)=>{
row.cells[0].innerText=index+1;
});

}

/* ROW CLICK HANDLER */

function attachRowClick(row){

row.addEventListener("click",function(e){

if(e.ctrlKey){

row.classList.toggle("selected-row");

if(row.classList.contains("selected-row")){
selectedRows.push(row);
}else{
selectedRows=selectedRows.filter(r=>r!==row);
}

}else{

document.querySelectorAll("#beneficiaryTable tbody tr").forEach(r=>{
r.classList.remove("selected-row");
});

selectedRows=[];

row.classList.add("selected-row");
selectedRows.push(row);

}

});

}

/* INITIALIZE EXISTING ROWS */

window.onload=function(){

let rows=document.querySelectorAll("#beneficiaryTable tbody tr");

rows.forEach(row=>{
attachRowClick(row);
});

};

</script>

</head>

<body>

<div class="controls d-flex justify-content-end gap-2">

<button class="btn btn-success" onclick="addRow()">
<i class="bi bi-plus-circle"></i> Add Row
</button>

<button class="btn btn-danger" onclick="removeRow()">
<i class="bi bi-dash-circle"></i> Remove Row
</button>

<button class="btn btn-primary" onclick="printPage()">
<i class="bi bi-printer"></i> Print
</button>

<button class="btn btn-secondary" onclick="cancelPrint()">
<i class="bi bi-x-circle"></i> Cancel
</button>

</div>

<h2 contenteditable="true">RELIEF DISTRIBUTION SHEET</h2>

<div class="header-row">

<div contenteditable="true">
<strong>Name of Event / Nature of Augmentation:</strong>
_____________________________________________
</div>

<div contenteditable="true">
<strong>Date of Distribution:</strong>
____________________
</div>

</div>

<table id="beneficiaryTable">

<thead>

<tr>

<th rowspan="3">#</th>

<th colspan="4">NAME</th>

<th colspan="2">SEX</th>

<th rowspan="3">BIRTHDAY<br>(mm/dd/yyyy)</th>

<th rowspan="3">CIVIL STATUS</th>

<th rowspan="3">SECTORS</th>

<th rowspan="3">Member of Indigenous People</th>

<th colspan="6">QUANTITY AND KIND OF FOOD / NON-FOOD ITEMS (FNI) RECEIVED</th>

<th rowspan="3">BARANGAY</th>

<th rowspan="3">CITY / MUNICIPALITY</th>

<th rowspan="3">PROVINCE</th>

<th rowspan="3">SIGNATURE / THUMBMARK</th>

</tr>

<tr>

<th rowspan="2">Last Name</th>
<th rowspan="2">First Name</th>
<th rowspan="2">Middle Name</th>
<th rowspan="2">Ext</th>

<th rowspan="2">M</th>
<th rowspan="2">F</th>

<th>FFP</th>
<th>HK</th>
<th>FK</th>
<th>KK</th>
<th>SK</th>
<th>Others</th>

</tr>

<tr>
<th></th>
<th></th>
<th></th>
<th></th>
<th></th>
<th></th>
</tr>

</thead>

<tbody>

<?php while($row=$result->fetch_assoc()): ?>

<?php

$indigenous="";
if(!empty($row['indigenous'])){
$indigenous=str_replace("Indigenous: ","",$row['indigenous']);
}

$birthdate="";
if(!empty($row['birthdate'])){
$birthdate=date("m/d/Y",strtotime($row['birthdate']));
}

$sex=strtolower(trim($row['sex']));

?>

<tr>

<td><?= $count++ ?></td>

<td class="left" contenteditable="true"><?= htmlspecialchars($row['last_name']) ?></td>
<td class="left" contenteditable="true"><?= htmlspecialchars($row['first_name']) ?></td>
<td class="left" contenteditable="true"><?= htmlspecialchars($row['middle_name']) ?></td>
<td contenteditable="true"><?= htmlspecialchars($row['ext']) ?></td>

<td contenteditable="true"><?= ($sex=='m'||$sex=='male')?'✔':'' ?></td>
<td contenteditable="true"><?= ($sex=='f'||$sex=='female')?'✔':'' ?></td>

<td contenteditable="true"><?= $birthdate ?></td>

<td contenteditable="true"><?= htmlspecialchars($row['civil_status']) ?></td>

<td contenteditable="true"><?= htmlspecialchars($row['sectors']) ?></td>

<td contenteditable="true"><?= htmlspecialchars($indigenous) ?></td>

<td contenteditable="true"></td>
<td contenteditable="true"></td>
<td contenteditable="true"></td>
<td contenteditable="true"></td>
<td contenteditable="true"></td>
<td contenteditable="true"></td>

<td contenteditable="true"><?= htmlspecialchars($row['barangay']) ?></td>
<td contenteditable="true"><?= htmlspecialchars($row['city']) ?></td>
<td contenteditable="true"><?= htmlspecialchars($row['province']) ?></td>

<td contenteditable="true"></td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</body>
</html>