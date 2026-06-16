<?php
require 'koneksii.php';
include 'layout_adminn.php';

$id_jurusan = $_SESSION['id_jurusan'];

$search = isset($_GET['search'])
    ? mysqli_real_escape_string($conn, $_GET['search'])
    : '';

$query = mysqli_query($conn,
    "SELECT barang.*, jurusan.nama_jurusan
     FROM barang
     JOIN jurusan ON barang.id_jurusan = jurusan.id_jurusan
     WHERE barang.id_jurusan = '$id_jurusan'
     AND (
         barang.nama_barang LIKE '%$search%'
         OR barang.kode_barang LIKE '%$search%'
     )
     ORDER BY barang.id_barang DESC"
);
?>

<style>
.wrap {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.top-action {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.search-form {
    display: flex;
    gap: 8px;
    flex: 1;
    min-width: 200px;
}

.search-input {
    flex: 1;
    padding: 9px 14px;
    background: #1a2e4a;
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 7px;
    font-size: 13px;
    color: #e0eaf5;
    outline: none;
}
.search-input::placeholder { color: #4a6080; }

.btn-search {
    padding: 9px 18px;
    background: #1e3a6e;
    border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 7px;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}
.btn-search:hover { background: #254d8f; }

.btn-tambah {
    padding: 9px 18px;
    background: rgba(52,211,153,0.12);
    border: 0.5px solid rgba(52,211,153,0.2);
    border-radius: 7px;
    color: #34d399;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
}
.btn-tambah:hover { background: rgba(52,211,153,0.2); }

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
    padding: 12px 16px;
    color: #e0eaf5;
    vertical-align: middle;
}

.foto-wrap {
    width: 64px;
    height: 64px;
    border-radius: 8px;
    overflow: hidden;
    background: #0f1f3d;
    border: 0.5px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.foto-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.foto-placeholder {
    font-size: 26px;
    color: #2a4a6b;
}

.nama-barang { font-weight: 600; color: #e0eaf5; }
.td-sub { font-size: 11px; color: #7090b0; margin-top: 2px; }

.badge {
    display: inline-block;
    font-size: 11px;
    padding: 2px 9px;
    border-radius: 4px;
    font-weight: 600;
}
.badge-baik {
    background: rgba(52,211,153,0.12);
    color: #34d399;
    border: 0.5px solid rgba(52,211,153,0.2);
}
.badge-rusak {
    background: rgba(248,113,113,0.12);
    color: #f87171;
    border: 0.5px solid rgba(248,113,113,0.2);
}

.stok { font-weight: 700; }
.stok-aman  { color: #34d399; }
.stok-tipis { color: #fbbf24; }
.stok-habis { color: #f87171; }

.aksi { display: flex; gap: 8px; }

.btn-edit {
    padding: 6px 14px;
    border-radius: 6px;
    background: #1e3a6e;
    border: 0.5px solid rgba(255,255,255,0.12);
    color: #fff;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}
.btn-edit:hover { background: #254d8f; }

.btn-hapus {
    padding: 6px 14px;
    border-radius: 6px;
    background: rgba(248,113,113,0.12);
    border: 0.5px solid rgba(248,113,113,0.2);
    color: #f87171;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}
.btn-hapus:hover { background: rgba(248,113,113,0.22); }

.empty-row td {
    text-align: center;
    padding: 40px;
    color: #4a6080;
    font-size: 13px;
}

@media(max-width:1000px) {
    .table-wrap { overflow-x: auto; }
    table { min-width: 750px; }
}
</style>

<div class="wrap">

    <div class="top-action">
        <form method="GET" class="search-form">
            <input type="text"
                   name="search"
                   class="search-input"
                   placeholder="Cari nama alat atau kode..."
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-search">Cari</button>
        </form>
        <a href="tambah_alat_adminjurusan.php" class="btn-tambah">+ Tambah Alat</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Barang</th>
                    <th>Jurusan</th>
                    <th>Stok</th>
                    <th>Kondisi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($query) > 0): ?>
            <?php while ($d = mysqli_fetch_assoc($query)):
                $stok      = $d['stok_tersedia'];
                $stok_cls  = $stok > 10 ? 'stok-aman' : ($stok > 0 ? 'stok-tipis' : 'stok-habis');
                $has_foto  = !empty($d['foto']);
                $foto_src  = 'uploads/' . htmlspecialchars($d['foto']);
            ?>
                <tr>
                    <td>
                        <div class="foto-wrap">
                            <?php if ($has_foto): ?>
                                <img src="<?= $foto_src ?>"
                                     alt="<?= htmlspecialchars($d['nama_barang']) ?>"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <span class="foto-placeholder" style="display:none;">📷</span>
                            <?php else: ?>
                                <span class="foto-placeholder">📷</span>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td>
                        <div class="nama-barang"><?= htmlspecialchars($d['nama_barang']) ?></div>
                        <div class="td-sub"><?= htmlspecialchars($d['kode_barang']) ?></div>
                    </td>

                    <td><?= htmlspecialchars($d['nama_jurusan']) ?></td>

                    <td>
                        <span class="stok <?= $stok_cls ?>"><?= $stok ?></span>
                        <div class="td-sub">dari <?= $d['stok_total'] ?></div>
                    </td>

                    <td>
                        <span class="badge <?= $d['kondisi'] === 'baik' ? 'badge-baik' : 'badge-rusak' ?>">
                            <?= ucfirst($d['kondisi']) ?>
                        </span>
                    </td>

                    <td>
                        <div class="aksi">
                            <a href="edit_alat_adminjurusan.php?id=<?= $d['id_barang'] ?>" class="btn-edit">Edit</a>
                            <a href="hapus_alat_adminjurusan.php?id=<?= $d['id_barang'] ?>"
                               class="btn-hapus"
                               onclick="return confirm('Hapus barang ini?')">Hapus</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php else: ?>
                <tr class="empty-row">
                    <td colspan="6">Data alat tidak ditemukan.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>