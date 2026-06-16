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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi'])) {
    if ($_POST['aksi'] == 'bayar') {
        $id = (int)$_POST['id_peminjaman'];
        mysqli_query($conn, "UPDATE peminjaman SET denda = 0 WHERE id_peminjaman = $id");
        $pesan = 'sukses|Denda berhasil ditandai lunas.';
    }
    if ($_POST['aksi'] == 'set_denda') {
        $id    = (int)$_POST['id_peminjaman'];
        $denda = (int)$_POST['denda'];
        mysqli_query($conn, "UPDATE peminjaman SET denda = $denda WHERE id_peminjaman = $id");
        $pesan = 'sukses|Denda berhasil diperbarui.';
    }
}

$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search        = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where = "WHERE p.denda > 0";
if ($filter_status == 'lunas')    $where = "WHERE p.denda = 0";
if ($filter_status == 'semua')    $where = "WHERE 1=1";
if ($search) $where .= " AND (s.nama_siswa LIKE '%$search%' OR b.nama_barang LIKE '%$search%')";

$query = mysqli_query($conn, "
    SELECT p.*, s.nama_siswa, s.nis, s.kelas,
           b.nama_barang, b.kode_barang,
           j.nama_jurusan, j.kode_jurusan
    FROM peminjaman p
    JOIN siswa s ON p.id_siswa = s.id_siswa
    JOIN barang b ON p.id_barang = b.id_barang
    JOIN jurusan j ON b.id_jurusan = j.id_jurusan
    $where
    ORDER BY p.denda DESC, p.tgl_pinjam DESC
");

$total_denda = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(denda) as total FROM peminjaman"))['total'] ?? 0;
$belum_lunas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE denda > 0"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Denda - Super Admin</title>
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
        .stat-card { background: #1e293b; border: 1px solid rgba(255,255,255,.06); border-radius: 12px; padding: 18px 20px; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; }
        .stat-card.c1::before { background: #f87171; }
        .stat-card.c2::before { background: #fbbf24; }
        .stat-card.c3::before { background: #34d399; }
        .stat-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .stat-value { font-size: 24px; font-weight: 700; }
        .c1 .stat-value { color: #f87171; }
        .c2 .stat-value { color: #fbbf24; }
        .c3 .stat-value { color: #34d399; }

        .toolbar { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .toolbar input, .toolbar select { padding: 9px 14px; background: #1e293b; border: 1px solid rgba(255,255,255,.07); border-radius: 9px; color: #f1f5f9; font-size: 13px; outline: none; }
        .toolbar input { flex: 1; min-width: 200px; }
        .toolbar input::placeholder { color: #475569; }
        .toolbar select option { background: #1e293b; }
        .btn { padding: 9px 18px; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: .15s; }
        .btn-amber { background: #d97706; color: white; }
        .btn-amber:hover { background: #b45309; }
        .btn-slate { background: rgba(255,255,255,.07); color: #94a3b8; }
        .btn-slate:hover { background: rgba(255,255,255,.12); }

        .alert { padding: 12px 16px; border-radius: 9px; margin-bottom: 16px; font-size: 13px; }
        .alert-sukses { background: rgba(52,211,153,.12); color: #34d399; border: 1px solid rgba(52,211,153,.2); }
        .alert-error  { background: rgba(248,113,113,.12); color: #f87171; border: 1px solid rgba(248,113,113,.2); }

        .table-wrap { background: #1e293b; border: 1px solid rgba(255,255,255,.06); border-radius: 12px; overflow: hidden; }
        .table-info { padding: 13px 18px; font-size: 12px; color: #64748b; border-bottom: 1px solid rgba(255,255,255,.05); }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #0f172a; }
        thead th { padding: 12px 14px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .8px; text-align: left; }
        tbody tr { border-top: 1px solid rgba(255,255,255,.04); }
        tbody tr:hover { background: rgba(255,255,255,.02); }
        tbody td { padding: 10px 14px; font-size: 13px; color: #cbd5e1; vertical-align: middle; }

        .denda-amount { color: #f87171; font-weight: 700; }
        .denda-lunas  { color: #34d399; font-weight: 600; font-size: 12px; }
        .aksi-btn { padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; border: none; margin-right: 4px; text-decoration: none; display: inline-block; transition: .15s; }
        .btn-lunas { background: rgba(52,211,153,.2); color: #34d399; }
        .btn-lunas:hover { background: rgba(52,211,153,.35); }
        .btn-edit  { background: rgba(37,99,235,.2); color: #60a5fa; }
        .btn-edit:hover { background: rgba(37,99,235,.35); }

        .modal-bg { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,.65); z-index: 100; align-items: center; justify-content: center; }
        .modal-bg.show { display: flex; }
        .modal { background: #1e293b; border-radius: 14px; padding: 26px; width: 400px; border: 1px solid rgba(255,255,255,.08); }
        .modal-title { font-size: 15px; font-weight: 700; color: #f1f5f9; margin-bottom: 18px; }
        .form-group { margin-bottom: 13px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 9px 13px; background: #0f172a; border: 1px solid rgba(255,255,255,.08); border-radius: 8px; color: #f1f5f9; font-size: 13px; outline: none; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
        .btn-cancel { padding: 9px 18px; border-radius: 9px; background: rgba(255,255,255,.06); color: #94a3b8; font-size: 13px; font-weight: 600; border: none; cursor: pointer; }
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
            <a href="denda_superadmin.php"       class="menu-item active">Denda</a>
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
                <div class="topbar-title">Denda</div>
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
                [$tipe, $msg] = explode('|', $pesan); ?>
                <div class="alert alert-<?= $tipe ?>"><?= $msg ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card c1">
                    <div class="stat-label">Total Denda Terkumpul</div>
                    <div class="stat-value">Rp <?= number_format($total_denda, 0, ',', '.') ?></div>
                </div>
                <div class="stat-card c2">
                    <div class="stat-label">Belum Lunas</div>
                    <div class="stat-value"><?= $belum_lunas ?> Siswa</div>
                </div>
                <div class="stat-card c3">
                    <div class="stat-label">Tarif Denda</div>
                    <div class="stat-value" style="font-size:16px; padding-top:4px;">Rp 5.000 / hari</div>
                </div>
            </div>

            <form method="GET" class="toolbar">
                <input type="text" name="search" placeholder="Cari nama siswa atau nama alat..."
                       value="<?= htmlspecialchars($search) ?>">
                <select name="status">
                    <option value=""      <?= !$filter_status ? 'selected' : '' ?>>Belum Lunas</option>
                    <option value="lunas" <?= $filter_status == 'lunas' ? 'selected' : '' ?>>Sudah Lunas</option>
                    <option value="semua" <?= $filter_status == 'semua' ? 'selected' : '' ?>>Semua</option>
                </select>
                <button type="submit" class="btn btn-amber">Cari</button>
                <a href="denda_superadmin.php" class="btn btn-slate">Reset</a>
            </form>

            <div class="table-wrap">
                <div class="table-info"><?= mysqli_num_rows($query) ?> data ditemukan</div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Siswa</th>
                            <th>Alat</th>
                            <th>Jurusan</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
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
                                    <div style="font-weight:600; color:#f1f5f9;"><?= htmlspecialchars($row['nama_siswa']) ?></div>
                                    <div style="font-size:11px; color:#64748b;"><?= $row['nis'] ?> · <?= $row['kelas'] ?></div>
                                </td>
                                <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                <td>
                                    <span style="font-size:11px; padding:2px 7px; border-radius:4px; background:rgba(217,119,6,.12); color:#fbbf24;">
                                        <?= $row['kode_jurusan'] ?>
                                    </span>
                                </td>
                                <td style="font-size:12px;"><?= date('d/m/Y', strtotime($row['tgl_pinjam'])) ?></td>
                                <td style="font-size:12px;">
                                    <?= $row['tgl_kembali'] ? date('d/m/Y', strtotime($row['tgl_kembali'])) : '<span style="color:#f87171;">Belum</span>' ?>
                                </td>
                                <td>
                                    <?php if ($row['denda'] > 0): ?>
                                        <span class="denda-amount">Rp <?= number_format($row['denda'], 0, ',', '.') ?></span>
                                    <?php else: ?>
                                        <span class="denda-lunas">✓ Lunas</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['denda'] > 0): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="aksi" value="bayar">
                                        <input type="hidden" name="id_peminjaman" value="<?= $row['id_peminjaman'] ?>">
                                        <button type="submit" class="aksi-btn btn-lunas"
                                            onclick="return confirm('Tandai denda ini sebagai lunas?')">Lunas</button>
                                    </form>
                                    <?php endif; ?>
                                    <button class="aksi-btn btn-edit"
                                        onclick="bukaEditDenda(<?= $row['id_peminjaman'] ?>, <?= $row['denda'] ?>)">Edit</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; color:#475569; padding:28px;">Tidak ada data denda.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal-bg" id="modal-edit-denda">
    <div class="modal">
        <div class="modal-title">Edit Nominal Denda</div>
        <form method="POST">
            <input type="hidden" name="aksi" value="set_denda">
            <input type="hidden" name="id_peminjaman" id="denda-id">
            <div class="form-group">
                <label>Nominal Denda (Rp)</label>
                <input type="number" name="denda" id="denda-nilai" min="0" step="5000">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="tutupModal('modal-edit-denda')">Batal</button>
                <button type="submit" class="btn btn-amber">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModal(id) { document.getElementById(id).classList.add('show'); }
function tutupModal(id) { document.getElementById(id).classList.remove('show'); }
function bukaEditDenda(id, denda) {
    document.getElementById('denda-id').value    = id;
    document.getElementById('denda-nilai').value = denda;
    bukaModal('modal-edit-denda');
}
document.querySelectorAll('.modal-bg').forEach(function(el) {
    el.addEventListener('click', function(e) { if (e.target === el) tutupModal(el.id); });
});
</script>
</body>
</html>
