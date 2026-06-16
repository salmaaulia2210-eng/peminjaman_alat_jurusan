<?php
require 'koneksii.php';
$id = $_GET['id'];

mysqli_query($conn,
"DELETE FROM barang
WHERE id_barang='$id'");

echo "

<script>
alert('Data berhasil dihapus');
window.location='data_alat_adminjurusan.php';
</script>
";
?>