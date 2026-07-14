<?php
// dashboard.php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

$db = getDBConnection();

// --- 1. METRICS CALCULATION ---
// Get sum of initial balances from all accounts
$stmt = $db->query("SELECT SUM(saldo_awal) FROM akun");
$total_saldo_awal = (float)$stmt->fetchColumn();

// Get total income (pemasukan)
$stmt = $db->query("SELECT SUM(nominal) FROM transaksi WHERE jenis = 'masuk'");
$total_masuk = (float)$stmt->fetchColumn();

// Get total expenses (pengeluaran)
$stmt = $db->query("SELECT SUM(nominal) FROM transaksi WHERE jenis = 'keluar'");
$total_keluar = (float)$stmt->fetchColumn();

// Calculate differences
$selisih = $total_masuk - $total_keluar;
$saldo_saat_ini = $total_saldo_awal + $selisih;


// --- 2. ACCOUNTS LIST WITH ACTUAL BALANCE ---
// Actual Balance = saldo_awal + (incomes for this account) - (expenses for this account)
$accounts_query = "
    SELECT a.id, a.nama_akun, a.saldo_awal,
           COALESCE(SUM(CASE WHEN t.jenis = 'masuk' THEN t.nominal ELSE 0 END), 0) as total_masuk,
           COALESCE(SUM(CASE WHEN t.jenis = 'keluar' THEN t.nominal ELSE 0 END), 0) as total_keluar
    FROM akun a
    LEFT JOIN transaksi t ON a.id = t.akun_id
    GROUP BY a.id, a.nama_akun, a.saldo_awal
    ORDER BY a.nama_akun ASC
";
$accounts = $db->query($accounts_query)->fetchAll();


// --- 3. RECENT TRANSACTIONS (LIMIT 5) ---
$recent_transactions_query = "
    SELECT t.id, t.tanggal, t.jenis, t.nominal, t.keterangan,
           a.nama_akun, k.nama_kategori, u.nama as nama_user
    FROM transaksi t
    JOIN akun a ON t.akun_id = a.id
    JOIN kategori k ON t.kategori_id = k.id
    JOIN user u ON t.user_id = u.id
    ORDER BY t.tanggal DESC, t.id DESC
    LIMIT 5
";
$recent_transactions = $db->query($recent_transactions_query)->fetchAll();


// --- 4. CHART DATA PREPARATION (Last 6 Months) ---
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-$i months"));
}

$chartData = [];
foreach ($months as $m) {
    $chartData[$m] = ['masuk' => 0, 'keluar' => 0];
}

$chart_query = "
    SELECT DATE_FORMAT(tanggal, '%Y-%m') as bulan, jenis, SUM(nominal) as total
    FROM transaksi
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(tanggal, '%Y-%m'), jenis
";
$chart_result = $db->query($chart_query)->fetchAll();

foreach ($chart_result as $row) {
    $b = $row['bulan'];
    if (isset($chartData[$b])) {
        $chartData[$b][$row['jenis']] = (float)$row['total'];
    }
}

$labels = [];
$incomeValues = [];
$expenseValues = [];

foreach ($chartData as $m => $vals) {
    // Format to readable Indonesian Month, e.g. "Jul 2026"
    $labels[] = date('M Y', strtotime($m . "-01"));
    $incomeValues[] = $vals['masuk'];
    $expenseValues[] = $vals['keluar'];
}
?>

<div class="page-header">
    <div>
        <h2>Ringkasan Keuangan</h2>
        <p>Pantau arus kas masuk, keluar, dan sisa saldo Anda secara real-time.</p>
    </div>
    <div>
        <a href="transaksi.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Catat Transaksi
        </a>
    </div>
</div>

<!-- Stats Metric Row -->
<div class="stats-grid">
    <div class="card">
        <div class="card-icon icon-blue">
            <i class="fa-solid fa-wallet fa-lg"></i>
        </div>
        <div class="card-title">Saldo Saat Ini</div>
        <div class="card-value"><?= formatRupiah($saldo_saat_ini) ?></div>
    </div>
    
    <div class="card">
        <div class="card-icon icon-emerald">
            <i class="fa-solid fa-arrow-down fa-lg"></i>
        </div>
        <div class="card-title">Total Pemasukan</div>
        <div class="card-value"><?= formatRupiah($total_masuk) ?></div>
    </div>

    <div class="card">
        <div class="card-icon icon-rose">
            <i class="fa-solid fa-arrow-up fa-lg"></i>
        </div>
        <div class="card-title">Total Pengeluaran</div>
        <div class="card-value"><?= formatRupiah($total_keluar) ?></div>
    </div>

    <div class="card">
        <div class="card-icon icon-yellow">
            <i class="fa-solid fa-scale-balanced fa-lg"></i>
        </div>
        <div class="card-title">Selisih Arus Kas</div>
        <div class="card-value <?= $selisih >= 0 ? 'text-success' : 'text-danger' ?>">
            <?= ($selisih >= 0 ? '+' : '') . formatRupiah($selisih) ?>
        </div>
    </div>
