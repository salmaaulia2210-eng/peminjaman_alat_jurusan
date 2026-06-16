<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_adminn.php");
    exit;
}

$hak_akses  = $_SESSION['hak_akses'];
$nama_admin = $_SESSION['nama_admin'];
$id_jurusan = $_SESSION['id_jurusan'];

$halaman = basename($_SERVER['PHP_SELF']);

$titles = [
    'dashboard_adminjurusan.php' => 'Dashboard',
    'data_alat_adminjurusan.php' => 'Data Alat',
    'peminjaman_adminjurusan.php' => 'Peminjaman',
    'pengembalian_adminjurusan.php' => 'Pengembalian',
    'denda_adminjurusan.php' => 'Denda',
    'riwayat_adminjurusan.php' => 'Riwayat',
    'profil_adminjurusan.php' => 'Profil'
];

$page_title = $titles[$halaman] ?? 'Dashboard';
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= $page_title ?></title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Segoe UI',sans-serif;
    background:#0f172a;
    color:white;
    overflow:hidden;
}

.wrapper{
    display:flex;
    height:100vh;
}

.sidebar{
    width:250px;
    background:#111827;
    border-right:1px solid rgba(255,255,255,.05);
    display:flex;
    flex-direction:column;
    position:fixed;
    top:0;
    left:0;
    bottom:0;
}

.sidebar-brand{
    padding:24px 22px;
    border-bottom:1px solid rgba(255,255,255,.05);
}

.brand-title{
    font-size:18px;
    font-weight:700;
    color:white;
}

.brand-sub{
    margin-top:4px;
    color:#94a3b8;
    font-size:12px;
}

.admin-info{
    padding:20px;
    display:flex;
    align-items:center;
    gap:14px;
    border-bottom:1px solid rgba(255,255,255,.05);
}

.admin-avatar{
    width:48px;
    height:48px;
    border-radius:50%;
    background:#2563eb;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
    font-weight:bold;
}

.admin-name{
    font-size:14px;
    font-weight:600;
}

.hakakses-badge{
    display:inline-block;
    margin-top:5px;
    padding:4px 10px;
    border-radius:8px;
    font-size:11px;
    font-weight:700;
}

.hakakses-super{
    background:rgba(251,191,36,.15);
    color:#fbbf24;
}

.hakakses-jurusan{
    background:rgba(59,130,246,.15);
    color:#60a5fa;
}

.sidebar-menu{
    flex:1;
    overflow-y:auto;
    padding:14px 0;
}

.sidebar-menu::-webkit-scrollbar{
    width:5px;
}

.sidebar-menu::-webkit-scrollbar-thumb{
    background:#334155;
    border-radius:10px;
}

.menu-section{
    padding:15px 22px 8px;
    font-size:11px;
    color:#64748b;
    text-transform:uppercase;
    letter-spacing:1px;
    font-weight:700;
}

.menu-item{
    display:flex;
    align-items:center;
    padding:13px 22px;
    color:#cbd5e1;
    text-decoration:none;
    font-size:14px;
    transition:.2s;
    border-left:3px solid transparent;
}

.menu-item:hover{
    background:rgba(255,255,255,.04);
    color:white;
}

.menu-item.active{
    background:rgba(37,99,235,.15);
    border-left:3px solid #2563eb;
    color:white;
    font-weight:700;
}

.sidebar-footer{
    padding:20px;
    border-top:1px solid rgba(255,255,255,.05);
}

.btn-logout{
    display:block;
    width:100%;
    text-align:center;
    padding:12px;
    border-radius:12px;
    text-decoration:none;
    background:rgba(239,68,68,.15);
    color:#f87171;
    font-weight:700;
    transition:.2s;
}

.btn-logout:hover{
    background:rgba(239,68,68,.25);
}

.main{
    margin-left:250px;
    flex:1;
    display:flex;
    flex-direction:column;
    height:100vh;
}

.topbar{
    height:75px;
    min-height:75px;
    background:#111827;
    border-bottom:1px solid rgba(255,255,255,.05);
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 30px;
}

.topbar-title{
    font-size:24px;
    font-weight:700;
}

.topbar-right{
    display:flex;
    align-items:center;
    gap:10px;
    color:#94a3b8;
    font-size:13px;
}

.content{
    flex:1;
    overflow-y:auto;
    padding:25px;
    background:#0f172a;
}

.content::-webkit-scrollbar{
    width:6px;
}

.content::-webkit-scrollbar-thumb{
    background:#334155;
    border-radius:10px;
}

@media(max-width:900px){

    .sidebar{
        width:220px;
    }

    .main{
        margin-left:220px;
    }

}

@media(max-width:700px){

    .sidebar{
        width:200px;
    }

    .main{
        margin-left:200px;
    }

    .topbar-title{
        font-size:20px;
    }

}

</style>

</head>
<body>

<div class="wrapper">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-title">
                Peminjaman Alat
            </div>

            <div class="brand-sub">
                SMKN 1 Kota Cirebon
            </div>
        </div>

        <div class="admin-info">
            <div class="admin-avatar">
                <?= strtoupper(substr($nama_admin,0,1)) ?>
            </div>

            <div>

                <div class="admin-name">
                    <?= htmlspecialchars($nama_admin) ?>
                </div>

                <?php if($hak_akses == 'super_admin'): ?>

                    <span class="hakakses-badge hakakses-super">
                        Super Admin
                    </span>

                <?php else: ?>

                    <span class="hakakses-badge hakakses-jurusan">
                        Admin Jurusan
                    </span>

                <?php endif; ?>
            </div>
        </div>

        <div class="sidebar-menu">
            <div class="menu-section">
                Menu Utama
            </div>

            <a href="dashboard_adminjurusan.php"
               class="menu-item <?= $halaman == 'dashboard_adminjurusan.php' ? 'active' : '' ?>">
               Dashboard
            </a>

            <div class="menu-section">
                Manajemen
            </div>

            <a href="data_alat_adminjurusan.php"
               class="menu-item <?= $halaman == 'data_alat_adminjurusan.php' ? 'active' : '' ?>">
               Data Alat
            </a>

            <a href="peminjaman_adminjurusan.php"
               class="menu-item <?= $halaman == 'peminjaman_adminjurusan.php' ? 'active' : '' ?>">
               Peminjaman
            </a>

            <a href="pengembalian_adminjurusan.php"
               class="menu-item <?= $halaman == 'pengembalian_adminjurusan.php' ? 'active' : '' ?>">
               Pengembalian
            </a>

            <a href="denda_adminjurusan.php"
               class="menu-item <?= $halaman == 'denda_adminjurusan.php' ? 'active' : '' ?>">
               Denda
            </a>

            <a href="riwayat_adminjurusan.php"
               class="menu-item <?= $halaman == 'riwayat_adminjurusan.php' ? 'active' : '' ?>">
               Riwayat
            </a>

            <div class="menu-section">
                Akun
            </div>

            <a href="profil_adminjurusan.php"
               class="menu-item <?= $halaman == 'profil_adminjurusan.php' ? 'active' : '' ?>">
               Profil
            </a>
        </div>

        <div class="sidebar-footer">

            <a href="logout_adminn.php" class="btn-logout">
                Logout
            </a>
        </div>
    </div>

    <div class="main">

        <div class="topbar">
            <div class="topbar-title">
                <?= $page_title ?>
            </div>

            <div class="topbar-right">
                <span>
                    <?= htmlspecialchars($nama_admin) ?>
                </span>

                <span>•</span>

                <span>
                    <?= date('d M Y') ?>
                </span>
            </div>
        </div>
        <!-- CONTENT -->
        <div class="content">