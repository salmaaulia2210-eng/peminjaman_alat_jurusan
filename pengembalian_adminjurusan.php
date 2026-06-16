<?php
require_once 'koneksii.php';
include 'layout_adminn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {

    $id_pinjam       = (int)$_POST['id_peminjaman'];
    $kondisi_kembali = mysqli_real_escape_string($conn, $_POST['kondisi_kembali']);
    $denda_input     = (int)$_POST['denda'];

    $data = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM peminjaman
     WHERE id_peminjaman = '$id_pinjam'"));

    if($data){

        $tgl_kembali = date('Y-m-d H:i:s');
        $denda_terlambat = 0;

        if(strtotime($data['tgl_kembali_seharusnya']) < time()){

            $hari_terlambat =
            ceil(
                (time() - strtotime($data['tgl_kembali_seharusnya']))
                / 86400
            );

            $denda_terlambat =
            $hari_terlambat * 20000 * $data['jumlah_pinjam'];
        }

        $total_denda = $denda_terlambat + $denda_input;

        mysqli_query($conn,

        "UPDATE peminjaman SET

        status='dikembalikan',
        tgl_kembali='$tgl_kembali',
        kondisi_kembali='$kondisi_kembali',
        denda='$total_denda'

        WHERE id_peminjaman='$id_pinjam'"

        );

        mysqli_query($conn,
        "UPDATE barang SET
        stok_tersedia = stok_tersedia + {$data['jumlah_pinjam']},
        kondisi='$kondisi_kembali'
        WHERE id_barang = {$data['id_barang']}"

        );
    }

    echo "

    <script>

    alert('Pengembalian berhasil diproses');
    window.location='pengembalian_adminjurusan.php';
    </script>
    ";
}

$search = $_GET['search'] ?? '';
$where = "WHERE p.status='dipinjam'";

if($search != ''){
    $where .= " AND (
    s.nama_siswa LIKE '%$search%' OR
    s.nis LIKE '%$search%' OR
    b.nama_barang LIKE '%$search%'
    )";
}

$result = mysqli_query($conn,

"SELECT p.*, s.nama_siswa, s.nis, s.kelas,
        b.nama_barang, b.kode_barang,
        j.nama_jurusan, j.kode_jurusan

 FROM peminjaman p

 JOIN siswa s
 ON p.id_siswa = s.id_siswa
 JOIN barang b
 ON p.id_barang = b.id_barang
 JOIN jurusan j
 ON b.id_jurusan = j.id_jurusan

 $where
 ORDER BY p.tgl_kembali_seharusnya ASC"

);

$total = mysqli_num_rows($result);

?>

<style>

.wrap{
    display:flex;
    flex-direction:column;
    gap:22px;
}

/* HEADER */

.page-box{
    background:#111827;
    border:1px solid rgba(255,255,255,.05);
    border-radius:18px;
    padding:24px;
}

.page-title{
    font-size:28px;
    font-weight:700;
    color:white;
}

.page-sub{
    margin-top:8px;
    font-size:14px;
    color:#94a3b8;
}

/* SEARCH */

.search-box{
    background:#111827;
    border:1px solid rgba(255,255,255,.05);
    border-radius:18px;
    padding:18px;
}

.search-form{
    display:flex;
    gap:12px;
}

.search-input{
    flex:1;
    padding:13px 16px;
    border:none;
    border-radius:12px;
    background:#1e293b;
    color:white;
    font-size:14px;
}

.search-input:focus{
    outline:none;
}

.search-btn{
    padding:13px 22px;
    border:none;
    border-radius:12px;
    background:#2563eb;
    color:white;
    font-weight:600;
    cursor:pointer;
}

.search-btn:hover{
    background:#1d4ed8;
}

/* INFO */

