<?php
// Gestion basique des sessions et helpers d'auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si BASE_URL n'est pas défini (ex : logout.php inclus auth.php seul), inclure config si disponible
if (!defined('BASE_URL') && file_exists(__DIR__ . '/../config/db.php')) {
    include_once __DIR__ . '/../config/db.php';
}

// Fonction d'échappement pour éviter XSS
function esc($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

// Gestion messages flash
function flash_set($key, $message) {
    $_SESSION['flash'][$key] = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
}

function flash_get($key) {
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        flash_set('error', 'Vous devez vous connecter pour accéder à cette page.');
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function is_admin() {
    // On considère role_id == 1 comme administrateur
    return !empty($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
}

function require_admin() {
    if (!is_logged_in() || !is_admin()) {
        flash_set('error', 'Accès refusé. Vous n\'avez pas les droits nécessaires.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

