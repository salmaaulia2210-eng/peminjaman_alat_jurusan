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

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id == $_SESSION['id_admin']) {
        $pesan = 'error|Tidak bisa menghapus akun sendiri.';
    } else {
        mysqli_query($conn, "DELETE FROM admin WHERE id_admin = $id");
        header("Location: data_admin_superadmin.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi'])) {

    if ($_POST['aksi'] == 'tambah') {
        $username   = mysqli_real_escape_string($conn, trim($_POST['username']));
        $password   = mysqli_real_escape_string($conn, trim($_POST['password']));
        $nama       = mysqli_real_escape_string($conn, trim($_POST['nama_admin']));
        $hak_akses  = $_POST['hak_akses'] == 'super_admin' ? 'super_admin' : 'admin_jurusan';
        $id_jurusan = $hak_akses == 'admin_jurusan' ? (int)$_POST['id_jurusan'] : 'NULL';

        $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_admin FROM admin WHERE username = '$username'"));
        if ($cek) {
            $pesan = 'error|Username sudah digunakan.';
        } elseif (empty($username) || empty($password) || empty($nama)) {
            $pesan = 'error|Semua field wajib diisi.';
        } else {
            $jur_val = $id_jurusan === 'NULL' ? 'NULL' : $id_jurusan;
            mysqli_query($conn, "
                INSERT INTO admin (username, password, nama_admin, id_jurusan, hak_akses)
                VALUES ('$username', '$password', '$nama', $jur_val, '$hak_akses')
            ");
            $pesan = 'sukses|Admin berhasil ditambahkan.';
        }
    }

    if ($_POST['aksi'] == 'edit') {
        $id        = (int)$_POST['id_admin'];
        $nama      = mysqli_real_escape_string($conn, trim($_POST['nama_admin']));
        $hak_akses = $_POST['hak_akses'] == 'super_admin' ? 'super_admin' : 'admin_jurusan';
        $id_jurusan = $hak_akses == 'admin_jurusan' ? (int)$_POST['id_jurusan'] : 'NULL';
        $jur_val   = $id_jurusan === 'NULL' ? 'NULL' : $id_jurusan;

        if (!empty($_POST['password'])) {
            $password = mysqli_real_escape_string($conn, trim($_POST['password']));
            mysqli_query($conn, "UPDATE admin SET nama_admin='$nama', password='$password', hak_akses='$hak_akses', id_jurusan=$jur_val WHERE id_admin=$id");
        } else {
            mysqli_query($conn, "UPDATE admin SET nama_admin='$nama', hak_akses='$hak_akses', id_jurusan=$jur_val WHERE id_admin=$id");
        }
        $pesan = 'sukses|Data admin berhasil diupdate.';
    }
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where  = $search ? "WHERE a.nama_admin LIKE '%$search%' OR a.username LIKE '%$search%'" : '';

$query_admin = mysqli_query($conn, "
    SELECT a.*, j.nama_jurusan, j.kode_jurusan
    FROM admin a
    LEFT JOIN jurusan j ON a.id_jurusan = j.id_jurusan
    $where
    ORDER BY a.hak_akses ASC, a.nama_admin ASC
");

$query_jurusan = mysqli_query($conn, "SELECT * FROM jurusan ORDER BY nama_jurusan ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Admin Super Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; overflow: hidden; }
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
        .content { flex: 1; overflow-y: auto; padding: 24px 28px; background: #0f172a; }
        .content::-webkit-scrollbar { width: 5px; }
        .content::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        .toolbar { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .toolbar input { flex: 1; min-width: 200px; padding: 9px 14px; background: #1e293b; border: 1px solid rgba(255,255,255,.07); border-radius: 9px; color: #f1f5f9; font-size: 13px; outline: none; }
        .toolbar input::placeholder { color: #475569; }
        .btn { padding: 9px 18px; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: .15s; text-decoration: none; display: inline-block; }
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

        .badge { display: inline-block; padding: 3px 9px; border-radius: 5px; font-size: 11px; font-weight: 700; }
        .badge-super  { background: rgba(217,119,6,.15); color: #fbbf24; }
        .badge-jurusan { background: rgba(96,165,250,.15); color: #60a5fa; }

        .aksi-btn { padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; border: none; margin-right: 4px; text-decoration: none; display: inline-block; transition: .15s; }
        .btn-edit  { background: rgba(37,99,235,.2); color: #60a5fa; }
        .btn-edit:hover  { background: rgba(37,99,235,.35); }
        .btn-hapus { background: rgba(185,28,28,.2); color: #f87171; }
        .btn-hapus:hover { background: rgba(185,28,28,.35); }

        .modal-bg { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,.65); z-index: 100; align-items: center; justify-content: center; }
        .modal-bg.show { display: flex; }
        .modal { background: #1e293b; border-radius: 14px; padding: 26px; width: 440px; border: 1px solid rgba(255,255,255,.08); max-height: 90vh; overflow-y: auto; }
        .modal-title { font-size: 15px; font-weight: 700; color: #f1f5f9; margin-bottom: 18px; }
        .form-group { margin-bottom: 13px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 9px 13px; background: #0f172a; border: 1px solid rgba(255,255,255,.08); border-radius: 8px; color: #f1f5f9; font-size: 13px; outline: none; }
        .form-group select option { background: #1e293b; }
        .form-hint { font-size: 11px; color: #475569; margin-top: 4px; }
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
            <a href="data_admin_superadmin.php" class="menu-item active">Data Admin</a>
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
                <div class="topbar-title">Data Admin</div>
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

            <form method="GET" class="toolbar">
                <input type="text" name="search" placeholder="Cari nama atau username admin..."
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-amber">Cari</button>
                <a href="data_admin_superadmin.php" class="btn btn-slate">Reset</a>
                <button type="button" class="btn btn-amber" onclick="bukaModal('modal-tambah')">+ Tambah Admin</button>
            </form>

            <div class="table-wrap">
                <div class="table-info"><?= mysqli_num_rows($query_admin) ?> admin ditemukan</div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Admin</th>
                            <th>Username</th>
                            <th>Hak Akses</th>
                            <th>Jurusan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query_admin) > 0): ?>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($query_admin)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td style="font-weight:600; color:#f1f5f9;"><?= htmlspecialchars($row['nama_admin']) ?></td>
                                <td style="color:#64748b; font-size:12px;"><?= htmlspecialchars($row['username']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $row['hak_akses'] == 'super_admin' ? 'super' : 'jurusan' ?>">
                                        <?= $row['hak_akses'] == 'super_admin' ? 'Super Admin' : 'Admin Jurusan' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['nama_jurusan']): ?>
                                        <span style="font-size:11px; padding:2px 7px; border-radius:4px; background:rgba(217,119,6,.12); color:#fbbf24;">
                                            <?= $row['kode_jurusan'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#334155; font-size:12px;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="aksi-btn btn-edit"
                                        onclick="bukaEdit(
                                            <?= $row['id_admin'] ?>,
                                            '<?= htmlspecialchars($row['nama_admin'], ENT_QUOTES) ?>',
                                            '<?= $row['hak_akses'] ?>',
                                            <?= $row['id_jurusan'] ?? 0 ?>
                                        )">Edit</button>
                                    <?php if ($row['id_admin'] != $_SESSION['id_admin']): ?>
                                    <a href="?hapus=<?= $row['id_admin'] ?>"
                                       class="aksi-btn btn-hapus"
                                       onclick="return confirm('Hapus admin <?= htmlspecialchars($row['nama_admin'], ENT_QUOTES) ?>?')">Hapus</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; color:#475569; padding:28px;">Tidak ada data admin.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal-bg" id="modal-tambah">
    <div class="modal">
        <div class="modal-title">Tambah Admin Baru</div>
        <form method="POST">
            <input type="hidden" name="aksi" value="tambah">
            <div class="form-group">
                <label>Nama Admin</label>
                <input type="text" name="nama_admin" placeholder="Nama lengkap" required>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Username login" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <label>Hak Akses</label>
                <select name="hak_akses" id="tambah-hakakses" onchange="toggleJurusan('tambah')">
                    <option value="admin_jurusan">Admin Jurusan</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
            <div class="form-group" id="tambah-jurusan-wrap">
                <label>Jurusan</label>
                <select name="id_jurusan">
                    <option value="">-- Pilih Jurusan --</option>
                    <?php
                    $qj = mysqli_query($conn, "SELECT * FROM jurusan ORDER BY nama_jurusan ASC");
                    while ($j = mysqli_fetch_assoc($qj)):
                    ?>
                    <option value="<?= $j['id_jurusan'] ?>"><?= htmlspecialchars($j['nama_jurusan']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="tutupModal('modal-tambah')">Batal</button>
                <button type="submit" class="btn btn-amber">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-bg" id="modal-edit">
    <div class="modal">
        <div class="modal-title">Edit Admin</div>
        <form method="POST">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id_admin" id="edit-id">
            <div class="form-group">
                <label>Nama Admin</label>
                <input type="text" name="nama_admin" id="edit-nama" required>
            </div>
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="password" placeholder="Kosongkan jika tidak diubah">
                <div class="form-hint">Biarkan kosong jika tidak ingin mengubah password.</div>
            </div>
            <div class="form-group">
                <label>Hak Akses</label>
                <select name="hak_akses" id="edit-hakakses" onchange="toggleJurusan('edit')">
                    <option value="admin_jurusan">Admin Jurusan</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
            <div class="form-group" id="edit-jurusan-wrap">
                <label>Jurusan</label>
                <select name="id_jurusan" id="edit-jurusan">
                    <option value="">-- Pilih Jurusan --</option>
                    <?php
                    $qj2 = mysqli_query($conn, "SELECT * FROM jurusan ORDER BY nama_jurusan ASC");
                    while ($j = mysqli_fetch_assoc($qj2)):
                    ?>
                    <option value="<?= $j['id_jurusan'] ?>"><?= htmlspecialchars($j['nama_jurusan']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="tutupModal('modal-edit')">Batal</button>
                <button type="submit" class="btn btn-amber">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModal(id) { document.getElementById(id).classList.add('show'); }
function tutupModal(id) { document.getElementById(id).classList.remove('show'); }

function toggleJurusan(prefix) {
    var hak   = document.getElementById(prefix + '-hakakses').value;
    var wrap  = document.getElementById(prefix + '-jurusan-wrap');
    wrap.style.display = (hak == 'super_admin') ? 'none' : 'block';
}

function bukaEdit(id, nama, hak, jurusan) {
    document.getElementById('edit-id').value       = id;
    document.getElementById('edit-nama').value     = nama;
    document.getElementById('edit-hakakses').value = hak;
    document.getElementById('edit-jurusan').value  = jurusan;
    toggleJurusan('edit');
    bukaModal('modal-edit');
}

document.querySelectorAll('.modal-bg').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (e.target === el) tutupModal(el.id);
    });
});
</script>
</body>
</html>
