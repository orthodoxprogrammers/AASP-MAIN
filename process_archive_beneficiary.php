<?php
session_start();
include __DIR__.'/config/db.php';

/* RBAC */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'MSWDO') {
    die("Access Denied");
}

if(!isset($_POST['beneficiary_id'])){
    die("Invalid request.");
}

$beneficiary_id=(int)$_POST['beneficiary_id'];
$reason=trim($_POST['reason']);
$user_id=$_SESSION['user_id'];

$stmt=$conn->prepare("
UPDATE beneficiaries
SET
status='archived',
archived_reason=?,
archived_at=NOW(),
archived_by=?
WHERE beneficiary_id=?
");

$stmt->bind_param("sii",$reason,$user_id,$beneficiary_id);

$stmt->execute();

$stmt->close();

header("Location: beneficiary.php");
exit();