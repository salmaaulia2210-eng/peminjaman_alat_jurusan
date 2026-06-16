<?php
require_once 'koneksii.php';
include 'layout_adminn.php';

$id_jurusan = $_SESSION['id_jurusan'];

$jurusan = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT * FROM jurusan
 WHERE id_jurusan = '$id_jurusan'"));


$total_alat = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total
 FROM barang
 WHERE id_jurusan = '$id_jurusan'"))['total'];

$total_dipinjam = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total
 FROM peminjaman p
 JOIN barang b ON p.id_barang = b.id_barang
 WHERE b.id_jurusan = '$id_jurusan'
 AND p.status = 'dipinjam'"))['total'];

$total_tersedia = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(stok_tersedia) as total
 FROM barang
 WHERE id_jurusan = '$id_jurusan'"))['total'] ?? 0;

$total_denda = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(p.denda) as total
 FROM peminjaman p
 JOIN barang b ON p.id_barang = b.id_barang
 WHERE b.id_jurusan = '$id_jurusan'
 AND p.denda > 0"))['total'] ?? 0;

$terlambat = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total
 FROM peminjaman p
 JOIN barang b ON p.id_barang = b.id_barang
 WHERE b.id_jurusan = '$id_jurusan'
 AND p.status = 'dipinjam'
 AND p.tgl_kembali_seharusnya < NOW()"))['total'];

?>

<style>

.dash-wrap{
    display:flex;
    flex-direction:column;
    gap:22px;
}

.alert-box{
    background:
    linear-gradient(
    145deg,
    rgba(248,113,113,.10),
    rgba(127,29,29,.08)
    );

    border:1px solid rgba(248,113,113,.15);
    color:#fca5a5;
    padding:16px 20px;
    border-radius:18px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    backdrop-filter:blur(8px);
    box-shadow:
    0 10px 25px rgba(0,0,0,.18);
}

.alert-box a{
    color:#93c5fd;
    text-decoration:none;
    font-weight:700;
    transition:.2s;
}

.alert-box a:hover{
    color:white;
}

.stats-row{
    display:grid;
    grid-template-columns:
    repeat(auto-fit,minmax(230px,1fr));
    gap:18px;
}

.sc{
    background:
    linear-gradient(
    145deg,
    #1e293b,
    #162033
    );

    border-radius:22px;
    padding:24px;
    border:1px solid
    rgba(255,255,255,.05);
    transition:.25s ease;
    position:relative;
    overflow:hidden;
    box-shadow:
    0 10px 30px rgba(0,0,0,.20);
}

.sc::before{
    content:'';
    position:absolute;
    top:-40px;
    right:-40px;
    width:100px;
    height:100px;
    background:
    rgba(59,130,246,.08);
    border-radius:50%;
}

.sc:hover{
    transform:translateY(-4px);
    border-color:
    rgba(59,130,246,.18);
    box-shadow:
    0 15px 35px rgba(37,99,235,.12);
}

.sc-lbl{
    font-size:12px;
    color:#94a3b8;
    margin-bottom:12px;
    letter-spacing:.4px;
    text-transform:uppercase;
    font-weight:600;
}

.sc-num{
    font-size:34px;
    font-weight:800;
    color:white;
    line-height:1;
}

.amber{
    color:#fbbf24;
}

.green{
    color:#34d399;
}

.red{
    color:#f87171;
}

.jcard{
    background:
    linear-gradient(
    145deg,
    #1e293b,
    #162033
    );

    border-radius:24px;
    overflow:hidden;
    border:1px solid
    rgba(255,255,255,.05);
    box-shadow:
    0 15px 35px rgba(0,0,0,.22);
}

.jhead{
    padding:26px;
    border-bottom:
    1px solid rgba(255,255,255,.05);
    position:relative;
}

.jhead::after{
    content:'';
    position:absolute;
    bottom:0;
    left:26px;
    width:70px;
    height:3px;
    background:#3b82f6;
    border-radius:10px;
}

.jname{
    font-size:26px;
    font-weight:800;
    color:white;
}

.jkode{
    margin-top:8px;
    color:#94a3b8;
    font-size:13px;
    letter-spacing:.5px;
}

