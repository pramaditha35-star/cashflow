<?php
// transaksi_aksi.php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Enforce login
checkAuth();

$db = getDBConnection();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. ADD TRANSACTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $tanggal = $_POST['tanggal'] ?? '';
    $akun_id = (int)($_POST['akun_id'] ?? 0);
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $jenis = $_POST['jenis'] ?? '';
    $nominal = (float)($_POST['nominal'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    
    // Get logged-in user id
    $user = getLoggedInUser();
    $user_id = $user['id'];

    // Validation
    if (empty($tanggal) || $akun_id <= 0 || $kategori_id <= 0 || !in_array($jenis, ['masuk', 'keluar']) || $nominal <= 0) {
        $_SESSION['msg'] = 'Semua field wajib diisi dengan benar. Nominal harus lebih besar dari 0!';
        $_SESSION['msg_status'] = 'danger';
        header("Location: transaksi.php");
        exit;
    }

    try {
        // Validate that category type matches transaction type
        $cat_stmt = $db->prepare("SELECT jenis FROM kategori WHERE id = ?");
        $cat_stmt->execute([$kategori_id]);
        $cat_jenis = $cat_stmt->fetchColumn();

        if ($cat_jenis !== $jenis) {
            $_SESSION['msg'] = 'Kategori yang dipilih tidak sesuai dengan jenis transaksi!';
            $_SESSION['msg_status'] = 'danger';
            header("Location: transaksi.php");
            exit;
        }

        // Insert Transaction
        $insert_query = "
            INSERT INTO transaksi (tanggal, akun_id, kategori_id, jenis, nominal, keterangan, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $db->prepare($insert_query);
        $stmt->execute([$tanggal, $akun_id, $kategori_id, $jenis, $nominal, $keterangan, $user_id]);

        $_SESSION['msg'] = 'Transaksi berhasil dicatat!';
        $_SESSION['msg_status'] = 'success';
    } catch (\PDOException $e) {
        $_SESSION['msg'] = 'Gagal mencatat transaksi: ' . $e->getMessage();
        $_SESSION['msg_status'] = 'danger';
    }

    header("Location: transaksi.php");
    exit;
}

// 2. DELETE TRANSACTION
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        try {
            $stmt = $db->prepare("DELETE FROM transaksi WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['msg'] = 'Transaksi berhasil dihapus!';
            $_SESSION['msg_status'] = 'success';
        } catch (\PDOException $e) {
            $_SESSION['msg'] = 'Gagal menghapus transaksi: ' . $e->getMessage();
            $_SESSION['msg_status'] = 'danger';
        }
    }
    header("Location: transaksi.php");
    exit;
}

// Fallback redirect
header("Location: transaksi.php");
exit;
