<?php
session_start();
include 'koneksii.php';

$error = "";

if(isset($_POST['login'])){

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($conn,
    "SELECT * FROM admin WHERE username='$username'");

    $data = mysqli_fetch_assoc($query);

    if($data){

        if($password == $data['password']){

            $_SESSION['id_admin']    = $data['id_admin'];
            $_SESSION['nama_admin']  = $data['nama_admin'];
            $_SESSION['hak_akses']   = $data['hak_akses'];
            $_SESSION['id_jurusan']  = $data['id_jurusan'];

            if($data['hak_akses'] == 'super_admin'){

                header("Location: dashboard_superadmin.php");
                exit;

            } else if($data['hak_akses'] == 'admin_jurusan'){

                header("Location: dashboard_adminjurusan.php");
                exit;

            } else {
                $error = "Hak akses tidak valid";
            }

        } else {
            $error = "Password salah";
        }

    } else {
        $error = "Username tidak ditemukan";
    }

}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Admin</title>

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial;
        }

        body{
            height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            background:
            linear-gradient(rgba(0,0,0,0.6),
            rgba(0,0,0,0.6)),
            url('gambar/bg login.jpg');

            background-size:cover;
            background-position:center;
        }

        .login-box{
            width:400px;
            background:rgba(11, 34, 61, 0.85);
            backdrop-filter:blur(10px);
            padding:40px;
            border-radius:20px;
            color:white;
            box-shadow:0 0 20px rgba(0,0,0,0.5);
        }

        h1{
            text-align:center;
            margin-bottom:10px;
        }

        p{
            text-align:center;
            margin-bottom:30px;
        }

        .input-box{
            margin-bottom:20px;
        }

        .input-box label{
            display:block;
            margin-bottom:8px;
        }

        .input-box input{
            width:100%;
            padding:14px;
            border:none;
            border-radius:10px;
            background:#1c3554;
            color:white;
            outline:none;
        }

        button{
            width:100%;
            padding:14px;
            border:none;
            border-radius:10px;
            background:#2d3c8c;
            color:white;
            cursor:pointer;
            transition:.2s;
        }

        button:hover{
            background:#4356c7;
        }

        .error{
            background:#dc2626;
            padding:10px;
            border-radius:8px;
            margin-bottom:20px;
            text-align:center;
        }

        .btn-kembali{
            display:block;
            text-align:center;
            padding:14px;
            border-radius:10px;
            background:#1c3554;
            color:white;
            text-decoration:none;
            transition:.2s;
        }

        .btn-kembali:hover{
            background:#294d79;
        }

    </style>
</head>
<body>

<div class="login-box">

    <h1>PinjamAlat SMK</h1>
    <p>Login Admin</p>

    <?php if($error != "") { ?>

        <div class="error">
            <?= $error; ?>
        </div>

    <?php } ?>

    <form method="POST">

        <div class="input-box">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>

        <div class="input-box">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" name="login">
            Masuk
        </button>

        <div style="text-align:center; margin-top:15px;">
            <a href="indexx.php"
                style="color:#93c5fd; text-decoration:none; font-size:14px;">
                ← Kembali ke Halaman Utama
            </a>
        </div>
    </form>
</div>
</body>
</html>