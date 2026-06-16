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

$pesan = '';
$pesan_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pinjam'])) {
    $id_barang         = intval($_POST['id_barang']);
    $jumlah_pinjam     = intval($_POST['jumlah_pinjam']);
    $tgl_kembali       = $_POST['tgl_kembali'];
    $tgl_pinjam        = date('Y-m-d H:i:s');

    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM barang WHERE id_barang='$id_barang'"));
    
    if (!$cek) {
        $pesan = "Barang tidak ditemukan!";
        $pesan_type = "err";
    } elseif ($jumlah_pinjam > $cek['stok_tersedia']) {
        $pesan = "Stok tidak cukup! Stok tersedia: " . $cek['stok_tersedia'];
        $pesan_type = "err";
    } elseif ($jumlah_pinjam < 1) {
        $pesan = "Jumlah pinjam minimal 1!";
        $pesan_type = "err";
    } else {
        $cek_aktif = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COUNT(*) as total FROM peminjaman 
            WHERE id_siswa='$id_siswa' AND id_barang='$id_barang' 
            AND status IN ('pending','dipinjam')
        "));
        
        if ($cek_aktif['total'] > 0) {
            $pesan = "Kamu sudah meminjam alat ini dan belum dikembalikan!";
            $pesan_type = "err";
        } else {
            // Insert peminjaman
            $insert = mysqli_query($conn, "
                INSERT INTO peminjaman (id_siswa, id_barang, tgl_pinjam, tgl_kembali_seharusnya, jumlah_pinjam, status)
                VALUES ('$id_siswa', '$id_barang', '$tgl_pinjam', '$tgl_kembali', '$jumlah_pinjam', 'pending')
            ");

            if ($insert) {
                // Kurangi stok
                mysqli_query($conn, "
                    UPDATE barang SET stok_tersedia = stok_tersedia - $jumlah_pinjam 
                    WHERE id_barang='$id_barang'
                ");
                $pesan = "Permintaan peminjaman berhasil dikirim! Menunggu persetujuan admin.";
                $pesan_type = "ok";
            } else {
                $pesan = "Gagal menyimpan peminjaman, coba lagi!";
                $pesan_type = "err";
            }
        }
    }
}

$id_barang_pilih = intval($_GET['id_barang'] ?? 0);
$barang_pilih = null;
if ($id_barang_pilih) {
    $barang_pilih = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT b.*, j.nama_jurusan, j.kode_jurusan 
        FROM barang b JOIN jurusan j ON b.id_jurusan = j.id_jurusan
        WHERE b.id_barang='$id_barang_pilih'
    "));
}

$q_pinjam = mysqli_query($conn, "
    SELECT p.*, b.nama_barang, b.kode_barang, j.kode_jurusan
    FROM peminjaman p
    JOIN barang b ON p.id_barang = b.id_barang
    JOIN jurusan j ON b.id_jurusan = j.id_jurusan
    WHERE p.id_siswa='$id_siswa'
    ORDER BY p.tgl_pinjam DESC
    LIMIT 20
");

$min_date = date('Y-m-d', strtotime('+1 day'));
$max_date = date('Y-m-d', strtotime('+30 days'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Peminjaman - PinjamAlat SMK</title>
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
.form-group { margin-bottom: 14px; }
.form-label { font-size: 12px; color: #8ab0d0; margin-bottom: 6px; display: block; }
.form-control {
    width: 100%; background: #0d1b2a; border: 1px solid #2a4a6b;
    border-radius: 8px; padding: 9px 12px; font-size: 13px; color: #fff; outline: none;
}
.form-control:focus { border-color: #185FA5; }
.form-control::placeholder { color: #8ab0d0; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

.barang-preview {
    background: #0d1b2a; border: 1px solid #2a4a6b; border-radius: 8px;
    padding: 12px; display: flex; align-items: center; gap: 12px; margin-bottom: 14px;
}
.barang-preview-icon {
    width: 40px; height: 40px; border-radius: 8px; background: #1a3a5c;
    display: flex; align-items: center; justify-content: center; font-size: 20px; color: #8ab0d0;
}
.barang-preview-name { font-size: 14px; font-weight: 700; color: #fff; }
.barang-preview-meta { font-size: 12px; color: #8ab0d0; margin-top: 2px; }

.btn-submit {
    background: #185FA5; color: #fff; border: none; border-radius: 8px;
    padding: 10px 24px; font-size: 14px; cursor: pointer; font-weight: 700; width: 100%;
}
.btn-submit:hover { background: #1a75c7; }
.btn-batal {
    background: none; color: #8ab0d0; border: 1px solid #2a4a6b; border-radius: 8px;
    padding: 10px 24px; font-size: 14px; cursor: pointer; text-decoration: none;
    display: inline-block; text-align: center; width: 100%; margin-top: 8px;
}

.alert { border-radius: 8px; padding: 12px 16px; font-size: 13px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.alert.ok  { background: #0f2d1f; border: 1px solid #5DCAA5; color: #5DCAA5; }
.alert.err { background: #2d0f0f; border: 1px solid #F09595; color: #F09595; }

.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th { background: #0d1b2a; color: #8ab0d0; padding: 10px 12px; text-align: left; font-weight: 600; }
td { padding: 10px 12px; border-bottom: 1px solid #2a4a6b; color: #fff; }
tr:last-child td { border-bottom: none; }

.badge { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 700; }
.badge.dipinjam  { background: #1a3a5c; color: #8ab0d0; }
.badge.pending   { background: #3d2e0f; color: #FAC775; }
.badge.terlambat { background: #2d0f0f; color: #F09595; }
.badge.dikembalikan { background: #0f2d1f; color: #5DCAA5; }
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
    <a class="nav-item active" href="peminjaman_alatt.php"><i class="ti ti-package"></i> Peminjaman</a>
    <a class="nav-item" href="pengembalian_siswaa.php"><i class="ti ti-arrow-back"></i> Pengembalian</a>
    <div class="nav-section">Informasi</div>
    <a class="nav-item" href="denda_siswaa.php"><i class="ti ti-receipt"></i> Denda</a>
    <a class="nav-item" href="riwayat_siswaa.php"><i class="ti ti-history"></i> Riwayat</a>
    <div class="nav-section">Akun</div>
    <a class="nav-item" href="profil_siswaa.php"><i class="ti ti-user"></i> Profil</a>
</div>

<div class="main">
    <div class="page-title">Peminjaman Alat</div>
    <div class="page-sub">Ajukan peminjaman alat dan lihat riwayat peminjaman kamu.</div>

    <?php if ($pesan): ?>
    <div class="alert <?php echo $pesan_type; ?>">
        <i class="ti ti-<?php echo $pesan_type === 'ok' ? 'check' : 'alert-circle'; ?>"></i>
        <?php echo $pesan; ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="section-title"><i class="ti ti-plus" style="color:#185FA5"></i> Form Peminjaman</div>

        <?php if ($barang_pilih): ?>
        <div class="barang-preview">
            <div class="barang-preview-icon"><i class="ti ti-tool"></i></div>
            <div>
                <div class="barang-preview-name"><?php echo $barang_pilih['nama_barang']; ?></div>
                <div class="barang-preview-meta">
                    <?php echo $barang_pilih['kode_barang']; ?> · <?php echo $barang_pilih['kode_jurusan']; ?> · 
                    Stok: <?php echo $barang_pilih['stok_tersedia']; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="pinjam" value="1">

            <div class="form-group">
                <label class="form-label">Pilih Alat</label>
                <select name="id_barang" class="form-control" required>
                    <option value="">-- Pilih Alat --</option>
                    <?php
                    $q_all = mysqli_query($conn, "
                        SELECT b.*, j.kode_jurusan FROM barang b 
                        JOIN jurusan j ON b.id_jurusan = j.id_jurusan
                        WHERE b.stok_tersedia > 0
                        ORDER BY b.nama_barang
                    ");
                    while ($b = mysqli_fetch_assoc($q_all)):
                    ?>
                    <option value="<?php echo $b['id_barang']; ?>"
                        <?php echo ($id_barang_pilih == $b['id_barang']) ? 'selected' : ''; ?>>
                        <?php echo $b['nama_barang']; ?> (<?php echo $b['kode_jurusan']; ?>) - Stok: <?php echo $b['stok_tersedia']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Jumlah Pinjam</label>
                    <input type="number" name="jumlah_pinjam" class="form-control" 
                           min="1" max="10" value="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Kembali</label>
                    <input type="date" name="tgl_kembali" class="form-control"
                           min="<?php echo $min_date; ?>" max="<?php echo $max_date; ?>" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="ti ti-send"></i> Ajukan Peminjaman
            </button>
            <a href="cari_alat.php" class="btn-batal">Batal / Cari Alat Lain</a>
        </form>
    </div>

    <div class="card">
        <div class="section-title"><i class="ti ti-list" style="color:#185FA5"></i> Riwayat Peminjaman</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Alat</th>
                        <th>Jumlah</th>
                        <th>Tgl Pinjam</th>
                        <th>Tgl Kembali</th>
                        <th>Status</th>
                        <th>Denda</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($q_pinjam) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($q_pinjam)):
                    $terlambat = ($row['status'] == 'dipinjam' && strtotime($row['tgl_kembali_seharusnya']) < time());
                    $badge = $terlambat ? 'terlambat' : $row['status'];
                    $label = $terlambat ? 'Terlambat' : ucfirst($row['status']);
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700"><?php echo $row['nama_barang']; ?></div>
                        <div style="font-size:11px;color:#8ab0d0"><?php echo $row['kode_jurusan']; ?></div>
                    </td>
                    <td><?php echo $row['jumlah_pinjam']; ?></td>
                    <td><?php echo date('d M Y', strtotime($row['tgl_pinjam'])); ?></td>
                    <td><?php echo date('d M Y', strtotime($row['tgl_kembali_seharusnya'])); ?></td>
                    <td><span class="badge <?php echo $badge; ?>"><?php echo $label; ?></span></td>
                    <td><?php echo $row['denda'] > 0 ? 'Rp '.number_format($row['denda'],0,',','.') : '-'; ?></td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:#8ab0d0;padding:30px">
                        Belum ada riwayat peminjaman
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>