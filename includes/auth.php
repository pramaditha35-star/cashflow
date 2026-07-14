<?php
// includes/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently logged in.
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['status'] == 1;
}

/**
 * Enforce authentication. Redirects to login.php if not logged in.
 */
function checkAuth() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Enforce guest access. Redirects to dashboard.php if already logged in.
 */
function checkGuest() {
    if (isLoggedIn()) {
        header("Location: dashboard.php");
        exit;
    }
}

/**
 * Get the current logged-in user's details.
 */
function getLoggedInUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nama' => $_SESSION['nama']
    ];
}
