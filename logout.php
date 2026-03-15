<?php

session_start();

$_SESSION = []; // Clear session variables
session_destroy(); // Destroy the session

header("Location: login.php");
exit;