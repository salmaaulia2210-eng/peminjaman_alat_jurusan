<?php
session_start();
if (isset($_SESSION['id_admin'])) {
    header("Location: dashboard_adminn.php"); exit;
}
if (isset($_SESSION['id_siswa'])) {
    header("Location: dashboard_siswaa.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Peminjaman Alat — SMKN 1 Kota Cirebon</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            overflow: hidden;
        }

        .hero {
            position: relative;
            width: 100%;
            height: 100vh;
            background: url('gambar/bg depan.JPG') center center / cover no-repeat;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to bottom,
                rgba(10, 20, 40, 0.72) 0%,
                rgba(10, 20, 40, 0.45) 50%,
                rgba(10, 20, 40, 0.70) 100%
            );
        }

        nav {
            position: absolute;
            top: 0; left: 0; right: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 48px;
            z-index: 10;
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(10px);
            border-radius: 0 0 23px 23px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .nav-brand .logo-circle {
            width: 40px; height: 40px;
            background: #1a56db;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }

        .nav-brand span {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.3px;
        }

        .nav-links {
            display: flex;
            gap: 12px;
        }

        .btn-nav {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 22px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }

        .btn-siswa {
            background: rgba(255,255,255,0.12);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.25);
        }
        .btn-siswa:hover {
            background: rgba(255,255,255,0.22);
        }

        .btn-admin {
            background: #1a56db;
            color: #fff;
            border: 1px solid #1a56db;
        }
        .btn-admin:hover {
            background: #1e40af;
        }

        .hero-content {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 5;
            width: 90%;
            max-width: 800px;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(26, 86, 219, 0.85);
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 16px;
            border-radius: 999px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .hero-content h1 {
            font-size: clamp(36px, 6vw, 72px);
            font-weight: 800;
            color: #ffffff;
            line-height: 1.1;
            letter-spacing: -1px;
            margin-bottom: -40px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.4);
        }

        .hero-content h1 span {
            color: #60a5fa;
        }

        .hero-content p {
            font-size: clamp(15px, 2vw, 20px);
            color: rgba(255,255,255,0.80);
            margin-bottom: 36px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .btn-hero-siswa {
            padding: 13px 32px;
            background: #fff;
            color: #1a56db;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
            border: 2px solid #fff;
        }
        .btn-hero-siswa:hover {
            background: #dbeafe;
        }

        .btn-hero-admin {
            padding: 13px 32px;
            background: #1a56db;
            color: #fff;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
            border: 2px solid #1a56db;
        }
        .btn-hero-admin:hover {
            background: #1e40af;
            border-color: #1e40af;
        }

        .stats-bar {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            background: rgba(10, 20, 40, 0.80);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255,255,255,0.10);
            padding: 20px 48px;
            display: flex;
            justify-content: center;
            gap: 60px;
            z-index: 10;
        }

        .stat-item {
            text-align: center;
        }

        .stat-item .num {
            font-size: 26px;
            font-weight: 800;
            color: #60a5fa;
        }

        .stat-item .lbl {
            font-size: 12px;
            color: rgba(255,255,255,0.55);
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .divider {
            width: 1px;
            background: rgba(255,255,255,0.12);
            align-self: stretch;
        }
    </style>
</head>
<body>

<div class="hero">

    <nav>
        <a href="#" class="nav-brand">
            <span>Peminjaman Jurusan</span>
        </a>
        <div class="nav-links">
            <a href="login_siswaa.php" class="btn-nav btn-siswa">Login Siswa</a>
            <a href="login_adminn.php" class="btn-nav btn-admin">Login Admin</a>
        </div>
    </nav>

    <div class="hero-content">
        <h1>Sistem Peminjaman<br><span>Alat Jurusan</span></h1>
    </div>

    <div class="stats-bar">
        <div class="stat-item">
            <div class="num">10</div>
            <div class="lbl">Jurusan</div>
        </div>
        <div class="divider"></div>
        <div class="stat-item">
            <div class="num">39+</div>
            <div class="lbl">Jenis Alat</div>
        </div>
        <div class="divider"></div>
        <div class="stat-item">
            <div class="num">35+</div>
            <div class="lbl">Siswa Terdaftar</div>
        </div>
        <div class="divider"></div>
        <div class="stat-item">
            <div class="num">12</div>
            <div class="lbl">Admin Aktif</div>
        </div>
    </div>
</div>
</body>
</html>