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

$siswa = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT s.*, j.nama_jurusan FROM siswa s
    JOIN jurusan j ON s.id_jurusan = j.id_jurusan
    WHERE s.id_siswa='$id_siswa'
"));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profil'])) {
    $nama_baru  = mysqli_real_escape_string($conn, trim($_POST['nama_siswa']));
    $kelas_baru = mysqli_real_escape_string($conn, trim($_POST['kelas']));
    $no_telp    = mysqli_real_escape_string($conn, trim($_POST['no_telepon']));

    if (empty($nama_baru) || empty($kelas_baru)) {
        $pesan = "Nama dan kelas tidak boleh kosong!";
        $pesan_type = "err";
    } else {
        mysqli_query($conn, "
            UPDATE siswa SET nama_siswa='$nama_baru', kelas='$kelas_baru', no_telepon='$no_telp'
            WHERE id_siswa='$id_siswa'
        ");
        $_SESSION['nama_siswa'] = $nama_baru;
        $_SESSION['kelas']      = $kelas_baru;
        $pesan = "Profil berhasil diperbarui!";
        $pesan_type = "ok";

        $siswa = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT s.*, j.nama_jurusan FROM siswa s
            JOIN jurusan j ON s.id_jurusan = j.id_jurusan
            WHERE s.id_siswa='$id_siswa'
        "));
        $nama_siswa = $nama_baru;
        $kelas      = $kelas_baru;
        $inisial = '';
        foreach (explode(' ', $nama_siswa) as $k) $inisial .= strtoupper(substr($k, 0, 1));
        $inisial = substr($inisial, 0, 2);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi    = $_POST['konfirmasi'];

    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi)) {
        $pesan = "Semua field password harus diisi!";
        $pesan_type = "err";
    } elseif ($password_baru !== $konfirmasi) {
        $pesan = "Konfirmasi password tidak cocok!";
        $pesan_type = "err";
    } elseif (strlen($password_baru) < 6) {
        $pesan = "Password baru minimal 6 karakter!";
        $pesan_type = "err";
    } else {
        $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM siswa WHERE id_siswa='$id_siswa'"));
        $cocok = password_verify($password_lama, $cek['password']) || $password_lama === $cek['password'];

        if (!$cocok) {
            $pesan = "Password lama salah!";
            $pesan_type = "err";
        } else {
            $hash = password_hash($password_baru, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE siswa SET password='$hash' WHERE id_siswa='$id_siswa'");
            $pesan = "Password berhasil diubah!";
            $pesan_type = "ok";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil - PinjamAlat SMK</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; background: #0d1b2a; color: #222; }
.topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-bottom: 1px solid #e0e0e0;
    background: #182e4a; position: fixed; top: 0; left: 0; right: 0; z-index: 100;
}
.logo { font-size: 15px; font-weight: 700; color: #fff; }
.logo span { color: #eeecf7; }
.user-info { display: flex; align-items: center; gap: 10px; }
.avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: #E1F5EE; display: flex; align-items: center;
    justify-content: center; font-size: 13px; font-weight: 700; color: #acb0c4;
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
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 768px) { .two-col { grid-template-columns: 1fr; } }
.card { background: #1a2d42; border: 1px solid #2a4a6b; border-radius: 12px; padding: 20px; margin-bottom: 16px; }
.section-title { font-size: 14px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; color: #fff; }
.alert { border-radius: 8px; padding: 12px 16px; font-size: 13px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.alert.ok  { background: #0f2d1f; border: 1px solid #5DCAA5; color: #5DCAA5; }
.alert.err { background: #2d0f0f; border: 1px solid #F09595; color: #F09595; }

.profil-header {
    display: flex; align-items: center; gap: 16px; margin-bottom: 20px;
    padding-bottom: 16px; border-bottom: 1px solid #2a4a6b;
}
.avatar-lg {
    width: 64px; height: 64px; border-radius: 50%;
    background: #E1F5EE; display: flex; align-items: center;
    justify-content: center; font-size: 24px; font-weight: 700; color: #0f1f6e;
    flex-shrink: 0;
}
.profil-nama { font-size: 18px; font-weight: 700; color: #fff; }
.profil-sub  { font-size: 13px; color: #8ab0d0; margin-top: 4px; }

.form-group { margin-bottom: 14px; }
.form-label { font-size: 12px; color: #8ab0d0; margin-bottom: 6px; display: block; }
.form-control {
    width: 100%; background: #0d1b2a; border: 1px solid #2a4a6b;
    border-radius: 8px; padding: 9px 12px; font-size: 13px; color: #fff; outline: none;
}
.form-control:focus { border-color: #185FA5; }
.form-control:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-submit {
    background: #185FA5; color: #fff; border: none; border-radius: 8px;
    padding: 10px 24px; font-size: 13px; cursor: pointer; font-weight: 700; width: 100%;
}
.btn-submit:hover { background: #1a75c7; }

.input-wrap { position: relative; }
.input-wrap .form-control { padding-right: 40px; }
.toggle-pw {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: #8ab0d0; cursor: pointer; font-size: 16px;
}
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
    <a class="nav-item" href="riwayat_siswaa.php"><i class="ti ti-history"></i> Riwayat</a>
    <div class="nav-section">Akun</div>
    <a class="nav-item active" href="profil_siswaa.php"><i class="ti ti-user"></i> Profil</a>
</div>

<div class="main">
    <div class="page-title">Profil Saya</div>
    <div class="page-sub">Lihat dan edit informasi akun kamu.</div>

    <?php if ($pesan): ?>
    <div class="alert <?php echo $pesan_type; ?>">
        <i class="ti ti-<?php echo $pesan_type === 'ok' ? 'check' : 'alert-circle'; ?>"></i>
        <?php echo $pesan; ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="profil-header">
            <div class="avatar-lg"><?php echo $inisial; ?></div>
            <div>
                <div class="profil-nama"><?php echo $siswa['nama_siswa']; ?></div>
                <div class="profil-sub"><?php echo $siswa['nis']; ?> · <?php echo $siswa['kelas']; ?> · <?php echo $siswa['nama_jurusan']; ?></div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
            <div>
                <div style="color:#8ab0d0;margin-bottom:4px;">NIS</div>
                <div style="color:#fff;font-weight:700"><?php echo $siswa['nis']; ?></div>
            </div>
            <div>
                <div style="color:#8ab0d0;margin-bottom:4px;">Kelas</div>
                <div style="color:#fff;font-weight:700"><?php echo $siswa['kelas']; ?></div>
            </div>
            <div>
                <div style="color:#8ab0d0;margin-bottom:4px;">Jurusan</div>
                <div style="color:#fff;font-weight:700"><?php echo $siswa['nama_jurusan']; ?></div>
            </div>
            <div>
                <div style="color:#8ab0d0;margin-bottom:4px;">No. Telepon</div>
                <div style="color:#fff;font-weight:700"><?php echo $siswa['no_telepon'] ?: '-'; ?></div>
            </div>
        </div>
    </div>

    <div class="two-col">
        <div class="card">
            <div class="section-title"><i class="ti ti-edit" style="color:#185FA5"></i> Edit Profil</div>
            <form method="POST">
                <input type="hidden" name="edit_profil" value="1">
                <div class="form-group">
                    <label class="form-label">NIS</label>
                    <input type="text" class="form-control" value="<?php echo $siswa['nis']; ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama_siswa" class="form-control"
                           value="<?php echo $siswa['nama_siswa']; ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kelas</label>
                    <input type="text" name="kelas" class="form-control"
                           value="<?php echo $siswa['kelas']; ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">No. Telepon</label>
                    <input type="text" name="no_telepon" class="form-control"
                           value="<?php echo $siswa['no_telepon']; ?>" placeholder="08xxxxxxxxxx">
                </div>
                <button type="submit" class="btn-submit">
                    <i class="ti ti-check"></i> Simpan Perubahan
                </button>
            </form>
        </div>

        <div class="card">
            <div class="section-title"><i class="ti ti-lock" style="color:#185FA5"></i> Ganti Password</div>
            <form method="POST">
                <input type="hidden" name="ganti_password" value="1">
                <div class="form-group">
                    <label class="form-label">Password Lama</label>
                    <div class="input-wrap">
                        <input type="password" name="password_lama" id="pw_lama" class="form-control" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('pw_lama', this)">
                            <i class="ti ti-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <div class="input-wrap">
                        <input type="password" name="password_baru" id="pw_baru" class="form-control" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('pw_baru', this)">
                            <i class="ti ti-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <div class="input-wrap">
                        <input type="password" name="konfirmasi" id="pw_konfirm" class="form-control" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('pw_konfirm', this)">
                            <i class="ti ti-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="ti ti-lock"></i> Ganti Password
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.innerHTML = isHidden ? '<i class="ti ti-eye-off"></i>' : '<i class="ti ti-eye"></i>';
}
</script>
</body>
</html>