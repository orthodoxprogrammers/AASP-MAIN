<?php
include 'config/db.php';

header('Content-Type: application/json');

/* GET INPUT */

$last  = trim($_POST['last'] ?? '');
$first = trim($_POST['first'] ?? '');

if($last === '' || $first === ''){
    echo json_encode([]);
    exit();
}

/*
  SEARCH POSSIBLE DUPLICATES
  - partial match
  - case insensitive
  - limit results for speed
*/

$stmt = $conn->prepare("
SELECT 
    CONCAT(last_name,' ',first_name,' ',IFNULL(middle_name,''),' ',IFNULL(ext,'')) AS full_name,
    barangay,
    age,
    birthdate
FROM beneficiaries
WHERE 
    last_name LIKE CONCAT(?, '%')
    AND first_name LIKE CONCAT(?, '%')
ORDER BY last_name, first_name
LIMIT 5
");

$stmt->bind_param("ss",$last,$first);
$stmt->execute();

$result = $stmt->get_result();

$data = [];

while($row = $result->fetch_assoc()){

    /* format birthdate nicely */
    if(!empty($row['birthdate'])){
        $row['birthdate'] = date("F d, Y", strtotime($row['birthdate']));
    }

    $data[] = $row;
}

echo json_encode($data);
?>