.info-card{
    background:#111827;
    border:1px solid rgba(255,255,255,.05);
    border-radius:18px;
    padding:18px 22px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.info-title{
    font-size:15px;
    color:#cbd5e1;
}

.info-total{
    font-size:30px;
    font-weight:bold;
    color:white;
}

/* TABLE */

.table-box{
    background:#111827;
    border:1px solid rgba(255,255,255,.05);
    border-radius:18px;
    overflow:hidden;
}

table{
    width:100%;
    border-collapse:collapse;
}

thead{
    background:#0f172a;
}

thead th{
    padding:16px;
    text-align:left;
    font-size:12px;
    color:#94a3b8;
    text-transform:uppercase;
}

tbody tr{
    border-top:1px solid rgba(255,255,255,.05);
    transition:.2s;
}

tbody tr:hover{
    background:rgba(255,255,255,.02);
}

tbody td{
    padding:16px;
    font-size:14px;
    color:white;
}

.td-sub{
    margin-top:5px;
    font-size:12px;
    color:#64748b;
}

.badge{
    padding:6px 12px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
    display:inline-block;
}

.badge-blue{
    background:rgba(59,130,246,.15);
    color:#60a5fa;
}

.badge-red{
    background:rgba(239,68,68,.15);
    color:#f87171;
}

/* BUTTON */

.btn-proses{
    padding:10px 16px;
    border:none;
    border-radius:10px;
    background:#2563eb;
    color:white;
    font-size:13px;
    font-weight:600;
    cursor:pointer;
}

.btn-proses:hover{
    background:#1d4ed8;
}

/* EMPTY */

.empty{
    text-align:center;
    padding:45px !important;
    color:#64748b;
}

/* MODAL */

.modal-bg{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.65);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:999;
}

.modal-bg.show{
    display:flex;
}

.modal-box{
    width:420px;
    background:#111827;
    border-radius:20px;
    padding:28px;
    border:1px solid rgba(255,255,255,.05);
}

.modal-title{
    font-size:22px;
    font-weight:700;
    margin-bottom:8px;
}

.modal-sub{
    font-size:13px;
    color:#94a3b8;
    margin-bottom:22px;
}

.detail-box{
    background:#1e293b;
    border-radius:14px;
    padding:16px;
    margin-bottom:18px;
}

.detail-item{
    display:flex;
    justify-content:space-between;
    margin-bottom:12px;
    font-size:13px;
}

.detail-item:last-child{
    margin-bottom:0;
}

.detail-label{
    color:#94a3b8;
}

.detail-value{
    color:white;
    font-weight:600;
}

.form-group{
    margin-bottom:18px;
}

.form-group label{
    display:block;
    margin-bottom:8px;
    font-size:13px;
    color:#cbd5e1;
}

.form-input{
    width:100%;
    padding:12px 14px;
    border:none;
    border-radius:12px;
    background:#1e293b;
    color:white;
}

.form-input:focus{
    outline:none;
}

.modal-footer{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:22px;
}

.btn-batal{
    padding:11px 18px;
    border:none;
    border-radius:10px;
    background:#334155;
    color:white;
    cursor:pointer;
}

.btn-submit{
    padding:11px 18px;
    border:none;
    border-radius:10px;
    background:#2563eb;
    color:white;
    font-weight:600;
    cursor:pointer;
}

.btn-submit:hover{
    background:#1d4ed8;
}

@media(max-width:900px){

table{
    min-width:900px;
}

.table-box{
    overflow:auto;
}
}

</style>

