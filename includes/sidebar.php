<?php
// includes/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <i class="fa-solid fa-wallet"></i> CashFlow
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <a href="dashboard.php">
                <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>
        </li>
        <li class="<?= $currentPage === 'transaksi.php' ? 'active' : '' ?>">
            <a href="transaksi.php">
                <i class="fa-solid fa-exchange-alt"></i> Transaksi
            </a>
        </li>
        <li class="<?= $currentPage === 'akun.php' ? 'active' : '' ?>">
            <a href="akun.php">
                <i class="fa-solid fa-university"></i> Rekening / Akun
            </a>
        </li>
        <li class="<?= $currentPage === 'kategori.php' ? 'active' : '' ?>">
            <a href="kategori.php">
                <i class="fa-solid fa-tags"></i> Kategori
            </a>
        </li>
    </ul>
    
    <div class="sidebar-user">
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($currentUser['nama']) ?></div>
            <div class="user-role">@<?= htmlspecialchars($currentUser['username']) ?></div>
        </div>
        <a href="logout.php" class="logout-btn" title="Keluar">
            <i class="fa-solid fa-sign-out-alt"></i>
        </a>
    </div>
</aside>
