<?php
// Memanggil Dompdf secara manual (tanpa Composer)
require 'dompdf/autoload.inc.php'; 
require 'config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Menghitung Total Keseluruhan Modal
$stmtTotal = $pdo->query("SELECT 
    SUM(CASE WHEN trans_type = 'Pemasukan' THEN amount ELSE 0 END) - 
    SUM(CASE WHEN trans_type = 'Pengeluaran' THEN amount ELSE 0 END) AS total_modal 
    FROM transactions");
$totalModal = $stmtTotal->fetchColumn() ?: 0;

// 2. Mengambil Data Transaksi (diurutkan dari yang terlama ke terbaru untuk laporan)
$stmtData = $pdo->query("SELECT * FROM transactions ORDER BY trans_date ASC, id ASC");
$transactions = $stmtData->fetchAll(PDO::FETCH_ASSOC);

function formatRupiah(float $angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// 3. Merakit Tampilan HTML untuk PDF (Desain Biru - Putih Mewah)
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; 
            color: #334155; 
            font-size: 14px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        .header h1 { 
            color: #1e3a8a; /* Biru Tua */
            margin: 0; 
            font-size: 28px;
            letter-spacing: 1px;
        }
        .header p { 
            color: #64748b; 
            margin: 5px 0 0 0; 
        }
        .total-box { 
            background-color: #1e40af; /* Biru elegan */
            color: #ffffff; 
            padding: 20px; 
            text-align: center; 
            border-radius: 8px; 
            margin-bottom: 30px;
        }
        .total-box p {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #bfdbfe;
        }
        .total-box h2 { 
            margin: 0; 
            font-size: 32px; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        th { 
            background-color: #2563eb; /* Biru cerah */
            color: #ffffff; 
            padding: 12px; 
            text-align: left; 
            font-size: 14px;
        }
        td { 
            padding: 12px; 
            border-bottom: 1px solid #e2e8f0; 
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .pemasukan { color: #16a34a; font-weight: bold; }
        .pengeluaran { color: #dc2626; font-weight: bold; }
        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PENLU</h1>
        <p>Laporan Pendapatan & Pengeluaran Toko</p>
    </div>
    
    <div class="total-box">
        <p>Total Keseluruhan Modal</p>
        <h2>' . formatRupiah((float)$totalModal) . '</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th width="15%">Tanggal</th>
                <th width="20%">Jenis</th>
                <th width="40%">Keterangan</th>
                <th width="25%">Nominal</th>
            </tr>
        </thead>
        <tbody>';

if (count($transactions) > 0) {
    foreach ($transactions as $row) {
        $class = ($row['trans_type'] == 'Pemasukan') ? 'pemasukan' : 'pengeluaran';
        $tanda = ($row['trans_type'] == 'Pemasukan') ? '+' : '-';
        
        $html .= '<tr>
                    <td>' . date('d/m/Y', strtotime($row['trans_date'])) . '</td>
                    <td>' . $row['trans_type'] . '</td>
                    <td>' . htmlspecialchars($row['description']) . '</td>
                    <td class="' . $class . '">' . $tanda . ' ' . formatRupiah((float)$row['amount']) . '</td>
                  </tr>';
    }
} else {
    $html .= '<tr><td colspan="4" style="text-align:center;">Belum ada data transaksi</td></tr>';
}

$html .= '
        </tbody>
    </table>
    
    <div class="footer">
        Dicetak pada: ' . date('d M Y, H:i') . ' WIB
    </div>
</body>
</html>';

// 4. Konfigurasi dan Render PDF
$options = new Options();
$options->set('isRemoteEnabled', true); 
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

// Setting ukuran kertas dan orientasi (A4, Potret)
$dompdf->setPaper('A4', 'portrait');

// Render file HTML ke PDF
$dompdf->render();

// 5. Output PDF (Download otomatis)
$dompdf->stream("Laporan_Keuangan_PENLU_" . date('Ymd') . ".pdf", array("Attachment" => true));
?>