<?php
include __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../config/db.php';
require_admin();

$filter_statut = $_GET['statut'] ?? 'en_attente'; // Default to 'en_attente' for admin view

// Si on filtre par "emprunte", on cherche dans la table emprunts au lieu de reservations
if ($filter_statut === 'emprunte') {
    $sql = "SELECT e.emprunt_id as reservation_id, l.titre, u.prenom AS utilisateur_prenom, u.nom AS utilisateur_nom,
                   COALESCE(a.prenom, '') AS auteur_prenom, COALESCE(a.nom, '') AS auteur_nom,
                   e.date_emprunt as date_reservation, 'emprunte' as statut
            FROM emprunts e
            JOIN livres l ON e.livre_id = l.livre_id
            JOIN utilisateurs u ON e.utilisateur_id = u.utilisateur_id
            LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id
            WHERE e.statut = 'en_cours'
            ORDER BY e.date_emprunt ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_execute($stmt);
} else if ($filter_statut === 'all') {
    // Afficher tous les réservations + tous les emprunts en cours
    $sql = "SELECT r.reservation_id, l.titre, u.prenom AS utilisateur_prenom, u.nom AS utilisateur_nom,
                   COALESCE(a.prenom, '') AS auteur_prenom, COALESCE(a.nom, '') AS auteur_nom,
                   r.date_reservation, r.statut
            FROM reservations r
            JOIN livres l ON r.livre_id = l.livre_id
            JOIN utilisateurs u ON r.utilisateur_id = u.utilisateur_id
            LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id
    
            UNION ALL
            
            SELECT e.emprunt_id as reservation_id, l.titre, u.prenom AS utilisateur_prenom, u.nom AS utilisateur_nom,
                   COALESCE(a.prenom, '') AS auteur_prenom, COALESCE(a.nom, '') AS auteur_nom,
                   e.date_emprunt as date_reservation, 'emprunte' as statut
            FROM emprunts e
            JOIN livres l ON e.livre_id = l.livre_id
            JOIN utilisateurs u ON e.utilisateur_id = u.utilisateur_id
            LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id
            WHERE e.statut = 'en_cours'
            
            ORDER BY date_reservation ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_execute($stmt);
} else {
    $sql = "SELECT r.reservation_id, l.titre, COALESCE(a.prenom, '') AS auteur_prenom, COALESCE(a.nom, '') AS auteur_nom, u.prenom AS utilisateur_prenom, u.nom AS utilisateur_nom,
                   r.date_reservation, r.statut
            FROM reservations r
            JOIN livres l ON r.livre_id = l.livre_id
            JOIN utilisateurs u ON r.utilisateur_id = u.utilisateur_id
            LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id";

    $params = [];
    $types = '';

    if ($filter_statut !== 'all') {
        $sql .= " WHERE r.statut = ?";
        $params[] = $filter_statut;
        $types .= 's';
    }

    $sql .= " ORDER BY r.date_reservation ASC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) {
        // Construire les références pour bind_param
        $refs = [];
        foreach ($params as &$param) {
            $refs[] = &$param;
        }
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $types], $refs));
    }
    mysqli_stmt_execute($stmt);
}
$res = mysqli_stmt_get_result($stmt);

include __DIR__ . '/../includes/header.php';
?>

<h1>Tableau de bord admin</h1>

<div class="admin-nav">
    <a class="btn" href="<?php echo BASE_URL; ?>/admin/livres/index.php">Gérer les livres</a>
    <a class="btn" href="<?php echo BASE_URL; ?>/admin/utilisateurs/index.php">Gérer les utilisateurs</a>
    <a class="btn" href="<?php echo BASE_URL; ?>/admin/modifier_retour.php">Modifier les dates de retour</a>
</div>

<h2>Gestion des réservations</h2>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo $msg; ?></div>
<?php endif; ?>
<?php if ($err = flash_get('error')): ?>
    <div class="alert alert-error"><?php echo $err; ?></div>
<?php endif; ?>

<!-- STATISTIQUES RAPIDES -->
<?php
$stats_en_attente = mysqli_query($conn, "SELECT COUNT(*) as count FROM reservations WHERE statut = 'en_attente'");
$stats_emprunts = mysqli_query($conn, "SELECT COUNT(*) as count FROM emprunts WHERE statut = 'en_cours'");

$nb_en_attente = mysqli_fetch_assoc($stats_en_attente)['count'];
$nb_emprunts = mysqli_fetch_assoc($stats_emprunts)['count'];
?>

<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2rem;">
    <div style="padding: 1rem; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
        <div style="font-size: 0.9rem; color: #856404;">En attente</div>
        <div style="font-size: 2rem; font-weight: bold; color: #856404;"><?php echo $nb_en_attente; ?></div>
    </div>
    <div style="padding: 1rem; background: #e7d4f5; border-radius: 8px; border-left: 4px solid #6f42c1;">
        <div style="font-size: 0.9rem; color: #3d1f6f;">Empruntés</div>
        <div style="font-size: 2rem; font-weight: bold; color: #3d1f6f;"><?php echo $nb_emprunts; ?></div>
    </div>
</div>

<div class="filters">
    <a class="btn <?php echo ($filter_statut === 'en_attente') ? 'active' : ''; ?>" href="?statut=en_attente">En attente</a>
    <a class="btn <?php echo ($filter_statut === 'emprunte') ? 'active' : ''; ?>" href="?statut=emprunte">Empruntées</a>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Livre</th>
            <th>Auteur</th>
            <th>Réservataire</th>
            <th>Date de réservation</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
    <?php if (mysqli_num_rows($res) > 0): ?>
        <?php while ($reservation = mysqli_fetch_assoc($res)): ?>
            <tr>
                <td><?php echo esc($reservation['titre']); ?></td>
                <td><?php 
                    $auteur = trim(($reservation['auteur_prenom'] ?? '') . ' ' . ($reservation['auteur_nom'] ?? ''));
                    echo esc($auteur ?: 'N/A'); 
                ?></td>
                <td><?php echo esc($reservation['utilisateur_prenom'] . ' ' . $reservation['utilisateur_nom']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($reservation['date_reservation'])); ?></td>
                <td>
                    <span class="badge" style="background-color: <?php 
                        echo (($reservation['statut'] === 'en_attente') ? '#ffc107' : 
                             (($reservation['statut'] === 'validee') ? '#28a745' :
                             (($reservation['statut'] === 'emprunte') ? 'transparent' : '#6c757d')));
                    ?>; color: <?php echo ($reservation['statut'] === 'emprunte') ? '#000' : '#fff'; ?>">
                        <?php 
                            $statut_display = ucfirst(str_replace('_', ' ', $reservation['statut']));
                            echo ($reservation['statut'] === 'emprunte') ? 'Emprunté' : $statut_display;
                        ?>
                    </span>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="5" style="text-align: center; padding: 2rem; color: #666;">Aucune réservation</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>