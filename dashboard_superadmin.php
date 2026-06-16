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

$total_alat      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM barang"))['total'];
$sedang_dipinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dipinjam'"))['total'];
$stok_tersedia   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stok_tersedia) as total FROM barang"))['total'];
$total_denda     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(denda) as total FROM peminjaman"))['total'];
if (!$total_denda) $total_denda = 0;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where  = $search ? "WHERE nama_jurusan LIKE '%$search%' OR kode_jurusan LIKE '%$search%'" : '';
$query_jurusan = mysqli_query($conn, "SELECT * FROM jurusan $where ORDER BY id_jurusan ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Super Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            overflow: hidden;
        }

        .wrapper { display: flex; height: 100vh; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 230px;
            background: #111827;
            border-right: 1px solid rgba(255,255,255,.05);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
        }

        .sidebar-brand {
            padding: 20px 18px 16px;
            border-bottom: 1px solid rgba(255,255,255,.05);
        }

        .brand-title { font-size: 14px; font-weight: 700; color: #f1f5f9; }
        .brand-sub   { font-size: 10px; color: #64748b; margin-top: 2px; }

        .admin-info {
            padding: 14px 18px;
            border-bottom: 1px solid rgba(255,255,255,.05);
            display: flex; align-items: center; gap: 11px;
        }

        .admin-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: #d97706;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; font-weight: 700; color: white; flex-shrink: 0;
        }

        .admin-name  { font-size: 13px; font-weight: 600; color: #f1f5f9; }

        .admin-badge {
            display: inline-block; margin-top: 3px;
            padding: 2px 8px; border-radius: 4px;
            font-size: 10px; font-weight: 700;
            background: rgba(217,119,6,.2); color: #fbbf24;
        }

        .sidebar-menu {
            flex: 1; overflow-y: auto; padding: 8px 0;
        }

        .sidebar-menu::-webkit-scrollbar { width: 0; }

        .menu-section {
            padding: 12px 20px 5px;
            font-size: 10px; font-weight: 700; color: #334155;
            text-transform: uppercase; letter-spacing: 1.4px;
        }

        .menu-item {
            display: block; padding: 11px 20px;
            color: #94a3b8; text-decoration: none;
            font-size: 13px; border-left: 3px solid transparent;
            transition: .15s;
        }

        .menu-item:hover { background: rgba(255,255,255,.05); color: #f1f5f9; }

        .menu-item.active {
            background: rgba(217,119,6,.1);
            border-left: 3px solid #f59e0b;
            color: #fde68a; font-weight: 700;
        }

        .sidebar-footer { padding: 14px; }

        .btn-logout {
            display: block; width: 100%; padding: 11px;
            border-radius: 10px;
            background: rgba(185,28,28,.2);
            color: #f87171; font-size: 13px; font-weight: 700;
            text-align: center; text-decoration: none;
            border: 1px solid rgba(185,28,28,.25); transition: .15s;
        }

        .btn-logout:hover { background: rgba(185,28,28,.35); }

        .main {
            margin-left: 230px; flex: 1;
            display: flex; flex-direction: column; height: 100vh;
        }

        .topbar {
            height: 60px; min-height: 60px;
            background: #111827;
            border-bottom: 1px solid rgba(255,255,255,.05);
            display: flex; align-items: center;
            justify-content: space-between; padding: 0 28px;
        }

        .topbar-left { display: flex; align-items: center; gap: 12px; }
        .topbar-title { font-size: 20px; font-weight: 700; color: #f1f5f9; }

        .topbar-chip {
            padding: 3px 10px; border-radius: 20px;
            font-size: 10px; font-weight: 700;
            background: rgba(217,119,6,.15); color: #fbbf24;
            border: 1px solid rgba(217,119,6,.2);
        }

        .topbar-right {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; color: #64748b;
        }

        .content {
            flex: 1; overflow-y: auto; padding: 24px 28px;
            background: #0f172a;
        }

        .content::-webkit-scrollbar { width: 5px; }
        .content::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        .search-wrap {
            display: flex; gap: 10px; margin-bottom: 22px;
        }

        .search-wrap input {
            flex: 1; padding: 11px 16px;
            background: #1e293b;
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 10px; color: #f1f5f9; font-size: 13px; outline: none;
            transition: .15s;
        }

        .search-wrap input::placeholder { color: #475569; }
        .search-wrap input:focus { border-color: rgba(217,119,6,.4); }

        .search-wrap button {
            padding: 11px 22px; border-radius: 10px;
            background: #d97706; color: white;
            font-size: 13px; font-weight: 700;
            border: none; cursor: pointer; transition: .15s;
        }

        .search-wrap button:hover { background: #b45309; }

        .stats-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 14px; margin-bottom: 26px;
        }

        .stat-card {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 12px; padding: 18px 20px;
            position: relative; overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0;
            height: 3px;
        }

        .stat-card.c1::before { background: #60a5fa; }
        .stat-card.c2::before { background: #fbbf24; }
        .stat-card.c3::before { background: #34d399; }
        .stat-card.c4::before { background: #f87171; }

        .stat-label {
            font-size: 10px; font-weight: 700; color: #64748b;
            text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;
        }

        .stat-value { font-size: 28px; font-weight: 700; }

        .c1 .stat-value { color: #60a5fa; }
        .c2 .stat-value { color: #fbbf24; }
        .c3 .stat-value { color: #34d399; }
        .c4 .stat-value { color: #f87171; }

        .section-label {
            font-size: 11px; font-weight: 700; color: #475569;
            text-transform: uppercase; letter-spacing: 1.3px; margin-bottom: 14px;
        }

        .jurusan-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;
        }

        .jurusan-card {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 12px; overflow: hidden;
            transition: .2s;
        }

        .jurusan-card:hover {
            border-color: rgba(217,119,6,.3);
            transform: translateY(-2px);
        }

        .jurusan-head {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255,255,255,.05);
            display: flex; align-items: flex-start; justify-content: space-between;
        }

        .jurusan-nama { font-size: 13px; font-weight: 700; color: #f1f5f9; line-height: 1.3; }

        .jurusan-kode {
            font-size: 10px; font-weight: 700;
            padding: 2px 7px; border-radius: 4px;
            background: rgba(217,119,6,.15); color: #fbbf24;
            flex-shrink: 0; margin-left: 8px; margin-top: 1px;
        }

        .mini-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 1px; background: rgba(255,255,255,.05);
        }

        .mini-cell {
            background: #1e293b;
            background: #1e293b;
            padding: 10px 14px;
        }

        .mini-val  { font-size: 18px; font-weight: 700; margin-bottom: 2px; }
        .mini-lbl  { font-size: 10px; color: #64748b; }

        .val-hijau { color: #34d399; }
        .val-merah { color: #f87171; }
        .val-biru  { color: #60a5fa; }

        .jurusan-foot {
            padding: 9px 16px;
            border-top: 1px solid rgba(255,255,255,.05);
            display: flex; align-items: center; justify-content: space-between;
        }

        .tag-aman  { font-size: 11px; color: #34d399; font-weight: 600; }
        .tag-rusak { font-size: 11px; color: #f87171; font-weight: 600; }

        .link-detail {
            font-size: 11px; color: #64748b; text-decoration: none; transition: .15s;
        }

        .link-detail:hover { color: #fbbf24; }

        .empty-msg {
            grid-column: span 3;
            padding: 30px 0; text-align: center;
            font-size: 13px; color: #475569;
        }
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
            <a href="dashboard_superadmin.php" class="menu-item active">Dashboard</a>

            <div class="menu-section">Manajemen</div>
            <a href="data_alat_superadmin.php"  class="menu-item">Data Alat</a>
            <a href="data_admin_superadmin.php" class="menu-item">Data Admin</a>
            <a href="denda_superadmin.php"       class="menu-item">Denda</a>
            <a href="riwayat_superadmin.php"     class="menu-item">Riwayat</a>

            <div class="menu-section">Sistem</div>
            <a href="laporan_superadmin.php"     class="menu-item">Laporan</a>
            <a href="profil_superadmin.php"  class="menu-item">Profil</a>
        </div>

        <div class="sidebar-footer">
            <a href="logout_adminn.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <div class="topbar-title">Dashboard</div>
                <span class="topbar-chip">Super Admin</span>
            </div>
            <div class="topbar-right">
                <span><?= htmlspecialchars($nama_admin) ?></span>
                <span>•</span>
                <span><?= date('d M Y') ?></span>
            </div>
        </div>

        <div class="content">

            <form method="GET" class="search-wrap">
                <input type="text" name="search"
                       placeholder="Cari jurusan atau kode jurusan..."
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Cari</button>
            </form>

            <div class="stats-grid">
                <div class="stat-card c1">
                    <div class="stat-label">Total Jenis Alat</div>
                    <div class="stat-value"><?= $total_alat ?></div>
                </div>
                <div class="stat-card c2">
                    <div class="stat-label">Sedang Dipinjam</div>
                    <div class="stat-value"><?= $sedang_dipinjam ?></div>
                </div>
                <div class="stat-card c3">
                    <div class="stat-label">Stok Tersedia</div>
                    <div class="stat-value"><?= $stok_tersedia ?></div>
                </div>
                <div class="stat-card c4">
                    <div class="stat-label">Total Denda</div>
                    <div class="stat-value">Rp <?= number_format($total_denda, 0, ',', '.') ?></div>
                </div>
            </div>

            <div class="section-label">Data per Jurusan</div>
            <div class="jurusan-grid">

                <?php if (mysqli_num_rows($query_jurusan) == 0): ?>
                    <div class="empty-msg">Jurusan tidak ditemukan.</div>
                <?php endif; ?>

                <?php while ($j = mysqli_fetch_assoc($query_jurusan)):
                    $jenis   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM barang WHERE id_jurusan = {$j['id_jurusan']}"))['t'];
                    $sedia   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stok_tersedia) as t FROM barang WHERE id_jurusan = {$j['id_jurusan']}"))['t'] ?? 0;
                    $rusak   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM barang WHERE id_jurusan = {$j['id_jurusan']} AND kondisi = 'rusak'"))['t'];
                    $denda_j = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(p.denda) as t FROM peminjaman p JOIN barang b ON p.id_barang = b.id_barang WHERE b.id_jurusan = {$j['id_jurusan']}"))['t'] ?? 0;
                ?>
                <div class="jurusan-card">
                    <div class="jurusan-head">
                        <div class="jurusan-nama"><?= htmlspecialchars($j['nama_jurusan']) ?></div>
                        <span class="jurusan-kode"><?= $j['kode_jurusan'] ?></span>
                    </div>
                    <div class="mini-grid">
                        <div class="mini-cell">
                            <div class="mini-val val-biru"><?= $jenis ?></div>
                            <div class="mini-lbl">Jenis Alat</div>
                        </div>
                        <div class="mini-cell">
                            <div class="mini-val val-hijau"><?= $sedia ?></div>
                            <div class="mini-lbl">Tersedia</div>
                        </div>
                        <div class="mini-cell">
                            <div class="mini-val val-merah"><?= $rusak ?></div>
                            <div class="mini-lbl">Alat Rusak</div>
                        </div>
                        <div class="mini-cell">
                            <div class="mini-val val-merah" style="font-size:13px;">Rp <?= number_format($denda_j, 0, ',', '.') ?></div>
                            <div class="mini-lbl">Total Denda</div>
                        </div>
                    </div>
                    <div class="jurusan-foot">
                        <?php if ($rusak > 0): ?>
                            <span class="tag-rusak">⚠ <?= $rusak ?> Rusak</span>
                        <?php else: ?>
                            <span class="tag-aman">✓ Aman</span>
                        <?php endif; ?>
                        <a href="data_alat_superadmin.php?jurusan=<?= $j['id_jurusan'] ?>" class="link-detail">Lihat Detail →</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>