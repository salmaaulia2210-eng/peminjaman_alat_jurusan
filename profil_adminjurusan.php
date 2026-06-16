<?php
require_once 'koneksii.php';
include 'layout_adminn.php';

$id = $_SESSION['id_admin'];

$admin = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT a.*, j.nama_jurusan, j.kode_jurusan
     FROM admin a
     LEFT JOIN jurusan j ON a.id_jurusan = j.id_jurusan
     WHERE a.id_admin = $id"));

$success = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    if ($aksi === 'update_profil') {
        $nama = mysqli_real_escape_string($conn, $_POST['nama_admin']);

        mysqli_query($conn,
            "UPDATE admin SET nama_admin = '$nama'
             WHERE id_admin = $id");

        $_SESSION['nama_admin'] = $nama;
        $success = "Profil berhasil diperbarui.";

        $admin = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT a.*, j.nama_jurusan, j.kode_jurusan
             FROM admin a
             LEFT JOIN jurusan j ON a.id_jurusan = j.id_jurusan
             WHERE a.id_admin = $id"));

    } elseif ($aksi === 'ganti_password') {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $konfirmasi    = $_POST['konfirmasi'];

        if ($password_lama !== $admin['password']) {
            $error = "Password lama tidak sesuai.";
        } elseif (strlen($password_baru) < 6) {
            $error = "Password baru minimal 6 karakter.";
        } elseif ($password_baru !== $konfirmasi) {
            $error = "Konfirmasi password tidak cocok.";
        } else {
            mysqli_query($conn,
                "UPDATE admin SET password = '$password_baru'
                 WHERE id_admin = $id");
            $success = "Password berhasil diubah.";
        }
    }
}
?>

<style>
.wrap {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.alert-success,
.alert-error {
    padding: 12px 16px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 500;
}
.alert-success {
    background: rgba(52,211,153,.1);
    border: 0.5px solid rgba(52,211,153,.2);
    color: #34d399;
}
.alert-error {
    background: rgba(248,113,113,.1);
    border: 0.5px solid rgba(248,113,113,.2);
    color: #f87171;
}

.hero-banner {
    background: #1a2e4a;
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    padding: 28px 28px 24px;
    display: flex;
    align-items: center;
    gap: 22px;
    position: relative;
}

.hero-meta {
    position: absolute;
    top: 18px;
    right: 24px;
    text-align: right;
    font-size: 11px;
    color: #7090b0;
    line-height: 1.8;
}

.hero-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #d97706;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 700;
    color: #ffffff;
    flex-shrink: 0;
}

.hero-name {
    font-size: 22px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 6px;
}

.hero-username {
    font-size: 13px;
    color: #7090b0;
    margin-bottom: 10px;
}

.badge-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.badge {
    display: inline-block;
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 4px;
    font-weight: 600;
}

.badge-super {
    background: rgba(251,191,36,0.12);
    color: #fbbf24;
    border: 0.5px solid rgba(251,191,36,0.2);
}

.badge-jurusan {
    background: rgba(96,165,250,0.12);
    color: #60a5fa;
    border: 0.5px solid rgba(96,165,250,0.2);
}

.badge-aktif {
    background: rgba(52,211,153,0.12);
    color: #34d399;
    border: 0.5px solid rgba(52,211,153,0.2);
}

.two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    align-items: start;
}

.card {
    background: #1a2e4a;
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    overflow: hidden;
}

.card-title {
    padding: 12px 18px;
    font-size: 11px;
    font-weight: 700;
    color: #a0b4cc;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 0.5px solid rgba(255,255,255,0.06);
    background: #0f1f3d;
}

.card-body {
    padding: 18px;
}

.info-list {
    display: flex;
    flex-direction: column;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    padding: 11px 0;
    border-bottom: 0.5px solid rgba(255,255,255,0.05);
}

