<?php
include __DIR__ . '/includes/auth.php';
include __DIR__ . '/config/db.php';
require_login();

$livre_id = (int)($_GET['livre_id'] ?? 0);
$utilisateur_id = $_SESSION['user_id'];

if ($livre_id <= 0) {
    flash_set('error', 'ID de livre invalide.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// 1. Vérifier la disponibilité du livre
$stmt = mysqli_prepare($conn, 'SELECT titre, exemplaires_disponibles FROM livres WHERE livre_id = ?');
mysqli_stmt_bind_param($stmt, 'i', $livre_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$livre = mysqli_fetch_assoc($res);

if (!$livre) {
    flash_set('error', 'Le livre demandé n\'existe pas ou a été supprimé.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($livre['exemplaires_disponibles'] <= 0) {
    flash_set('error', '"' . $livre['titre'] . '" est actuellement emprunté. Vous pouvez le réserver pour le recevoir dès que possible.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// 2. Vérifier si l'utilisateur n'a pas déjà emprunté ce livre et qu'il est toujours "en_cours"
$stmt = mysqli_prepare($conn, 'SELECT emprunt_id FROM emprunts WHERE utilisateur_id = ? AND livre_id = ? AND statut = "en_cours"');
mysqli_stmt_bind_param($stmt, 'ii', $utilisateur_id, $livre_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    flash_set('error', 'Vous avez déjà ce livre en votre possession.');
    header('Location: ' . BASE_URL . '/mes_emprunts.php');
    exit;
}

// 3. Enregistrer l'emprunt
$date_emprunt = date('Y-m-d H:i:s');
$date_echeance = date('Y-m-d H:i:s', strtotime('+10 days'));
$statut = 'en_cours';

$stmt = mysqli_prepare($conn, 'INSERT INTO emprunts (utilisateur_id, livre_id, date_emprunt, date_echeance, statut) VALUES (?, ?, ?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'iisss', $utilisateur_id, $livre_id, $date_emprunt, $date_echeance, $statut);

if (mysqli_stmt_execute($stmt)) {
    // 4. Mettre à jour le nombre d'exemplaires disponibles
    $stmt_update_livre = mysqli_prepare($conn, 'UPDATE livres SET exemplaires_disponibles = exemplaires_disponibles - 1 WHERE livre_id = ?');
    mysqli_stmt_bind_param($stmt_update_livre, 'i', $livre_id);
    mysqli_stmt_execute($stmt_update_livre);

    flash_set('success', 'Vous avez emprunté "' . $livre['titre'] . '" avec succès ! Date d\'échéance : ' . date('d/m/Y', strtotime($date_echeance)));
    header('Location: ' . BASE_URL . '/mes_emprunts.php');
    exit;
} else {
    flash_set('error', 'Une erreur est survenue lors de l\'emprunt du livre.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
?>