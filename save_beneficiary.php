<?php
session_start();
include 'config/db.php';

function formatName($text){
    $text = trim($text);
    $text = strtolower($text);
    return ucwords($text);
}

error_reporting(E_ALL);
ini_set('display_errors',1);

/* LOGIN CHECK */
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){

$conn->begin_transaction();

try{

/* =========================
   CLIENT INFORMATION
========================= */

$c_last   = formatName($_POST['c_last'] ?? '');
$c_first  = formatName($_POST['c_first'] ?? '');
$c_middle = formatName($_POST['c_middle'] ?? '');
$c_ext = trim($_POST['c_ext'] ?? '');
$c_street   = trim($_POST['c_street'] ?? '');
$c_barangay = trim($_POST['c_barangay'] ?? '');
$c_city     = trim($_POST['c_city'] ?? '');
$c_province = trim($_POST['c_province'] ?? '');
$c_region   = trim($_POST['c_region'] ?? '');
$c_mobile   = trim($_POST['c_mobile'] ?? '');
$c_birthdate   = $_POST['c_birthdate'] ?? null;
$c_age         = $_POST['c_age'] ?? null;
$c_sex         = $_POST['c_sex'] ?? '';
$c_civil       = $_POST['c_civil'] ?? '';
$c_occupation  = $_POST['c_occupation'] ?? '';
$c_income      = $_POST['c_income'] ?? 0;
$c_relationship= $_POST['c_relationship'] ?? '';

$client_id = NULL;

if($c_last != "" || $c_first != ""){

$stmt = $conn->prepare("
INSERT INTO clients
(last_name,first_name,middle_name,ext,
street_address,barangay,city,province,region,
mobile_number,birthdate,age,sex,civil_status,
occupation,monthly_income,relationship_to_beneficiary)

VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
"sssssssssssisssds",
$c_last,$c_first,$c_middle,$c_ext,
$c_street,$c_barangay,$c_city,$c_province,$c_region,
$c_mobile,$c_birthdate,$c_age,$c_sex,$c_civil,
$c_occupation,$c_income,$c_relationship
);

$stmt->execute();
$client_id = $conn->insert_id;

}

/* =========================
   BENEFICIARY DATA
========================= */

$b_last   = formatName($_POST['b_last'] ?? '');
$b_first  = formatName($_POST['b_first'] ?? '');
$b_middle = formatName($_POST['b_middle'] ?? '');
$b_ext = trim($_POST['b_ext'] ?? '');
$b_street   = trim($_POST['b_street'] ?? '');
$b_barangay = trim($_POST['b_barangay'] ?? '');
$b_city     = trim($_POST['b_city'] ?? '');
$b_province = trim($_POST['b_province'] ?? '');
$b_region   = trim($_POST['b_region'] ?? '');
$b_mobile   = trim($_POST['b_mobile'] ?? '');
$b_birthdate  = $_POST['b_birthdate'] ?? null;
$b_age        = $_POST['b_age'] ?? null;
$b_sex        = $_POST['b_sex'] ?? '';
$b_civil      = $_POST['b_civil'] ?? '';
$b_occupation = $_POST['b_occupation'] ?? '';
$b_income = isset($_POST['b_income']) && $_POST['b_income'] !== '' 
            ? (float)$_POST['b_income'] 
            : 0;

/* VALIDATE BENEFICIARY */

if(trim($b_last) === "" || trim($b_first) === ""){
    throw new Exception("Beneficiary last name and first name are required.");
}

/* =========================
   INSERT BENEFICIARY
========================= */

$stmt = $conn->prepare("
INSERT INTO beneficiaries
(client_id,last_name,first_name,middle_name,ext,
street_address,barangay,city,province,region,
mobile_number,birthdate,age,sex,civil_status,
occupation,monthly_income)

VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
"issssssssssissssd",
$client_id,
$b_last,$b_first,$b_middle,$b_ext,
$b_street,$b_barangay,$b_city,$b_province,$b_region,
$b_mobile,$b_birthdate,$b_age,$b_sex,$b_civil,
$b_occupation,$b_income
);

$stmt->execute();
$beneficiary_id = $conn->insert_id;

/* =========================
   SECTOR
========================= */

if(!empty($_POST['sector']) && is_array($_POST['sector'])){

$stmt = $conn->prepare("
INSERT INTO beneficiary_sectors
(beneficiary_id,sector_name)
VALUES (?,?)
");

foreach($_POST['sector'] as $sector){

$sector = trim($sector);
if($sector === "") continue;

$stmt->bind_param("is",$beneficiary_id,$sector);
$stmt->execute();

}

}

/* =========================
   SUBCATEGORIES
========================= */

/* =========================
   SPECIAL TAGS (STORE IN SUBCATEGORY TABLE)
========================= */

if(!empty($_POST['tags']) && is_array($_POST['tags'])){

$stmt = $conn->prepare("
INSERT INTO beneficiary_subcategories
(beneficiary_id,subcategory_name)
VALUES (?,?)
");

foreach($_POST['tags'] as $tag){

$tag = trim($tag);
if($tag === "") continue;

$stmt->bind_param("is",$beneficiary_id,$tag);
$stmt->execute();

}

}

if(!empty($_POST['subcategory']) && is_array($_POST['subcategory'])){

$stmt = $conn->prepare("
INSERT INTO beneficiary_subcategories
(beneficiary_id,subcategory_name)
VALUES (?,?)
");

foreach($_POST['subcategory'] as $sub){

$sub = trim($sub);
if($sub === "") continue;

$stmt->bind_param("is",$beneficiary_id,$sub);
$stmt->execute();

}
}

/* =========================
   CALCULATE PRIORITY SCORE
========================= */

$priority = 0;

/* Income based priority */

$income_priority = 0;

if($b_income == 0){
$income_priority = 4;
}
elseif($b_income < 5000){
$income_priority = 3;
}
elseif($b_income < 10400){
$income_priority = 2;
}
elseif($b_income < 20000){
$income_priority = 1;
}

$priority += $income_priority;

/* Age priority */

if($b_age >= 60){
$priority += 2;
}

/* Subcategory based priority */

$sector_priority = 0;

if(!empty($_POST['subcategory'])){

foreach($_POST['subcategory'] as $sub){

if($sub == "Street Dwellers"){
$sector_priority = max($sector_priority,4);
}
elseif($sub == "Solo Parent"){
$sector_priority = max($sector_priority,2);
}
elseif($sub == "Indigenous People"){
$sector_priority = max($sector_priority,2);
}
elseif($sub == "4Ps Beneficiary"){
$sector_priority = max($sector_priority,1);
}

}

}

$priority += $sector_priority;

/* Tag based priority */

if(!empty($_POST['tags'])){

foreach($_POST['tags'] as $tag){

if($tag == "Recovering PWUD"){
$priority += 2;
}

}

}

/* UPDATE BENEFICIARY PRIORITY */

$stmt = $conn->prepare("
UPDATE beneficiaries
SET priority_score = ?
WHERE beneficiary_id = ?
");

$stmt->bind_param("ii",$priority,$beneficiary_id);
$stmt->execute();

/* =========================
   FAMILY MEMBERS
========================= */

if(!empty($_POST['fam_name'])){

$stmt = $conn->prepare("
INSERT INTO family_composition
(beneficiary_id,full_name,relationship_to_beneficiary,age,occupation,monthly_income)
VALUES (?,?,?,?,?,?)
");

for($i=0;$i<count($_POST['fam_name']);$i++){

$name = formatName($_POST['fam_name'][$i] ?? '');
if($name=="") continue;

$relation   = $_POST['fam_relation'][$i] ?? '';
$age        = $_POST['fam_age'][$i] ?? 0;
$occupation = $_POST['fam_occupation'][$i] ?? '';
$income     = $_POST['fam_income'][$i] ?? 0;

$stmt->bind_param(
"isissd",
$beneficiary_id,$name,$relation,$age,$occupation,$income
);

$stmt->execute();

}

}

/* =========================
   DSWD ASSISTANCE
========================= */

if(!empty($_POST['assist_type'])){

$stmt = $conn->prepare("
INSERT INTO dswd_assistance
(beneficiary_id,assistance_type,date_received)
VALUES (?,?,?)
");

for($i=0;$i<count($_POST['assist_type']);$i++){

$type = $_POST['assist_type'][$i];
$date = $_POST['assist_date'][$i] ?? null;

if($type=="") continue;

$stmt->bind_param("iss",$beneficiary_id,$type,$date);
$stmt->execute();

}

}

/* =========================
   GENERAL INTAKE
========================= */

$aics = isset($_POST['aics']) ? 1 : 0;
$akap = isset($_POST['akap']) ? 1 : 0;

$others_program = $_POST['others_program'] ?? '';
$visit_type     = $_POST['visit_type'] ?? '';
$client_source  = $_POST['client_source'] ?? '';
$intake_date    = $_POST['intake_date'] ?? null;
$purpose        = $_POST['purpose'] ?? '';
$amount         = $_POST['amount'] ?? 0;

$stmt = $conn->prepare("
INSERT INTO general_intake
(beneficiary_id,aics,akap,others_program,
visit_type,client_source,intake_date,
purpose_of_assistance,amount_needed)

VALUES (?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
"iiisssssd",
$beneficiary_id,$aics,$akap,$others_program,
$visit_type,$client_source,$intake_date,
$purpose,$amount
);

$stmt->execute();

/* =========================
   COMMIT
========================= */

$conn->commit();

header("Location: beneficiary.php?success=1");
exit();

}catch(Exception $e){

$conn->rollback();
echo "Database Error: ".$e->getMessage();

}

}
?>