.info-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.info-item .lbl { color: #7090b0; }
.info-item .val { color: #e0eaf5; font-weight: 600; }

.form-group {
    margin-bottom: 14px;
}

.form-group:last-of-type {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #a0b4cc;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.form-group input {
    width: 100%;
    padding: 10px 13px;
    background: #0f1f3d;
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 7px;
    font-size: 13px;
    color: #e0eaf5;
    outline: none;
    box-sizing: border-box;
    transition: border-color .15s;
}

.form-group input:focus      { border-color: rgba(217,119,6,.4); }
.form-group input::placeholder { color: #4a6080; }
.form-group input:disabled   { opacity: .4; cursor: not-allowed; }

.form-hint {
    font-size: 11px;
    color: #4a6080;
    margin-top: 5px;
}

.btn-submit {
    margin-top: 16px;
    padding: 10px 22px;
    background: #1e3a6e;
    border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 7px;
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}

.btn-submit:hover { background: #254d8f; }

.card-password {
    grid-column: 1 / -1;
}
</style>

<div class="wrap">

    <?php if ($success): ?>
    <div class="alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert-error"><?= $error ?></div>
    <?php endif; ?>

    <div class="hero-banner">
        <div class="hero-meta">
            ID Admin: #<?= str_pad($admin['id_admin'], 4, '0', STR_PAD_LEFT) ?><br>
            <?= $admin['hak_akses'] === 'super_admin' ? 'Akses penuh sistem' : 'Admin Jurusan' ?><br>
            <?= date('d M Y') ?>
        </div>
        <div class="hero-avatar">
            <?= strtoupper(substr($admin['nama_admin'], 0, 1)) ?>
        </div>
        <div>
            <div class="hero-name"><?= htmlspecialchars($admin['nama_admin']) ?></div>
            <div class="hero-username">@<?= htmlspecialchars($admin['username']) ?></div>
            <div class="badge-row">
                <?php if ($admin['hak_akses'] === 'super_admin'): ?>
                    <span class="badge badge-super">Super Admin</span>
                <?php else: ?>
                    <span class="badge badge-jurusan">Admin Jurusan</span>
                <?php endif; ?>
                <span class="badge badge-aktif">Aktif</span>
            </div>
        </div>
    </div>

    <div class="two-col">

        <div class="card">
            <div class="card-title">Informasi Akun</div>
            <div class="card-body">
                <div class="info-list">
                    <div class="info-item">
                        <span class="lbl">ID Admin</span>
                        <span class="val">#<?= str_pad($admin['id_admin'], 4, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="lbl">Username</span>
                        <span class="val"><?= htmlspecialchars($admin['username']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="lbl">Hak Akses</span>
                        <span class="val"><?= ucfirst(str_replace('_', ' ', $admin['hak_akses'])) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="lbl">Cakupan</span>
                        <span class="val">
                            <?php if ($admin['nama_jurusan']): ?>
                                <?= htmlspecialchars($admin['nama_jurusan']) ?>
                                (<?= $admin['kode_jurusan'] ?>)
                            <?php else: ?>
                                Semua Jurusan
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Ubah Nama</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="aksi" value="update_profil">

                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_admin"
                               value="<?= htmlspecialchars($admin['nama_admin']) ?>"
                               placeholder="Masukkan nama lengkap" required>
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text"
                               value="<?= htmlspecialchars($admin['username']) ?>"
                               disabled>
                        <div class="form-hint">Username tidak dapat diubah.</div>
                    </div>

                    <button type="submit" class="btn-submit">Simpan Perubahan</button>
                </form>
            </div>
        </div>

        <div class="card card-password">
            <div class="card-title">Ganti Password</div>
            <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;align-items:end;">
                <form method="POST" style="display:contents;">
                    <input type="hidden" name="aksi" value="ganti_password">

                    <div class="form-group" style="margin-bottom:0;">
                        <label>Password Lama</label>
                        <input type="password" name="password_lama"
                               placeholder="Masukkan password lama" required>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label>Password Baru</label>
                        <input type="password" name="password_baru"
                               placeholder="Minimal 6 karakter" required>
                        <div class="form-hint">Minimal 6 karakter.</div>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label>Konfirmasi Password Baru</label>
                        <input type="password" name="konfirmasi"
                               placeholder="Ulangi password baru" required>
                    </div>

                    <div style="grid-column:1/-1;margin-top:4px;">
                        <button type="submit" class="btn-submit">Ganti Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>