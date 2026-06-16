<?php
require_once 'koneksii.php';

if (session_status() === PHP_SESSION_NONE) {
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {

    $id_pinjam = (int)$_POST['id_peminjaman'];
    $aksi      = $_POST['aksi'];

    $cek = mysqli_query($conn,

    "SELECT peminjaman.*, barang.stok_tersedia
     FROM peminjaman
     JOIN barang
     ON peminjaman.id_barang = barang.id_barang
     WHERE peminjaman.id_peminjaman = '$id_pinjam'"
    );

    $data = mysqli_fetch_assoc($cek);

    if($data){

        if($aksi == 'setujui'){

            if($data['stok_tersedia'] >= $data['jumlah_pinjam']){

                mysqli_query($conn,

                "UPDATE peminjaman
                 SET status='dipinjam'
                 WHERE id_peminjaman='$id_pinjam'"
                );

                mysqli_query($conn,

                "UPDATE barang
                 SET stok_tersedia =
                 stok_tersedia - {$data['jumlah_pinjam']}
                 WHERE id_barang='{$data['id_barang']}'"
                );

            } else {

                echo "
                <script>
                alert('Stok barang tidak cukup');
                window.location='peminjaman_adminjurusan.php';
                </script>
                ";

                exit;
            }
        }

        if($aksi == 'tolak'){

            mysqli_query($conn,

            "UPDATE peminjaman
             SET status='ditolak'
             WHERE id_peminjaman='$id_pinjam'"
            );
        }
    }

    header("Location: peminjaman_adminjurusan.php?success=1");
    exit;
}

include 'layout_adminn.php';

$search = $_GET['search'] ?? '';
$search = mysqli_real_escape_string($conn, $search);

$where = "WHERE p.status='pending'";

if($hak_akses != 'super_admin'){
    $where .= " AND b.id_jurusan='$id_jurusan'";
}

if($search != ''){

    $where .= "

    AND (
        s.nama_siswa LIKE '%$search%'
        OR s.nis LIKE '%$search%'
        OR b.nama_barang LIKE '%$search%'
    )
    ";
}

$query = mysqli_query($conn,

"SELECT
p.*,
s.nama_siswa,
s.nis,
s.kelas,
b.nama_barang,
b.kode_barang,
b.stok_tersedia,
j.nama_jurusan

FROM peminjaman p

JOIN siswa s
ON p.id_siswa = s.id_siswa

JOIN barang b
ON p.id_barang = b.id_barang

JOIN jurusan j
ON b.id_jurusan = j.id_jurusan

$where
ORDER BY p.id_peminjaman DESC"
);

$total_pending = mysqli_num_rows($query);
?>

<style>

.stat-box{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:15px;
    margin-bottom:25px;
}

.card-stat{
    background:#1e293b;
    padding:20px;
    border-radius:14px;
}

.card-stat h3{
    font-size:14px;
    color:#94a3b8;
    margin-bottom:10px;
}

.card-stat .num{
    font-size:30px;
    font-weight:700;
}

.search-box{
    margin-bottom:20px;
}

.search-form{
    display:flex;
    gap:10px;
}

.search-input{
    flex:1;
    padding:13px;
    border:none;
    border-radius:10px;
    background:#1e293b;
    color:white;
}

.btn-search{
    padding:13px 18px;
    border:none;
    border-radius:10px;
    background:#2563eb;
    color:white;
    cursor:pointer;
}

.table-box{
    background:#1e293b;
    border-radius:16px;
    overflow:auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

table th{
    background:#334155;
    padding:15px;
    text-align:left;
    font-size:13px;
}

table td{
    padding:15px;
    border-bottom:1px solid rgba(255,255,255,.05);
    font-size:13px;
}

table tr:hover{
    background:rgba(255,255,255,.03);
}

.badge{
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}

.pending{
    background:rgba(250,204,21,.15);
    color:#fde047;
}

.dipinjam{
    background:rgba(34,197,94,.15);
    color:#4ade80;
}

.btn{
    padding:8px 12px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    color:white;
    font-size:12px;
}

.btn-setuju{
    background:#16a34a;
}

.btn-tolak{
    background:#dc2626;
}

.aksi{
    display:flex;
    gap:8px;
}

.empty{
    padding:40px;
    text-align:center;
    color:#94a3b8;
}

.info{
    color:#94a3b8;
    font-size:12px;
    margin-top:4px;
}

</style>

<div class="stat-box">

<div class="card-stat">
<h3>Total Pending</h3>
<div class="num"><?= $total_pending ?></div>
</div>

<div class="card-stat">
<h3>Admin</h3>
<div class="num">
<?= htmlspecialchars($nama_admin) ?>
</div>
</div>

<div class="card-stat">
<h3>Status</h3>
<div class="num">Aktif</div>
</div>

</div>

<div class="search-box">
<form method="GET" class="search-form">

<input type="text"
       name="search"
       class="search-input"
       placeholder="Cari siswa atau barang..."
       value="<?= htmlspecialchars($search) ?>">

<button class="btn-search">
Cari
</button>
</form>
</div>

<div class="table-box">
<table>

<tr>
<th>No</th>
<th>Siswa</th>
<th>Barang</th>
<th>Jurusan</th>
<th>Jumlah</th>
<th>Stok</th>
<th>Tanggal</th>
<th>Status</th>
<th>Aksi</th>
</tr>

<?php if(mysqli_num_rows($query) > 0): ?>
<?php $no=1; while($d = mysqli_fetch_assoc($query)) : ?>

<tr>
<td><?= $no++ ?></td>

<td>
<?= $d['nama_siswa'] ?>

<div class="info">
<?= $d['nis'] ?> • <?= $d['kelas'] ?>
</div>
</td>

<td>
<?= $d['nama_barang'] ?>

<div class="info">
<?= $d['kode_barang'] ?>
</div>
</td>

<td>
<?= $d['nama_jurusan'] ?>
</td>

<td>
<?= $d['jumlah_pinjam'] ?>
</td>

<td>
<?= $d['stok_tersedia'] ?>
</td>

<td>
<?= date('d M Y', strtotime($d['tgl_pinjam'])) ?>
</td>

<td>
<span class="badge pending">
        Pending
</span>
</td>
<td>

<div class="aksi">
<form method="POST">

<input type="hidden"
       name="id_peminjaman"
       value="<?= $d['id_peminjaman'] ?>">

<input type="hidden"
       name="aksi"
       value="setujui">

<button type="submit"
        class="btn btn-setuju">
        Setujui
</button>
</form>

<form method="POST">

<input type="hidden"
       name="id_peminjaman"
       value="<?= $d['id_peminjaman'] ?>">

<input type="hidden"
       name="aksi"
       value="tolak">

<button type="submit"
        class="btn btn-tolak">
        Tolak
</button>
</form>
</div>
</td>
</tr>

<?php endwhile; ?>
<?php else: ?>

<tr>
<td colspan="9">

<div class="empty">
Tidak ada pengajuan peminjaman
</div>

</td>
</tr>
<?php endif; ?>
</table>
</div>