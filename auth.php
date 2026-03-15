<?php
session_start();
include 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $_SESSION['error'] = "Please enter username and password.";
        header("Location: login.php");
        exit();
    }

    // Get user with role
    $stmt = $conn->prepare("
        SELECT users.user_id, users.username, users.password, users.barangay,
               roles.role_name
        FROM users
        JOIN roles ON users.role_id = roles.role_id
        WHERE users.username = ?
    ");

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['barangay'] = $user['barangay'];

        // Redirect based on role
        if ($user['role_name'] === "MSWDO") {
            header("Location: dashboard.php");
        } else {
            header("Location: barangay/barangay_households.php");
        }

        exit();

    } else {
        $_SESSION['error'] = "Invalid username or password.";
        header("Location: login.php");
        exit();
    }

    $stmt->close();
}
?>