<?php
session_start();
include __DIR__ . '/config/db.php';

/* RBAC */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'MSWDO') {
    die("Access Denied");
}

if(!isset($_POST['beneficiary_id'])){
    die("Invalid request.");
}

$beneficiary_id = (int)$_POST['beneficiary_id'];

/* =========================
   CLIENT INFORMATION
========================= */

$c_last = $_POST['c_last'] ?? '';
$c_first = $_POST['c_first'] ?? '';
$c_middle = $_POST['c_middle'] ?? '';
$c_ext = $_POST['c_ext'] ?? '';
$c_street = $_POST['c_street'] ?? '';
$c_barangay = $_POST['c_barangay'] ?? '';
$c_city = $_POST['c_city'] ?? '';
$c_province = $_POST['c_province'] ?? '';
$c_region = $_POST['c_region'] ?? '';
$c_mobile = $_POST['c_mobile'] ?? '';
$c_birthdate = $_POST['c_birthdate'] ?? null;
$c_age = $_POST['c_age'] ?? null;
$c_sex = $_POST['c_sex'] ?? '';
$c_civil = $_POST['c_civil'] ?? '';
$c_occupation = $_POST['c_occupation'] ?? '';
$c_income = $_POST['c_income'] ?? 0;
$c_relationship = $_POST['c_relationship'] ?? '';

