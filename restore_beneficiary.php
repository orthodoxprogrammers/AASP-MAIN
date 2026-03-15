<?php
session_start();
include __DIR__.'/config/db.php';

/* RBAC */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'MSWDO') {
    die("Access Denied");
}

if(!isset($_GET['id'])){
    die("Invalid request.");
}

$id=(int)$_GET['id'];

$stmt=$conn->prepare("
UPDATE beneficiaries
SET
status='active',
archived_reason=NULL,
archived_at=NULL,
archived_by=NULL
WHERE beneficiary_id=?
");

$stmt->bind_param("i",$id);

$stmt->execute();

$stmt->close();

header("Location: archived_beneficiaries.php");
exit();