</div>

<div class="content-grid">
    <!-- Left Column: Chart -->
    <div class="card">
        <div class="section-title">
            <span>Tren Arus Kas (6 Bulan Terakhir)</span>
        </div>
        <div class="chart-container">
            <canvas id="cashFlowChart"></canvas>
        </div>
    </div>

    <!-- Right Column: Account Balances -->
    <div class="card">
        <div class="section-title">
            <span>Rekening / Akun</span>
            <a href="akun.php" class="btn btn-secondary btn-secondary-xs">Kelola</a>
        </div>
        <div class="account-list">
            <?php foreach ($accounts as $acc): ?>
                <?php 
                $acc_balance = (float)$acc['saldo_awal'] + (float)$acc['total_masuk'] - (float)$acc['total_keluar'];
                ?>
                <div class="account-item">
                    <div>
                        <div class="account-name"><?= htmlspecialchars($acc['nama_akun']) ?></div>
                        <div class="account-starting">Saldo Awal: <?= formatRupiah($acc['saldo_awal']) ?></div>
                    </div>
                    <div class="account-balance"><?= formatRupiah($acc_balance) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($accounts)): ?>
                <div class="no-data-msg">
                    Belum ada rekening terdaftar.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Transactions Table -->
<div class="card">
    <div class="section-title">
        <span>Transaksi Terakhir</span>
        <a href="transaksi.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Akun/Rekening</th>
                    <th>Kategori</th>
                    <th>Jenis</th>
                    <th>Nominal</th>
                    <th>Keterangan</th>
                    <th>Pencatat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_transactions as $trans): ?>
                    <tr>
                        <td><?= date('d-m-Y', strtotime($trans['tanggal'])) ?></td>
                        <td>
                            <i class="fa-solid fa-university text-muted icon-spaced"></i>
                            <?= htmlspecialchars($trans['nama_akun']) ?>
                        </td>
                        <td>
                            <i class="fa-solid fa-tag text-muted icon-spaced"></i>
                            <?= htmlspecialchars($trans['nama_kategori']) ?>
                        </td>
                        <td>
                            <?php if ($trans['jenis'] === 'masuk'): ?>
                                <span class="badge badge-success"><i class="fa-solid fa-arrow-trend-up icon-badge"></i> Masuk</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="fa-solid fa-arrow-trend-down icon-badge"></i> Keluar</span>
                            <?php endif; ?>
                        </td>
                        <td class="font-semibold <?= $trans['jenis'] === 'masuk' ? 'text-success' : 'text-danger-light' ?>">
                            <?= ($trans['jenis'] === 'masuk' ? '+' : '-') . formatRupiah($trans['nominal']) ?>
                        </td>
                        <td><span class="transaction-desc"><?= htmlspecialchars($trans['keterangan'] ?: '-') ?></span></td>
                        <td>
                            <span class="transaction-user-badge">
                                <?= htmlspecialchars($trans['nama_user']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recent_transactions)): ?>
                    <tr>
                        <td colspan="7" class="table-no-data">
                            <i class="fa-solid fa-receipt fa-2x table-no-data-icon"></i>
                            Belum ada catatan transaksi masuk atau keluar.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js Library via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('cashFlowChart').getContext('2d');
        
        const labels = <?= json_encode($labels) ?>;
        const incomeData = <?= json_encode($incomeValues) ?>;
        const expenseData = <?= json_encode($expenseValues) ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: incomeData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.05)',
                        borderWidth: 3,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: 'rgba(255, 255, 255, 0.5)',
                        pointHoverRadius: 6,
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Pengeluaran',
                        data: expenseData,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.05)',
                        borderWidth: 3,
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: 'rgba(255, 255, 255, 0.5)',
                        pointHoverRadius: 6,
                        tension: 0.35,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#e5e7eb',
                            font: {
                                family: 'Outfit',
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderWidth: 1,
                        borderColor: 'rgba(255,255,255,0.1)',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { family: 'Outfit', weight: 'bold' },
                        bodyFont: { family: 'Outfit' },
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255,255,255,0.03)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#9ca3af',
                            font: { family: 'Outfit' }
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(255,255,255,0.03)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#9ca3af',
                            font: { family: 'Outfit' },
                            callback: function(value) {
                                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value).replace(/,00$/, '');
                            }
                        }
                    }
                }
            }
        });
    });
</script>

<?php
require_once 'includes/footer.php';
?>
