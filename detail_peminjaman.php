<?php
session_start();
require_once 'koneksii.php';
include 'layout_adminn.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header("Location: peminjaman_adminn.php"); exit;
}

$query = "SELECT p.*, s.nama_siswa, s.nis, s.kelas, s.no_telepon,
                 b.nama_barang, b.kode_barang, b.kondisi,
                 j.nama_jurusan, j.kode_jurusan,
                 a.nama_admin
          FROM peminjaman p
          JOIN siswa s ON p.id_siswa = s.id_siswa
          JOIN barang b ON p.id_barang = b.id_barang
          JOIN jurusan j ON b.id_jurusan = j.id_jurusan
          LEFT JOIN admin a ON p.id_admin = a.id_admin
          WHERE p.id_peminjaman = $id";

$result = mysqli_query($conn, $query);
$row    = mysqli_fetch_assoc($result);

if (!$row) {
    header("Location: peminjaman_adminn.php"); exit;
}

$terlambat = ($row['status'] === 'dipinjam'
    && strtotime($row['tgl_kembali_seharusnya']) < time());

$hari_terlambat = 0;
$denda_otomatis = $row['denda'];

if ($terlambat) {
    $hari_terlambat = ceil((time() - strtotime($row['tgl_kembali_seharusnya'])) / 86400);
    $denda_otomatis = $hari_terlambat * 1000 * $row['jumlah_pinjam'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    if ($aksi === 'setujui') {
        mysqli_query($conn,
            "UPDATE peminjaman SET status = 'dipinjam' WHERE id_peminjaman = $id");
        mysqli_query($conn,
            "UPDATE barang SET stok_tersedia = stok_tersedia - {$row['jumlah_pinjam']}
             WHERE id_barang = {$row['id_barang']}");
    } elseif ($aksi === 'tolak') {
        mysqli_query($conn,
            "UPDATE peminjaman SET status = 'ditolak' WHERE id_peminjaman = $id");
    }

    header("Location: detail_peminjamann.php?id=$id"); exit;
}
?>

<style>
.detail-wrap {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-width: 860px;
}

.back-link {
    font-size: 13px;
    color: #7eb3f5;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.back-link:hover { color: #ffffff; }

.header-card {
    background: #1a2e4a;
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    padding: 20px 24px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.header-left h2 {
    font-size: 18px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 4px;
}

.header-left p {
    font-size: 13px;
    color: #a0b4cc;
}

.status {
    display: inline-block;
    padding: 5px 14px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.status-pending     { background: rgba(251,191,36,0.12); color: #fbbf24; border: 0.5px solid rgba(251,191,36,0.25); }
.status-dipinjam    { background: rgba(96,165,250,0.12); color: #60a5fa; border: 0.5px solid rgba(96,165,250,0.25); }
.status-dikembalikan{ background: rgba(52,211,153,0.12); color: #34d399; border: 0.5px solid rgba(52,211,153,0.25); }
.status-ditolak     { background: rgba(248,113,113,0.12); color: #f87171; border: 0.5px solid rgba(248,113,113,0.25); }
.status-terlambat   { background: rgba(239,68,68,0.15); color: #f87171; border: 0.5px solid rgba(239,68,68,0.3); }

.alert-terlambat {
    background: rgba(248,113,113,0.08);
    border: 0.5px solid rgba(248,113,113,0.25);
    border-radius: 8px;
    padding: 14px 18px;
    font-size: 13px;
    color: #fca5a5;
}

.alert-terlambat strong { color: #f87171; }

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.info-card {
    background: #1a2e4a;
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    overflow: hidden;
}

.info-card-title {
    padding: 12px 18px;
    font-size: 11px;
    font-weight: 700;
    color: #a0b4cc;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    border-bottom: 0.5px solid rgba(255,255,255,0.06);
    background: #0f1f3d;
}

.info-list {
    padding: 14px 18px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
}

.info-row .lbl { color: #7090b0; }
.info-row .val { color: #ffffff; font-weight: 500; text-align: right; }
.info-row .val.red { color: #f87171; font-weight: 700; }
.info-row .val.green { color: #34d399; font-weight: 700; }
.info-row .val.yellow { color: #fbbf24; font-weight: 700; }

.divider {
    border: none;
    border-top: 0.5px solid rgba(255,255,255,0.06);
    margin: 4px 0;
}

.aksi-card {
    background: #1a2e4a;
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}

.aksi-card p {
    font-size: 13px;
    color: #a0b4cc;
}

.aksi-btns {
    display: flex;
    gap: 10px;
    flex-shrink: 0;
}

.btn-setujui {
    padding: 9px 20px;
    background: rgba(52,211,153,0.12);
    border: 0.5px solid rgba(52,211,153,0.25);
    border-radius: 7px;
    color: #34d399;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
}

.btn-setujui:hover { background: rgba(52,211,153,0.2); }

.btn-tolak {
    padding: 9px 20px;
    background: rgba(248,113,113,0.12);
    border: 0.5px solid rgba(248,113,113,0.25);
    border-radius: 7px;
    color: #f87171;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
}

.btn-tolak:hover { background: rgba(248,113,113,0.2); }

.btn-back-list {
    padding: 9px 20px;
    background: rgba(126,179,245,0.1);
    border: 0.5px solid rgba(126,179,245,0.2);
    border-radius: 7px;
    color: #7eb3f5;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
}

.btn-back-list:hover { background: rgba(126,179,245,0.18); }

.qr-card {
    background: #1a2e4a;
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    overflow: hidden;
}

.qr-body {
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.qr-img {
    width: 90px; height: 90px;
    background: #ffffff;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #0f1f3d;
    font-weight: 600;
    text-align: center;
    padding: 8px;
    flex-shrink: 0;
}

.qr-info p {
    font-size: 13px;
    color: #a0b4cc;
    margin-bottom: 6px;
}

.qr-code-text {
    font-size: 15px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 1px;
}
</style>

<div class="detail-wrap">
    <a href="peminjaman_adminn.php" class="back-link">← Kembali ke Daftar Peminjaman</a>

    <div class="header-card">
        <div class="header-left">
            <h2>Detail Peminjaman #<?= $id ?></h2>
            <p>Dicatat pada <?= date('d M Y, H:i', strtotime($row['tgl_pinjam'])) ?></p>
        </div>
        <?php
        $status_label = ucfirst($row['status']);
        $status_class = 'status-' . $row['status'];
        if ($terlambat) { $status_label = 'Terlambat'; $status_class = 'status-terlambat'; }
        ?>
        <span class="status <?= $status_class ?>"><?= $status_label ?></span>
    </div>

    <?php if ($terlambat): ?>
    <div class="alert-terlambat">
        Peminjaman ini sudah melewati batas pengembalian <strong><?= $hari_terlambat ?> hari</strong>.
        Denda yang dikenakan: <strong>Rp <?= number_format($denda_otomatis, 0, ',', '.') ?></strong>
        (Rp 10.000 x <?= $row['jumlah_pinjam'] ?> alat x <?= $hari_terlambat ?> hari)
    </div>
    <?php endif; ?>

    <div class="info-grid">

        <div class="info-card">
            <div class="info-card-title">Data Siswa</div>
            <div class="info-list">
                <div class="info-row">
                    <span class="lbl">Nama</span>
                    <span class="val"><?= htmlspecialchars($row['nama_siswa']) ?></span>
                </div>
                <hr class="divider">
                <div class="info-row">
                    <span class="lbl">NIS</span>
                    <span class="val"><?= $row['nis'] ?></span>
                </div>
                <hr class="divider">
                <div class="info-row">
                    <span class="lbl">Kelas</span>
                    <span class="val"><?= $row['kelas'] ?></span>
                </div>
                <hr class="divider">
                <div class="info-row">
                    <span class="lbl">No. Telepon</span>
                    <span class="val"><?= $row['no_telepon'] ?: '—' ?></span>
                </div>
            </div>
        </div>

        <div class="info-card">
            <div class="info-card-title">Data Alat</div>
            <div class="info-list">
                <div class="info-row">
                    <span class="lbl">Nama Alat</span>
                    <span class="val"><?= htmlspecialchars($row['nama_barang']) ?></span>
                </div>
                <hr class="divider">
                <div class="info-row">
                    <span class="lbl">Kode Barang</span>
                    <span class="val"><?= $row['kode_barang'] ?></span>
                </div>
                <hr class="divider">
                <div class="info-row">
                    <span class="lbl">Jurusan</span>
                    <span class="val"><?= htmlspecialchars($row['nama_jurusan']) ?>
                        (<?= $row['kode_jurusan'] ?>)</span>
                </div>
                <hr class="divider">
                <div class="info-row">
                    <span class="lbl">Kondisi Alat</span>
                    <span class="val"><?= ucfirst($row['kondisi']) ?></span>
                </div>
            </div>
        </div>

        <div class="info-card">
            <div class="info-card-title">Data Peminjaman</div>
            <div class="info-list">
                <div class="info-row">
                    <span class="lbl">Jumlah Pinjam</span>
                    <span class="val"><?= $row['jumlah_pinjam'] ?> unit</span>
                </div>
                <hr class="divider">
                <div class="info-row">
                    <span class="lbl">Tanggal Pinjam</span>
                    <span class="val"><?= date('d M Y', strtotime($row['tgl_pinjam'])) ?></span>
                </div>
                <hr class="divider">
                <div class="info-row">
                    <span class="lbl">Batas Kembali</span>
                    <span class="val <?= $terlambat ? 'red' : '' ?>">
                        <?= date('d M Y', strtotime($row['tgl_kembali_seharusnya'])) ?>
                    </span>
                </div>
                <hr class="divider">
                <div class="info-row">
                    <span class="lbl">Tgl Kembali Aktual</span>
                    <span class="val">
                        <?= $row['tgl_kembali'] ? date('d M Y', strtotime($row['tgl_kembali'])) : '—' ?>
                    </span>
                </div>
                <hr class="divider">
                <div class="info-row">
                    <span class="lbl">Diproses oleh</span>
                    <span class="val"><?= $row['nama_admin'] ?? '—' ?></span>
                </div>
            </div>
        </div>

        <div class="info-card">
            <div class="info-card-title">Denda & Kondisi Kembali</div>
            <div class="info-list">
                <div class="info-row">
                    <span class="lbl">Hari Terlambat</span>
                    <span class="val <?= $hari_terlambat > 0 ? 'red' : 'green' ?>">
                        <?= $hari_terlambat > 0 ? $hari_terlambat . ' hari' : 'Tepat waktu' ?>
                    </span>
                </div>
                <hr class="divider">
                <div class="info-row">
                    <span class="lbl">Denda Keterlambatan</span>
                    <span class="val <?= $denda_otomatis > 0 ? 'red' : '' ?>">
                        <?= $denda_otomatis > 0
                            ? 'Rp ' . number_format($denda_otomatis, 0, ',', '.')
                            : '—' ?>
                    </span>
                </div>
                <hr class="divider">
                <div class="info-row">
                    <span class="lbl">Kondisi Kembali</span>
                    <span class="val">
                        <?= $row['kondisi_kembali'] ? ucfirst($row['kondisi_kembali']) : '—' ?>
                    </span>
                </div>
            </div>
        </div>

    </div>

    <?php if ($row['qr_code']): ?>
    <div class="qr-card">
        <div class="info-card-title">QR Code Peminjaman</div>
        <div class="qr-body">
            <div class="qr-img">
                <?php if (file_exists('assets/qr/' . $row['qr_code'])): ?>
                    <img src="assets/qr/<?= $row['qr_code'] ?>"
                         width="80" height="80" alt="QR Code">
                <?php else: ?>
                    QR tidak tersedia
                <?php endif; ?>
            </div>
            <div class="qr-info">
                <p>Kode QR peminjaman ini:</p>
                <div class="qr-code-text"><?= $row['qr_code'] ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="aksi-card">
        <?php if ($row['status'] === 'pending'): ?>
            <p>Peminjaman ini menunggu persetujuan admin.</p>
            <div class="aksi-btns">
                <form method="POST" style="display:inline">
                    <input type="hidden" name="aksi" value="setujui">
                    <button type="submit" class="btn-setujui">Setujui Peminjaman</button>
                </form>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="aksi" value="tolak">
                    <button type="submit" class="btn-tolak">Tolak</button>
                </form>
            </div>
        <?php else: ?>
            <p>Status: <strong style="color:#ffffff"><?= $status_label ?></strong></p>
            <div class="aksi-btns">
                <a href="peminjaman_adminn.php" class="btn-back-list">Kembali ke Daftar</a>
            </div>
        <?php endif; ?>
    </div>
</div>