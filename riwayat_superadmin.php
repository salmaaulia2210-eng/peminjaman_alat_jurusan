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

$pesan = '';

if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    mysqli_query($conn, "UPDATE peminjaman SET status='dipinjam', id_admin={$_SESSION['id_admin']} WHERE id_peminjaman=$id AND status='pending'");
    header("Location: riwayat_superadmin.php");
    exit;
}

if (isset($_GET['tolak'])) {
    $id = (int)$_GET['tolak'];
    $p  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM peminjaman WHERE id_peminjaman=$id"));
    if ($p) {
        mysqli_query($conn, "UPDATE barang SET stok_tersedia = stok_tersedia + {$p['jumlah_pinjam']} WHERE id_barang = {$p['id_barang']}");
        mysqli_query($conn, "DELETE FROM peminjaman WHERE id_peminjaman=$id AND status='pending'");
    }
    header("Location: riwayat_superadmin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi']) && $_POST['aksi'] == 'kembalikan') {
    $id              = (int)$_POST['id_peminjaman'];
    $kondisi_kembali = in_array($_POST['kondisi_kembali'], ['bagus','rusak','hilang']) ? $_POST['kondisi_kembali'] : 'bagus';
    $denda           = (int)$_POST['denda'];

    $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM peminjaman WHERE id_peminjaman=$id"));
    if ($p) {
        $now  = date('Y-m-d H:i:s');
        $batas = strtotime($p['tgl_kembali_seharusnya']);
        $now_t = strtotime($now);
        $telat = ($now_t > $batas) ? 'terlambat' : 'dikembalikan';

        mysqli_query($conn, "UPDATE peminjaman SET status='$telat', tgl_kembali='$now', kondisi_kembali='$kondisi_kembali', denda=$denda, id_admin={$_SESSION['id_admin']} WHERE id_peminjaman=$id");
        mysqli_query($conn, "UPDATE barang SET stok_tersedia = stok_tersedia + {$p['jumlah_pinjam']} WHERE id_barang = {$p['id_barang']}");
        if ($kondisi_kembali == 'rusak') {
            mysqli_query($conn, "UPDATE barang SET kondisi='rusak' WHERE id_barang = {$p['id_barang']}");
        }
    }
    header("Location: riwayat_superadmin.php");
    exit;
}

$filter_status  = isset($_GET['status']) ? $_GET['status'] : '';
$filter_jurusan = isset($_GET['jurusan']) ? (int)$_GET['jurusan'] : 0;
$search         = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where = "WHERE 1=1";
if ($filter_status)  $where .= " AND p.status = '$filter_status'";
if ($filter_jurusan) $where .= " AND b.id_jurusan = $filter_jurusan";
if ($search)         $where .= " AND (s.nama_siswa LIKE '%$search%' OR b.nama_barang LIKE '%$search%' OR s.nis LIKE '%$search%')";

$query = mysqli_query($conn, "
    SELECT p.*, s.nama_siswa, s.nis, s.kelas,
           b.nama_barang, b.kode_barang,
           j.nama_jurusan, j.kode_jurusan
    FROM peminjaman p
    JOIN siswa s ON p.id_siswa = s.id_siswa
    JOIN barang b ON p.id_barang = b.id_barang
    JOIN jurusan j ON b.id_jurusan = j.id_jurusan
    $where
    ORDER BY p.tgl_pinjam DESC
");

$query_jurusan = mysqli_query($conn, "SELECT * FROM jurusan ORDER BY nama_jurusan ASC");

$cnt_pending   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM peminjaman WHERE status='pending'"))['t'];
$cnt_dipinjam  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM peminjaman WHERE status='dipinjam'"))['t'];
$cnt_kembali   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM peminjaman WHERE status='dikembalikan' OR status='terlambat'"))['t'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - Super Admin</title>
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

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 22px; }
        .stat-card { background: #1e293b; border: 1px solid rgba(255,255,255,.06); border-radius: 12px; padding: 16px 20px; position: relative; overflow: hidden; cursor: pointer; transition: .15s; }
        .stat-card:hover { border-color: rgba(255,255,255,.12); }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; }
        .stat-card.c1::before { background: #fbbf24; }
        .stat-card.c2::before { background: #60a5fa; }
        .stat-card.c3::before { background: #34d399; }
        .stat-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .stat-value { font-size: 26px; font-weight: 700; }
        .c1 .stat-value { color: #fbbf24; }
        .c2 .stat-value { color: #60a5fa; }
        .c3 .stat-value { color: #34d399; }

        .toolbar { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .toolbar input, .toolbar select { padding: 9px 14px; background: #1e293b; border: 1px solid rgba(255,255,255,.07); border-radius: 9px; color: #f1f5f9; font-size: 13px; outline: none; }
        .toolbar input { flex: 1; min-width: 180px; }
        .toolbar input::placeholder { color: #475569; }
        .toolbar select option { background: #1e293b; }
        .btn { padding: 9px 18px; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: .15s; text-decoration: none; display: inline-block; }
        .btn-amber { background: #d97706; color: white; }
        .btn-amber:hover { background: #b45309; }
        .btn-slate { background: rgba(255,255,255,.07); color: #94a3b8; }
        .btn-slate:hover { background: rgba(255,255,255,.12); }

        .table-wrap { background: #1e293b; border: 1px solid rgba(255,255,255,.06); border-radius: 12px; overflow: hidden; }
        .table-info { padding: 13px 18px; font-size: 12px; color: #64748b; border-bottom: 1px solid rgba(255,255,255,.05); }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #0f172a; }
        thead th { padding: 12px 14px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .8px; text-align: left; }
        tbody tr { border-top: 1px solid rgba(255,255,255,.04); }
        tbody tr:hover { background: rgba(255,255,255,.02); }
        tbody td { padding: 10px 14px; font-size: 13px; color: #cbd5e1; vertical-align: middle; }

        .badge { display: inline-block; padding: 3px 9px; border-radius: 5px; font-size: 11px; font-weight: 700; }
        .badge-pending     { background: rgba(251,191,36,.15);  color: #fbbf24; }
        .badge-dipinjam    { background: rgba(96,165,250,.15);  color: #60a5fa; }
        .badge-dikembalikan { background: rgba(52,211,153,.15); color: #34d399; }
        .badge-terlambat   { background: rgba(248,113,113,.15); color: #f87171; }

        .aksi-btn { padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; border: none; margin-right: 4px; text-decoration: none; display: inline-block; transition: .15s; }
        .btn-approve { background: rgba(52,211,153,.2); color: #34d399; }
        .btn-approve:hover { background: rgba(52,211,153,.35); }
        .btn-tolak   { background: rgba(248,113,113,.2); color: #f87171; }
        .btn-tolak:hover { background: rgba(248,113,113,.35); }
        .btn-kembali { background: rgba(96,165,250,.2); color: #60a5fa; }
        .btn-kembali:hover { background: rgba(96,165,250,.35); }

        .modal-bg { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,.65); z-index: 100; align-items: center; justify-content: center; }
        .modal-bg.show { display: flex; }
        .modal { background: #1e293b; border-radius: 14px; padding: 26px; width: 420px; border: 1px solid rgba(255,255,255,.08); }
        .modal-title { font-size: 15px; font-weight: 700; color: #f1f5f9; margin-bottom: 18px; }
        .form-group { margin-bottom: 13px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 5px; }
        .form-group select, .form-group input { width: 100%; padding: 9px 13px; background: #0f172a; border: 1px solid rgba(255,255,255,.08); border-radius: 8px; color: #f1f5f9; font-size: 13px; outline: none; }
        .form-group select option { background: #1e293b; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
        .btn-cancel { padding: 9px 18px; border-radius: 9px; background: rgba(255,255,255,.06); color: #94a3b8; font-size: 13px; font-weight: 600; border: none; cursor: pointer; }
        .info-box { background: #0f172a; border-radius: 8px; padding: 12px 14px; margin-bottom: 14px; font-size: 12px; color: #94a3b8; line-height: 1.7; }
        .info-box strong { color: #f1f5f9; }
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
            <a href="riwayat_superadmin.php"     class="menu-item active">Riwayat</a>
            <div class="menu-section">Sistem</div>
            <a href="laporan_superadmin.php"     class="menu-item">Laporan</a>
            <a href="pengaturan_superadmin.php"  class="menu-item">Pengaturan</a>
        </div>
        <div class="sidebar-footer">
            <a href="logout_adminn.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <div class="topbar-title">Riwayat Peminjaman</div>
                <span class="topbar-chip">Super Admin</span>
            </div>
            <div class="topbar-right">
                <span><?= htmlspecialchars($nama_admin) ?></span>
                <span>•</span>
                <span><?= date('d M Y') ?></span>
            </div>
        </div>

        <div class="content">

            <div class="stats-grid">
                <a href="?status=pending" style="text-decoration:none;">
                    <div class="stat-card c1">
                        <div class="stat-label">Pending Approval</div>
                        <div class="stat-value"><?= $cnt_pending ?></div>
                    </div>
                </a>
                <a href="?status=dipinjam" style="text-decoration:none;">
                    <div class="stat-card c2">
                        <div class="stat-label">Sedang Dipinjam</div>
                        <div class="stat-value"><?= $cnt_dipinjam ?></div>
                    </div>
                </a>
                <a href="?status=dikembalikan" style="text-decoration:none;">
                    <div class="stat-card c3">
                        <div class="stat-label">Sudah Dikembalikan</div>
                        <div class="stat-value"><?= $cnt_kembali ?></div>
                    </div>
                </a>
            </div>

            <form method="GET" class="toolbar">
                <input type="text" name="search" placeholder="Cari nama siswa, NIS, atau alat..."
                       value="<?= htmlspecialchars($search) ?>">
                <select name="status">
                    <option value="" <?= !$filter_status ? 'selected' : '' ?>>Semua Status</option>
                    <option value="pending"      <?= $filter_status == 'pending'      ? 'selected' : '' ?>>Pending</option>
                    <option value="dipinjam"     <?= $filter_status == 'dipinjam'     ? 'selected' : '' ?>>Dipinjam</option>
                    <option value="dikembalikan" <?= $filter_status == 'dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                    <option value="terlambat"    <?= $filter_status == 'terlambat'    ? 'selected' : '' ?>>Terlambat</option>
                </select>
                <select name="jurusan">
                    <option value="0">Semua Jurusan</option>
                    <?php while ($j = mysqli_fetch_assoc($query_jurusan)): ?>
                    <option value="<?= $j['id_jurusan'] ?>" <?= $filter_jurusan == $j['id_jurusan'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($j['nama_jurusan']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-amber">Cari</button>
                <a href="riwayat_superadmin.php" class="btn btn-slate">Reset</a>
            </form>

            <div class="table-wrap">
                <div class="table-info"><?= mysqli_num_rows($query) ?> data ditemukan</div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Siswa</th>
                            <th>Alat</th>
                            <th>Jml</th>
                            <th>Tgl Pinjam</th>
                            <th>Batas Kembali</th>
                            <th>Status</th>
                            <th>Denda</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query) > 0): ?>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($query)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div style="font-weight:600; color:#f1f5f9; font-size:12px;"><?= htmlspecialchars($row['nama_siswa']) ?></div>
                                    <div style="font-size:11px; color:#64748b;"><?= $row['nis'] ?></div>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($row['nama_barang']) ?></div>
                                    <div style="font-size:11px; color:#64748b;"><?= $row['kode_jurusan'] ?></div>
                                </td>
                                <td style="text-align:center;"><?= $row['jumlah_pinjam'] ?></td>
                                <td style="font-size:12px;"><?= date('d/m/Y H:i', strtotime($row['tgl_pinjam'])) ?></td>
                                <td style="font-size:12px;">
                                    <?php
                                    $batas = strtotime($row['tgl_kembali_seharusnya']);
                                    $now   = time();
                                    $telat = ($now > $batas && $row['status'] == 'dipinjam');
                                    ?>
                                    <span style="<?= $telat ? 'color:#f87171;' : '' ?>">
                                        <?= date('d/m/Y', $batas) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $row['status'] ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['denda'] > 0): ?>
                                        <span style="color:#f87171; font-size:12px; font-weight:600;">
                                            Rp <?= number_format($row['denda'], 0, ',', '.') ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#334155; font-size:12px;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <a href="?approve=<?= $row['id_peminjaman'] ?>" class="aksi-btn btn-approve"
                                           onclick="return confirm('Setujui peminjaman ini?')">Approve</a>
                                        <a href="?tolak=<?= $row['id_peminjaman'] ?>" class="aksi-btn btn-tolak"
                                           onclick="return confirm('Tolak dan hapus peminjaman ini?')">Tolak</a>
                                    <?php elseif ($row['status'] == 'dipinjam'): ?>
                                        <button class="aksi-btn btn-kembali"
                                            onclick="bukaKembali(
                                                <?= $row['id_peminjaman'] ?>,
                                                '<?= htmlspecialchars($row['nama_siswa'], ENT_QUOTES) ?>',
                                                '<?= htmlspecialchars($row['nama_barang'], ENT_QUOTES) ?>',
                                                '<?= date('d/m/Y', strtotime($row['tgl_pinjam'])) ?>'
                                            )">Kembalikan</button>
                                    <?php else: ?>
                                        <span style="color:#334155; font-size:12px;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align:center; color:#475569; padding:28px;">Tidak ada data peminjaman.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal-bg" id="modal-kembali">
    <div class="modal">
        <div class="modal-title">Proses Pengembalian</div>
        <div class="info-box" id="kembali-info"></div>
        <form method="POST">
            <input type="hidden" name="aksi" value="kembalikan">
            <input type="hidden" name="id_peminjaman" id="kembali-id">
            <div class="form-group">
                <label>Kondisi Alat Kembali</label>
                <select name="kondisi_kembali">
                    <option value="bagus">Bagus</option>
                    <option value="rusak">Rusak</option>
                    <option value="hilang">Hilang</option>
                </select>
            </div>
            <div class="form-group">
                <label>Denda (Rp)</label>
                <input type="number" name="denda" id="kembali-denda" value="0" min="0" step="5000">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="tutupModal('modal-kembali')">Batal</button>
                <button type="submit" class="btn btn-amber">Konfirmasi</button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModal(id) { document.getElementById(id).classList.add('show'); }
function tutupModal(id) { document.getElementById(id).classList.remove('show'); }

function bukaKembali(id, siswa, alat, tgl) {
    document.getElementById('kembali-id').value = id;
    document.getElementById('kembali-info').innerHTML =
        '<strong>Siswa:</strong> ' + siswa + '<br>' +
        '<strong>Alat:</strong> ' + alat + '<br>' +
        '<strong>Dipinjam:</strong> ' + tgl;
    bukaModal('modal-kembali');
}

document.querySelectorAll('.modal-bg').forEach(function(el) {
    el.addEventListener('click', function(e) { if (e.target === el) tutupModal(el.id); });
});
</script>
</body>
</html>
