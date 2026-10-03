<?php
require 'config.php';

// Menghitung Total Keseluruhan Modal
$stmtTotal = $pdo->query("SELECT 
    SUM(CASE WHEN trans_type = 'Pemasukan' THEN amount ELSE 0 END) - 
    SUM(CASE WHEN trans_type = 'Pengeluaran' THEN amount ELSE 0 END) AS total_modal 
    FROM transactions");
$totalModal = $stmtTotal->fetchColumn() ?: 0;

// Mengambil Data Transaksi
$stmtData = $pdo->query("SELECT * FROM transactions ORDER BY trans_date DESC, id DESC");
$transactions = $stmtData->fetchAll(PDO::FETCH_ASSOC);

$groupedData = [];
foreach ($transactions as $row) {
    $groupedData[$row['trans_date']][] = $row;
}

function formatRupiah(float $angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PENLU - Keuangan Toko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style> 
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; } 
        /* Kustomisasi font SweetAlert agar senada dengan web */
        .swal2-title, .swal2-html-container { font-family: 'Inter', sans-serif !important; }
    </style>
</head>
<body class="text-slate-800">

<div class="max-w-4xl mx-auto p-4 md:p-8">
    <!-- Header & Total Modal (Sudah Responsif untuk Mobile & PC) -->
    <div class="bg-gradient-to-r from-blue-900 to-blue-700 rounded-3xl shadow-2xl p-6 md:p-8 mb-8 text-white relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <h1 class="text-3xl font-bold tracking-wide mb-1">PENLU</h1>
                <p class="text-blue-200 text-sm mb-4 md:mb-6">Aplikasi Keuangan Toko</p>
                <p class="text-blue-100 text-sm">Total Keseluruhan Modal</p>
                <h2 class="text-4xl md:text-5xl font-bold mt-2 break-all"><?= formatRupiah((float)$totalModal) ?></h2>
            </div>
            
            <!-- Tombol Aksi -->
            <div class="flex flex-wrap gap-2 md:flex-col md:items-end w-full md:w-auto">
                <div class="flex gap-2">
                    <a href="export_excel.php" class="flex-1 text-center bg-white/20 hover:bg-white/30 backdrop-blur-md px-4 py-2 rounded-xl text-sm font-semibold transition">Export Excel</a>
                    <a href="export_pdf.php" class="flex-1 text-center bg-white/20 hover:bg-white/30 backdrop-blur-md px-4 py-2 rounded-xl text-sm font-semibold transition">Export PDF</a>
                </div>
                <!-- Tombol Reset memanggil JavaScript, bukan link langsung -->
                <button id="btnReset" class="w-full md:w-auto mt-1 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-semibold transition shadow-lg shadow-red-500/30">Reset Data</button>
            </div>
        </div>
        <!-- Ornamen dekorasi -->
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-50"></div>
    </div>

    <!-- Grid Responsif: 1 kolom di HP, 3 kolom di PC -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- Form Input -->
        <div class="md:col-span-1 bg-white p-6 rounded-2xl shadow-lg border border-slate-100 self-start">
            <h3 class="text-lg font-bold text-blue-900 mb-4">Tambah Data</h3>
            <form action="process.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm text-slate-500 mb-1">Tanggal</label>
                    <input type="date" name="trans_date" required class="w-full border border-slate-200 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm text-slate-500 mb-1">Jenis</label>
                    <select name="trans_type" required class="w-full border border-slate-200 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition bg-white">
                        <option value="Pemasukan">Pemasukan</option>
                        <option value="Pengeluaran">Pengeluaran</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-slate-500 mb-1">Nominal (Rp)</label>
                    <input type="number" name="amount" required class="w-full border border-slate-200 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm text-slate-500 mb-1">Keterangan</label>
                    <textarea name="description" rows="2" placeholder="Contoh: Beli stok, Gaji..." class="w-full border border-slate-200 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition"></textarea>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-blue-200">
                    Simpan Transaksi
                </button>
            </form>
        </div>

        <!-- Riwayat Harian -->
        <div class="md:col-span-2 space-y-6">
            <h3 class="text-xl font-bold text-slate-800">Riwayat Transaksi</h3>
            
            <?php if(empty($groupedData)): ?>
                <div class="bg-white p-6 rounded-2xl shadow-sm text-center text-slate-400">Belum ada data transaksi.</div>
            <?php endif; ?>

            <?php foreach ($groupedData as $date => $items): 
                $harianPemasukan = 0;
                $harianPengeluaran = 0;
            ?>
                <div class="bg-white rounded-2xl shadow-md border border-slate-100 overflow-hidden">
                    <div class="bg-slate-50 px-4 md:px-6 py-3 border-b border-slate-100 font-semibold text-slate-700">
                        Tanggal: <?= date('d M Y', strtotime($date)) ?>
                    </div>
                    <div class="p-4 md:p-6 space-y-3">
                        <?php foreach ($items as $item): 
                            if ($item['trans_type'] == 'Pemasukan') $harianPemasukan += $item['amount'];
                            else $harianPengeluaran += $item['amount'];
                        ?>
                        <div class="flex justify-between items-center pb-3 border-b border-slate-50 last:border-0 last:pb-0">
                            <div class="pr-4">
                                <p class="font-semibold <?= $item['trans_type'] == 'Pemasukan' ? 'text-green-600' : 'text-red-500' ?>">
                                    <?= $item['trans_type'] ?>
                                </p>
                                <p class="text-sm text-slate-500 break-words"><?= htmlspecialchars($item['description']) ?></p>
                            </div>
                            <div class="font-bold text-right <?= $item['trans_type'] == 'Pemasukan' ? 'text-green-600' : 'text-red-500' ?>">
                                <?= $item['trans_type'] == 'Pemasukan' ? '+' : '-' ?> <br class="block md:hidden"> <?= formatRupiah((float)$item['amount']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Sisa Saldo Hari Ini -->
                    <div class="bg-blue-50/50 px-4 md:px-6 py-4 border-t border-slate-100 flex flex-col md:flex-row justify-between md:items-center gap-1">
                        <span class="text-sm font-semibold text-blue-900">Sisa Saldo Hari Ini</span>
                        <span class="font-bold text-blue-700 text-lg">
                            <?= formatRupiah((float)($harianPemasukan - $harianPengeluaran)) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- SweetAlert2 Script -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // 1. Konfirmasi sebelum reset data
    document.getElementById('btnReset').addEventListener('click', function(e) {
        e.preventDefault(); // Mencegah pindah halaman langsung
        Swal.fire({
            title: 'Reset Semua Data?',
            text: "Data akan dihapus permanen dan tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444', // Merah Tailwind
            cancelButtonColor: '#94a3b8',  // Abu-abu
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'rounded-2xl'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Arahkan ke file reset.php jika klik "Ya"
                window.location.href = 'reset.php';
            }
        })
    });

    // 2. Notifikasi sukses setelah reset (Membaca parameter dari URL)
    <?php if(isset($_GET['status']) && $_GET['status'] == 'reset_success'): ?>
        Swal.fire({
            title: 'Berhasil!',
            text: 'Semua data transaksi telah dibersihkan.',
            icon: 'success',
            confirmButtonColor: '#2563eb', // Biru Tailwind
            customClass: {
                popup: 'rounded-2xl'
            }
        }).then(() => {
            // Bersihkan parameter ?status=reset_success dari URL agar tidak muncul terus saat di-refresh
            window.history.replaceState(null, null, window.location.pathname);
        });
    <?php endif; ?>
</script>

</body>
</html>