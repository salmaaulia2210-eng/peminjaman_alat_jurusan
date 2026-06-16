<?php
require_once 'koneksii.php';
include 'layout_adminn.php';

$search         = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_jurusan = isset($_GET['jurusan']) ? (int)$_GET['jurusan'] : 0;
$filter_jenis   = isset($_GET['jenis']) ? mysqli_real_escape_string($conn, $_GET['jenis']) : '';

$where = "WHERE p.denda > 0";

if ($search !== '') {
    $where .= " AND (s.nama_siswa LIKE '%$search%'
                OR s.nis LIKE '%$search%')";
}

if ($hak_akses !== 'super_admin') {
    $where .= " AND b.id_jurusan = $id_jurusan";
} elseif ($filter_jurusan > 0) {
    $where .= " AND b.id_jurusan = $filter_jurusan";
}

if ($filter_jenis === 'terlambat') {
    $where .= " AND p.kondisi_kembali = 'baik'";
} elseif ($filter_jenis === 'rusak') {
    $where .= " AND p.kondisi_kembali = 'rusak'";
} elseif ($filter_jenis === 'hilang') {
    $where .= " AND p.kondisi_kembali = 'hilang'";
}

$result = mysqli_query($conn,
    "SELECT p.*, s.nama_siswa, s.nis, s.kelas,
            b.nama_barang, b.kode_barang,
            j.nama_jurusan, j.kode_jurusan
     FROM peminjaman p
     JOIN siswa s ON p.id_siswa = s.id_siswa
     JOIN barang b ON p.id_barang = b.id_barang
     JOIN jurusan j ON b.id_jurusan = j.id_jurusan
     $where
     ORDER BY p.denda DESC");

$total = mysqli_num_rows($result);

$total_denda_all = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(p.denda) as total
     FROM peminjaman p
     JOIN barang b ON p.id_barang = b.id_barang
     WHERE p.denda > 0
     " . ($hak_akses !== 'super_admin' ? "AND b.id_jurusan = $id_jurusan" : "")))['total'] ?? 0;

$total_belum = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(p.denda) as total
     FROM peminjaman p
     JOIN barang b ON p.id_barang = b.id_barang
     WHERE p.denda > 0 AND p.status = 'dipinjam'
     " . ($hak_akses !== 'super_admin' ? "AND b.id_jurusan = $id_jurusan" : "")))['total'] ?? 0;

$total_lunas = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(p.denda) as total
     FROM peminjaman p
     JOIN barang b ON p.id_barang = b.id_barang
     WHERE p.denda > 0 AND p.status = 'dikembalikan'
     " . ($hak_akses !== 'super_admin' ? "AND b.id_jurusan = $id_jurusan" : "")))['total'] ?? 0;

$list_jurusan = mysqli_query($conn, "SELECT * FROM jurusan ORDER BY nama_jurusan");
?>

<style>
.wrap {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.stat-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.stat-card {
    background: #1a2e4a;
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    padding: 16px 18px;
}

.stat-card .lbl {
    font-size: 11px;
    color: #a0b4cc;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    font-weight: 600;
}

.stat-card .val {
    font-size: 22px;
    font-weight: 700;
    color: #ffffff;
}

.stat-card .val.red   { color: #f87171; }
.stat-card .val.yellow{ color: #fbbf24; }
.stat-card .val.green { color: #34d399; }

.filter-bar {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-bar input,
.filter-bar select {
    padding: 9px 14px;
    background: #1a2e4a;
    border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 7px;
    font-size: 13px;
    color: #ffffff;
    outline: none;
}

.filter-bar input { flex: 1; min-width: 180px; }
.filter-bar input::placeholder { color: #7090b0; }
.filter-bar select option { background: #1a2e4a; }

.btn-filter {
    padding: 9px 18px;
    background: #1e3a6e;
    border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 7px;
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.btn-filter:hover { background: #254d8f; }

.btn-reset {
    padding: 9px 14px;
    background: transparent;
    border: 0.5px solid rgba(255,255,255,0.15);
    border-radius: 7px;
    color: #a0b4cc;
    font-size: 13px;
    cursor: pointer;
    text-decoration: none;
}

.btn-reset:hover { color: #ffffff; }

.info-bar {
    font-size: 13px;
    color: #a0b4cc;
}

.info-bar strong { color: #ffffff; }

.table-wrap {
    background: #1a2e4a;
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    overflow: hidden;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

thead tr {
    background: #0f1f3d;
    border-bottom: 0.5px solid rgba(255,255,255,0.08);
}

thead th {
    padding: 12px 16px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    color: #a0b4cc;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

tbody tr {
    border-bottom: 0.5px solid rgba(255,255,255,0.05);
    transition: background 0.1s;
}

tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: rgba(255,255,255,0.02); }

tbody td {
    padding: 13px 16px;
    color: #e0eaf5;
    vertical-align: middle;
}

.td-sub {
    font-size: 11px;
    color: #7090b0;
    margin-top: 2px;
}

.badge {
    display: inline-block;
    font-size: 11px;
    padding: 2px 9px;
    border-radius: 4px;
    font-weight: 600;
}

.badge-terlambat {
    background: rgba(251,191,36,0.12);
    color: #fbbf24;
    border: 0.5px solid rgba(251,191,36,0.2);
}

.badge-rusak {
    background: rgba(248,113,113,0.12);
    color: #f87171;
    border: 0.5px solid rgba(248,113,113,0.2);
}

.badge-hilang {
    background: rgba(239,68,68,0.15);
    color: #f87171;
    border: 0.5px solid rgba(239,68,68,0.25);
}

.badge-lunas {
    background: rgba(52,211,153,0.12);
    color: #34d399;
    border: 0.5px solid rgba(52,211,153,0.2);
}

.badge-belum {
    background: rgba(248,113,113,0.12);
    color: #f87171;
    border: 0.5px solid rgba(248,113,113,0.2);
}

.denda-val {
    font-weight: 700;
    color: #f87171;
}

.empty-row td {
    text-align: center;
    padding: 40px;
    color: #7090b0;
    font-size: 13px;
}
</style>

<div class="wrap">

    <div class="stat-row">
        <div class="stat-card">
            <div class="lbl">Total Denda</div>
            <div class="val red">Rp <?= number_format($total_denda_all, 0, ',', '.') ?></div>
        </div>
        <div class="stat-card">
            <div class="lbl">Belum Lunas</div>
            <div class="val yellow">Rp <?= number_format($total_belum, 0, ',', '.') ?></div>
        </div>
        <div class="stat-card">
            <div class="lbl">Sudah Lunas</div>
            <div class="val green">Rp <?= number_format($total_lunas, 0, ',', '.') ?></div>
        </div>
    </div>

    <form method="GET">
        <div class="filter-bar">
            <input type="text" name="search"
                   value="<?= htmlspecialchars($search) ?>"
                   placeholder="Cari nama siswa atau NIS...">

            <select name="jenis">
                <option value="">Semua Jenis Denda</option>
                <option value="terlambat" <?= $filter_jenis === 'terlambat' ? 'selected' : '' ?>>
                    Keterlambatan
                </option>
                <option value="rusak" <?= $filter_jenis === 'rusak' ? 'selected' : '' ?>>
                    Kerusakan
                </option>
                <option value="hilang" <?= $filter_jenis === 'hilang' ? 'selected' : '' ?>>
                    Kehilangan
                </option>
            </select>

            <?php if ($hak_akses === 'super_admin'): ?>
            <select name="jurusan">
                <option value="">Semua Jurusan</option>
                <?php while ($jrs = mysqli_fetch_assoc($list_jurusan)): ?>
                <option value="<?= $jrs['id_jurusan'] ?>"
                    <?= $filter_jurusan == $jrs['id_jurusan'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($jrs['kode_jurusan']) ?> —
                    <?= htmlspecialchars($jrs['nama_jurusan']) ?>
                </option>
                <?php endwhile; ?>
            </select>
            <?php endif; ?>

            <button type="submit" class="btn-filter">Cari</button>
            <a href="denda_adminn.php" class="btn-reset">Reset</a>
        </div>
    </form>

    <div class="info-bar">
        <strong><?= $total ?></strong> data denda ditemukan
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Siswa</th>
                    <th>Alat</th>
                    <th>Jurusan</th>
                    <th>Tgl Pinjam</th>
                    <th>Tgl Kembali</th>
                    <th>Jenis Denda</th>
                    <th>Jumlah Denda</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            if ($total === 0):
            ?>
                <tr class="empty-row">
                    <td colspan="9">Tidak ada data denda.</td>
                </tr>
            <?php
            else:
            while ($row = mysqli_fetch_assoc($result)):
                if ($row['kondisi_kembali'] === 'rusak') {
                    $jenis = '<span class="badge badge-rusak">Kerusakan</span>';
                } elseif ($row['kondisi_kembali'] === 'hilang') {
                    $jenis = '<span class="badge badge-hilang">Kehilangan</span>';
                } else {
                    $jenis = '<span class="badge badge-terlambat">Keterlambatan</span>';
                }

                $lunas = $row['status'] === 'dikembalikan';
            ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <div><?= htmlspecialchars($row['nama_siswa']) ?></div>
                        <div class="td-sub"><?= $row['nis'] ?> · <?= $row['kelas'] ?></div>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($row['nama_barang']) ?></div>
                        <div class="td-sub"><?= $row['kode_barang'] ?></div>
                    </td>
                    <td>
                        <div><?= $row['kode_jurusan'] ?></div>
                        <div class="td-sub"><?= htmlspecialchars($row['nama_jurusan']) ?></div>
                    </td>
                    <td><?= date('d M Y', strtotime($row['tgl_pinjam'])) ?></td>
                    <td>
                        <?= date('d M Y', strtotime($row['tgl_kembali_seharusnya'])) ?>
                        <?php if ($row['tgl_kembali']): ?>
                            <div class="td-sub">
                                Kembali: <?= date('d M Y', strtotime($row['tgl_kembali'])) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= $jenis ?></td>
                    <td>
                        <span class="denda-val">
                            Rp <?= number_format($row['denda'], 0, ',', '.') ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($lunas): ?>
                            <span class="badge badge-lunas">Lunas</span>
                        <?php else: ?>
                            <span class="badge badge-belum">Belum Lunas</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php
            endwhile;
            endif;
            ?>
            </tbody>
        </table>
    </div>
</div>