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

$filter_status = $_GET['status'] ?? '';
$filter_bulan  = $_GET['bulan'] ?? '';

$where = ["p.id_siswa='$id_siswa'"];
if ($filter_status) $where[] = "p.status='$filter_status'";
if ($filter_bulan)  $where[] = "DATE_FORMAT(p.tgl_pinjam, '%Y-%m') = '$filter_bulan'";

$where_str = implode(' AND ', $where);

$q_riwayat = mysqli_query($conn, "
    SELECT p.*, b.nama_barang, b.kode_barang, j.kode_jurusan
    FROM peminjaman p
    JOIN barang b ON p.id_barang = b.id_barang
    JOIN jurusan j ON b.id_jurusan = j.id_jurusan
    WHERE $where_str
    ORDER BY p.tgl_pinjam DESC
");

// Statistik
$q_stat = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status='dikembalikan' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status IN ('dipinjam','terlambat') THEN 1 ELSE 0 END) as aktif,
        SUM(CASE WHEN status='terlambat' THEN 1 ELSE 0 END) as terlambat,
        SUM(denda) as total_denda
    FROM peminjaman WHERE id_siswa='$id_siswa'
"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat - PinjamAlat SMK</title>
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

.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 16px; }
.stat-card { background: #1a2d42; border: 1px solid #2a4a6b; border-radius: 10px; padding: 14px 16px; }
.stat-label { font-size: 12px; color: #8ab0d0; margin-bottom: 6px; }
.stat-val { font-size: 20px; font-weight: 700; }
.stat-val.blue  { color: #8ab0d0; }
.stat-val.green { color: #5DCAA5; }
.stat-val.amber { color: #FAC775; }
.stat-val.red   { color: #F09595; }

/* FILTER */
.filter-box {
    background: #1a2d42; border: 1px solid #2a4a6b; border-radius: 12px;
    padding: 14px 16px; margin-bottom: 16px;
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
}
.filter-box select {
    background: #0d1b2a; border: 1px solid #2a4a6b; border-radius: 8px;
    padding: 7px 12px; font-size: 13px; color: #fff; outline: none;
}
.filter-box select option { background: #0d1b2a; }
.btn-filter {
    background: #185FA5; color: #fff; border: none; border-radius: 8px;
    padding: 7px 16px; font-size: 13px; cursor: pointer; font-weight: 700;
}
.btn-reset {
    background: none; color: #8ab0d0; border: 1px solid #2a4a6b; border-radius: 8px;
    padding: 7px 14px; font-size: 13px; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 4px;
}

/* TABLE */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th { background: #0d1b2a; color: #8ab0d0; padding: 10px 12px; text-align: left; font-weight: 600; }
td { padding: 10px 12px; border-bottom: 1px solid #2a4a6b; color: #fff; }
tr:last-child td { border-bottom: none; }

.badge { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 700; }
.badge.dipinjam    { background: #1a3a5c; color: #8ab0d0; }
.badge.pending     { background: #3d2e0f; color: #FAC775; }
.badge.terlambat   { background: #2d0f0f; color: #F09595; }
.badge.dikembalikan { background: #0f2d1f; color: #5DCAA5; }

.empty-state { text-align: center; padding: 30px; color: #8ab0d0; font-size: 13px; }
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
    <a class="nav-item" href="denda_siswaa.php"><i class="ti ti-receipt"></i> Denda</a>
    <a class="nav-item active" href="riwayat_siswaa.php"><i class="ti ti-history"></i> Riwayat</a>
    <div class="nav-section">Akun</div>
    <a class="nav-item" href="profil_siswaa.php"><i class="ti ti-user"></i> Profil</a>
</div>

<div class="main">
    <div class="page-title">Riwayat Peminjaman</div>
    <div class="page-sub">Semua histori peminjaman alat kamu.</div>

    <!-- STATISTIK -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Total Peminjaman</div>
            <div class="stat-val blue"><?php echo $q_stat['total']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Selesai</div>
            <div class="stat-val green"><?php echo $q_stat['selesai']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Masih Aktif</div>
            <div class="stat-val amber"><?php echo $q_stat['aktif']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pernah Terlambat</div>
            <div class="stat-val red"><?php echo $q_stat['terlambat']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Denda</div>
            <div class="stat-val red">Rp <?php echo number_format($q_stat['total_denda'] ?? 0, 0, ',', '.'); ?></div>
        </div>
    </div>

    <!-- FILTER -->
    <form method="GET">
        <div class="filter-box">
            <select name="status">
                <option value="">Semua Status</option>
                <option value="pending"      <?php echo $filter_status === 'pending'      ? 'selected' : ''; ?>>Pending</option>
                <option value="dipinjam"     <?php echo $filter_status === 'dipinjam'     ? 'selected' : ''; ?>>Dipinjam</option>
                <option value="terlambat"    <?php echo $filter_status === 'terlambat'    ? 'selected' : ''; ?>>Terlambat</option>
                <option value="dikembalikan" <?php echo $filter_status === 'dikembalikan' ? 'selected' : ''; ?>>Dikembalikan</option>
            </select>
            <input type="month" name="bulan" value="<?php echo $filter_bulan; ?>"
                style="background:#0d1b2a;border:1px solid #2a4a6b;border-radius:8px;padding:7px 12px;font-size:13px;color:#fff;outline:none;">
            <button type="submit" class="btn-filter"><i class="ti ti-filter"></i> Filter</button>
            <a href="riwayat_siswaa.php" class="btn-reset"><i class="ti ti-x"></i> Reset</a>
        </div>
    </form>

    <!-- TABEL RIWAYAT -->
    <div class="card">
        <div class="section-title"><i class="ti ti-history" style="color:#185FA5"></i> 
            Ditemukan <?php echo mysqli_num_rows($q_riwayat); ?> data
        </div>
        <?php if (mysqli_num_rows($q_riwayat) > 0): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Alat</th>
                        <th>Jumlah</th>
                        <th>Tgl Pinjam</th>
                        <th>Batas Kembali</th>
                        <th>Tgl Kembali</th>
                        <th>Status</th>
                        <th>Denda</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = mysqli_fetch_assoc($q_riwayat)): ?>
                <tr>
                    <td>
                        <div style="font-weight:700"><?php echo $row['nama_barang']; ?></div>
                        <div style="font-size:11px;color:#8ab0d0"><?php echo $row['kode_jurusan']; ?> · <?php echo $row['kode_barang']; ?></div>
                    </td>
                    <td><?php echo $row['jumlah_pinjam']; ?></td>
                    <td><?php echo date('d M Y', strtotime($row['tgl_pinjam'])); ?></td>
                    <td><?php echo date('d M Y', strtotime($row['tgl_kembali_seharusnya'])); ?></td>
                    <td>
                        <?php echo $row['tgl_kembali'] ? date('d M Y', strtotime($row['tgl_kembali'])) : '-'; ?>
                    </td>
                    <td><span class="badge <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                    <td>
                        <?php echo $row['denda'] > 0 
                            ? '<span style="color:#F09595;font-weight:700">Rp '.number_format($row['denda'],0,',','.').'</span>' 
                            : '-'; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="ti ti-history" style="font-size:36px;display:block;margin-bottom:8px;"></i>
            Belum ada riwayat peminjaman
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>