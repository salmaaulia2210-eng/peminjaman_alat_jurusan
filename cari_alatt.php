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

$keyword = trim($_GET['q'] ?? '');
$jurusan = $_GET['jurusan'] ?? '';
$status  = $_GET['status'] ?? '';

$q_jurusan = mysqli_query($conn, "SELECT * FROM jurusan ORDER BY nama_jurusan");

$where = ["1=1"];
if ($keyword) $where[] = "(b.nama_barang LIKE '%$keyword%' OR b.kode_barang LIKE '%$keyword%')";
if ($jurusan) $where[] = "b.id_jurusan = '$jurusan'";
if ($status === 'tersedia') $where[] = "b.stok_tersedia > 0";
if ($status === 'habis')    $where[] = "b.stok_tersedia = 0";

$where_str = implode(' AND ', $where);

$q_barang = mysqli_query($conn, "
    SELECT b.*, j.nama_jurusan, j.kode_jurusan
    FROM barang b
    JOIN jurusan j ON b.id_jurusan = j.id_jurusan
    WHERE $where_str
    ORDER BY b.nama_barang ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cari Alat - PinjamAlat SMK</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; background: #0d1b2a; color: #222; }

.topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-bottom: 1px solid #1a2d42;
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
    background: none; border: 1px solid #2a4a6b; border-radius: 8px;
    padding: 6px 12px; font-size: 12px; color: #8ab0d0; cursor: pointer; text-decoration: none;
}
.btn-logout:hover { background: rgba(248,113,113,.1); color: #f87171; border-color: rgba(248,113,113,.3); }

.sidebar {
    position: fixed; left: 0; top: 57px; bottom: 0; width: 200px;
    border-right: 1px solid #1a2d42; background: #0d1b2a;
    padding: 16px 0; overflow-y: auto; z-index: 99;
}
.nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 18px; cursor: pointer; font-size: 13px;
    color: #8ab0d0; text-decoration: none; transition: background 0.15s;
}
.nav-item:hover { background: #275ede; color: #fff; }
.nav-item.active { color: #939bf3; background: #1d1f1f; font-weight: 700; border-right: 2px solid #1d289e; }
.nav-item i { font-size: 16px; }
.nav-section { font-size: 11px; color: #4a6080; padding: 14px 18px 4px; text-transform: uppercase; letter-spacing: .5px; }

.main { margin-left: 200px; padding: 20px; margin-top: 57px; background: #0d1b2a; min-height: 100vh; }
.page-title { font-size: 18px; font-weight: 700; margin-bottom: 4px; color: #fff; }
.page-sub { font-size: 13px; color: #8ab0d0; margin-bottom: 20px; }

.search-box {
    background: #1a2d42; border: 0.5px solid rgba(255,255,255,0.08); border-radius: 10px;
    padding: 16px; margin-bottom: 16px;
    display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;
}
.search-box input, .search-box select {
    background: #0d1b2a; border: 0.5px solid rgba(255,255,255,0.08); border-radius: 7px;
    padding: 8px 12px; font-size: 13px; color: #e0eaf5; outline: none;
}
.search-box input { flex: 1; min-width: 180px; }
.search-box input::placeholder { color: #4a6080; }
.search-box select option { background: #0d1b2a; }
.btn-search {
    background: #1e3a6e; color: #fff; border: 0.5px solid rgba(255,255,255,0.12); border-radius: 7px;
    padding: 8px 18px; font-size: 13px; cursor: pointer; font-weight: 600;
}
.btn-search:hover { background: #254d8f; }
.btn-reset {
    background: none; color: #8ab0d0; border: 0.5px solid rgba(255,255,255,0.08); border-radius: 7px;
    padding: 8px 14px; font-size: 13px; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 4px;
}
.btn-reset:hover { color: #f87171; border-color: rgba(248,113,113,.3); }

.result-info { font-size: 13px; color: #8ab0d0; margin-bottom: 14px; }
.result-info b { color: #e0eaf5; }

.alat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 14px;
}

.alat-card {
    background: #1a2d42;
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    cursor: pointer;
    transition: transform 0.15s, border-color 0.15s;
}
.alat-card:hover {
    transform: translateY(-3px);
    border-color: rgba(96,165,250,0.3);
}

.card-cover {
    width: 100%;
    height: 150px;
    overflow: hidden;
    background: #0f1f3d;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    flex-shrink: 0;
}
.card-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.card-cover-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    height: 100%;
    background: #0f1f3d;
}
.card-cover-placeholder i {
    font-size: 48px;
    color: #2a4a6b;
}
.card-cover-placeholder span {
    font-size: 10px;
    color: #2a4a6b;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.cover-overlay-habis {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(13, 27, 42, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
}
.cover-overlay-habis span {
    background: rgba(163,45,45,0.85);
    color: #f7c1c1;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 4px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.card-body {
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
}
.card-name { font-size: 13px; font-weight: 700; color: #e0eaf5; line-height: 1.4; }
.card-meta { font-size: 11px; color: #7090b0; line-height: 1.7; }
.card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 4px;
}
.badge { display: inline-block; font-size: 11px; padding: 2px 9px; border-radius: 4px; font-weight: 600; }
.badge.tersedia { background: rgba(52,211,153,0.12); color: #34d399; border: 0.5px solid rgba(52,211,153,0.2); }
.badge.habis    { background: rgba(248,113,113,0.12); color: #f87171; border: 0.5px solid rgba(248,113,113,0.2); }

.btn-pinjam {
    background: #1e3a6e; color: #fff; border: none; border-radius: 6px;
    padding: 5px 12px; font-size: 11px; cursor: pointer; text-decoration: none;
    font-weight: 600; display: inline-block;
}
.btn-pinjam:hover { background: #254d8f; }
.btn-pinjam.disabled {
    background: transparent;
    color: #4a6080;
    border: 0.5px solid rgba(255,255,255,0.06);
    cursor: not-allowed;
    pointer-events: none;
}

.empty-state {
    text-align: center; padding: 50px; color: #4a6080;
    font-size: 14px; grid-column: 1 / -1;
}
.empty-state i { font-size: 48px; display: block; margin-bottom: 12px; color: #2a4a6b; }

.modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    z-index: 200;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal-backdrop.open { display: flex; }

.modal {
    background: #1a2d42;
    border: 0.5px solid rgba(255,255,255,0.1);
    border-radius: 14px;
    width: 100%;
    max-width: 480px;
    overflow: hidden;
    position: relative;
    animation: modalIn 0.18s ease;
}
@keyframes modalIn {
    from { opacity: 0; transform: scale(0.96) translateY(10px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-cover {
    width: 100%;
    height: 220px;
    overflow: hidden;
    background: #0f1f3d;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
.modal-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.modal-cover-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    height: 100%;
}
.modal-cover-placeholder i { font-size: 72px; color: #2a4a6b; }
.modal-cover-placeholder span { font-size: 11px; color: #2a4a6b; text-transform: uppercase; letter-spacing: 0.5px; }

.modal-close {
    position: absolute;
    top: 12px; right: 12px;
    width: 30px; height: 30px;
    background: rgba(13,27,42,0.7);
    border: 0.5px solid rgba(255,255,255,0.1);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    color: #a0b4cc;
    font-size: 16px;
    z-index: 10;
    transition: background 0.15s;
}
.modal-close:hover { background: rgba(248,113,113,0.2); color: #f87171; }

.modal-body { padding: 20px; }
.modal-name { font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 4px; }
.modal-code { font-size: 12px; color: #7090b0; margin-bottom: 16px; }

.modal-info-list { display: flex; flex-direction: column; margin-bottom: 18px; }
.modal-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    padding: 10px 0;
    border-bottom: 0.5px solid rgba(255,255,255,0.05);
}
.modal-info-item:last-child { border-bottom: none; padding-bottom: 0; }
.modal-info-item .lbl { color: #7090b0; }
.modal-info-item .val { color: #e0eaf5; font-weight: 600; }

.modal-footer { display: flex; gap: 10px; }
.btn-modal-pinjam {
    flex: 1;
    padding: 11px;
    background: #1e3a6e;
    border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: 8px;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.15s;
    display: block;
}
.btn-modal-pinjam:hover { background: #254d8f; }
.btn-modal-pinjam.disabled {
    background: transparent;
    color: #4a6080;
    border: 0.5px solid rgba(255,255,255,0.06);
    cursor: not-allowed;
    pointer-events: none;
}
.btn-modal-tutup {
    padding: 11px 20px;
    background: transparent;
    border: 0.5px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    color: #8ab0d0;
    font-size: 13px;
    cursor: pointer;
}
.btn-modal-tutup:hover { color: #fff; }
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
    <a class="nav-item active" href="cari_alatt.php"><i class="ti ti-search"></i> Cari Alat</a>
    <a class="nav-item" href="peminjaman_siswaa.php"><i class="ti ti-package"></i> Peminjaman</a>
    <a class="nav-item" href="pengembalian_siswaa.php"><i class="ti ti-arrow-back"></i> Pengembalian</a>
    <div class="nav-section">Informasi</div>
    <a class="nav-item" href="denda_siswaa.php"><i class="ti ti-receipt"></i> Denda</a>
    <a class="nav-item" href="riwayat_siswaa.php"><i class="ti ti-history"></i> Riwayat</a>
    <div class="nav-section">Akun</div>
    <a class="nav-item" href="profil_siswaa.php"><i class="ti ti-user"></i> Profil</a>
</div>

<div class="main">
    <div class="page-title">Cari Alat</div>
    <div class="page-sub">Temukan alat yang tersedia untuk dipinjam.</div>

    <form method="GET" action="cari_alatt.php">
        <div class="search-box">
            <input type="text" name="q" placeholder="Cari nama atau kode alat..." value="<?php echo htmlspecialchars($keyword); ?>">
            <select name="jurusan">
                <option value="">Semua Jurusan</option>
                <?php while ($j = mysqli_fetch_assoc($q_jurusan)): ?>
                <option value="<?php echo $j['id_jurusan']; ?>" <?php echo ($jurusan == $j['id_jurusan']) ? 'selected' : ''; ?>>
                    <?php echo $j['nama_jurusan']; ?>
                </option>
                <?php endwhile; ?>
            </select>
            <select name="status">
                <option value="">Semua Status</option>
                <option value="tersedia" <?php echo $status === 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                <option value="habis"    <?php echo $status === 'habis'    ? 'selected' : ''; ?>>Stok Habis</option>
            </select>
            <button type="submit" class="btn-search"><i class="ti ti-search"></i> Cari</button>
            <a href="cari_alatt.php" class="btn-reset"><i class="ti ti-x"></i> Reset</a>
        </div>
    </form>

    <?php $total = mysqli_num_rows($q_barang); ?>
    <div class="result-info">Ditemukan <b><?php echo $total; ?></b> alat<?php echo $keyword ? " untuk \"<b>" . htmlspecialchars($keyword) . "</b>\"" : ''; ?></div>

    <div class="alat-grid">
        <?php if ($total > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($q_barang)):
            $tersedia  = $row['stok_tersedia'] > 0;
            $foto_path = $row['foto'] ? 'uploads/' . $row['foto'] : '';
            $has_foto  = !empty($foto_path);

            $modal_data = htmlspecialchars(json_encode([
                'id'       => $row['id_barang'],
                'nama'     => $row['nama_barang'],
                'kode'     => $row['kode_barang'],
                'jurusan'  => $row['nama_jurusan'],
                'kode_jrs' => $row['kode_jurusan'],
                'stok_tot' => $row['stok_total'],
                'stok_ada' => $row['stok_tersedia'],
                'kondisi'  => $row['kondisi'],
                'foto'     => $foto_path,
                'tersedia' => $tersedia,
            ]), ENT_QUOTES);
        ?>
        <div class="alat-card" onclick="bukaModal(<?php echo $modal_data; ?>)">

            <div class="card-cover">
                <?php if ($has_foto): ?>
                    <img src="<?php echo htmlspecialchars($foto_path); ?>"
                         alt="<?php echo htmlspecialchars($row['nama_barang']); ?>"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <div class="card-cover-placeholder" style="display:none;">
                        <i class="ti ti-tool"></i>
                        <span>No Image</span>
                    </div>
                <?php else: ?>
                    <div class="card-cover-placeholder">
                        <i class="ti ti-tool"></i>
                        <span>No Image</span>
                    </div>
                <?php endif; ?>

                <?php if (!$tersedia): ?>
                    <div class="cover-overlay-habis"><span>Stok Habis</span></div>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <div class="card-name"><?php echo htmlspecialchars($row['nama_barang']); ?></div>
                <div class="card-meta">
                    <div>Kode: <b><?php echo $row['kode_barang']; ?></b></div>
                    <div>Jurusan: <b><?php echo $row['kode_jurusan']; ?></b></div>
                    <div>Stok: <b><?php echo $row['stok_tersedia']; ?></b></div>
                </div>
                <div class="card-footer">
                    <span class="badge <?php echo $tersedia ? 'tersedia' : 'habis'; ?>">
                        <?php echo $tersedia ? 'Tersedia' : 'Stok Habis'; ?>
                    </span>
                    <a href="peminjaman_siswaa.php?id_barang=<?php echo $row['id_barang']; ?>"
                       class="btn-pinjam <?php echo !$tersedia ? 'disabled' : ''; ?>"
                       onclick="event.stopPropagation()">
                        Pinjam
                    </a>
                </div>
            </div>

        </div>
        <?php endwhile; ?>
        <?php else: ?>
        <div class="empty-state">
            <i class="ti ti-mood-empty"></i>
            Tidak ada alat yang ditemukan
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal-backdrop" id="modalBackdrop" onclick="tutupModal(event)">
    <div class="modal" id="modalBox">

        <button class="modal-close" onclick="tutupModalLangsung()">
            <i class="ti ti-x"></i>
        </button>

        <div class="modal-cover" id="modalCover">
            <div class="modal-cover-placeholder" id="modalPlaceholder">
                <i class="ti ti-tool"></i>
                <span>No Image</span>
            </div>
            <img id="modalImg" src="" alt="" style="display:none;"
                 onerror="this.style.display='none';document.getElementById('modalPlaceholder').style.display='flex';">
        </div>

        <div class="modal-body">
            <div class="modal-name" id="modalNama"></div>
            <div class="modal-code" id="modalKode"></div>

            <div class="modal-info-list">
                <div class="modal-info-item">
                    <span class="lbl">Jurusan</span>
                    <span class="val" id="modalJurusan"></span>
                </div>
                <div class="modal-info-item">
                    <span class="lbl">Stok Total</span>
                    <span class="val" id="modalStokTot"></span>
                </div>
                <div class="modal-info-item">
                    <span class="lbl">Stok Tersedia</span>
                    <span class="val" id="modalStokAda"></span>
                </div>
                <div class="modal-info-item">
                    <span class="lbl">Kondisi</span>
                    <span class="val" id="modalKondisi"></span>
                </div>
                <div class="modal-info-item">
                    <span class="lbl">Status</span>
                    <span class="val" id="modalStatus"></span>
                </div>
            </div>

            <div class="modal-footer">
                <a id="modalBtnPinjam" href="#" class="btn-modal-pinjam">
                    <i class="ti ti-package"></i> Pinjam Sekarang
                </a>
                <button class="btn-modal-tutup" onclick="tutupModalLangsung()">Tutup</button>
            </div>
        </div>

    </div>
</div>

<script>
function bukaModal(d) {
    var backdrop = document.getElementById('modalBackdrop');
    var img       = document.getElementById('modalImg');
    var ph        = document.getElementById('modalPlaceholder');

    document.getElementById('modalNama').textContent    = d.nama;
    document.getElementById('modalKode').textContent    = 'Kode: ' + d.kode;
    document.getElementById('modalJurusan').textContent = d.kode_jrs + ' — ' + d.jurusan;
    document.getElementById('modalStokTot').textContent = d.stok_tot;
    document.getElementById('modalStokAda').textContent = d.stok_ada;
    document.getElementById('modalKondisi').textContent = d.kondisi.charAt(0).toUpperCase() + d.kondisi.slice(1);

    var statusEl = document.getElementById('modalStatus');
    if (d.tersedia) {
        statusEl.textContent = 'Tersedia';
        statusEl.style.color = '#34d399';
    } else {
        statusEl.textContent = 'Stok Habis';
        statusEl.style.color = '#f87171';
    }

    var btnPinjam = document.getElementById('modalBtnPinjam');
    btnPinjam.href = 'peminjaman_siswaa.php?id_barang=' + d.id;
    if (d.tersedia) {
        btnPinjam.classList.remove('disabled');
    } else {
        btnPinjam.classList.add('disabled');
    }

    if (d.foto) {
        img.src = d.foto;
        img.style.display = 'block';
        ph.style.display  = 'none';
    } else {
        img.style.display = 'none';
        ph.style.display  = 'flex';
    }

    backdrop.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function tutupModal(e) {
    if (e.target === document.getElementById('modalBackdrop')) {
        tutupModalLangsung();
    }
}

function tutupModalLangsung() {
    document.getElementById('modalBackdrop').classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') tutupModalLangsung();
});
</script>
</body>
</html>