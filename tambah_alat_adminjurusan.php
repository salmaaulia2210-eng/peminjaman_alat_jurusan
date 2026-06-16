<?php
require 'koneksii.php';
include 'layout_adminn.php';

$id_jurusan_admin = $_SESSION['id_jurusan'];

$query_jurusan = mysqli_query($conn,
"SELECT * FROM jurusan
WHERE id_jurusan = '$id_jurusan_admin'
ORDER BY nama_jurusan ASC");

if(isset($_POST['simpan'])){
    $kode_barang = mysqli_real_escape_string($conn, $_POST['kode_barang']);
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $id_jurusan  = mysqli_real_escape_string($conn, $_POST['id_jurusan']);
    $stok_total  = mysqli_real_escape_string($conn, $_POST['stok_total']);
    $kondisi     = mysqli_real_escape_string($conn, $_POST['kondisi']);
    $foto = '';

    if($_FILES['foto']['name'] != ''){
        $nama_file = time() . '_' . $_FILES['foto']['name'];
        $tmp       = $_FILES['foto']['tmp_name'];
        move_uploaded_file($tmp, 'uploads/' . $nama_file);
        $foto = $nama_file;
    }

    mysqli_query($conn,

    "INSERT INTO barang
    (
        kode_barang,
        nama_barang,
        id_jurusan,
        stok_total,
        stok_tersedia,
        kondisi,
        foto
    )

    VALUES
    (
        '$kode_barang',
        '$nama_barang',
        '$id_jurusan',
        '$stok_total',
        '$stok_total',
        '$kondisi',
        '$foto'
    )"

    );

    echo "
    <script>
    alert('Data alat berhasil ditambahkan');
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

select.input{
    cursor:pointer;
}

.file-input{
    background:#1e293b;
    padding:12px;
    border-radius:12px;
    color:#cbd5e1;
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

.btn-simpan{
    padding:14px 22px;
    border:none;
    border-radius:12px;
    background:#22c55e;
    color:white;
    cursor:pointer;
    font-size:14px;
    font-weight:700;
    transition:.2s;
}

.btn-simpan:hover{
    background:#16a34a;
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

@media(max-width:800px){

    .form-grid{
        grid-template-columns:1fr;
    }

}

</style>

<div class="form-wrapper">
<div class="form-container">

    <div class="form-title">
        Tambah Alat
    </div>

    <div class="form-subtitle">
        Tambahkan data alat baru untuk jurusan anda
    </div>

    <div class="info-box">
        Data alat akan otomatis masuk ke jurusan admin yang sedang login.
    </div>

    <form method="POST"
          enctype="multipart/form-data">

        <div class="form-grid">
            <div class="input-box">
                <label>Kode Barang</label>

                <input type="text"
                       name="kode_barang"
                       class="input"
                       placeholder="Contoh : BRG-RPL-01"
                       required>
            </div>

            <div class="input-box">
                <label>Nama Barang</label>

                <input type="text"
                       name="nama_barang"
                       class="input"
                       placeholder="Masukkan nama barang"
                       required>
            </div>

            <div class="input-box">
                <label>Jurusan</label>

                <select name="id_jurusan"
                        class="input"
                        required>

                    <?php while($j = mysqli_fetch_assoc($query_jurusan)) : ?>

                    <option value="<?= $j['id_jurusan'] ?>">
                        <?= $j['nama_jurusan'] ?>
                    </option>

                    <?php endwhile; ?>
                </select>
            </div>

            <div class="input-box">
                <label>Jumlah Stok</label>

                <input type="number"
                       name="stok_total"
                       class="input"
                       placeholder="Masukkan jumlah stok"
                       required>
            </div>

            <div class="input-box">
                <label>Kondisi Barang</label>
                <select name="kondisi"
                        class="input">

                    <option value="baik">
                        Baik
                    </option>

                    <option value="rusak">
                        Rusak
                    </option>
                </select>
            </div>

            <div class="input-box">
                <label>Foto Barang</label>

                <input type="file"
                       name="foto"
                       class="input file-input">
            </div>
        </div>

        <div class="btn-area">
            <a href="data_alat_adminjurusan.php"
               class="btn-kembali">
               Kembali
            </a>

            <button type="submit"
                    name="simpan"
                    class="btn-simpan">
                Simpan Data
            </button>
        </div>
    </form>
</div>
</div>
</div>
</div>
</body>
</html>