<?php
header('Content-Type: application/json');
include __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../config/db.php';

// Vérifier l'authentification
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
    exit;
}

$livre_id = (int)($_POST['livre_id'] ?? 0);
$utilisateur_id = $_SESSION['user_id'];

if ($livre_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de livre invalide.']);
    exit;
}

// Vérifier si l'utilisateur a déjà une réservation active pour ce livre
$stmt = mysqli_prepare($conn, "SELECT reservation_id FROM reservations WHERE utilisateur_id = ? AND livre_id = ? AND (statut = 'en_attente' OR statut = 'disponible' OR statut = 'validee')");
mysqli_stmt_bind_param($stmt, 'ii', $utilisateur_id, $livre_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    echo json_encode(['success' => false, 'message' => 'Vous avez déjà réservé ce livre.']);
    exit;
}

// Vérifier si le livre existe et est indisponible
$stmt = mysqli_prepare($conn, 'SELECT titre, exemplaires_disponibles FROM livres WHERE livre_id = ?');
mysqli_stmt_bind_param($stmt, 'i', $livre_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$livre = mysqli_fetch_assoc($res);

if (!$livre) {
    echo json_encode(['success' => false, 'message' => 'Livre introuvable.']);
    exit;
}

if ($livre['exemplaires_disponibles'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Le livre est disponible. Vous pouvez l\'emprunter directement.']);
    exit;
}

// Créer la réservation
$stmt = mysqli_prepare($conn, 'INSERT INTO reservations (utilisateur_id, livre_id, date_reservation, statut) VALUES (?, ?, NOW(), ?)');
$statut = 'en_attente';
mysqli_stmt_bind_param($stmt, 'iis', $utilisateur_id, $livre_id, $statut);
if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Réservation effectuée avec succès. Vous serez notifié quand le livre sera disponible.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la réservation.']);
}
?>
