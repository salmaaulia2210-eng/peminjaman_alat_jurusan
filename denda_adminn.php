<?php
require 'koneksii.php';
include 'layout_adminn.php';

$query_jurusan = mysqli_query($conn,
"SELECT * FROM jurusan ORDER BY nama_jurusan ASC");

if(isset($_POST['simpan'])){

    $kode_barang = $_POST['kode_barang'];
    $nama_barang = $_POST['nama_barang'];
    $id_jurusan  = $_POST['id_jurusan'];
    $stok_total  = $_POST['stok_total'];
    $kondisi     = $_POST['kondisi'];
    $foto        = $_FILES['foto']['name'];
    $tmp         = $_FILES['foto']['tmp_name'];

    move_uploaded_file($tmp, 'uploads/' . $foto);

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

    window.location='data_alat_adminn.php';

    </script>

    ";
}
?>

<style>

.content{
    padding:30px;
}

.form-container{
    width:70%;
    margin:auto;
    background:#1e293b;
    padding:35px;
    border-radius:18px;
}

.page-title{
    font-size:35px;
    font-weight:bold;
    margin-bottom:30px;
}

.form-group{
    margin-bottom:22px;
}

.form-group label{
    display:block;
    margin-bottom:8px;
    font-weight:600;
}

.form-group input,
.form-group select{
    width:100%;
    padding:14px;
    border:none;
    border-radius:10px;
    background:#334155;
    color:white;
    box-sizing:border-box;
}

.btn-simpan{
    background:#22c55e;
    color:white;
    border:none;
    padding:14px 25px;
    border-radius:10px;
    cursor:pointer;
    font-weight:bold;
}

.judul{
    font-size:28px;
    margin-bottom:20px;
    font-weight:bold;
}

.form-box{
    background:#1e293b;
    padding:25px;
    border-radius:12px;
    width:100%;
}

.input-box{
    margin-bottom:18px;
}

.input-box label{
    display:block;
    margin-bottom:8px;
    font-size:14px;
}

.input{
    width:100%;
    padding:12px;
    border:none;
    border-radius:8px;
    background:#334155;
    color:white;
    font-size:14px;
}

.input:focus{
    outline:none;
}

.btn{
    background:#22c55e;
    color:white;
    border:none;
    padding:12px 18px;
    border-radius:8px;
    cursor:pointer;
    font-size:14px;
}

.btn:hover{
    background:#16a34a;
}

@media(max-width:800px){

.form-box{
    width:100%;
}

}

</style>

<div class="content">
<div class="form-container">

<h1 class="judul">
Tambah Alat
</h1>

<div class="form-box">
<form method="POST" enctype="multipart/form-data">

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

<option value="">
Pilih Jurusan
</option>

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
<label>Foto barang</label>

<input type="file"
       name="foto"
       class="input">
</div>

<button type="submit"
        name="simpan"
        class="btn">
Simpan
</button>
</form>
</div>
</div>
</div>
</body>
</html>