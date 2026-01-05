<?php
include __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../config/db.php';
require_admin();

$reservation_id = (int)($_GET['reservation_id'] ?? 0);

if ($reservation_id <= 0) {
    flash_set('error', 'ID de réservation invalide.');
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

// 1. Récupérer les informations de la réservation
$sql = "SELECT r.statut, l.titre, r.livre_id
        FROM reservations r
        JOIN livres l ON r.livre_id = l.livre_id
        WHERE r.reservation_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $reservation_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$reservation = mysqli_fetch_assoc($res);

if (!$reservation) {
    flash_set('error', 'Réservation introuvable.');
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

if ($reservation['statut'] === 'emprunte') {
    flash_set('error', 'Cette réservation a déjà été empruntée et ne peut pas être annulée.');
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

// 2. Mettre à jour le statut de la réservation à 'annulee'
$stmt_cancel = mysqli_prepare($conn, 'UPDATE reservations SET statut = "annulee" WHERE reservation_id = ?');
mysqli_stmt_bind_param($stmt_cancel, 'i', $reservation_id);

if (mysqli_stmt_execute($stmt_cancel)) {
    flash_set('success', 'La réservation pour le livre "' . $reservation['titre'] . '" a été annulée.');
} else {
    flash_set('error', 'Une erreur est survenue lors de l\'annulation de la réservation.');
}
header('Location: ' . BASE_URL . '/admin/index.php');
exit;
?>