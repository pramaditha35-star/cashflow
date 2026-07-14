<?php
// config/database.php

$host = 'localhost';
$db   = 'cashflow';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$pdo = null;

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (\PDOException $e) {
    $pdo = null;
}

function getDBConnection() {
    global $pdo, $host, $db, $user, $pass, $charset;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (\PDOException $e) {
            // If DB connection fails and we are not already on setup.php, redirect there
            $current_page = basename($_SERVER['PHP_SELF']);
            if ($current_page !== 'setup.php') {
                header("Location: setup.php");
                exit;
            }
            throw $e;
        }
    }
    return $pdo;
}

function formatRupiah($angka) {
    if ($angka === null) $angka = 0;
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
