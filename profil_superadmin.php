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
$id_admin   = $_SESSION['id_admin'];

require_once 'koneksii.php';

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi'])) {

    if ($_POST['aksi'] == 'ubah_profil') {
        $nama    = mysqli_real_escape_string($conn, trim($_POST['nama_admin']));
        $old_pw  = mysqli_real_escape_string($conn, $_POST['password_lama']);
        $new_pw  = trim($_POST['password_baru']);
        $konfirm = trim($_POST['password_konfirm']);

        $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM admin WHERE id_admin=$id_admin"));

        if ($cek['password'] !== $old_pw) {
            $pesan = 'error|Password lama tidak sesuai.';
        } elseif (!empty($new_pw) && $new_pw !== $konfirm) {
            $pesan = 'error|Password baru dan konfirmasi tidak cocok.';
        } elseif (!empty($new_pw) && strlen($new_pw) < 6) {
            $pesan = 'error|Password baru minimal 6 karakter.';
        } else {
            if (!empty($new_pw)) {
                $new_pw_esc = mysqli_real_escape_string($conn, $new_pw);
                mysqli_query($conn, "UPDATE admin SET nama_admin='$nama', password='$new_pw_esc' WHERE id_admin=$id_admin");
            } else {
                mysqli_query($conn, "UPDATE admin SET nama_admin='$nama' WHERE id_admin=$id_admin");
            }
            $_SESSION['nama_admin'] = $nama;
            $nama_admin = $nama;
            $pesan = 'sukses|Profil berhasil diperbarui.';
        }
    }
}

$admin_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM admin WHERE id_admin=$id_admin"));

$total_approve = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM peminjaman WHERE id_admin=$id_admin AND status IN ('dipinjam','dikembalikan','terlambat')"))['t'];
$total_denda   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(denda) as t FROM peminjaman WHERE id_admin=$id_admin"))['t'] ?? 0;
$total_barang  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM barang"))['t'];
$total_jurusan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM jurusan"))['t'];
$total_admin   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM admin"))['t'];
$pending_now   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM peminjaman WHERE status='pending'"))['t'];

