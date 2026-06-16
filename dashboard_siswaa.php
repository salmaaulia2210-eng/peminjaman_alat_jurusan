<?php
session_start();
include 'koneksii.php';

if (!isset($_SESSION['id_siswa'])) {
    header("Location: login_siswaa.php");
    exit();
}

$id_siswa   = $_SESSION['id_siswa'];
$nama_siswa = $_SESSION['nama_siswa'];
$kelas      = $_SESSION['kelas'];
$nis        = $_SESSION['nis'];

$inisial = '';
$pecah = explode(' ', $nama_siswa);
foreach ($pecah as $k) $inisial .= strtoupper(substr($k, 0, 1));
$inisial = substr($inisial, 0, 2);

$q_dipinjam = mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE id_siswa='$id_siswa' AND status='dipinjam'");
$dipinjam   = mysqli_fetch_assoc($q_dipinjam)['total'];

$q_menunggu = mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE id_siswa='$id_siswa' AND status='pending'");
$menunggu   = mysqli_fetch_assoc($q_menunggu)['total'];

$q_selesai  = mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE id_siswa='$id_siswa' AND status='dikembalikan'");
$selesai    = mysqli_fetch_assoc($q_selesai)['total'];

$q_denda    = mysqli_query($conn, "SELECT SUM(denda) as total FROM peminjaman WHERE id_siswa='$id_siswa'");
$total_denda = mysqli_fetch_assoc($q_denda)['total'] ?? 0;

