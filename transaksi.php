<?php
// transaksi.php
$pageTitle = 'Catatan Transaksi';
require_once 'includes/header.php';

$db = getDBConnection();
$message = '';
$status = 'info';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check session messages
if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg'];
    $status = $_SESSION['msg_status'] ?? 'info';
    unset($_SESSION['msg']);
    unset($_SESSION['msg_status']);
}

// --- FETCH METADATA FOR SELECT OPTIONS ---
$accounts = $db->query("SELECT * FROM akun ORDER BY nama_akun ASC")->fetchAll();
$categories = $db->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll();

// --- BUILD FILTER QUERY ---
$where = [];
$params = [];

$filter_tgl_mulai = $_GET['tgl_mulai'] ?? '';
$filter_tgl_selesai = $_GET['tgl_selesai'] ?? '';
$filter_akun_id = $_GET['akun_id'] ?? '';
$filter_kategori_id = $_GET['kategori_id'] ?? '';
$filter_jenis = $_GET['jenis'] ?? '';
$filter_search = $_GET['search'] ?? '';

if ($filter_tgl_mulai !== '') {
    $where[] = "t.tanggal >= ?";
    $params[] = $filter_tgl_mulai;
}
if ($filter_tgl_selesai !== '') {
    $where[] = "t.tanggal <= ?";
    $params[] = $filter_tgl_selesai;
}
if ($filter_akun_id !== '') {
    $where[] = "t.akun_id = ?";
    $params[] = (int)$filter_akun_id;
}
if ($filter_kategori_id !== '') {
    $where[] = "t.kategori_id = ?";
    $params[] = (int)$filter_kategori_id;
}
if ($filter_jenis !== '' && in_array($filter_jenis, ['masuk', 'keluar'])) {
    $where[] = "t.jenis = ?";
    $params[] = $filter_jenis;
}
if ($filter_search !== '') {
    $where[] = "t.keterangan LIKE ?";
    $params[] = '%' . $filter_search . '%';
}

$where_clause = "";
if (count($where) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $where);
}

// Fetch filtered transactions
$query = "
    SELECT t.id, t.tanggal, t.jenis, t.nominal, t.keterangan,
           a.nama_akun, k.nama_kategori, u.nama as nama_user
    FROM transaksi t
    JOIN akun a ON t.akun_id = a.id
    JOIN kategori k ON t.kategori_id = k.id
    JOIN user u ON t.user_id = u.id
    $where_clause
    ORDER BY t.tanggal DESC, t.id DESC
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// --- COMPUTE SUMMARY FOR FILTERED SUBSET ---
$subset_masuk = 0;
$subset_keluar = 0;
foreach ($transactions as $t) {
    if ($t['jenis'] === 'masuk') {
        $subset_masuk += (float)$t['nominal'];
    } else {
        $subset_keluar += (float)$t['nominal'];
    }
}
$subset_selisih = $subset_masuk - $subset_keluar;
?>

<div class="page-header">
    <div>
        <h2>Catatan Transaksi Keuangan</h2>
        <p>Lacak detail keluar masuk kas, lakukan pencarian data, serta filter laporan periodik.</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openModal('addTransaksiModal')">
            <i class="fa-solid fa-plus"></i> Catat Transaksi
        </button>
    </div>
</div>

<?php if ($message != ''): ?>
    <div class="alert alert-<?= $status; ?>">
        <i class="fa-solid <?= $status === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?= $message; ?></span>
    </div>
<?php endif; ?>

