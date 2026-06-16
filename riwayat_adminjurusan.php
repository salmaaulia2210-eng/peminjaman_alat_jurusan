<?php
require_once 'koneksii.php';

if (session_status() === PHP_SESSION_NONE) {
}

include 'layout_adminn.php';

$search = $_GET['search'] ?? '';
$search = mysqli_real_escape_string($conn, $search);
$filter_status = $_GET['status'] ?? '';
$where = "WHERE 1=1";

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

if($filter_status != ''){
    $where .= " AND p.status='$filter_status'";
}

$query = mysqli_query($conn,

"SELECT
p.*,
s.nama_siswa,
s.nis,
s.kelas,
b.nama_barang,
b.kode_barang,
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

$total_data = mysqli_num_rows($query);
$total_pending = mysqli_fetch_assoc(mysqli_query($conn,

"SELECT COUNT(*) as total
FROM peminjaman p
JOIN barang b
ON p.id_barang = b.id_barang
WHERE p.status='pending'
" . ($hak_akses != 'super_admin'
? " AND b.id_jurusan='$id_jurusan'"
: "")

))['total'];

$total_dipinjam = mysqli_fetch_assoc(mysqli_query($conn,

"SELECT COUNT(*) as total
FROM peminjaman p
JOIN barang b
ON p.id_barang = b.id_barang
WHERE p.status='dipinjam'
" . ($hak_akses != 'super_admin'
? " AND b.id_jurusan='$id_jurusan'"
: "")

))['total'];

$total_kembali = mysqli_fetch_assoc(mysqli_query($conn,

"SELECT COUNT(*) as total
FROM peminjaman p
JOIN barang b
ON p.id_barang = b.id_barang
WHERE p.status='dikembalikan'
" . ($hak_akses != 'super_admin'
? " AND b.id_jurusan='$id_jurusan'"
: "")

))['total'];

?>

<style>

.riwayat-wrap{
    display:flex;
    flex-direction:column;
    gap:20px;
}

.stat-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:16px;
}

.stat-card{
    background:#111827;
    border:1px solid rgba(255,255,255,.05);
    border-radius:18px;
    padding:22px;
}

.stat-title{
    font-size:13px;
    color:#94a3b8;
    margin-bottom:10px;
}

.stat-value{
    font-size:32px;
    font-weight:700;
    color:white;
}

.search-box{
    background:#111827;
    border:1px solid rgba(255,255,255,.05);
    border-radius:18px;
    padding:18px;
}

.search-form{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.search-input,
.search-select{
    flex:1;
    min-width:180px;
    padding:13px 15px;
    background:#0f172a;
    border:1px solid rgba(255,255,255,.05);
    border-radius:12px;
    color:white;
    outline:none;
}

.btn-search{
    padding:13px 20px;
    border:none;
    border-radius:12px;
    background:#2563eb;
    color:white;
    cursor:pointer;
    font-weight:600;
}

.btn-reset{
    padding:13px 18px;
    border-radius:12px;
    background:#1e293b;
    color:#cbd5e1;
    text-decoration:none;
    font-size:14px;
}

.table-box{
    background:#111827;
    border:1px solid rgba(255,255,255,.05);
    border-radius:18px;
    overflow:auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

table th{
    background:#0f172a;
    color:#94a3b8;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.5px;
    padding:16px;
    text-align:left;
}

table td{
    padding:16px;
    border-bottom:1px solid rgba(255,255,255,.05);
    font-size:14px;
    color:white;
}

table tr:hover{
    background:rgba(255,255,255,.02);
}

.info-sub{
    font-size:12px;
    color:#94a3b8;
    margin-top:4px;
}

.badge{
    padding:6px 12px;
    border-radius:30px;
    font-size:12px;
    font-weight:600;
}

.pending{
    background:rgba(250,204,21,.12);
    color:#fde047;
}

.dipinjam{
    background:rgba(59,130,246,.12);
    color:#60a5fa;
}

.dikembalikan{
    background:rgba(34,197,94,.12);
    color:#4ade80;
}

.ditolak{
    background:rgba(239,68,68,.12);
    color:#f87171;
}

.empty{
    padding:50px;
    text-align:center;
    color:#94a3b8;
}

@media(max-width:900px){

    .stat-grid{
        grid-template-columns:1fr;
    }

}

</style>

<div class="riwayat-wrap">

<div class="stat-grid">

<div class="stat-card">
<div class="stat-title">Pending</div>
<div class="stat-value"><?= $total_pending ?></div>
</div>

<div class="stat-card">
<div class="stat-title">Dipinjam</div>
<div class="stat-value"><?= $total_dipinjam ?></div>
</div>

<div class="stat-card">
<div class="stat-title">Dikembalikan</div>
<div class="stat-value"><?= $total_kembali ?></div>
</div>

</div>

<div class="search-box">
<form method="GET" class="search-form">

<input type="text"
       name="search"
       class="search-input"
       placeholder="Cari siswa atau barang..."
       value="<?= htmlspecialchars($search) ?>">

<select name="status" class="search-select">
<option value="">Semua Status</option>

<option value="pending"
<?= $filter_status == 'pending' ? 'selected' : '' ?>>
Pending
</option>

<option value="dipinjam"
<?= $filter_status == 'dipinjam' ? 'selected' : '' ?>>
Dipinjam
</option>

<option value="dikembalikan"
<?= $filter_status == 'dikembalikan' ? 'selected' : '' ?>>
Dikembalikan
</option>

<option value="ditolak"
<?= $filter_status == 'ditolak' ? 'selected' : '' ?>>
Ditolak
</option>

</select>

<button class="btn-search">
Cari
</button>

<a href="riwayat_adminjurusan.php"
   class="btn-reset">
Reset
</a>

</form>

</div>

<div class="table-box">

<table>

<tr>
<th>No</th>
<th>Siswa</th>
<th>Barang</th>
<th>Jurusan</th>
<th>Tanggal</th>
<th>Jumlah</th>
<th>Status</th>
</tr>

<?php if(mysqli_num_rows($query) > 0): ?>
<?php $no=1; while($d = mysqli_fetch_assoc($query)) : ?>

<tr>

<td><?= $no++ ?></td>

<td>
<?= htmlspecialchars($d['nama_siswa']) ?>

<div class="info-sub">
<?= $d['nis'] ?> • <?= $d['kelas'] ?>
</div>
</td>

<td>
<?= htmlspecialchars($d['nama_barang']) ?>

<div class="info-sub">
<?= $d['kode_barang'] ?>
</div>
</td>

<td>
<?= htmlspecialchars($d['nama_jurusan']) ?>
</td>

<td>
<?= date('d M Y', strtotime($d['tgl_pinjam'])) ?>
</td>

<td>
<?= $d['jumlah_pinjam'] ?>
</td>

<td>

<?php if($d['status'] == 'pending'): ?>
<span class="badge pending">Pending</span>

<?php elseif($d['status'] == 'dipinjam'): ?>
<span class="badge dipinjam">Dipinjam</span>

<?php elseif($d['status'] == 'dikembalikan'): ?>
<span class="badge dikembalikan">Dikembalikan</span>

<?php else: ?>
<span class="badge ditolak">Ditolak</span>
<?php endif; ?>

</td>
</tr>

<?php endwhile; ?>
<?php else: ?>

<tr>
<td colspan="7">

<div class="empty">
Belum ada data riwayat
</div>

</td>
</tr>
<?php endif; ?>
</table>
</div>
</div>