<?php
require 'koneksii.php';
include 'layout_adminn.php';

$id = $_GET['id'];
$id_jurusan_admin = $_SESSION['id_jurusan'];

$query = mysqli_query($conn,

"SELECT * FROM barang
WHERE id_barang = '$id'
AND id_jurusan = '$id_jurusan_admin'"
);

$data = mysqli_fetch_assoc($query);
if(!$data){
    echo "
    <script>
    alert('Data tidak ditemukan');
    window.location='data_alat_adminjurusan.php';
    </script>
    ";
    exit;
}

$query_jurusan = mysqli_query($conn,
"SELECT * FROM jurusan
WHERE id_jurusan = '$id_jurusan_admin'"
);

if(isset($_POST['update'])){

    $kode_barang = mysqli_real_escape_string($conn, $_POST['kode_barang']);
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $id_jurusan  = mysqli_real_escape_string($conn, $_POST['id_jurusan']);
    $stok_total  = mysqli_real_escape_string($conn, $_POST['stok_total']);
    $kondisi     = mysqli_real_escape_string($conn, $_POST['kondisi']);

    $foto = $data['foto'];

    if($_FILES['foto']['name'] != ''){
        $nama_file = time() . '_' . $_FILES['foto']['name'];
        $tmp       = $_FILES['foto']['tmp_name'];
        move_uploaded_file($tmp, 'uploads/' . $nama_file);
        $foto = $nama_file;
    }

    mysqli_query($conn,

    "UPDATE barang SET

    kode_barang    = '$kode_barang',
    nama_barang    = '$nama_barang',
    id_jurusan     = '$id_jurusan',
    stok_total     = '$stok_total',
    stok_tersedia  = '$stok_total',
    kondisi        = '$kondisi',
    foto           = '$foto'

    WHERE id_barang = '$id'"
    );

    echo "
    <script>
    alert('Data alat berhasil diupdate');
    window.location='data_alat_adminjurusan.php';
    </script>
    ";
}
?>

<style>

.form-wrapper{
    width:100%;
    display:flex;
    justify-content:center;
}

.form-container{
    width:100%;
    max-width:850px;
    background:#111827;
    border:1px solid rgba(255,255,255,.05);
    border-radius:20px;
    padding:35px;
}

.form-title{
    font-size:28px;
    font-weight:700;
    margin-bottom:8px;
    color:white;
}

.form-subtitle{
    font-size:14px;
    color:#94a3b8;
    margin-bottom:30px;
}

.form-grid{
    display:flex;
    flex-direction:column;
    gap:20px;
}

.input-box{
    display:flex;
    flex-direction:column;
}

.input-box label{
    margin-bottom:8px;
    font-size:14px;
    font-weight:600;
    color:#e2e8f0;
}

.input{
    width:100%;
    padding:14px 16px;
    border:none;
    border-radius:12px;
    background:#1e293b;
    color:white;
    font-size:14px;
    transition:.2s;
}

.input:focus{
    outline:none;
    background:#273549;
}

.input::placeholder{
    color:#94a3b8;
}

.file-input{
    background:#1e293b;
    padding:12px;
    border-radius:12px;
    color:#cbd5e1;
}

.preview-img{
    width:140px;
    height:140px;
    object-fit:cover;
    border-radius:14px;
    margin-top:15px;
    border:1px solid rgba(255,255,255,.08);
}

.btn-area{
    margin-top:30px;
    display:flex;
    justify-content:flex-end;
    gap:12px;
}

.btn-kembali{
    padding:14px 20px;
    border-radius:12px;
    background:#334155;
    color:white;
    text-decoration:none;
    font-size:14px;
    font-weight:600;
}

.btn-update{
    padding:14px 22px;
    border:none;
    border-radius:12px;
    background:#2563eb;
    color:white;
    cursor:pointer;
    font-size:14px;
    font-weight:700;
    transition:.2s;
}

.btn-update:hover{
    background:#1d4ed8;
}

.info-box{
    background:rgba(37,99,235,.12);
    border:1px solid rgba(37,99,235,.2);
    padding:14px 16px;
    border-radius:12px;
    margin-bottom:25px;
    font-size:13px;
    color:#93c5fd;
}

</style>

<div class="form-wrapper">
<div class="form-container">

    <div class="form-title">
        Edit Alat
    </div>

    <div class="form-subtitle">
        Ubah data alat jurusan
    </div>

    <div class="info-box">
        Pastikan data alat yang diubah sudah benar sebelum disimpan.
    </div>

    <form method="POST"
          enctype="multipart/form-data">

        <div class="form-grid">
            <div class="input-box">
                <label>Kode Barang</label>

                <input type="text"
                       name="kode_barang"
                       class="input"
                       value="<?= htmlspecialchars($data['kode_barang']) ?>"
                       required>
            </div>

            <div class="input-box">
                <label>Nama Barang</label>

                <input type="text"
                       name="nama_barang"
                       class="input"
                       value="<?= htmlspecialchars($data['nama_barang']) ?>"
                       required>
            </div>

            <div class="input-box">
                <label>Jurusan</label>

                <select name="id_jurusan"
                        class="input"
                        required>

                    <?php while($j = mysqli_fetch_assoc($query_jurusan)) : ?>
                    <option value="<?= $j['id_jurusan'] ?>"

                    <?php
                    if($j['id_jurusan'] == $data['id_jurusan']){
                        echo "selected";
                    }
                    ?>
                    >
                    <?= htmlspecialchars($j['nama_jurusan']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="input-box">
                <label>Jumlah Stok</label>

                <input type="number"
                       name="stok_total"
                       class="input"
                       value="<?= $data['stok_total'] ?>"
                       required>
            </div>

            <div class="input-box">
                <label>Kondisi Barang</label>

                <select name="kondisi"
                        class="input">

                    <option value="baik"
                    <?php
                    if($data['kondisi'] == 'baik'){
                        echo "selected";
                    }
                    ?>

                    >
                    Baik
                    </option>

                    <option value="rusak"
                    <?php
                    if($data['kondisi'] == 'rusak'){
                        echo "selected";
                    }
                    ?>

                    >
                    Rusak
                    </option>
                </select>
            </div>

            <div class="input-box">
                <label>Foto Barang</label>
                <input type="file"
                       name="foto"
                       class="input file-input">

                <?php if($data['foto'] != ''): ?>
                    <img src="uploads/<?= $data['foto'] ?>"
                         class="preview-img">
                <?php endif; ?>
            </div>
        </div>

        <div class="btn-area">
            <a href="data_alat_adminjurusan.php"
               class="btn-kembali">
               Kembali
            </a>

            <button type="submit"
                    name="update"
                    class="btn-update">
                Update Data
            </button>
        </div>
    </form>
</div>
</div>
</div>
</div>
</body>
</html>