<!-- Filter Panel -->
<div class="card filter-card">
    <div class="section-title filter-title">
        <span><i class="fa-solid fa-filter"></i> Filter & Cari Transaksi</span>
        <?php if (!empty($_GET)): ?>
            <a href="transaksi.php" class="clear-filter-link"><i class="fa-solid fa-xmark"></i> Bersihkan Filter</a>
        <?php endif; ?>
    </div>
    <form method="GET" action="">
        <div class="filter-row">
            <div class="filter-group">
                <label for="tgl_mulai">Dari Tanggal</label>
                <input type="date" id="tgl_mulai" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($filter_tgl_mulai) ?>">
            </div>
            
            <div class="filter-group">
                <label for="tgl_selesai">Sampai Tanggal</label>
                <input type="date" id="tgl_selesai" name="tgl_selesai" class="form-control" value="<?= htmlspecialchars($filter_tgl_selesai) ?>">
            </div>

            <div class="filter-group">
                <label for="filter_jenis">Jenis</label>
                <select id="filter_jenis" name="jenis" class="form-control">
                    <option value="">Semua</option>
                    <option value="masuk" <?= $filter_jenis === 'masuk' ? 'selected' : '' ?>>Pemasukan</option>
                    <option value="keluar" <?= $filter_jenis === 'keluar' ? 'selected' : '' ?>>Pengeluaran</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="filter_akun">Akun</label>
                <select id="filter_akun" name="akun_id" class="form-control">
                    <option value="">Semua Rekening</option>
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>" <?= $filter_akun_id == $acc['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($acc['nama_akun']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="filter_kategori">Kategori</label>
                <select id="filter_kategori" name="kategori_id" class="form-control">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filter_kategori_id == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nama_kategori']) ?> (<?= $cat['jenis'] === 'masuk' ? 'Masuk' : 'Keluar' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group filter-search-group">
                <label for="search">Keterangan</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="Cari deskripsi catatan..." value="<?= htmlspecialchars($filter_search) ?>">
            </div>

            <div>
                <button type="submit" class="btn btn-primary btn-filter-submit">
                    <i class="fa-solid fa-magnifying-glass"></i> Terapkan
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Filter Summary Strip -->
<div class="filter-strip">
    <div class="filter-strip-card masuk">
        <span class="filter-strip-label">Total Pemasukan Filter</span>
        <span class="filter-strip-value text-success"><?= formatRupiah($subset_masuk) ?></span>
    </div>
    <div class="filter-strip-card keluar">
        <span class="filter-strip-label">Total Pengeluaran Filter</span>
        <span class="filter-strip-value text-danger"><?= formatRupiah($subset_keluar) ?></span>
    </div>
    <div class="filter-strip-card selisih">
        <span class="filter-strip-label">Selisih Hasil Filter</span>
        <span class="filter-strip-value <?= $subset_selisih >= 0 ? 'text-success' : 'text-danger-light' ?>"><?= ($subset_selisih >= 0 ? '+' : '') . formatRupiah($subset_selisih) ?></span>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
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
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $trans): ?>
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
                        <td class="text-right">
                            <a href="transaksi_aksi.php?delete=<?= $trans['id']; ?>" 
                               class="btn btn-danger btn-secondary-xs" 
                               onclick="return confirm('Apakah Anda yakin ingin menghapus catatan transaksi ini?')">
                                <i class="fa-solid fa-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="8" class="table-no-data">
                            <i class="fa-solid fa-receipt fa-3x table-no-data-icon"></i>
                            <p>Tidak ada transaksi yang cocok dengan kriteria pencarian/filter.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ADD TRANSACTION MODAL -->
<div id="addTransaksiModal" class="modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <h3>Catat Transaksi Keuangan</h3>
            <button class="modal-close" onclick="closeModal('addTransaksiModal')">&times;</button>
        </div>
        <form method="POST" action="transaksi_aksi.php">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="tanggal">Tanggal Transaksi</label>
                <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label for="jenis">Jenis Transaksi</label>
                <select id="jenis" name="jenis" class="form-control" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="masuk">Pemasukan (Uang Masuk)</option>
                    <option value="keluar">Pengeluaran (Uang Keluar)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="akun_id">Akun / Rekening Keuangan</label>
                <select id="akun_id" name="akun_id" class="form-control" required>
                    <option value="">-- Pilih Rekening --</option>
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['nama_akun']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="kategori_id">Kategori</label>
                <select id="kategori_id" name="kategori_id" class="form-control" required disabled>
                    <option value="">-- Pilih Jenis Transaksi Dulu --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" data-jenis="<?= $cat['jenis'] ?>" class="display-none">
                            <?= htmlspecialchars($cat['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="nominal">Nominal (Rupiah)</label>
                <input type="number" step="0.01" id="nominal" name="nominal" class="form-control" placeholder="Contoh: 50000" required min="0.01">
            </div>

            <div class="form-group">
                <label for="keterangan">Keterangan / Deskripsi</label>
                <textarea id="keterangan" name="keterangan" class="form-control" rows="3" placeholder="Masukkan deskripsi singkat catatan transaksi..."></textarea>
            </div>

            <div class="flex-end-gap">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addTransaksiModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const jenisSelect = document.getElementById('jenis');
    const kategoriSelect = document.getElementById('kategori_id');
    const kategoriOptions = Array.from(kategoriSelect.options);

    jenisSelect.addEventListener('change', function() {
        const selectedJenis = this.value;
        
        // Reset kategori selection
        kategoriSelect.value = '';
        
        if (selectedJenis === '') {
            kategoriSelect.disabled = true;
            kategoriSelect.options[0].textContent = '-- Pilih Jenis Transaksi Dulu --';
            return;
        }

        // Enable and filter options
        kategoriSelect.disabled = false;
        kategoriSelect.options[0].textContent = '-- Pilih Kategori --';
        
        kategoriOptions.forEach(option => {
            if (option.value === '') return;
            
            const optJenis = option.getAttribute('data-jenis');
            if (optJenis === selectedJenis) {
                option.classList.remove('display-none');
                option.disabled = false;
            } else {
                option.classList.add('display-none');
                option.disabled = true;
            }
        });
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>
