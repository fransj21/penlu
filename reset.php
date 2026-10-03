<?php
require 'config.php';

try {
    // Menghapus semua isi tabel dan mereset ID kembali ke 1
    $pdo->exec("TRUNCATE TABLE transactions");
    
    // Redirect ke halaman utama dengan parameter status sukses
    header("Location: index.php?status=reset_success");
    exit;
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>