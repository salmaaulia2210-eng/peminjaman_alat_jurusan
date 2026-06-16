<?php
session_start();
include 'koneksii.php';

if (isset($_SESSION['id_siswa'])) {
    header("Location: dashboard_siswaa.php"); exit;
}

$error = "";

if (isset($_POST['login'])) {
    $nis      = mysqli_real_escape_string($conn, $_POST['nis']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query  = "SELECT s.*, j.nama_jurusan, j.kode_jurusan 
               FROM siswa s 
               LEFT JOIN jurusan j ON s.id_jurusan = j.id_jurusan 
               WHERE s.nis = '$nis'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $siswa = mysqli_fetch_assoc($result);

        if (password_verify($password, $siswa['password']) || $password == $siswa['password']) {
            $_SESSION['id_siswa']     = $siswa['id_siswa'];
            $_SESSION['nis']          = $siswa['nis'];
            $_SESSION['nama_siswa']   = $siswa['nama_siswa'];
            $_SESSION['kelas']        = $siswa['kelas'];
            $_SESSION['id_jurusan']   = $siswa['id_jurusan'];
            $_SESSION['nama_jurusan'] = $siswa['nama_jurusan'];
            $_SESSION['kode_jurusan'] = $siswa['kode_jurusan'];

            header("Location: dashboard_siswaa.php");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "NIS tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Siswa - Peminjaman Alat</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: Arial, sans-serif;
    background: #ece1e1;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    background: url('gambar/bg login.jpg') center center / cover no-repeat;

}
.login-box {
    background: #141414;
    border-radius: 12px;
    padding: 36px 32px;
    width: 100%;
    max-width: 380px;
    border: 1px solid #e0e0e0;
}

.logo {
    text-align: center;
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 6px;
    color: #fcf9f9;
}
.logo span { color: #2e3e84; }
.subtitle {
    text-align: center;
    font-size: 13px;
    color: #888;
    margin-bottom: 28px;
}
.form-group { margin-bottom: 16px; }
.form-group label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}
.form-group input {
    width: 100%;
    padding: 10px 12px;
    font-size: 14px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: #fff;
    color: #0a0a0a;
}
.form-group input:focus {
    outline: none;
    border-color: #484281;
}
.btn-login {
    width: 100%;
    padding: 11px;
    background: #2e3063;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 6px;
}
.btn-login:hover { background: #3d346f; }
.error {
    background: #FCEBEB;
    color: #A32D2D;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 13px;
    margin-bottom: 16px;
    text-align: center;
}
.info {
    margin-top: 20px;
    font-size: 12px;
    color: #aaa;
    text-align: center;
}
</style>
</head>
<body>

<div class="login-box">
    <div class="logo"><span>Pinjam</span>Alat · SMK</div>
    <div class="subtitle">Masuk sebagai Siswa</div>

    <?php if ($error != ""): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
    <div class="form-group">
        <label>NIS</label>
        <input type="text" name="nis" placeholder="Masukkan NIS kamu" required autofocus />
    </div>
    <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan password" required />
    </div>
    <button type="submit" name="login" class="btn-login">Masuk</button>

    <div style="text-align:center; margin-top:14px;">
        <a href="indexx.php" style="font-size:13px; color:#2e3063; text-decoration:none;">
            ← Kembali ke Halaman Utama
        </a>
    </div>
</form>
</body>
</html>