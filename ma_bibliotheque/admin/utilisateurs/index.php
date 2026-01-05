<?php
include __DIR__ . '/../../includes/auth.php';
include __DIR__ . '/../../config/db.php';
require_admin();

// Récupérer tous les utilisateurs avec leur rôle
$sql = "SELECT u.utilisateur_id, u.prenom, u.nom, u.email, u.est_actif, r.nom_role
        FROM utilisateurs u
        LEFT JOIN roles r ON u.role_id = r.role_id
        ORDER BY u.nom, u.prenom";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

include __DIR__ . '/../../includes/header.php';
?>

<h1>Gérer les utilisateurs</h1>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo $msg; ?></div>
<?php endif; ?>
<?php if ($err = flash_get('error')): ?>
    <div class="alert alert-error"><?php echo $err; ?></div>
<?php endif; ?>

<p><a class="btn" href="ajouter.php">Ajouter un utilisateur</a></p>

<table class="table">
    <thead>
        <tr>
            <th>Nom</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($user = mysqli_fetch_assoc($res)): ?>
        <tr>
            <td><?php echo esc($user['prenom'] . ' ' . $user['nom']); ?></td>
            <td><?php echo esc($user['email']); ?></td>
            <td><?php echo esc($user['nom_role']); ?></td>
            <td><?php echo $user['est_actif'] ? 'Actif' : 'Inactif'; ?></td>
            <td>
                <a class="btn small" href="modifier.php?id=<?php echo (int)$user['utilisateur_id']; ?>">Modifier</a>
                <a class="btn small danger" href="supprimer.php?id=<?php echo (int)$user['utilisateur_id']; ?>">Supprimer</a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php include __DIR__ . '/../../includes/footer.php'; ?>