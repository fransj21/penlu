<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['trans_date'];
    $type = $_POST['trans_type'];
    $amount = $_POST['amount'];
    $desc = $_POST['description'];

    // Insert ke database
    $stmt = $pdo->prepare("INSERT INTO transactions (trans_date, trans_type, amount, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$date, $type, $amount, $desc]);

    // Redirect kembali ke halaman utama
    header("Location: index.php");
    exit;
}
?>