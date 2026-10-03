<?php
require 'config.php';

// Set header agar browser mengunduhnya sebagai Excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_PENLU.xls");

$stmtData = $pdo->query("SELECT * FROM transactions ORDER BY trans_date ASC");
$transactions = $stmtData->fetchAll(PDO::FETCH_ASSOC);
?>
<table border="1">
    <tr>
        <th>Tanggal</th>
        <th>Jenis Transaksi</th>
        <th>Keterangan</th>
        <th>Nominal</th>
    </tr>
    <?php foreach ($transactions as $row): ?>
    <tr>
        <td><?= $row['trans_date'] ?></td>
        <td><?= $row['trans_type'] ?></td>
        <td><?= htmlspecialchars($row['description']) ?></td>
        <td><?= $row['amount'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>