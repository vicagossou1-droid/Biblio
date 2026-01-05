<?php
include __DIR__ . '/includes/auth.php';
include __DIR__ . '/config/db.php';
require_login();

$utilisateur_id = $_SESSION['user_id'];

// Récupérer les emprunts de l'utilisateur ET les réservations validées
$sql = "SELECT e.emprunt_id as id, l.titre, a.prenom AS auteur_prenom, a.nom AS auteur_nom, e.date_emprunt as date, e.date_echeance, e.statut, 'emprunt' as type
        FROM emprunts e
        JOIN livres l ON e.livre_id = l.livre_id
        LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id
        WHERE e.utilisateur_id = ?
        
        UNION ALL
        
        SELECT r.reservation_id as id, l.titre, a.prenom AS auteur_prenom, a.nom AS auteur_nom, r.date_reservation as date, r.date_reservation as date_echeance, r.statut, 'reservation' as type
        FROM reservations r
        JOIN livres l ON r.livre_id = l.livre_id
        LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id
        WHERE r.utilisateur_id = ? AND r.statut = 'validee'
        
        ORDER BY date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $utilisateur_id, $utilisateur_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

include __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Mes Emprunts et Réservations</h1>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo $msg; ?></div>
<?php endif; ?>
<?php if ($err = flash_get('error')): ?>
    <div class="alert alert-error"><?php echo $err; ?></div>
<?php endif; ?>

<?php if (mysqli_num_rows($res) === 0): ?>
    <p>Vous n'avez aucun emprunt ou réservation pour le moment.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Titre du livre</th>
                <th>Auteur</th>
                <th>Date d'emprunt</th>
                <th>Date d'échéance</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($emprunt = mysqli_fetch_assoc($res)): ?>
            <tr>
                <td><?php echo esc($emprunt['titre']); ?></td>
                <td><?php echo esc(trim($emprunt['auteur_prenom'] . ' ' . $emprunt['auteur_nom'])); ?></td>
                <td><?php echo date('d/m/Y', strtotime($emprunt['date'])); ?></td>
                <td><?php echo date('d/m/Y', strtotime($emprunt['date_echeance'])); ?></td>
                <td><?php echo esc($emprunt['statut']); ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>