<?php
include __DIR__ . '/includes/auth.php';
include __DIR__ . '/config/db.php';
require_login();

$reservation_id = (int)($_GET['reservation_id'] ?? 0);
$utilisateur_id = $_SESSION['user_id'];

if ($reservation_id <= 0) {
    flash_set('error', 'ID de réservation invalide.');
    header('Location: ' . BASE_URL . '/mes_emprunts.php');
    exit;
}

// 1. Vérifier que la réservation existe, appartient à l'utilisateur et peut être annulée
$stmt = mysqli_prepare($conn, 'SELECT r.statut, l.titre, r.livre_id FROM reservations r JOIN livres l ON r.livre_id = l.livre_id WHERE r.reservation_id = ? AND r.utilisateur_id = ?');
mysqli_stmt_bind_param($stmt, 'ii', $reservation_id, $utilisateur_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$reservation = mysqli_fetch_assoc($res);

if (!$reservation) {
    flash_set('error', 'Cette réservation n\'existe pas ou vous n\'avez pas l\'autorisation de la gérer.');
    header('Location: ' . BASE_URL . '/mes_emprunts.php');
    exit;
}

if (!in_array($reservation['statut'], ['en_attente', 'disponible'])) {
    flash_set('error', 'Vous ne pouvez plus annuler cette réservation. Elle a déjà été validée.');
    header('Location: ' . BASE_URL . '/mes_emprunts.php');
    exit;
}

// 2. Annuler la réservation (dans une transaction)
mysqli_begin_transaction($conn);

try {
    // Mettre à jour la réservation actuelle à 'annulee'
    $stmt_cancel = mysqli_prepare($conn, 'UPDATE reservations SET statut = "annulee" WHERE reservation_id = ?');
    mysqli_stmt_bind_param($stmt_cancel, 'i', $reservation_id);
    mysqli_stmt_execute($stmt_cancel);

    // Si la réservation était 'disponible', il faut libérer l'exemplaire
    if ($reservation['statut'] === 'disponible') {
        // Chercher la prochaine réservation en attente pour ce livre
        $stmt_next = mysqli_prepare($conn, 'SELECT reservation_id FROM reservations WHERE livre_id = ? AND statut = "en_attente" ORDER BY date_reservation ASC LIMIT 1');
        mysqli_stmt_bind_param($stmt_next, 'i', $reservation['livre_id']);
        mysqli_stmt_execute($stmt_next);
        $res_next = mysqli_stmt_get_result($stmt_next);
        $next_reservation = mysqli_fetch_assoc($res_next);

        if ($next_reservation) {
            // Attribuer l'exemplaire à la réservation suivante
            $stmt_update_next = mysqli_prepare($conn, 'UPDATE reservations SET statut = "disponible" WHERE reservation_id = ?');
            mysqli_stmt_bind_param($stmt_update_next, 'i', $next_reservation['reservation_id']);
            mysqli_stmt_execute($stmt_update_next);
        } else {
            // Personne en attente, rendre le livre disponible dans le catalogue
            $stmt_update_livre = mysqli_prepare($conn, 'UPDATE livres SET exemplaires_disponibles = exemplaires_disponibles + 1 WHERE livre_id = ?');
            mysqli_stmt_bind_param($stmt_update_livre, 'i', $reservation['livre_id']);
            mysqli_stmt_execute($stmt_update_livre);
        }
    }

    // Valider la transaction
    mysqli_commit($conn);
    flash_set('success', 'Votre réservation pour le livre "' . $reservation['titre'] . '" a été annulée avec succès.');

} catch (Exception $e) {
    mysqli_rollback($conn);
    flash_set('error', 'Une erreur technique est survenue lors de l\'annulation. Veuillez réessayer.');
}

header('Location: ' . BASE_URL . '/mes_emprunts.php');
exit;
?>