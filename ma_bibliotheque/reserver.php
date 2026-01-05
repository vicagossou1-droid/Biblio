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

// 1. Vérifier si le livre est introuvable ou disponible (on ne réserve que les indisponibles)
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

if ($livre['exemplaires_disponibles'] > 0) {
    flash_set('error', 'Ce livre est actuellement disponible. Vous pouvez le consulter et l\'emprunter directement.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// 2. Vérifier si l'utilisateur n'a pas déjà une réservation active pour ce livre
$stmt = mysqli_prepare($conn, 'SELECT reservation_id FROM reservations WHERE utilisateur_id = ? AND livre_id = ? AND (statut = "en_attente" OR statut = "disponible")');
mysqli_stmt_bind_param($stmt, 'ii', $utilisateur_id, $livre_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    flash_set('error', 'Vous avez déjà une réservation active pour le livre "' . $livre['titre'] . '".');
    header('Location: ' . BASE_URL . '/mes_emprunts.php');
    exit;
}

// 3. Enregistrer la réservation
$date_reservation = date('Y-m-d H:i:s');
$statut = 'en_attente';

$stmt = mysqli_prepare($conn, 'INSERT INTO reservations (utilisateur_id, livre_id, date_reservation, statut) VALUES (?, ?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'iiss', $utilisateur_id, $livre_id, $date_reservation, $statut);

if (mysqli_stmt_execute($stmt)) {
    flash_set('success', 'Votre réservation pour le livre "' . $livre['titre'] . '" a été enregistrée ! Vous serez notifié de sa disponibilité.');
    header('Location: ' . BASE_URL . '/mes_emprunts.php');
    exit;
} else {
    flash_set('error', 'Une erreur est survenue lors de l\'enregistrement de votre réservation.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
?>