$q_aktif = mysqli_query($conn, "
    SELECT p.*, b.nama_barang, b.kode_barang, j.kode_jurusan
    FROM peminjaman p
    JOIN barang b ON p.id_barang = b.id_barang
    JOIN jurusan j ON b.id_jurusan = j.id_jurusan
    WHERE p.id_siswa='$id_siswa' AND p.status IN ('dipinjam','pending')
    ORDER BY p.tgl_pinjam DESC
");

$q_terlambat = mysqli_query($conn, "
    SELECT p.*, b.nama_barang
    FROM peminjaman p
    JOIN barang b ON p.id_barang = b.id_barang
    WHERE p.id_siswa='$id_siswa' 
    AND p.status='dipinjam' 
    AND p.tgl_kembali_seharusnya < NOW()
");
$jml_terlambat = mysqli_num_rows($q_terlambat);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Siswa - Peminjaman Alat</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; background: #f5f5f5; color: #e5e7ef; }

.topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-bottom: 1px solid #e0e0e0;
    background: #182e4a; position: fixed; top: 0; left: 0; right: 0; z-index: 100;
}
.logo { font-size: 15px; font-weight: 700; }
.logo span { color: #eeecf7; }
.user-info { display: flex; align-items: center; gap: 10px; }
.avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: #E1F5EE; display: flex; align-items: center;
    justify-content: center; font-size: 13px; font-weight: 700; color: #232f53;
}
.user-name { font-size: 13px; color: #ffffff; } /* putih biar keliatan */
.notif-btn {
    background: none; border: 1px solid #2a4a6b; border-radius: 8px;
    padding: 6px 8px; cursor: pointer; color: #8ab0d0; position: relative; text-decoration: none;
    display: inline-flex; align-items: center;
}
.notif-dot {
    width: 7px; height: 7px; background: #D85A30;
    border-radius: 50%; position: absolute; top: 4px; right: 4px;
}

.sidebar {
    position: fixed; left: 0; top: 57px; bottom: 0; width: 200px;
    border-right: 1px solid #1a2d42; background: #0d1b2a;
    padding: 16px 0; overflow-y: auto; z-index: 99;
}
.nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 18px; cursor: pointer; font-size: 13px;
    color: #8ab0d0; text-decoration: none; transition: background 0.15s;

}
.nav-item:hover { background: #275ede; }
.nav-item.active { color: #939bf3; background: #1d1f1f; font-weight: 700; border-right: 2px solid #1d289e; }
.nav-item i { font-size: 16px; }
.nav-section { font-size: 11px; color: #aaa; padding: 14px 18px 4px; text-transform: uppercase; letter-spacing: .5px; }

.main { margin-left: 200px; padding: 20px; margin-top: 57px; background: #0d1b2a; } /* ← tambah background disini */
.page-title { font-size: 18px; font-weight: 700; margin-bottom: 4px; color: #ffffff; } /* ← teks jadi putih */
.page-sub { font-size: 13px; color: #8ab0d0; margin-bottom: 20px; } /* ← teks sub jadi biru muda */

.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
.stat-card { background: #1a2d42; border-radius: 8px; padding: 14px 16px; } /* ← card jadi biru gelap */
.stat-label { font-size: 12px; color: #8ab0d0; margin-bottom: 6px; } /* ← label biru muda */
.stat-val { font-size: 22px; font-weight: 700; }
.stat-val.blue { color: #185FA5; }
.stat-val.amber { color: #854F0B; }
.stat-val.red { color: #A32D2D; }
.stat-val.green { color: #0F6E56; }

.card { background: #1a2d42; border: 1px solid #2a4a6b; border-radius: 12px; padding: 16px; margin-bottom: 14px; }
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
.section-title { font-size: 14px; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; color: #ffffff; }

.notif-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 1px solid #2a4a6b; }
.notif-item:last-child { border-bottom: none; }
.notif-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 15px; }
.notif-icon.warn { background: #3d2e0f; color: #FAC775; }
.notif-icon.ok   { background: #0f2d1f; color: #5DCAA5; }
.notif-icon.err  { background: #2d0f0f; color: #F09595; }
.notif-text { font-size: 13px; line-height: 1.4; color: #ffffff; }
.notif-time { font-size: 11px; color: #8ab0d0; margin-top: 2px; }

.borrow-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #2a4a6b; }
.borrow-item:last-child { border-bottom: none; }
.alat-icon { width: 38px; height: 38px; border-radius: 8px; background: #1a3a5c; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #8ab0d0; flex-shrink: 0; }
.borrow-info { flex: 1; }
.borrow-name { font-size: 13px; font-weight: 700; color: #ffffff; }
.borrow-meta { font-size: 12px; color: #8ab0d0; margin-top: 2px; }

.badge { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 700; }
.badge.dipinjam  { background: #1a3a5c; color: #8ab0d0; }
.badge.menunggu  { background: #3d2e0f; color: #FAC775; }
.badge.terlambat { background: #2d0f0f; color: #F09595; }
.badge.selesai   { background: #0f2d1f; color: #5DCAA5; }
.badge.ditolak   { background: #2d0f0f; color: #F09595; }

.alert-warn {
    background: #FAEEDA; border: 1px solid #FAC775; border-radius: 8px;
    padding: 12px 16px; font-size: 13px; color: #633806; margin-bottom: 14px;
    display: flex; align-items: center; gap: 8px;
}

.btn-logout {
    background: none; border: 1px solid #e0e0e0; border-radius: 8px;
    padding: 6px 12px; font-size: 12px; color: #888; cursor: pointer; text-decoration: none;
}
.btn-logout:hover { background: #FCEBEB; color: #A32D2D; border-color: #A32D2D; }
</style>
</head>
<body>

<div class="topbar">
    <div class="logo"><span>Pinjam</span>Alat · SMK</div>
    <div class="user-info">
        <?php if ($jml_terlambat > 0): ?>
        <a class="notif-btn" href="#notif">
            <i class="ti ti-bell"></i>
            <div class="notif-dot"></div>
        </a>
        <?php endif; ?>
        <span class="user-name"><?php echo $nama_siswa; ?> · <?php echo $kelas; ?></span>
        <div class="avatar"><?php echo $inisial; ?></div>
        <a href="logout_siswaa.php" class="btn-logout"><i class="ti ti-logout"></i> Keluar</a>
    </div>
</div>

<div class="sidebar">
    <div class="nav-section">Menu</div>
    <a class="nav-item active" href="dashboard_siswaa.php"><i class="ti ti-home"></i> Dashboard</a>
    <a class="nav-item" href="cari_alatt.php"><i class="ti ti-search"></i> Cari Alat</a>
    <a class="nav-item" href="peminjaman_siswaa.php"><i class="ti ti-package"></i> Peminjaman</a>
    <a class="nav-item" href="pengembalian_siswaa.php"><i class="ti ti-arrow-back"></i> Pengembalian</a>
    <div class="nav-section">Informasi</div>
    <a class="nav-item" href="denda_siswaa.php"><i class="ti ti-receipt"></i> Denda</a>
    <a class="nav-item" href="riwayat_siswaa.php"><i class="ti ti-history"></i> Riwayat</a>
    <div class="nav-section">Akun</div>
    <a class="nav-item" href="profil_siswaa.php"><i class="ti ti-user"></i> Profil</a>
</div>

<div class="main">
    <div class="page-title">Dashboard Siswa</div>
    <div class="page-sub">Selamat datang, <?php echo $nama_siswa; ?>! Berikut ringkasan aktivitas peminjaman alat kamu.</div>

    <?php if ($jml_terlambat > 0): ?>
    <div class="alert-warn" id="notif">
        <i class="ti ti-alert-triangle"></i>
        Kamu memiliki <b><?php echo $jml_terlambat; ?> alat</b> yang terlambat dikembalikan! Segera kembalikan ke admin.
    </div>
    <?php endif; ?>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Sedang Dipinjam</div>
            <div class="stat-val blue"><?php echo $dipinjam; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Menunggu Persetujuan</div>
            <div class="stat-val amber"><?php echo $menunggu; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Denda</div>
            <div class="stat-val red">Rp <?php echo number_format($total_denda, 0, ',', '.'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pinjaman Selesai</div>
            <div class="stat-val green"><?php echo $selesai; ?></div>
        </div>
    </div>

    <div class="two-col">
        <div class="card">
            <div class="section-title"><i class="ti ti-bell" style="color:#854F0B"></i> Notifikasi Pengembalian</div>
            <?php
            mysqli_data_seek($q_terlambat, 0);
            if (mysqli_num_rows($q_terlambat) > 0):
                while ($row = mysqli_fetch_assoc($q_terlambat)):
                $selisih = (strtotime(date('Y-m-d')) - strtotime($row['tgl_kembali_seharusnya']));
                $hari    = round($selisih / 86400);
            ?>
            <div class="notif-item">
                <div class="notif-icon err"><i class="ti ti-alert-triangle"></i></div>
                <div>
                    <div class="notif-text"><b><?php echo $row['nama_barang']; ?></b> terlambat <?php echo $hari; ?> hari</div>
                    <div class="notif-time">Batas: <?php echo date('d M Y', strtotime($row['tgl_kembali_seharusnya'])); ?></div>
                </div>
            </div>
            <?php endwhile; else: ?>
            <div class="notif-item">
                <div class="notif-icon ok"><i class="ti ti-check"></i></div>
                <div><div class="notif-text">Tidak ada keterlambatan</div></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="section-title"><i class="ti ti-user" style="color:#185FA5"></i> Info Akun</div>
            <div style="font-size:13px; line-height:2; color:#ffffff;">
    <div><span style="color:#8ab0d0; width:80px; display:inline-block;">NIS</span> <b><?php echo $nis; ?></b></div>
    <div><span style="color:#8ab0d0; width:80px; display:inline-block;">Nama</span> <b><?php echo $nama_siswa; ?></b></div>
    <div><span style="color:#8ab0d0; width:80px; display:inline-block;">Kelas</span> <b><?php echo $kelas; ?></b></div>
    <div><span style="color:#8ab0d0; width:80px; display:inline-block;">Jurusan</span> <b><?php echo $_SESSION['nama_jurusan'] ?? '-'; ?></b></div>
</div>
        </div>
    </div>

    <div class="card">
        <div class="section-title"><i class="ti ti-package" style="color:#185FA5"></i> Alat yang Sedang Dipinjam / Menunggu</div>
        <?php if (mysqli_num_rows($q_aktif) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($q_aktif)):
            $terlambat = ($row['status'] == 'dipinjam' && strtotime($row['tgl_kembali_seharusnya']) < time());
            $badge = $terlambat ? 'terlambat' : $row['status'];
            $label = $terlambat ? 'Terlambat' : ucfirst($row['status']);
        ?>
        <div class="borrow-item">
            <div class="alat-icon"><i class="ti ti-tool"></i></div>
            <div class="borrow-info">
                <div class="borrow-name"><?php echo $row['nama_barang']; ?></div>
                <div class="borrow-meta">
                    <?php echo $row['kode_jurusan']; ?> ·
                    Dipinjam: <?php echo date('d M Y', strtotime($row['tgl_pinjam'])); ?> ·
                    Kembali: <?php echo date('d M Y', strtotime($row['tgl_kembali_seharusnya'])); ?>
                </div>
            </div>
            <span class="badge <?php echo $badge; ?>"><?php echo $label; ?></span>
        </div>
        <?php endwhile; ?>
        <?php else: ?>
        <div style="font-size:13px; color:#888; text-align:center; padding:20px 0;">
            Tidak ada alat yang sedang dipinjam
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>