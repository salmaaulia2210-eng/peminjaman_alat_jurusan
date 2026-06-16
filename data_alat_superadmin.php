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

$filter_jurusan = isset($_GET['jurusan']) ? (int)$_GET['jurusan'] : 0;
$search         = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$pesan          = '';

if (isset($_GET['hapus'])) {
    $id  = (int)$_GET['hapus'];
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT foto FROM barang WHERE id_barang = $id"));
    if ($row && $row['foto'] && file_exists("uploads/" . $row['foto'])) {
        unlink("uploads/" . $row['foto']);
    }
    mysqli_query($conn, "DELETE FROM barang WHERE id_barang = $id");
    header("Location: data_alat_superadmin.php" . ($filter_jurusan ? "?jurusan=$filter_jurusan" : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi'])) {

    if ($_POST['aksi'] == 'tambah') {
        $kode       = mysqli_real_escape_string($conn, $_POST['kode_barang']);
        $nama       = mysqli_real_escape_string($conn, $_POST['nama_barang']);
        $id_jurusan = (int)$_POST['id_jurusan'];
        $stok_total = (int)$_POST['stok_total'];
        $kondisi    = $_POST['kondisi'] == 'rusak' ? 'rusak' : 'baik';
        $foto       = '';

        if (!empty($_FILES['foto']['name'])) {
            $ext       = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nama_file = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $nama) . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/" . $nama_file);
            $foto = $nama_file;
        }

        $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_barang FROM barang WHERE kode_barang = '$kode'"));
        if ($cek) {
            $pesan = 'error|Kode barang sudah digunakan.';
        } else {
            mysqli_query($conn, "
                INSERT INTO barang (kode_barang, nama_barang, id_jurusan, stok_total, stok_tersedia, kondisi, foto)
                VALUES ('$kode', '$nama', $id_jurusan, $stok_total, $stok_total, '$kondisi', '$foto')
            ");
            $pesan = 'sukses|Alat berhasil ditambahkan.';
        }
    }

    if ($_POST['aksi'] == 'edit') {
        $id         = (int)$_POST['id_barang'];
        $nama       = mysqli_real_escape_string($conn, $_POST['nama_barang']);
        $stok_total = (int)$_POST['stok_total'];
        $kondisi    = $_POST['kondisi'] == 'rusak' ? 'rusak' : 'baik';
        $foto_lama  = mysqli_real_escape_string($conn, $_POST['foto_lama']);

        if (!empty($_FILES['foto']['name'])) {
            if ($foto_lama && file_exists("uploads/" . $foto_lama)) {
                unlink("uploads/" . $foto_lama);
            }
            $ext       = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nama_file = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $nama) . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/" . $nama_file);
            $foto_lama = $nama_file;
        }

        mysqli_query($conn, "
            UPDATE barang SET nama_barang = '$nama', stok_total = $stok_total,
                              kondisi = '$kondisi', foto = '$foto_lama'
            WHERE id_barang = $id
        ");
        $pesan = 'sukses|Data alat berhasil diupdate.';
    }
}

$query_jurusan = mysqli_query($conn, "SELECT * FROM jurusan ORDER BY nama_jurusan ASC");

$where = "WHERE 1=1";
if ($filter_jurusan) $where .= " AND b.id_jurusan = $filter_jurusan";
if ($search)         $where .= " AND (b.nama_barang LIKE '%$search%' OR b.kode_barang LIKE '%$search%')";

$query_barang = mysqli_query($conn, "
    SELECT b.*, j.nama_jurusan, j.kode_jurusan
    FROM barang b
    JOIN jurusan j ON b.id_jurusan = j.id_jurusan
    $where
    ORDER BY j.id_jurusan ASC, b.nama_barang ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Alat Super Admin</title>
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

        .admin-info {
            padding: 14px 18px; border-bottom: 1px solid rgba(255,255,255,.05);
            display: flex; align-items: center; gap: 11px;
        }

        .admin-avatar {
            width: 38px; height: 38px; border-radius: 50%; background: #d97706;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; font-weight: 700; color: white; flex-shrink: 0;
        }

        .admin-name  { font-size: 13px; font-weight: 600; color: #f1f5f9; }
        .admin-badge {
            display: inline-block; margin-top: 3px; padding: 2px 8px; border-radius: 4px;
            font-size: 10px; font-weight: 700; background: rgba(217,119,6,.2); color: #fbbf24;
        }

        .sidebar-menu { flex: 1; overflow-y: auto; padding: 8px 0; }
        .sidebar-menu::-webkit-scrollbar { width: 0; }

        .menu-section {
            padding: 12px 20px 5px; font-size: 10px; font-weight: 700; color: #334155;
            text-transform: uppercase; letter-spacing: 1.4px;
        }

        .menu-item {
            display: block; padding: 11px 20px; color: #94a3b8; text-decoration: none;
            font-size: 13px; border-left: 3px solid transparent; transition: .15s;
        }
        .menu-item:hover { background: rgba(255,255,255,.04); color: #f1f5f9; }
        .menu-item.active {
            background: rgba(217,119,6,.1); border-left: 3px solid #f59e0b;
            color: #fde68a; font-weight: 700;
        }

        .sidebar-footer { padding: 14px; }
        .btn-logout {
            display: block; width: 100%; padding: 11px; border-radius: 10px;
            background: rgba(185,28,28,.2); color: #f87171; font-size: 13px; font-weight: 700;
            text-align: center; text-decoration: none; border: 1px solid rgba(185,28,28,.25); transition: .15s;
        }
        .btn-logout:hover { background: rgba(185,28,28,.35); }

        .main { margin-left: 230px; flex: 1; display: flex; flex-direction: column; height: 100vh; }

        .topbar {
            height: 60px; min-height: 60px; background: #111827;
            border-bottom: 1px solid rgba(255,255,255,.05);
            display: flex; align-items: center; justify-content: space-between; padding: 0 28px;
        }

        .topbar-left { display: flex; align-items: center; gap: 12px; }
        .topbar-title { font-size: 20px; font-weight: 700; color: #f1f5f9; }
        .topbar-chip {
            padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700;
            background: rgba(217,119,6,.15); color: #fbbf24; border: 1px solid rgba(217,119,6,.2);
        }
        .topbar-right { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; }

        .content { flex: 1; overflow-y: auto; padding: 24px 28px; background: #0f172a; }
        .content::-webkit-scrollbar { width: 5px; }
        .content::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        .alert { padding: 12px 16px; border-radius: 9px; margin-bottom: 16px; font-size: 13px; }
        .alert-sukses { background: rgba(52,211,153,.12); color: #34d399; border: 1px solid rgba(52,211,153,.2); }
        .alert-error  { background: rgba(248,113,113,.12); color: #f87171; border: 1px solid rgba(248,113,113,.2); }

        .toolbar {
            display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;
        }

        .toolbar input, .toolbar select {
            padding: 9px 14px; background: #1e293b;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 9px; color: #f1f5f9; font-size: 13px; outline: none;
        }
        .toolbar input { flex: 1; min-width: 180px; }
        .toolbar input::placeholder { color: #475569; }
        .toolbar select option { background: #1e293b; }

        .btn {
            padding: 9px 18px; border-radius: 9px; font-size: 13px; font-weight: 600;
            cursor: pointer; border: none; transition: .15s; text-decoration: none; display: inline-block;
        }
        .btn-amber { background: #d97706; color: white; }
        .btn-amber:hover { background: #b45309; }
        .btn-slate { background: rgba(255,255,255,.07); color: #94a3b8; }
        .btn-slate:hover { background: rgba(255,255,255,.12); }

        .table-wrap {
            background: #1e293b; border: 1px solid rgba(255,255,255,.06);
            border-radius: 12px; overflow: hidden;
        }

        .table-info {
            padding: 13px 18px; font-size: 12px; color: #64748b;
            border-bottom: 1px solid rgba(255,255,255,.05);
        }
        .table-info strong { color: #f1f5f9; }

        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #0f172a; }
        thead th {
            padding: 12px 14px; font-size: 11px; font-weight: 700;
            color: #64748b; text-transform: uppercase; letter-spacing: .8px; text-align: left;
            white-space: nowrap;
        }
        tbody tr { border-top: 1px solid rgba(255,255,255,.04); }
        tbody tr:hover { background: rgba(255,255,255,.02); }
        tbody td { padding: 10px 14px; font-size: 13px; color: #cbd5e1; vertical-align: middle; }

        .foto-wrap {
            width: 52px; height: 52px; border-radius: 8px; overflow: hidden;
            background: #0f172a; border: 1px solid rgba(255,255,255,.08);
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .foto-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .foto-wrap .ph { font-size: 20px; color: #334155; }

        .badge {
            display: inline-block; padding: 3px 9px; border-radius: 5px;
            font-size: 11px; font-weight: 700;
        }
        .badge-baik  { background: rgba(52,211,153,.15); color: #34d399; }
        .badge-rusak { background: rgba(248,113,113,.15); color: #f87171; }
        .badge-jrs   { background: rgba(217,119,6,.12);   color: #fbbf24; }

        .stok-val { font-weight: 700; color: #34d399; }

        .aksi-btn {
            padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 600;
            cursor: pointer; border: none; margin-right: 4px; text-decoration: none;
            display: inline-block; transition: .15s;
        }
        .btn-edit  { background: rgba(37,99,235,.2);  color: #60a5fa; }
        .btn-edit:hover  { background: rgba(37,99,235,.35); }
        .btn-hapus { background: rgba(185,28,28,.2);  color: #f87171; }
        .btn-hapus:hover { background: rgba(185,28,28,.35); }

        .modal-bg {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.65); z-index: 100;
            align-items: center; justify-content: center;
        }
        .modal-bg.show { display: flex; }

        .modal {
            background: #1e293b; border-radius: 14px; padding: 26px; width: 460px;
            border: 1px solid rgba(255,255,255,.08); max-height: 90vh; overflow-y: auto;
        }
        .modal::-webkit-scrollbar { width: 4px; }
        .modal::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

        .modal-title { font-size: 15px; font-weight: 700; color: #f1f5f9; margin-bottom: 18px; }

        .form-group { margin-bottom: 13px; }
        .form-group label {
            display: block; font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%; padding: 9px 13px; background: #0f172a;
            border: 1px solid rgba(255,255,255,.08); border-radius: 8px;
            color: #f1f5f9; font-size: 13px; outline: none;
        }
        .form-group input[type="file"] { padding: 7px 13px; cursor: pointer; }
        .form-group select option { background: #1e293b; }

        .preview-foto {
            width: 70px; height: 70px; border-radius: 8px; object-fit: cover;
            margin-top: 8px; border: 1px solid rgba(255,255,255,.1); display: none;
        }

        .modal-footer {
            display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px;
        }
        .btn-cancel {
            padding: 9px 18px; border-radius: 9px; background: rgba(255,255,255,.06);
            color: #94a3b8; font-size: 13px; font-weight: 600; border: none; cursor: pointer;
        }
        .btn-cancel:hover { background: rgba(255,255,255,.1); }

        @media(max-width:1100px) {
            .table-wrap { overflow-x: auto; }
            table { min-width: 900px; }
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
            <a href="dashboard_superadmin.php" class="menu-item">Dashboard</a>
            <div class="menu-section">Manajemen</div>
            <a href="data_alat_superadmin.php"  class="menu-item active">Data Alat</a>
            <a href="data_admin_superadmin.php" class="menu-item">Data Admin</a>
            <a href="denda_superadmin.php"       class="menu-item">Denda</a>
            <a href="riwayat_superadmin.php"     class="menu-item">Riwayat</a>
            <div class="menu-section">Sistem</div>
            <a href="laporan_superadmin.php"     class="menu-item">Laporan</a>
            <a href="profil_superadmin.php"      class="menu-item">Profil</a>
        </div>
        <div class="sidebar-footer">
            <a href="logout_adminn.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <div class="topbar-title">Data Alat</div>
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
                <div class="alert alert-<?= $tipe ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <form method="GET" class="toolbar">
                <input type="text" name="search" placeholder="Cari nama atau kode alat..."
                       value="<?= htmlspecialchars($search) ?>">
                <select name="jurusan">
                    <option value="0">Semua Jurusan</option>
                    <?php while ($j = mysqli_fetch_assoc($query_jurusan)): ?>
                    <option value="<?= $j['id_jurusan'] ?>"
                        <?= $filter_jurusan == $j['id_jurusan'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($j['nama_jurusan']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-amber">Cari</button>
                <a href="data_alat_superadmin.php" class="btn btn-slate">Reset</a>
                <button type="button" class="btn btn-amber" onclick="bukaModal('modal-tambah')">+ Tambah Alat</button>
            </form>

            <div class="table-wrap">
                <div class="table-info">
                    <strong><?= mysqli_num_rows($query_barang) ?></strong> alat ditemukan
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Foto</th>
                            <th>Kode</th>
                            <th>Nama Alat</th>
                            <th>Jurusan</th>
                            <th>Stok Total</th>
                            <th>Tersedia</th>
                            <th>Kondisi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($query_barang) > 0):
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($query_barang)):
                            $has_foto = !empty($row['foto']);
                            $foto_src = 'uploads/' . htmlspecialchars($row['foto']);
                    ?>
                        <tr>
                            <td style="color:#64748b;"><?= $no++ ?></td>

                            <td>
                                <div class="foto-wrap">
                                    <?php if ($has_foto): ?>
                                        <img src="<?= $foto_src ?>"
                                             alt="<?= htmlspecialchars($row['nama_barang']) ?>"
                                             onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
                                        <span class="ph" style="display:none;">📷</span>
                                    <?php else: ?>
                                        <span class="ph">📷</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td style="color:#64748b; font-size:11px;"><?= htmlspecialchars($row['kode_barang']) ?></td>
                            <td><?= htmlspecialchars($row['nama_barang']) ?></td>

                            <td><span class="badge badge-jrs"><?= $row['kode_jurusan'] ?></span></td>

                            <td><?= $row['stok_total'] ?></td>
                            <td><span class="stok-val"><?= $row['stok_tersedia'] ?></span></td>

                            <td>
                                <span class="badge badge-<?= $row['kondisi'] ?>">
                                    <?= ucfirst($row['kondisi']) ?>
                                </span>
                            </td>

                            <td>
                                <button class="aksi-btn btn-edit"
                                    onclick="bukaEdit(
                                        <?= $row['id_barang'] ?>,
                                        '<?= htmlspecialchars($row['nama_barang'], ENT_QUOTES) ?>',
                                        <?= $row['stok_total'] ?>,
                                        '<?= $row['kondisi'] ?>',
                                        '<?= htmlspecialchars($row['foto'], ENT_QUOTES) ?>'
                                    )">Edit</button>
                                <a href="?hapus=<?= $row['id_barang'] ?><?= $filter_jurusan ? '&jurusan='.$filter_jurusan : '' ?>"
                                   class="aksi-btn btn-hapus"
                                   onclick="return confirm('Hapus alat ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr>
                            <td colspan="9" style="text-align:center; color:#475569; padding:32px;">
                                Tidak ada data alat.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal-bg" id="modal-tambah">
    <div class="modal">
        <div class="modal-title">Tambah Alat Baru</div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi" value="tambah">
            <div class="form-group">
                <label>Kode Barang</label>
                <input type="text" name="kode_barang" placeholder="cth: BRG-RPL-05" required>
            </div>
            <div class="form-group">
                <label>Nama Alat</label>
                <input type="text" name="nama_barang" placeholder="Nama alat" required>
            </div>
            <div class="form-group">
                <label>Jurusan</label>
                <select name="id_jurusan" required>
                    <option value="">-- Pilih Jurusan --</option>
                    <?php
                    $q = mysqli_query($conn, "SELECT * FROM jurusan ORDER BY nama_jurusan ASC");
                    while ($j = mysqli_fetch_assoc($q)): ?>
                    <option value="<?= $j['id_jurusan'] ?>"><?= htmlspecialchars($j['nama_jurusan']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Stok Total</label>
                <input type="number" name="stok_total" min="1" value="1" required>
            </div>
            <div class="form-group">
                <label>Kondisi</label>
                <select name="kondisi">
                    <option value="baik">Baik</option>
                    <option value="rusak">Rusak</option>
                </select>
            </div>
            <div class="form-group">
                <label>Foto Alat (opsional)</label>
                <input type="file" name="foto" accept="image/*" onchange="previewFoto(this, 'preview-tambah')">
                <img id="preview-tambah" class="preview-foto">
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
        <div class="modal-title">Edit Alat</div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id_barang"  id="edit-id">
            <input type="hidden" name="foto_lama"  id="edit-foto-lama">
            <div class="form-group">
                <label>Nama Alat</label>
                <input type="text" name="nama_barang" id="edit-nama" required>
            </div>
            <div class="form-group">
                <label>Stok Total</label>
                <input type="number" name="stok_total" id="edit-stok" min="1" required>
            </div>
            <div class="form-group">
                <label>Kondisi</label>
                <select name="kondisi" id="edit-kondisi">
                    <option value="baik">Baik</option>
                    <option value="rusak">Rusak</option>
                </select>
            </div>
            <div class="form-group">
                <label>Ganti Foto (opsional)</label>
                <input type="file" name="foto" accept="image/*" onchange="previewFoto(this, 'preview-edit')">
                <img id="foto-saat-ini" class="preview-foto">
                <img id="preview-edit"  class="preview-foto">
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

function bukaEdit(id, nama, stok, kondisi, foto) {
    document.getElementById('edit-id').value        = id;
    document.getElementById('edit-nama').value      = nama;
    document.getElementById('edit-stok').value      = stok;
    document.getElementById('edit-kondisi').value   = kondisi;
    document.getElementById('edit-foto-lama').value = foto;

    var fotoSaatIni = document.getElementById('foto-saat-ini');
    if (foto) {
        fotoSaatIni.src          = 'uploads/' + foto;
        fotoSaatIni.style.display = 'block';
    } else {
        fotoSaatIni.style.display = 'none';
    }

    document.getElementById('preview-edit').style.display = 'none';
    bukaModal('modal-edit');
}

function previewFoto(input, previewId) {
    var preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src          = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.querySelectorAll('.modal-bg').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (e.target === el) tutupModal(el.id);
    });
});
</script>
</body>
</html>