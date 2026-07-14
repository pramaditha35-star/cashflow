<?php
// akun.php
$pageTitle = 'Kelola Rekening / Akun';
require_once 'includes/header.php';

$db = getDBConnection();
$message = '';
$status = 'info';

// Start session helper variables if session is active
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

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ADD ACCOUNT
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $nama_akun = trim($_POST['nama_akun'] ?? '');
        $saldo_awal = (float)($_POST['saldo_awal'] ?? 0);

        if (empty($nama_akun)) {
            $_SESSION['msg'] = 'Nama akun wajib diisi!';
            $_SESSION['msg_status'] = 'danger';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO akun (nama_akun, saldo_awal) VALUES (?, ?)");
                $stmt->execute([$nama_akun, $saldo_awal]);
                $_SESSION['msg'] = 'Akun berhasil ditambahkan!';
                $_SESSION['msg_status'] = 'success';
            } catch (\PDOException $e) {
                $_SESSION['msg'] = 'Gagal menambahkan akun: ' . $e->getMessage();
                $_SESSION['msg_status'] = 'danger';
            }
        }
        header("Location: akun.php");
        exit;
    }

    // 2. EDIT ACCOUNT
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama_akun = trim($_POST['nama_akun'] ?? '');
        $saldo_awal = (float)($_POST['saldo_awal'] ?? 0);

        if ($id <= 0 || empty($nama_akun)) {
            $_SESSION['msg'] = 'Nama akun wajib diisi!';
            $_SESSION['msg_status'] = 'danger';
        } else {
            try {
                $stmt = $db->prepare("UPDATE akun SET nama_akun = ?, saldo_awal = ? WHERE id = ?");
                $stmt->execute([$nama_akun, $saldo_awal, $id]);
                $_SESSION['msg'] = 'Data akun berhasil diperbarui!';
                $_SESSION['msg_status'] = 'success';
            } catch (\PDOException $e) {
                $_SESSION['msg'] = 'Gagal memperbarui akun: ' . $e->getMessage();
                $_SESSION['msg_status'] = 'danger';
            }
        }
        header("Location: akun.php");
        exit;
    }
}

// --- HANDLE DELETE ACTION ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        try {
            $stmt = $db->prepare("DELETE FROM akun WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['msg'] = 'Akun berhasil dihapus!';
            $_SESSION['msg_status'] = 'success';
        } catch (\PDOException $e) {
            $_SESSION['msg'] = 'Gagal menghapus akun: ' . $e->getMessage();
            $_SESSION['msg_status'] = 'danger';
        }
    }
    header("Location: akun.php");
    exit;
}

// --- FETCH ACCOUNTS ---
// Compute current balance of each account
$query = "
    SELECT a.id, a.nama_akun, a.saldo_awal,
           COALESCE(SUM(CASE WHEN t.jenis = 'masuk' THEN t.nominal ELSE 0 END), 0) as total_masuk,
           COALESCE(SUM(CASE WHEN t.jenis = 'keluar' THEN t.nominal ELSE 0 END), 0) as total_keluar
    FROM akun a
    LEFT JOIN transaksi t ON a.id = t.akun_id
    GROUP BY a.id, a.nama_akun, a.saldo_awal
    ORDER BY a.nama_akun ASC
";
$accounts = $db->query($query)->fetchAll();
?>

<div class="page-header">
    <div>
        <h2>Rekening / Akun Keuangan</h2>
        <p>Kelola wadah keuangan Anda seperti Kas Utama, Bank BCA, Bank Mandiri, Dompet Digital, dll.</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openModal('addAccountModal')">
            <i class="fa-solid fa-plus"></i> Tambah Akun
        </button>
    </div>
</div>

<?php if ($message != ''): ?>
    <div class="alert alert-<?= $status; ?>">
        <i class="fa-solid <?= $status === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?= $message; ?></span>
    </div>
<?php endif; ?>

<!-- Accounts grid list -->
<div class="stats-grid">
    <?php foreach ($accounts as $acc): ?>
        <?php 
        $acc_balance = (float)$acc['saldo_awal'] + (float)$acc['total_masuk'] - (float)$acc['total_keluar'];
        ?>
        <div class="card account-card-large">
            <div>
                <div class="flex-between-start">
                    <div class="card-icon icon-blue">
                        <i class="fa-solid fa-building-columns fa-lg"></i>
                    </div>
                    <div class="flex-gap-8">
                        <button class="btn btn-secondary btn-secondary-xs" 
                                onclick="editAccount(<?= $acc['id']; ?>, '<?= htmlspecialchars($acc['nama_akun'], ENT_QUOTES); ?>', <?= $acc['saldo_awal']; ?>)">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <a href="akun.php?delete=<?= $acc['id']; ?>" 
                           class="btn btn-danger btn-secondary-xs" 
                           onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini? Menghapus akun akan menghapus semua transaksi terkait!')">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </div>
                </div>
                <div class="account-card-title">
                    <?= htmlspecialchars($acc['nama_akun']) ?>
                </div>
                <div class="account-card-starting">
                    Saldo Awal: <?= formatRupiah($acc['saldo_awal']) ?>
                </div>
            </div>
            
            <div class="account-card-footer">
                <span class="account-card-label">Saldo Saat Ini</span>
                <span class="account-card-balance"><?= formatRupiah($acc_balance) ?></span>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($accounts)): ?>
        <div class="card no-accounts-msg">
            <i class="fa-solid fa-folder-open fa-3x table-no-data-icon"></i>
            <p class="text-muted">Belum ada rekening/akun yang ditambahkan. Silakan klik tombol Tambah Akun di kanan atas.</p>
        </div>
    <?php endif; ?>
</div>


<!-- 1. ADD ACCOUNT MODAL -->
<div id="addAccountModal" class="modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <h3>Tambah Akun Baru</h3>
            <button class="modal-close" onclick="closeModal('addAccountModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="nama_akun">Nama Akun / Rekening</label>
                <input type="text" id="nama_akun" name="nama_akun" class="form-control" placeholder="Contoh: Bank BCA, Kas Toko, dll" required autocomplete="off">
            </div>
            <div class="form-group">
                <label for="saldo_awal">Saldo Awal</label>
                <input type="number" step="0.01" id="saldo_awal" name="saldo_awal" class="form-control" placeholder="0" required min="0">
            </div>
            <div class="flex-end-gap">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addAccountModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. EDIT ACCOUNT MODAL -->
<div id="editAccountModal" class="modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <h3>Ubah Data Akun</h3>
            <button class="modal-close" onclick="closeModal('editAccountModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_id" name="id">
            <div class="form-group">
                <label for="edit_nama_akun">Nama Akun / Rekening</label>
                <input type="text" id="edit_nama_akun" name="nama_akun" class="form-control" required autocomplete="off">
            </div>
            <div class="form-group">
                <label for="edit_saldo_awal">Saldo Awal</label>
                <input type="number" step="0.01" id="edit_saldo_awal" name="saldo_awal" class="form-control" required min="0">
            </div>
            <div class="flex-end-gap">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editAccountModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editAccount(id, nama, saldo) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama_akun').value = nama;
    document.getElementById('edit_saldo_awal').value = saldo;
    openModal('editAccountModal');
}
</script>

<?php
require_once 'includes/footer.php';
?>