/* check existing client */
$stmt = $conn->prepare("SELECT client_id FROM beneficiaries WHERE beneficiary_id=?");
$stmt->bind_param("i",$beneficiary_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

$client_id = $res['client_id'] ?? null;

/* UPDATE CLIENT OR INSERT */
if(!empty($c_last) && !empty($c_first)){

if($client_id){

$stmt=$conn->prepare("UPDATE clients SET
last_name=?,first_name=?,middle_name=?,ext=?,
street_address=?,barangay=?,city=?,province=?,region=?,
mobile_number=?,birthdate=?,age=?,sex=?,civil_status=?,
occupation=?,monthly_income=?,relationship_to_beneficiary=?
WHERE client_id=?");

$stmt->bind_param(
"sssssssssssisssisi",
$c_last,$c_first,$c_middle,$c_ext,
$c_street,$c_barangay,$c_city,$c_province,$c_region,
$c_mobile,$c_birthdate,$c_age,$c_sex,$c_civil,
$c_occupation,$c_income,$c_relationship,$client_id
);

$stmt->execute();
$stmt->close();

}else{

$stmt=$conn->prepare("INSERT INTO clients(
last_name,first_name,middle_name,ext,
street_address,barangay,city,province,region,
mobile_number,birthdate,age,sex,civil_status,
occupation,monthly_income,relationship_to_beneficiary
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

$stmt->bind_param(
"sssssssssssisssis",
$c_last,$c_first,$c_middle,$c_ext,
$c_street,$c_barangay,$c_city,$c_province,$c_region,
$c_mobile,$c_birthdate,$c_age,$c_sex,$c_civil,
$c_occupation,$c_income,$c_relationship
);

$stmt->execute();
$client_id = $stmt->insert_id;
$stmt->close();

$conn->query("UPDATE beneficiaries SET client_id=$client_id WHERE beneficiary_id=$beneficiary_id");

}

}

/* =========================
   BENEFICIARY UPDATE
========================= */

$b_last = $_POST['b_last'] ?? '';
$b_first = $_POST['b_first'] ?? '';
$b_middle = $_POST['b_middle'] ?? '';
$b_ext = $_POST['b_ext'] ?? '';
$b_street = $_POST['b_street'] ?? '';
$b_barangay = $_POST['b_barangay'] ?? '';
$b_city = $_POST['b_city'] ?? '';
$b_province = $_POST['b_province'] ?? '';
$b_region = $_POST['b_region'] ?? '';
$b_mobile = $_POST['b_mobile'] ?? '';
$b_birthdate = $_POST['b_birthdate'] ?? null;
$b_age = $_POST['b_age'] ?? null;
$b_sex = $_POST['b_sex'] ?? '';
$b_civil = $_POST['b_civil'] ?? '';
$b_occupation = $_POST['b_occupation'] ?? '';
$b_income = $_POST['b_income'] ?? 0;

$stmt=$conn->prepare("UPDATE beneficiaries SET
last_name=?,first_name=?,middle_name=?,ext=?,
street_address=?,barangay=?,city=?,province=?,region=?,
mobile_number=?,birthdate=?,age=?,sex=?,civil_status=?,
occupation=?,monthly_income=?
WHERE beneficiary_id=?");

$stmt->bind_param(
"sssssssssssisssii",
$b_last,$b_first,$b_middle,$b_ext,
$b_street,$b_barangay,$b_city,$b_province,$b_region,
$b_mobile,$b_birthdate,$b_age,$b_sex,$b_civil,
$b_occupation,$b_income,$beneficiary_id
);

$stmt->execute();
$stmt->close();

/* =========================
   FAMILY COMPOSITION
========================= */

$conn->query("DELETE FROM family_composition WHERE beneficiary_id=$beneficiary_id");

if(!empty($_POST['fam_name'])){

$names=$_POST['fam_name'];
$relations=$_POST['fam_relation'];
$ages=$_POST['fam_age'];
$occupations=$_POST['fam_occupation'];
$incomes=$_POST['fam_income'];

foreach($names as $i=>$name){

$name = trim($name);
if($name=="") continue;

$relation = $relations[$i] ?? '';
$age = (int)($ages[$i] ?? 0);
$occupation = $occupations[$i] ?? '';
$income = (float)($incomes[$i] ?? 0);

$stmt=$conn->prepare("INSERT INTO family_composition(
beneficiary_id,full_name,relationship_to_beneficiary,
age,occupation,monthly_income
) VALUES (?,?,?,?,?,?)");

$stmt->bind_param(
"issisd",
$beneficiary_id,$name,$relation,$age,$occupation,$income
);

$stmt->execute();
$stmt->close();

}

}

/* =========================
   GENERAL INTAKE
========================= */

$aics = isset($_POST['aics']) ? 1 : 0;
$akap = isset($_POST['akap']) ? 1 : 0;
$others = $_POST['others_program'] ?? '';
$visit = $_POST['visit_type'] ?? '';
$source = $_POST['client_source'] ?? '';
$date = $_POST['intake_date'] ?? null;
$purpose = $_POST['purpose'] ?? '';
$amount = $_POST['amount'] ?? 0;

$conn->query("DELETE FROM general_intake WHERE beneficiary_id=$beneficiary_id");

$stmt=$conn->prepare("INSERT INTO general_intake(
beneficiary_id,aics,akap,others_program,
visit_type,client_source,intake_date,
purpose_of_assistance,amount_needed
) VALUES (?,?,?,?,?,?,?,?,?)");

$stmt->bind_param(
"iiisssssd",
$beneficiary_id,$aics,$akap,$others,
$visit,$source,$date,$purpose,$amount
);

$stmt->execute();
$stmt->close();

/* =========================
   SECTORS
========================= */

$conn->query("DELETE FROM beneficiary_sectors WHERE beneficiary_id=$beneficiary_id");

if(!empty($_POST['sector'])){

foreach($_POST['sector'] as $sector){

$stmt=$conn->prepare("INSERT INTO beneficiary_sectors(
beneficiary_id,sector_name
) VALUES (?,?)");

$stmt->bind_param("is",$beneficiary_id,$sector);
$stmt->execute();
$stmt->close();

}

}

/* =========================
   SUBCATEGORIES
========================= */

$conn->query("DELETE FROM beneficiary_subcategories WHERE beneficiary_id=$beneficiary_id");

/* normal subcategories */

if(!empty($_POST['subcategory'])){

foreach($_POST['subcategory'] as $sub){

$stmt=$conn->prepare("INSERT INTO beneficiary_subcategories(
beneficiary_id,subcategory_name
) VALUES (?,?)");

$stmt->bind_param("is",$beneficiary_id,$sub);
$stmt->execute();
$stmt->close();

}

}

/* INDIGENOUS PEOPLE */

if(isset($_POST['indigenous_check']) && !empty($_POST['indigenous_group'])){

$tribe = trim($_POST['indigenous_group']);

$sub = "Indigenous: ".$tribe;

$stmt=$conn->prepare("INSERT INTO beneficiary_subcategories(
beneficiary_id,subcategory_name
) VALUES (?,?)");

$stmt->bind_param("is",$beneficiary_id,$sub);
$stmt->execute();
$stmt->close();

}

/* =========================
   DSWD ASSISTANCE
========================= */

$conn->query("DELETE FROM dswd_assistance WHERE beneficiary_id=$beneficiary_id");

if(!empty($_POST['assist_type'])){

$types = $_POST['assist_type'];
$dates = $_POST['assist_date'];

foreach($types as $i=>$type){

$type = trim($type);
if($type=="") continue;

$date = $dates[$i] ?? null;

$stmt=$conn->prepare("INSERT INTO dswd_assistance(
beneficiary_id,assistance_type,date_received
) VALUES (?,?,?)");

$stmt->bind_param("iss",$beneficiary_id,$type,$date);

$stmt->execute();
$stmt->close();

}

}

/* REDIRECT */

header("Location: view_beneficiary_info.php?id=".$beneficiary_id);
exit();
?>