.jbody{
    padding:24px;
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:18px;
}

.jstat{
    background:
    rgba(15,23,42,.88);
    border:
    1px solid rgba(255,255,255,.04);
    padding:22px;
    border-radius:18px;
    transition:.2s ease;
}

.jstat:hover{
    transform:translateY(-3px);
    border-color:
    rgba(59,130,246,.16);
    background:
    rgba(15,23,42,1);
}

.jnum{
    font-size:28px;
    font-weight:800;
    line-height:1;
}

.jlbl{
    margin-top:8px;
    font-size:12px;
    color:#94a3b8;
    letter-spacing:.3px;
}

.jfoot{
    padding:20px 24px;
    border-top:
    1px solid rgba(255,255,255,.05);
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.notif{
    padding:8px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
    letter-spacing:.3px;
}

.warn{
    background:
    rgba(248,113,113,.12);
    color:#f87171;
    border:
    1px solid rgba(248,113,113,.18);
}

.aman{
    background:
    rgba(52,211,153,.12);
    color:#34d399;
    border:
    1px solid rgba(52,211,153,.18);
}

.btn-detail{
    color:#93c5fd;
    text-decoration:none;
    font-weight:700;
    transition:.2s;
}

.btn-detail:hover{
    color:white;
    transform:translateX(3px);
}

@media(max-width:700px){

.jbody{
    grid-template-columns:1fr;
}

.sc-num{
    font-size:28px;
}

.jname{
    font-size:22px;
}

}

</style>

<div class="dash-wrap">

    <?php if($terlambat > 0): ?>

    <div class="alert-box">
        <div>
            Ada <?= $terlambat ?> peminjaman yang terlambat dikembalikan
        </div>

        <a href="pengembalian_adminjurusan.php">
            Lihat
        </a>
    </div>

    <?php endif; ?>

    <div class="stats-row">

        <div class="sc">
            <div class="sc-lbl">Total Jenis Alat</div>
            <div class="sc-num">
                <?= $total_alat ?>
            </div>
        </div>

        <div class="sc">
            <div class="sc-lbl">Sedang Dipinjam</div>
            <div class="sc-num amber">
                <?= $total_dipinjam ?>
            </div>
        </div>

        <div class="sc">
            <div class="sc-lbl">Stok Tersedia</div>
            <div class="sc-num green">
                <?= $total_tersedia ?>
            </div>
        </div>

        <div class="sc">
            <div class="sc-lbl">Total Denda</div>
            <div class="sc-num red">
                Rp <?= number_format($total_denda,0,',','.') ?>
            </div>
        </div>
    </div>

    <div class="jcard">
        <div class="jhead">

            <div class="jname">
                <?= htmlspecialchars($jurusan['nama_jurusan']) ?>
            </div>

            <div class="jkode">
                <?= htmlspecialchars($jurusan['kode_jurusan']) ?>
            </div>

        </div>

        <div class="jbody">

            <div class="jstat">
                <div class="jnum">
                    <?= $total_alat ?>
                </div>

                <div class="jlbl">
                    Jenis Alat
                </div>
            </div>

            <div class="jstat">
                <div class="jnum amber">
                    <?= $total_dipinjam ?>
                </div>

                <div class="jlbl">
                    Sedang Dipinjam
                </div>
            </div>

            <div class="jstat">
                <div class="jnum green">
                    <?= $total_tersedia ?>
                </div>

                <div class="jlbl">
                    Tersedia
                </div>
            </div>

            <div class="jstat">
                <div class="jnum red">
                    Rp <?= number_format($total_denda,0,',','.') ?>
                </div>

                <div class="jlbl">
                    Total Denda
                </div>
            </div>
        </div>

        <div class="jfoot">

            <?php if($terlambat > 0): ?>
                <span class="notif warn">
                    <?= $terlambat ?> Terlambat
                </span>

            <?php else: ?>
                <span class="notif aman">
                    Aman
                </span>

            <?php endif; ?>
            <a href="peminjaman_adminjurusan.php"
               class="btn-detail">
               Lihat Detail →
            </a>
        </div>
    </div>
</div>
</div>
</div>
</body>
</html>