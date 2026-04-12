<?php
session_start();
unset($_SESSION['penjual_id']);
unset($_SESSION['penjual_nama_toko']);
header("Location: pasar.php");
exit();
?>