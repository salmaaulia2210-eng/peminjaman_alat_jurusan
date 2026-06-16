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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan'])) {
    $id_peminjaman  = intval($_POST['id_peminjaman']);
    $kondisi_kembali = $_POST['kondisi_kembali'];

    $cek = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT * FROM peminjaman 
        WHERE id_peminjaman='$id_peminjaman' AND id_siswa='$id_siswa'
        AND status='dipinjam'
    "));

    if (!$cek) {
        $pesan = "Peminjaman tidak ditemukan atau sudah diajukan!";
        $pesan_type = "err";
    } else {
        $update = mysqli_query($conn, "
            UPDATE peminjaman 
            SET status='pending', kondisi_kembali='$kondisi_kembali'
            WHERE id_peminjaman='$id_peminjaman'
        ");

        if ($update) {
            $pesan = "Pengembalian berhasil diajukan! Menunggu konfirmasi admin.";
            $pesan_type = "ok";
        } else {
            $pesan = "Gagal mengajukan pengembalian, coba lagi!";
            $pesan_type = "err";
        }
    }
}

$q_dipinjam = mysqli_query($conn, "
    SELECT p.*, b.nama_barang, b.kode_barang, j.kode_jurusan
    FROM peminjaman p
    JOIN barang b ON p.id_barang = b.id_barang
    JOIN jurusan j ON b.id_jurusan = j.id_jurusan
    WHERE p.id_siswa='$id_siswa' AND p.status='dipinjam' AND p.status='dipinjam'
    ORDER BY p.tgl_kembali_seharusnya ASC
");

$q_menunggu = mysqli_query($conn, "
    SELECT p.*, b.nama_barang, b.kode_barang, j.kode_jurusan
    FROM peminjaman p
    JOIN barang b ON p.id_barang = b.id_barang
    JOIN jurusan j ON b.id_jurusan = j.id_jurusan
    WHERE p.id_siswa='$id_siswa' AND p.status='pending'
    ORDER BY p.tgl_pinjam DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengembalian - PinjamAlat SMK</title>
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
.alert { border-radius: 8px; padding: 12px 16px; font-size: 13px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.alert.ok  { background: #0f2d1f; border: 1px solid #5DCAA5; color: #5DCAA5; }
.alert.err { background: #2d0f0f; border: 1px solid #F09595; color: #F09595; }

.pinjam-item {
    background: #0d1b2a; border: 1px solid #2a4a6b; border-radius: 10px;
    padding: 14px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px;
}
.pinjam-item:last-child { margin-bottom: 0; }
.pinjam-icon {
    width: 42px; height: 42px; border-radius: 8px; background: #1a3a5c;
    display: flex; align-items: center; justify-content: center; font-size: 20px; color: #8ab0d0; flex-shrink: 0;
}
.pinjam-info { flex: 1; }
.pinjam-name { font-size: 14px; font-weight: 700; color: #fff; }
.pinjam-meta { font-size: 12px; color: #8ab0d0; margin-top: 3px; line-height: 1.6; }
.pinjam-action { display: flex; flex-direction: column; gap: 6px; align-items: flex-end; }

.badge { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 700; }
.badge.terlambat { background: #2d0f0f; color: #F09595; }
.badge.aman      { background: #0f2d1f; color: #5DCAA5; }
.badge.menunggu  { background: #3d2e0f; color: #FAC775; }

.modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7);
    z-index: 200; align-items: center; justify-content: center;
}
.modal-overlay.show { display: flex; }
.modal {
    background: #1a2d42; border: 1px solid #2a4a6b; border-radius: 14px;
    padding: 24px; width: 100%; max-width: 400px; margin: 20px;
}
.modal-title { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 16px; }
.modal-info { font-size: 13px; color: #8ab0d0; margin-bottom: 16px; line-height: 1.6; }
.form-label { font-size: 12px; color: #8ab0d0; margin-bottom: 6px; display: block; }
.form-control {
    width: 100%; background: #0d1b2a; border: 1px solid #2a4a6b;
    border-radius: 8px; padding: 9px 12px; font-size: 13px; color: #fff; outline: none; margin-bottom: 14px;
}
.form-control:focus { border-color: #185FA5; }
.btn-row { display: flex; gap: 8px; }
.btn-submit {
    flex: 1; background: #185FA5; color: #fff; border: none; border-radius: 8px;
    padding: 10px; font-size: 13px; cursor: pointer; font-weight: 700;
}
.btn-submit:hover { background: #1a75c7; }
.btn-batal {
    flex: 1; background: none; color: #8ab0d0; border: 1px solid #2a4a6b;
    border-radius: 8px; padding: 10px; font-size: 13px; cursor: pointer;
}
.btn-kembalikan {
    background: #185FA5; color: #fff; border: none; border-radius: 8px;
    padding: 6px 14px; font-size: 12px; cursor: pointer; font-weight: 700;
    white-space: nowrap;
}
.btn-kembalikan:hover { background: #1a75c7; }

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
    <a class="nav-item active" href="pengembalian_siswaa.php"><i class="ti ti-arrow-back"></i> Pengembalian</a>
    <div class="nav-section">Informasi</div>
    <a class="nav-item" href="denda_siswaa.php"><i class="ti ti-receipt"></i> Denda</a>
    <a class="nav-item" href="riwayat_siswaa.php"><i class="ti ti-history"></i> Riwayat</a>
    <div class="nav-section">Akun</div>
    <a class="nav-item" href="profil_siswaa.php"><i class="ti ti-user"></i> Profil</a>
</div>

<div class="main">
    <div class="page-title">Pengembalian Alat</div>
    <div class="page-sub">Ajukan pengembalian alat yang sudah selesai dipinjam.</div>

    <?php if ($pesan): ?>
    <div class="alert <?php echo $pesan_type; ?>">
        <i class="ti ti-<?php echo $pesan_type === 'ok' ? 'check' : 'alert-circle'; ?>"></i>
        <?php echo $pesan; ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="section-title"><i class="ti ti-arrow-back" style="color:#185FA5"></i> Alat Sedang Dipinjam</div>
        <?php if (mysqli_num_rows($q_dipinjam) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($q_dipinjam)):
            $terlambat = strtotime($row['tgl_kembali_seharusnya']) < time();
            $hari_sisa = round((strtotime($row['tgl_kembali_seharusnya']) - time()) / 86400);
        ?>
        <div class="pinjam-item">
            <div class="pinjam-icon"><i class="ti ti-tool"></i></div>
            <div class="pinjam-info">
                <div class="pinjam-name"><?php echo $row['nama_barang']; ?></div>
                <div class="pinjam-meta">
                    <?php echo $row['kode_jurusan']; ?> · Jumlah: <?php echo $row['jumlah_pinjam']; ?><br>
                    Dipinjam: <?php echo date('d M Y', strtotime($row['tgl_pinjam'])); ?><br>
                    Batas kembali: <?php echo date('d M Y', strtotime($row['tgl_kembali_seharusnya'])); ?>
                    <?php if ($terlambat): ?>
                        · <span style="color:#F09595">Terlambat <?php echo abs($hari_sisa); ?> hari</span>
                    <?php else: ?>
                        · <span style="color:#5DCAA5">Sisa <?php echo $hari_sisa; ?> hari</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="pinjam-action">
                <span class="badge <?php echo $terlambat ? 'terlambat' : 'aman'; ?>">
                    <?php echo $terlambat ? 'Terlambat' : 'Aman'; ?>
                </span>
                <button class="btn-kembalikan" onclick="bukaModal(
                    <?php echo $row['id_peminjaman']; ?>,
                    '<?php echo addslashes($row['nama_barang']); ?>',
                    <?php echo $row['jumlah_pinjam']; ?>,
                    '<?php echo date('d M Y', strtotime($row['tgl_kembali_seharusnya'])); ?>'
                )">
                    Kembalikan
                </button>
            </div>
        </div>
        <?php endwhile; ?>
        <?php else: ?>
        <div class="empty-state">
            <i class="ti ti-mood-happy" style="font-size:36px; display:block; margin-bottom:8px;"></i>
            Tidak ada alat yang perlu dikembalikan
        </div>
        <?php endif; ?>
    </div>

    <?php if (mysqli_num_rows($q_menunggu) > 0): ?>
    <div class="card">
        <div class="section-title"><i class="ti ti-clock" style="color:#854F0B"></i> Menunggu Konfirmasi Admin</div>
        <?php while ($row = mysqli_fetch_assoc($q_menunggu)): ?>
        <div class="pinjam-item">
            <div class="pinjam-icon"><i class="ti ti-tool"></i></div>
            <div class="pinjam-info">
                <div class="pinjam-name"><?php echo $row['nama_barang']; ?></div>
                <div class="pinjam-meta">
                    <?php echo $row['kode_jurusan']; ?> · Jumlah: <?php echo $row['jumlah_pinjam']; ?><br>
                    Kondisi: <?php echo ucfirst($row['kondisi_kembali']); ?>
                </div>
            </div>
            <span class="badge menunggu">Menunggu Admin</span>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <div class="modal-title"><i class="ti ti-arrow-back"></i> Ajukan Pengembalian</div>
        <div class="modal-info" id="modalInfo"></div>
        <form method="POST">
            <input type="hidden" name="ajukan" value="1">
            <input type="hidden" name="id_peminjaman" id="inputIdPeminjaman">
            <label class="form-label">Kondisi Barang Saat Dikembalikan</label>
            <select name="kondisi_kembali" class="form-control" required>
                <option value="bagus">Bagus / Normal</option>
                <option value="rusak">Rusak</option>
                <option value="hilang">Hilang</option>
            </select>
            <div class="btn-row">
                <button type="button" class="btn-batal" onclick="tutupModal()">Batal</button>
                <button type="submit" class="btn-submit">Ajukan</button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModal(id, nama, jumlah, tglKembali) {
    document.getElementById('inputIdPeminjaman').value = id;
    document.getElementById('modalInfo').innerHTML = 
        '<b>' + nama + '</b><br>Jumlah: ' + jumlah + '<br>Batas kembali: ' + tglKembali;
    document.getElementById('modalOverlay').classList.add('show');
}
function tutupModal() {
    document.getElementById('modalOverlay').classList.remove('show');
}
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) tutupModal();
});
</script>
</body>
</html>