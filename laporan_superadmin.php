<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: login_adminn.php");
    exit;
}

if ($_SESSION['hak_akses'] != 'super_admin') {
    header("Location: login_adminn.php");
    exit;
}

$nama_admin = $_SESSION['nama_admin'];

require_once 'koneksii.php';

$bulan  = isset($_GET['bulan'])  ? (int)$_GET['bulan']  : (int)date('m');
$tahun  = isset($_GET['tahun'])  ? (int)$_GET['tahun']  : (int)date('Y');
$tipe   = isset($_GET['tipe'])   ? $_GET['tipe']         : 'peminjaman';

$query_pinjam = mysqli_query($conn, "
    SELECT p.*, s.nama_siswa, s.nis, s.kelas,
           b.nama_barang, b.kode_barang,
           j.nama_jurusan, j.kode_jurusan
    FROM peminjaman p
    JOIN siswa s ON p.id_siswa = s.id_siswa
    JOIN barang b ON p.id_barang = b.id_barang
    JOIN jurusan j ON b.id_jurusan = j.id_jurusan
    WHERE MONTH(p.tgl_pinjam) = $bulan AND YEAR(p.tgl_pinjam) = $tahun
    ORDER BY p.tgl_pinjam DESC
");

$query_denda = mysqli_query($conn, "
    SELECT p.*, s.nama_siswa, s.nis,
           b.nama_barang, j.kode_jurusan
    FROM peminjaman p
    JOIN siswa s ON p.id_siswa = s.id_siswa
    JOIN barang b ON p.id_barang = b.id_barang
    JOIN jurusan j ON b.id_jurusan = j.id_jurusan
    WHERE p.denda > 0
    AND MONTH(p.tgl_pinjam) = $bulan AND YEAR(p.tgl_pinjam) = $tahun
    ORDER BY p.denda DESC
");

$query_rekap = mysqli_query($conn, "
    SELECT j.nama_jurusan, j.kode_jurusan,
           COUNT(p.id_peminjaman) as total_pinjam,
           SUM(CASE WHEN p.status='terlambat' THEN 1 ELSE 0 END) as total_terlambat,
           SUM(p.denda) as total_denda
    FROM jurusan j
    LEFT JOIN barang b ON b.id_jurusan = j.id_jurusan
    LEFT JOIN peminjaman p ON p.id_barang = b.id_barang
        AND MONTH(p.tgl_pinjam) = $bulan AND YEAR(p.tgl_pinjam) = $tahun
    GROUP BY j.id_jurusan
    ORDER BY total_pinjam DESC
");

$total_pinjam_bulan = mysqli_num_rows($query_pinjam);
$total_denda_bulan  = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(denda) as t FROM peminjaman
    WHERE MONTH(tgl_pinjam)=$bulan AND YEAR(tgl_pinjam)=$tahun
"))['t'] ?? 0;

$nama_bulan = ['', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Super Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; overflow: hidden; }
        .wrapper { display: flex; height: 100vh; }
        .sidebar { width: 230px; background: #111827; border-right: 1px solid rgba(255,255,255,.05); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; }
        .sidebar-brand { padding: 20px 18px 16px; border-bottom: 1px solid rgba(255,255,255,.05); }
        .brand-title { font-size: 14px; font-weight: 700; color: #f1f5f9; }
        .brand-sub { font-size: 10px; color: #64748b; margin-top: 2px; }
        .admin-info { padding: 14px 18px; border-bottom: 1px solid rgba(255,255,255,.05); display: flex; align-items: center; gap: 11px; }
        .admin-avatar { width: 38px; height: 38px; border-radius: 50%; background: #d97706; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 700; color: white; flex-shrink: 0; }
        .admin-name { font-size: 13px; font-weight: 600; color: #f1f5f9; }
        .admin-badge { display: inline-block; margin-top: 3px; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; background: rgba(217,119,6,.2); color: #fbbf24; }
        .sidebar-menu { flex: 1; overflow-y: auto; padding: 8px 0; }
        .sidebar-menu::-webkit-scrollbar { width: 0; }
        .menu-section { padding: 12px 20px 5px; font-size: 10px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 1.4px; }
        .menu-item { display: block; padding: 11px 20px; color: #94a3b8; text-decoration: none; font-size: 13px; border-left: 3px solid transparent; transition: .15s; }
        .menu-item:hover { background: rgba(255,255,255,.04); color: #f1f5f9; }
        .menu-item.active { background: rgba(217,119,6,.1); border-left: 3px solid #f59e0b; color: #fde68a; font-weight: 700; }
        .sidebar-footer { padding: 14px; }
        .btn-logout { display: block; width: 100%; padding: 11px; border-radius: 10px; background: rgba(185,28,28,.2); color: #f87171; font-size: 13px; font-weight: 700; text-align: center; text-decoration: none; border: 1px solid rgba(185,28,28,.25); transition: .15s; }
        .btn-logout:hover { background: rgba(185,28,28,.35); }
        .main { margin-left: 230px; flex: 1; display: flex; flex-direction: column; height: 100vh; }
        .topbar { height: 60px; min-height: 60px; background: #111827; border-bottom: 1px solid rgba(255,255,255,.05); display: flex; align-items: center; justify-content: space-between; padding: 0 28px; }
        .topbar-left { display: flex; align-items: center; gap: 12px; }
        .topbar-title { font-size: 20px; font-weight: 700; color: #f1f5f9; }
        .topbar-chip { padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; background: rgba(217,119,6,.15); color: #fbbf24; border: 1px solid rgba(217,119,6,.2); }
        .topbar-right { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; }
        .content { flex: 1; overflow-y: auto; padding: 24px 28px; background: #0f172a; }
        .content::-webkit-scrollbar { width: 5px; }
        .content::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        .filter-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 22px; flex-wrap: wrap; }
        .filter-bar select { padding: 9px 14px; background: #1e293b; border: 1px solid rgba(255,255,255,.07); border-radius: 9px; color: #f1f5f9; font-size: 13px; outline: none; }
        .filter-bar select option { background: #1e293b; }
        .btn { padding: 9px 18px; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: .15s; text-decoration: none; display: inline-block; }
        .btn-amber { background: #d97706; color: white; }
        .btn-amber:hover { background: #b45309; }

        .tabs { display: flex; gap: 6px; margin-bottom: 20px; }
        .tab-btn { padding: 8px 18px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid rgba(255,255,255,.07); background: #1e293b; color: #64748b; text-decoration: none; transition: .15s; }
        .tab-btn.active { background: rgba(217,119,6,.15); border-color: rgba(217,119,6,.3); color: #fbbf24; }

        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 22px; }
        .sum-card { background: #1e293b; border: 1px solid rgba(255,255,255,.06); border-radius: 12px; padding: 16px 20px; }
        .sum-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .sum-val { font-size: 22px; font-weight: 700; color: #f1f5f9; }

        .table-wrap { background: #1e293b; border: 1px solid rgba(255,255,255,.06); border-radius: 12px; overflow: hidden; }
        .table-header { padding: 14px 18px; border-bottom: 1px solid rgba(255,255,255,.05); display: flex; align-items: center; justify-content: space-between; }
        .table-header-title { font-size: 13px; font-weight: 700; color: #f1f5f9; }
        .table-info { font-size: 12px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #0f172a; }
        thead th { padding: 11px 14px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .8px; text-align: left; }
        tbody tr { border-top: 1px solid rgba(255,255,255,.04); }
        tbody tr:hover { background: rgba(255,255,255,.02); }
        tbody td { padding: 10px 14px; font-size: 12px; color: #cbd5e1; vertical-align: middle; }

        .badge { display: inline-block; padding: 3px 9px; border-radius: 5px; font-size: 11px; font-weight: 700; }
        .badge-dipinjam     { background: rgba(96,165,250,.15);  color: #60a5fa; }
        .badge-dikembalikan { background: rgba(52,211,153,.15);  color: #34d399; }
        .badge-terlambat    { background: rgba(248,113,113,.15); color: #f87171; }
        .badge-pending      { background: rgba(251,191,36,.15);  color: #fbbf24; }
    </style>
</head>

<body>
<div class="wrapper">
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-title">Peminjaman Alat</div>
            <div class="brand-sub">SMKN 1 Kota Cirebon</div>
        </div>
        <div class="admin-info">
            <div class="admin-avatar"><?= strtoupper(substr($nama_admin, 0, 1)) ?></div>
            <div>
                <div class="admin-name"><?= htmlspecialchars($nama_admin) ?></div>
                <span class="admin-badge">Super Admin</span>
            </div>
        </div>
        <div class="sidebar-menu">
            <div class="menu-section">Menu Utama</div>
            <a href="dashboard_superadmin.php" class="menu-item">Dashboard</a>
            <div class="menu-section">Manajemen</div>
            <a href="data_alat_superadmin.php"  class="menu-item">Data Alat</a>
            <a href="data_admin_superadmin.php" class="menu-item">Data Admin</a>
            <a href="denda_superadmin.php"       class="menu-item">Denda</a>
            <a href="riwayat_superadmin.php"     class="menu-item">Riwayat</a>
            <div class="menu-section">Sistem</div>
            <a href="laporan_superadmin.php"     class="menu-item active">Laporan</a>
            <a href="profil_superadmin.php"      class="menu-item">Profil</a>
        </div>
        <div class="sidebar-footer">
            <a href="logout_adminn.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <div class="topbar-title">Laporan</div>
                <span class="topbar-chip">Super Admin</span>
            </div>
            <div class="topbar-right">
                <span><?= htmlspecialchars($nama_admin) ?></span>
                <span>•</span>
                <span><?= date('d M Y') ?></span>
            </div>
        </div>

        <div class="content">

            <form method="GET" class="filter-bar">
                <input type="hidden" name="tipe" value="<?= $tipe ?>">
                <select name="bulan">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m == $bulan ? 'selected' : '' ?>><?= $nama_bulan[$m] ?></option>
                    <?php endfor; ?>
                </select>
                <select name="tahun">
                    <?php for ($y = 2024; $y <= (int)date('Y'); $y++): ?>
                    <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-amber">Tampilkan</button>
            </form>

            <div class="tabs">
                <a href="?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&tipe=peminjaman"
                   class="tab-btn <?= $tipe == 'peminjaman' ? 'active' : '' ?>">Peminjaman</a>
                <a href="?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&tipe=denda"
                   class="tab-btn <?= $tipe == 'denda' ? 'active' : '' ?>">Denda</a>
                <a href="?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&tipe=rekap"
                   class="tab-btn <?= $tipe == 'rekap' ? 'active' : '' ?>">Rekap Jurusan</a>
            </div>

            <div class="summary-grid">
                <div class="sum-card">
                    <div class="sum-label">Total Peminjaman</div>
                    <div class="sum-val"><?= $total_pinjam_bulan ?></div>
                </div>
                <div class="sum-card">
                    <div class="sum-label">Total Denda</div>
                    <div class="sum-val">Rp <?= number_format($total_denda_bulan, 0, ',', '.') ?></div>
                </div>
                <div class="sum-card">
                    <div class="sum-label">Periode</div>
                    <div class="sum-val" style="font-size:15px; padding-top:4px;"><?= $nama_bulan[$bulan] ?> <?= $tahun ?></div>
                </div>
            </div>

            <?php if ($tipe == 'peminjaman'): ?>
            <div class="table-wrap">
                <div class="table-header">
                    <div class="table-header-title">Laporan Peminjaman — <?= $nama_bulan[$bulan] ?> <?= $tahun ?></div>
                    <div class="table-info"><?= mysqli_num_rows($query_pinjam) ?> transaksi</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Siswa</th>
                            <th>Alat</th>
                            <th>Jurusan</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                            <th>Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query_pinjam) > 0): ?>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($query_pinjam)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div style="font-weight:600;"><?= htmlspecialchars($row['nama_siswa']) ?></div>
                                    <div style="font-size:11px; color:#64748b;"><?= $row['nis'] ?></div>
                                </td>
                                <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                <td><?= $row['kode_jurusan'] ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tgl_pinjam'])) ?></td>
                                <td><?= $row['tgl_kembali'] ? date('d/m/Y', strtotime($row['tgl_kembali'])) : '—' ?></td>
                                <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                                <td><?= $row['denda'] > 0 ? 'Rp '.number_format($row['denda'],0,',','.') : '—' ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center; color:#475569; padding:28px;">Tidak ada data untuk periode ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php elseif ($tipe == 'denda'): ?>
            <div class="table-wrap">
                <div class="table-header">
                    <div class="table-header-title">Laporan Denda — <?= $nama_bulan[$bulan] ?> <?= $tahun ?></div>
                    <div class="table-info"><?= mysqli_num_rows($query_denda) ?> data</div>
                </div>
                <table>
                    <thead>
                        <tr><th>#</th><th>Siswa</th><th>Alat</th><th>Jurusan</th><th>Denda</th></tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query_denda) > 0): ?>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($query_denda)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nama_siswa']) ?><br><span style="font-size:11px;color:#64748b;"><?= $row['nis'] ?></span></td>
                                <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                <td><?= $row['kode_jurusan'] ?></td>
                                <td style="color:#f87171; font-weight:700;">Rp <?= number_format($row['denda'],0,',','.') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; color:#475569; padding:28px;">Tidak ada denda untuk periode ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php else: ?>
            <div class="table-wrap">
                <div class="table-header">
                    <div class="table-header-title">Rekap per jurusan — <?= $nama_bulan[$bulan] ?> <?= $tahun ?></div>
                </div>
                <table>
                    <thead>
                        <tr><th>Jurusan</th><th>Kode</th><th>Total Pinjam</th><th>Terlambat</th><th>Total Denda</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($query_rekap)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama_jurusan']) ?></td>
                            <td><span style="font-size:11px; padding:2px 7px; border-radius:4px; background:rgba(217,119,6,.12); color:#fbbf24;"><?= $row['kode_jurusan'] ?></span></td>
                            <td style="text-align:center;"><?= $row['total_pinjam'] ?? 0 ?></td>
                            <td style="text-align:center; color:<?= $row['total_terlambat'] > 0 ? '#f87171' : '#64748b' ?>;"><?= $row['total_terlambat'] ?? 0 ?></td>
                            <td><?= $row['total_denda'] > 0 ? '<span style="color:#f87171;font-weight:600;">Rp '.number_format($row['total_denda'],0,',','.').'</span>' : '—' ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>