$query_aktivitas = mysqli_query($conn, "
    SELECT p.*, s.nama_siswa, b.nama_barang, j.kode_jurusan
    FROM peminjaman p
    JOIN siswa s ON p.id_siswa = s.id_siswa
    JOIN barang b ON p.id_barang = b.id_barang
    JOIN jurusan j ON b.id_jurusan = j.id_jurusan
    WHERE p.id_admin = $id_admin
    ORDER BY p.tgl_pinjam DESC
    LIMIT 5
");

$inisial = '';
foreach (explode(' ', $admin_data['nama_admin']) as $w) {
    $inisial .= strtoupper(mb_substr($w, 0, 1));
    if (strlen($inisial) >= 2) break;
}
if (!$inisial) $inisial = strtoupper(mb_substr($admin_data['nama_admin'], 0, 2));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Super Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            overflow: hidden;
        }

        .wrapper { display: flex; height: 100vh; }

        .sidebar {
            width: 230px; background: #111827;
            border-right: 1px solid rgba(255,255,255,.05);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0;
        }
        .sidebar-brand { padding: 20px 18px 16px; border-bottom: 1px solid rgba(255,255,255,.05); }
        .brand-title { font-size: 14px; font-weight: 700; color: #f1f5f9; }
        .brand-sub   { font-size: 10px; color: #64748b; margin-top: 2px; }
        .admin-info  { padding: 14px 18px; border-bottom: 1px solid rgba(255,255,255,.05); display: flex; align-items: center; gap: 11px; }
        .admin-avatar { width: 38px; height: 38px; border-radius: 50%; background: #d97706; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 700; color: white; flex-shrink: 0; }
        .admin-name  { font-size: 13px; font-weight: 600; color: #f1f5f9; }
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
        .content { flex: 1; overflow-y: auto; padding: 28px; background: #0f172a; }
        .content::-webkit-scrollbar { width: 5px; }
        .content::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        .alert { padding: 12px 16px; border-radius: 9px; margin-bottom: 20px; font-size: 13px; }
        .alert-sukses { background: rgba(52,211,153,.12); color: #34d399; border: 1px solid rgba(52,211,153,.2); }
        .alert-error  { background: rgba(248,113,113,.12); color: #f87171; border: 1px solid rgba(248,113,113,.2); }

        .profile-hero {
            background: linear-gradient(135deg, #1e293b 0%, #1a2438 100%);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 16px;
            padding: 28px;
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .profile-hero::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 180px; height: 180px;
            border-radius: 50%;
            background: rgba(217,119,6,.06);
        }

        .profile-hero::after {
            content: '';
            position: absolute;
            bottom: -60px; right: 80px;
            width: 140px; height: 140px;
            border-radius: 50%;
            background: rgba(217,119,6,.04);
        }

        .hero-avatar {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #d97706, #b45309);
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; font-weight: 700; color: white;
            flex-shrink: 0;
            border: 3px solid rgba(217,119,6,.3);
            box-shadow: 0 0 0 6px rgba(217,119,6,.08);
        }

        .hero-info { flex: 1; z-index: 1; }

        .hero-name {
            font-size: 22px; font-weight: 700; color: #f1f5f9;
            margin-bottom: 4px;
        }

        .hero-username {
            font-size: 13px; color: #64748b; margin-bottom: 10px;
        }

        .hero-badges { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        .badge-role {
            padding: 4px 12px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
            background: rgba(217,119,6,.2); color: #fbbf24;
            border: 1px solid rgba(217,119,6,.25);
        }

        .badge-status {
            padding: 4px 12px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
            background: rgba(52,211,153,.15); color: #34d399;
            border: 1px solid rgba(52,211,153,.2);
        }

        .hero-meta {
            text-align: right; z-index: 1;
            font-size: 11px; color: #475569; line-height: 1.8;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 12px;
            padding: 16px 18px;
            position: relative; overflow: hidden;
            transition: .2s;
        }

        .stat-card:hover { border-color: rgba(255,255,255,.1); transform: translateY(-1px); }

        .stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
        }

        .stat-card.s1::before { background: #60a5fa; }
        .stat-card.s2::before { background: #fbbf24; }
        .stat-card.s3::before { background: #34d399; }
        .stat-card.s4::before { background: #a78bfa; }

        .stat-icon {
            font-size: 22px; margin-bottom: 10px;
        }

        .stat-val { font-size: 24px; font-weight: 700; margin-bottom: 2px; }
        .stat-lbl { font-size: 11px; color: #64748b; }

        .s1 .stat-val { color: #60a5fa; }
        .s2 .stat-val { color: #fbbf24; }
        .s3 .stat-val { color: #34d399; }
        .s4 .stat-val { color: #a78bfa; }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        .card {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 14px;
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255,255,255,.05);
            display: flex; align-items: center; justify-content: space-between;
        }

        .card-title { font-size: 14px; font-weight: 700; color: #f1f5f9; }
        .card-sub   { font-size: 11px; color: #64748b; margin-top: 2px; }
        .card-body  { padding: 20px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 13px; }

        .form-group { margin-bottom: 14px; }
        .form-group:last-child { margin-bottom: 0; }

        .form-group label {
            display: block; font-size: 12px; font-weight: 600;
            color: #94a3b8; margin-bottom: 5px;
        }

        .form-group input {
            width: 100%; padding: 10px 13px;
            background: #0f172a;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 9px;
            color: #f1f5f9; font-size: 13px; outline: none;
            transition: border-color .15s;
        }

        .form-group input:focus { border-color: rgba(217,119,6,.4); }
        .form-group input::placeholder { color: #475569; }
        .form-group input:disabled { opacity: .45; cursor: not-allowed; }

        .form-hint { font-size: 11px; color: #475569; margin-top: 4px; }

        .divider {
            height: 1px; background: rgba(255,255,255,.05);
            margin: 16px 0;
        }

        .btn-save {
            padding: 10px 24px; border-radius: 9px;
            background: #d97706; color: white;
            font-size: 13px; font-weight: 700;
            border: none; cursor: pointer; transition: .15s;
        }
        .btn-save:hover { background: #b45309; }

        .info-list { display: flex; flex-direction: column; gap: 0; }

        .info-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,.04);
        }

        .info-item:last-child { border-bottom: none; }

        .info-key { font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 8px; }
        .info-key span { font-size: 16px; }
        .info-val { font-size: 13px; color: #f1f5f9; font-weight: 600; }

        .activity-list { display: flex; flex-direction: column; gap: 0; }

        .activity-item {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid rgba(255,255,255,.04);
        }

        .activity-item:last-child { border-bottom: none; }

        .activity-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            margin-top: 5px; flex-shrink: 0;
        }

        .dot-kembali   { background: #34d399; }
        .dot-dipinjam  { background: #60a5fa; }
        .dot-terlambat { background: #f87171; }
        .dot-pending   { background: #fbbf24; }

        .activity-name { font-size: 13px; color: #f1f5f9; font-weight: 600; margin-bottom: 2px; }
        .activity-desc { font-size: 11px; color: #64748b; }

        .activity-time {
            font-size: 11px; color: #475569;
            margin-left: auto; white-space: nowrap;
        }

        .empty-act {
            text-align: center; padding: 20px 0;
            font-size: 13px; color: #334155;
        }

        .pw-wrap { position: relative; }
        .pw-wrap input { padding-right: 38px; }
        .pw-toggle {
            position: absolute; right: 10px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #475569; font-size: 15px; padding: 4px;
            transition: color .15s;
        }
        .pw-toggle:hover { color: #94a3b8; }
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
            <a href="laporan_superadmin.php"     class="menu-item">Laporan</a>
            <div class="menu-section">Akun</div>
            <a href="profil_superadmin.php"      class="menu-item active">Profil Saya</a>
        </div>
        <div class="sidebar-footer">
            <a href="logout_adminn.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <div class="topbar-title">Profil Saya</div>
                <span class="topbar-chip">Super Admin</span>
            </div>
            <div class="topbar-right">
                <span><?= htmlspecialchars($nama_admin) ?></span>
                <span>•</span>
                <span><?= date('d M Y') ?></span>
            </div>
        </div>

        <div class="content">

            <?php if ($pesan):
                [$tipe_p, $msg] = explode('|', $pesan); ?>
                <div class="alert alert-<?= $tipe_p ?>"><?= $msg ?></div>
            <?php endif; ?>

            <!-- Hero -->
            <div class="profile-hero">
                <div class="hero-avatar"><?= $inisial ?></div>
                <div class="hero-info">
                    <div class="hero-name"><?= htmlspecialchars($admin_data['nama_admin']) ?></div>
                    <div class="hero-username">@<?= htmlspecialchars($admin_data['username']) ?></div>
                    <div class="hero-badges">
                        <span class="badge-role">Super Admin</span>
                        <span class="badge-status">Aktif</span>
                    </div>
                </div>
                <div class="hero-meta">
                    <div>ID Admin: #<?= str_pad($admin_data['id_admin'], 4, '0', STR_PAD_LEFT) ?></div>
                    <div>Akses penuh sistem</div>
                    <div><?= date('d M Y') ?></div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card s1">
                    <div class="stat-val"><?= $total_approve ?></div>
                    <div class="stat-lbl">Peminjaman Diproses</div>
                </div>
                <div class="stat-card s2">
                    <div class="stat-val"><?= $total_barang ?></div>
                    <div class="stat-lbl">Total Alat di Sistem</div>
                </div>
                <div class="stat-card s3">
                    <div class="stat-val"><?= $total_jurusan ?></div>
                    <div class="stat-lbl">Jurusan Terdaftar</div>
                </div>
                <div class="stat-card s4">
                    <div class="stat-val"><?= $total_admin ?></div>
                    <div class="stat-lbl">Total Admin</div>
                </div>
            </div>

            <div class="two-col">

                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Edit Profil</div>
                            <div class="card-sub">Perbarui nama dan password akun</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="aksi" value="ubah_profil">

                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama_admin"
                                       value="<?= htmlspecialchars($admin_data['nama_admin']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" value="<?= htmlspecialchars($admin_data['username']) ?>" disabled>
                                <div class="form-hint">Username tidak dapat diubah.</div>
                            </div>

                            <div class="form-group">
                                <label>Hak Akses</label>
                                <input type="text" value="Super Admin" disabled>
                            </div>

                            <div class="divider"></div>

                            <div class="form-group">
                                <label>Password Saat Ini <span style="color:#f87171;">*</span></label>
                                <div class="pw-wrap">
                                    <input type="password" name="password_lama" id="pw-lama"
                                           placeholder="Wajib diisi" required>
                                    <button type="button" class="pw-toggle" onclick="togglePw('pw-lama', this)">👁</button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Password Baru</label>
                                <div class="pw-wrap">
                                    <input type="password" name="password_baru" id="pw-baru"
                                           placeholder="Kosongkan jika tidak ingin ganti">
                                    <button type="button" class="pw-toggle" onclick="togglePw('pw-baru', this)">👁</button>
                                </div>
                                <div class="form-hint">Minimal 6 karakter.</div>
                            </div>

                            <div class="form-group">
                                <label>Konfirmasi Password Baru</label>
                                <div class="pw-wrap">
                                    <input type="password" name="password_konfirm" id="pw-konfirm"
                                           placeholder="Ulangi password baru">
                                    <button type="button" class="pw-toggle" onclick="togglePw('pw-konfirm', this)">👁</button>
                                </div>
                                <div id="pw-match-info" class="form-hint"></div>
                            </div>

                            <button type="submit" class="btn-save">Simpan Perubahan</button>
                        </form>
                    </div>
                </div>

                <div style="display:flex; flex-direction:column; gap:20px;">

                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title">Informasi Akun</div>
                                <div class="card-sub">Detail akun superadmin</div>
                            </div>
                        </div>
                        <div class="card-body" style="padding: 8px 20px;">
                            <div class="info-list">
                                <div class="info-item">
                                    <div class="info-key">ID Admin</div>
                                    <div class="info-val">#<?= str_pad($admin_data['id_admin'], 4, '0', STR_PAD_LEFT) ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-key">Username</div>
                                    <div class="info-val"><?= htmlspecialchars($admin_data['username']) ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-key">Hak Akses</div>
                                    <div class="info-val" style="color:#fbbf24;">Super Admin</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-key">Cakupan</div>
                                    <div class="info-val">Semua Jurusan</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-key">Pending Sekarang</div>
                                    <div class="info-val" style="color:<?= $pending_now > 0 ? '#fbbf24' : '#34d399' ?>;">
                                        <?= $pending_now ?> peminjaman
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-key">Total Denda Dikelola</div>
                                    <div class="info-val" style="color:#f87171;">Rp <?= number_format($total_denda, 0, ',', '.') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aktivitas Terakhir -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title">Aktivitas Terakhir</div>
                                <div class="card-sub">5 peminjaman terakhir yang diproses</div>
                            </div>
                        </div>
                        <div class="card-body" style="padding: 8px 20px;">
                            <div class="activity-list">
                                <?php if (mysqli_num_rows($query_aktivitas) > 0): ?>
                                    <?php while ($act = mysqli_fetch_assoc($query_aktivitas)): ?>
                                    <div class="activity-item">
                                        <div class="activity-dot dot-<?= $act['status'] ?>"></div>
                                        <div>
                                            <div class="activity-name"><?= htmlspecialchars($act['nama_siswa']) ?></div>
                                            <div class="activity-desc">
                                                <?= htmlspecialchars($act['nama_barang']) ?>
                                                <span style="color:#334155; margin:0 4px;">·</span>
                                                <span style="font-size:11px; padding:1px 6px; border-radius:4px; background:rgba(217,119,6,.1); color:#fbbf24;"><?= $act['kode_jurusan'] ?></span>
                                            </div>
                                        </div>
                                        <div class="activity-time"><?= date('d/m H:i', strtotime($act['tgl_pinjam'])) ?></div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="empty-act">Belum ada aktivitas.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
function togglePw(id, btn) {
    var input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type = 'password';
        btn.textContent = '👁';
    }
}

var pwBaru    = document.getElementById('pw-baru');
var pwKonfirm = document.getElementById('pw-konfirm');
var info      = document.getElementById('pw-match-info');

function cekMatch() {
    var b = pwBaru.value;
    var k = pwKonfirm.value;
    if (!b && !k) { info.textContent = ''; return; }
    if (k.length === 0) { info.textContent = ''; return; }
    if (b === k) {
        info.textContent = '✓ Password cocok';
        info.style.color = '#34d399';
    } else {
        info.textContent = '✗ Password tidak cocok';
        info.style.color = '#f87171';
    }
}

pwBaru.addEventListener('input', cekMatch);
pwKonfirm.addEventListener('input', cekMatch);
</script>
</body>
</html>