<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayuda Prioritization System - Login</title>

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap-icons-1.11.0/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">

    <style>
        body{
            height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            background:#f4f6f9;
            font-family:'Segoe UI',sans-serif;
        }
        .mainContainer{
            display:flex;
            flex-direction:column;
            align-items:center;
            gap:20px;
        }
        .login-card{
            width:360px;
            padding:60px 35px 35px;
            background:white;
            border-radius:12px;
            box-shadow:0 8px 25px rgba(0,0,0,0.2);
            position:relative;
        }

        .logo-container{
            position:absolute;
            top:-50px;
            left:50%;
            transform:translateX(-50%);
            background:white;
            border-radius:50%;
            padding:5px;
        }

        .logo-container img{
            height:100px;
            width:100px;
        }

        .login-card h3{
            text-align:center;
            margin-bottom:25px;
        }
        .btn-login{
            width:100%;
            background: #2563eb;
            border:none;
            border-radius:8px;
            padding:10px;
            color:white;
        }
        .btn-login:hover{
            background:#1d4ed8;
        }
        .system-title{
            text-align:center;
            margin-bottom:10px;
            font-size:14px;
            color:#555;
        }
    </style>
</head>

<body>
    <div class="mainContainer">
    
        <div class="login-card">
            <div class="logo-container">
                <img src="assets/mswdo_lidlidda_logo.png">
            </div>
            
            <p class="system-title">Automated Ayuda Prioritization System</p>

            <?php
            if(isset($_SESSION['error'])){
                echo "<div class='alert alert-danger'>".$_SESSION['error']."</div>";
                unset($_SESSION['error']);
            }
            ?>

            <form action="auth.php" method="POST" autocomplete="off">
                <div class="mb-3">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
            </form>
        </div>
    </div>
</body>
</html>