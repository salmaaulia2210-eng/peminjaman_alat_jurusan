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

$inisial = '';
foreach (explode(' ', $nama_siswa) as $k) $inisial .= strtoupper(substr($k, 0, 1));
$inisial = substr($inisial, 0, 2);

$q_terlambat = mysqli_query($conn, "
    SELECT * FROM peminjaman 
    WHERE id_siswa='$id_siswa' AND status='dipinjam' 
    AND tgl_kembali_seharusnya < NOW()
");
while ($row = mysqli_fetch_assoc($q_terlambat)) {
    $hari = max(1, round((time() - strtotime($row['tgl_kembali_seharusnya'])) / 86400));
    $denda = $hari * 10000;
    mysqli_query($conn, "
        UPDATE peminjaman SET denda='$denda', status='terlambat'
        WHERE id_peminjaman='{$row['id_peminjaman']}'
    ");
}

$q_total = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(denda) as total FROM peminjaman WHERE id_siswa='$id_siswa'
"));
$total_denda = $q_total['total'] ?? 0;

$q_belum_bayar = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(denda) as total FROM peminjaman 
    WHERE id_siswa='$id_siswa' AND denda > 0 AND status != 'dikembalikan'
"));
$denda_belum = $q_belum_bayar['total'] ?? 0;

$q_lunas = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(denda) as total FROM peminjaman 
    WHERE id_siswa='$id_siswa' AND denda > 0 AND status = 'dikembalikan'
"));
$denda_lunas = $q_lunas['total'] ?? 0;

$q_denda = mysqli_query($conn, "
    SELECT p.*, b.nama_barang, b.kode_barang, j.kode_jurusan
    FROM peminjaman p
    JOIN barang b ON p.id_barang = b.id_barang
    JOIN jurusan j ON b.id_jurusan = j.id_jurusan
    WHERE p.id_siswa='$id_siswa' AND p.denda > 0
    ORDER BY p.tgl_pinjam DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Denda - PinjamAlat SMK</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; background: #0d1b2a; color: #222; }
.topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-bottom: 1px solid #e0e0e0;
    background: #1a2d42; position: fixed; top: 0; left: 0; right: 0; z-index: 100;
}
.logo { font-size: 15px; font-weight: 700; color: #fff; }
.logo span { color: #eeecf7; }
.user-info { display: flex; align-items: center; gap: 10px; }
.avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: #E1F5EE; display: flex; align-items: center;
    justify-content: center; font-size: 13px; font-weight: 700; color: #0f1f6e;
}
.user-name { font-size: 13px; color: #fff; }
.btn-logout {
    background: none; border: 1px solid #e0e0e0; border-radius: 8px;
    padding: 6px 12px; font-size: 12px; color: #888; cursor: pointer; text-decoration: none;
}
.btn-logout:hover { background: #FCEBEB; color: #A32D2D; border-color: #A32D2D; }
.sidebar {
    position: fixed; left: 0; top: 57px; bottom: 0; width: 200px;
    border-right: 1px solid #1a2d42; background: #0d1b2a;
    padding: 16px 0; overflow-y: auto; z-index: 99;
}
.nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 18px; font-size: 13px;
    color: #8ab0d0; text-decoration: none; transition: background 0.15s;
}
.nav-item:hover { background: #275ede; }
.nav-item.active { color: #939bf3; background: #1d1f1f; font-weight: 700; border-right: 2px solid #1d289e; }
.nav-item i { font-size: 16px; }
.nav-section { font-size: 11px; color: #aaa; padding: 14px 18px 4px; text-transform: uppercase; letter-spacing: .5px; }
.main { margin-left: 200px; padding: 20px; margin-top: 57px; background: #0d1b2a; min-height: 100vh; }
.page-title { font-size: 18px; font-weight: 700; margin-bottom: 4px; color: #fff; }
.page-sub { font-size: 13px; color: #8ab0d0; margin-bottom: 20px; }
.card { background: #1a2d42; border: 1px solid #2a4a6b; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
.section-title { font-size: 14px; font-weight: 700; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; color: #fff; }

.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 16px; }
.stat-card { background: #1a2d42; border: 1px solid #2a4a6b; border-radius: 10px; padding: 14px 16px; }
.stat-label { font-size: 12px; color: #8ab0d0; margin-bottom: 6px; }
.stat-val { font-size: 20px; font-weight: 700; }
.stat-val.red   { color: #F09595; }
.stat-val.amber { color: #FAC775; }
.stat-val.green { color: #5DCAA5; }

.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th { background: #0d1b2a; color: #8ab0d0; padding: 10px 12px; text-align: left; font-weight: 600; }
td { padding: 10px 12px; border-bottom: 1px solid #2a4a6b; color: #fff; }
tr:last-child td { border-bottom: none; }

.badge { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 700; }
.badge.belum  { background: #2d0f0f; color: #F09595; }
.badge.lunas  { background: #0f2d1f; color: #5DCAA5; }

.empty-state { text-align: center; padding: 30px; color: #8ab0d0; font-size: 13px; }

.info-box {
    background: #3d2e0f; border: 1px solid #FAC775; border-radius: 8px;
    padding: 12px 16px; font-size: 13px; color: #FAC775;
    display: flex; align-items: center; gap: 8px; margin-bottom: 16px;
}
</style>
</head>
<body>

<div class="topbar">
    <div class="logo"><span>Pinjam</span>Alat · SMK</div>
    <div class="user-info">
        <span class="user-name"><?php echo $nama_siswa; ?> · <?php echo $kelas; ?></span>
        <div class="avatar"><?php echo $inisial; ?></div>
        <a href="logout_siswaa.php" class="btn-logout"><i class="ti ti-logout"></i> Keluar</a>
    </div>
</div>

<div class="sidebar">
    <div class="nav-section">Menu</div>
    <a class="nav-item" href="dashboard_siswaa.php"><i class="ti ti-home"></i> Dashboard</a>
    <a class="nav-item" href="cari_alatt.php"><i class="ti ti-search"></i> Cari Alat</a>
    <a class="nav-item" href="peminjaman_siswaa.php"><i class="ti ti-package"></i> Peminjaman</a>
    <a class="nav-item" href="pengembalian_siswaa.php"><i class="ti ti-arrow-back"></i> Pengembalian</a>
    <div class="nav-section">Informasi</div>
    <a class="nav-item active" href="denda_siswaa.php"><i class="ti ti-receipt"></i> Denda</a>
    <a class="nav-item" href="riwayat_siswaa.php"><i class="ti ti-history"></i> Riwayat</a>
    <div class="nav-section">Akun</div>
    <a class="nav-item" href="profil_siswaa.php"><i class="ti ti-user"></i> Profil</a>
</div>

<div class="main">
    <div class="page-title">Denda Keterlambatan</div>
    <div class="page-sub">Rincian denda keterlambatan pengembalian alat.</div>

    <div class="info-box">
        <i class="ti ti-info-circle"></i>
        Denda keterlambatan: <b>Rp 20.000 / hari</b>. Denda dibayar langsung ke admin.
    </div>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Total Denda</div>
            <div class="stat-val red">Rp <?php echo number_format($total_denda, 0, ',', '.'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Belum Lunas</div>
            <div class="stat-val amber">Rp <?php echo number_format($denda_belum, 0, ',', '.'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Sudah Lunas</div>
            <div class="stat-val green">Rp <?php echo number_format($denda_lunas, 0, ',', '.'); ?></div>
        </div>
    </div>

    <div class="card">
        <div class="section-title"><i class="ti ti-receipt" style="color:#F09595"></i> Rincian Denda</div>
        <?php if (mysqli_num_rows($q_denda) > 0): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Alat</th>
                        <th>Batas Kembali</th>
                        <th>Keterlambatan</th>
                        <th>Denda</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = mysqli_fetch_assoc($q_denda)):
                    $hari = max(1, round((time() - strtotime($row['tgl_kembali_seharusnya'])) / 86400));
                    $lunas = $row['status'] === 'dikembalikan';
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700"><?php echo $row['nama_barang']; ?></div>
                        <div style="font-size:11px;color:#8ab0d0"><?php echo $row['kode_jurusan']; ?></div>
                    </td>
                    <td><?php echo date('d M Y', strtotime($row['tgl_kembali_seharusnya'])); ?></td>
                    <td><?php echo $hari; ?> hari</td>
                    <td style="font-weight:700;color:#F09595">
                        Rp <?php echo number_format($row['denda'], 0, ',', '.'); ?>
                    </td>
                    <td>
                        <span class="badge <?php echo $lunas ? 'lunas' : 'belum'; ?>">
                            <?php echo $lunas ? 'Lunas' : 'Belum Lunas'; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="ti ti-mood-happy" style="font-size:36px; display:block; margin-bottom:8px;"></i>
            Tidak ada denda! Kamu selalu tepat waktu
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>