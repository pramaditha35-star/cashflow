<?php
// kategori.php
$pageTitle = 'Kelola Kategori';
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

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ADD CATEGORY
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        $jenis = $_POST['jenis'] ?? '';

        if (empty($nama_kategori) || !in_array($jenis, ['masuk', 'keluar'])) {
            $_SESSION['msg'] = 'Nama kategori dan jenis wajib valid!';
            $_SESSION['msg_status'] = 'danger';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO kategori (nama_kategori, jenis) VALUES (?, ?)");
                $stmt->execute([$nama_kategori, $jenis]);
                $_SESSION['msg'] = 'Kategori berhasil ditambahkan!';
                $_SESSION['msg_status'] = 'success';
            } catch (\PDOException $e) {
                $_SESSION['msg'] = 'Gagal menambahkan kategori: ' . $e->getMessage();
                $_SESSION['msg_status'] = 'danger';
            }
        }
        header("Location: kategori.php");
        exit;
    }

    // 2. EDIT CATEGORY
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        $jenis = $_POST['jenis'] ?? '';

        if ($id <= 0 || empty($nama_kategori) || !in_array($jenis, ['masuk', 'keluar'])) {
            $_SESSION['msg'] = 'Nama kategori dan jenis wajib valid!';
            $_SESSION['msg_status'] = 'danger';
        } else {
            try {
                $stmt = $db->prepare("UPDATE kategori SET nama_kategori = ?, jenis = ? WHERE id = ?");
                $stmt->execute([$nama_kategori, $jenis, $id]);
                $_SESSION['msg'] = 'Data kategori berhasil diperbarui!';
                $_SESSION['msg_status'] = 'success';
            } catch (\PDOException $e) {
                $_SESSION['msg'] = 'Gagal memperbarui kategori: ' . $e->getMessage();
                $_SESSION['msg_status'] = 'danger';
            }
        }
        header("Location: kategori.php");
        exit;
    }
}

// --- HANDLE DELETE ACTION ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        try {
            $stmt = $db->prepare("DELETE FROM kategori WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['msg'] = 'Kategori berhasil dihapus!';
            $_SESSION['msg_status'] = 'success';
        } catch (\PDOException $e) {
            $_SESSION['msg'] = 'Gagal menghapus kategori: ' . $e->getMessage();
            $_SESSION['msg_status'] = 'danger';
        }
    }
    header("Location: kategori.php");
    exit;
}

// --- FETCH CATEGORIES ---
// Income Categories
$stmt = $db->prepare("SELECT * FROM kategori WHERE jenis = 'masuk' ORDER BY nama_kategori ASC");
$stmt->execute();
$masuk_categories = $stmt->fetchAll();

// Expense Categories
$stmt = $db->prepare("SELECT * FROM kategori WHERE jenis = 'keluar' ORDER BY nama_kategori ASC");
$stmt->execute();
$keluar_categories = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h2>Kategori Transaksi</h2>
        <p>Definisikan klasifikasi pemasukan dan pengeluaran Anda agar analisis keuangan menjadi lebih teratur.</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openModal('addKategoriModal')">
            <i class="fa-solid fa-plus"></i> Tambah Kategori
        </button>
    </div>
</div>

<?php if ($message != ''): ?>
    <div class="alert alert-<?= $status; ?>">
        <i class="fa-solid <?= $status === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?= $message; ?></span>
    </div>
<?php endif; ?>

<div class="content-grid">
    <!-- Left Column: Income Categories -->
    <div class="card">
        <div class="section-title">
            <span class="text-success"><i class="fa-solid fa-arrow-trend-up icon-spaced"></i> Kategori Pemasukan</span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-70">Nama Kategori</th>
                        <th class="w-30 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($masuk_categories as $cat): ?>
                        <tr>
                            <td class="font-semibold"><?= htmlspecialchars($cat['nama_kategori']) ?></td>
                            <td class="text-right">
                                <div class="flex-gap-8 text-right">
                                    <button class="btn btn-secondary btn-secondary-xs" 
                                            onclick="editKategori(<?= $cat['id']; ?>, '<?= htmlspecialchars($cat['nama_kategori'], ENT_QUOTES); ?>', 'masuk')">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                    <a href="kategori.php?delete=<?= $cat['id']; ?>" 
                                       class="btn btn-danger btn-secondary-xs" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini? Semua transaksi dengan kategori ini juga akan terhapus!')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($masuk_categories)): ?>
                        <tr>
                            <td colspan="2" class="table-no-data">
                                Belum ada kategori pemasukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right Column: Expense Categories -->
    <div class="card">
        <div class="section-title">
            <span class="text-danger"><i class="fa-solid fa-arrow-trend-down icon-spaced"></i> Kategori Pengeluaran</span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-70">Nama Kategori</th>
                        <th class="w-30 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($keluar_categories as $cat): ?>
                        <tr>
                            <td class="font-semibold"><?= htmlspecialchars($cat['nama_kategori']) ?></td>
                            <td class="text-right">
                                <div class="flex-gap-8 text-right">
                                    <button class="btn btn-secondary btn-secondary-xs" 
                                            onclick="editKategori(<?= $cat['id']; ?>, '<?= htmlspecialchars($cat['nama_kategori'], ENT_QUOTES); ?>', 'keluar')">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                    <a href="kategori.php?delete=<?= $cat['id']; ?>" 
                                       class="btn btn-danger btn-secondary-xs" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini? Semua transaksi dengan kategori ini juga akan terhapus!')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($keluar_categories)): ?>
                        <tr>
                            <td colspan="2" class="table-no-data">
                                Belum ada kategori pengeluaran.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 1. ADD KATEGORI MODAL -->
<div id="addKategoriModal" class="modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <h3>Tambah Kategori Baru</h3>
            <button class="modal-close" onclick="closeModal('addKategoriModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="nama_kategori">Nama Kategori</label>
                <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" placeholder="Contoh: Transportasi, Bonus Akhir Tahun" required autocomplete="off">
            </div>
            <div class="form-group">
                <label for="jenis">Jenis Kategori</label>
                <select id="jenis" name="jenis" class="form-control" required>
                    <option value="masuk">Pemasukan (Uang Masuk)</option>
                    <option value="keluar">Pengeluaran (Uang Keluar)</option>
                </select>
            </div>
            <div class="flex-end-gap">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addKategoriModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. EDIT KATEGORI MODAL -->
<div id="editKategoriModal" class="modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <h3>Ubah Kategori</h3>
            <button class="modal-close" onclick="closeModal('editKategoriModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_id" name="id">
            <div class="form-group">
                <label for="edit_nama_kategori">Nama Kategori</label>
                <input type="text" id="edit_nama_kategori" name="nama_kategori" class="form-control" required autocomplete="off">
            </div>
            <div class="form-group">
                <label for="edit_jenis">Jenis Kategori</label>
                <select id="edit_jenis" name="jenis" class="form-control" required>
                    <option value="masuk">Pemasukan (Uang Masuk)</option>
                    <option value="keluar">Pengeluaran (Uang Keluar)</option>
                </select>
            </div>
            <div class="flex-end-gap">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editKategoriModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editKategori(id, nama, jenis) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama_kategori').value = nama;
    document.getElementById('edit_jenis').value = jenis;
    openModal('editKategoriModal');
}
</script>

<?php
require_once 'includes/footer.php';
?>