<div class="wrap">
    <div class="search-box">
        <form method="GET" class="search-form">

            <input type="text"
                   name="search"
                   class="search-input"
                   placeholder="Cari nama siswa, NIS, atau alat..."
                   value="<?= htmlspecialchars($search) ?>">

            <button type="submit"
                    class="search-btn">
                Cari
            </button>
        </form>
    </div>

    <div class="info-card">

        <div class="info-title">
            Total alat yang sedang dipinjam
        </div>

        <div class="info-total">
            <?= $total ?>
        </div>

    </div>

    <div class="table-box">
        <table>
            <thead>

                <tr>
                    <th>No</th>
                    <th>Siswa</th>
                    <th>Alat</th>
                    <th>Jurusan</th>
                    <th>Tgl Pinjam</th>
                    <th>Batas Kembali</th>
                    <th>Status</th>
                    <th>Denda</th>
                    <th>Aksi</th>
                </tr>

            </thead>

            <tbody>

            <?php
            if($total == 0):
            ?>

            <tr>
                <td colspan="9" class="empty">
                    Tidak ada alat yang sedang dipinjam
                </td>
            </tr>

            <?php

            else:

            $no = 1;

            while($row = mysqli_fetch_assoc($result)):
            $terlambat =
            strtotime($row['tgl_kembali_seharusnya']) < time();

            $hari =
            $terlambat
            ? ceil((time() - strtotime($row['tgl_kembali_seharusnya'])) / 86400)
            : 0;

            $denda =
            $hari * 20000 * $row['jumlah_pinjam'];

            ?>

            <tr>
                <td><?= $no++ ?></td>
                <td>

                    <div>
                        <?= htmlspecialchars($row['nama_siswa']) ?>
                    </div>

                    <div class="td-sub">
                        <?= $row['nis'] ?> · <?= $row['kelas'] ?>
                    </div>
                </td>

                <td>
                    <div>
                        <?= htmlspecialchars($row['nama_barang']) ?>
                    </div>

                    <div class="td-sub">
                        <?= $row['kode_barang'] ?>
                    </div>
                </td>

                <td>
                    <div>
                        <?= $row['kode_jurusan'] ?>
                    </div>

                    <div class="td-sub">
                        <?= $row['nama_jurusan'] ?>
                    </div>
                </td>

                <td>
                    <?= date('d M Y', strtotime($row['tgl_pinjam'])) ?>
                </td>

                <td>
                    <?= date('d M Y', strtotime($row['tgl_kembali_seharusnya'])) ?>
                    <?php if($terlambat): ?>

                    <div class="td-sub" style="color:#f87171">

                        <?= $hari ?> hari terlambat
                    </div>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if($terlambat): ?>
                    <span class="badge badge-red">
                        Terlambat
                    </span>

                    <?php else: ?>

                    <span class="badge badge-blue">
                        Dipinjam
                    </span>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if($denda > 0): ?>
                    
                    <span style="color:#f87171;font-weight:700;">
                        Rp <?= number_format($denda,0,',','.') ?>
                    </span>

                    <?php else: ?>

                    -

                    <?php endif; ?>

                </td>

                <td>
                    <button class="btn-proses"
                    onclick="bukaModal(
                    <?= $row['id_peminjaman'] ?>,
                    '<?= addslashes($row['nama_siswa']) ?>',
                    '<?= addslashes($row['nama_barang']) ?>',
                    <?= $row['jumlah_pinjam'] ?>,
                    '<?= date('d M Y', strtotime($row['tgl_kembali_seharusnya'])) ?>',
                    <?= $denda ?>

                    )">
                    Proses
                    </button>
                </td>

            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL -->

<div class="modal-bg" id="modalBg">
    <div class="modal-box">

        <div class="modal-title">
            Proses Pengembalian
        </div>

        <div class="modal-sub">
            Pastikan kondisi alat sudah dicek sebelum dikembalikan.
        </div>

        <div class="detail-box">
            <div class="detail-item">
                <span class="detail-label">Siswa</span>
                <span class="detail-value" id="mNama"></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Alat</span>
                <span class="detail-value" id="mAlat"></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Jumlah</span>
                <span class="detail-value" id="mJumlah"></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Batas</span>
                <span class="detail-value" id="mBatas"></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Denda</span>
                <span class="detail-value" id="mDenda"></span>
            </div>
        </div>

        <form method="POST">

            <input type="hidden"
                   name="aksi"
                   value="kembalikan">

            <input type="hidden"
                   name="id_peminjaman"
                   id="inputId">

            <div class="form-group">
                <label>Kondisi Barang</label>

                <select name="kondisi_kembali"
                        class="form-input">

                    <option value="baik">
                        Baik
                    </option>

                    <option value="rusak">
                        Rusak
                    </option>

                    <option value="hilang">
                        Hilang
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label>Denda Tambahan</label>

                <input type="number"
                       name="denda"
                       class="form-input"
                       value="0">
            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn-batal"
                        onclick="tutupModal()">
                    Batal
                </button>

                <button type="submit"
                        class="btn-submit">
                    Konfirmasi
                </button>
            </div>
        </form>
    </div>
</div>

<script>

function bukaModal(id,nama,alat,jumlah,batas,denda){

    document.getElementById('modalBg')
    .classList.add('show');
    document.getElementById('inputId').value = id;
    document.getElementById('mNama').innerHTML = nama;
    document.getElementById('mAlat').innerHTML = alat;
    document.getElementById('mJumlah').innerHTML =
    jumlah + ' unit';
    document.getElementById('mBatas').innerHTML = batas;

    if(denda > 0){
        document.getElementById('mDenda').innerHTML =
        'Rp ' + denda.toLocaleString('id-ID');

    }else{
        document.getElementById('mDenda').innerHTML =
        'Tidak ada';
    }
}

function tutupModal(){
    document.getElementById('modalBg')
    .classList.remove('show');
}

document.getElementById('modalBg')
.addEventListener('click',function(e){

    if(e.target === this){

        tutupModal();
    }
});
</script>