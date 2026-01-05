<?php
include __DIR__ . '/includes/auth.php';
include __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = mysqli_prepare($conn, '
    SELECT f.frais_id, f.montant, f.raison, f.statut, f.date_creation
    FROM frais f
    JOIN emprunts e ON f.emprunt_id = e.emprunt_id
    WHERE e.utilisateur_id = ?
    ORDER BY f.date_creation DESC
');
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$res_frais = mysqli_stmt_get_result($stmt);
$frais_list = mysqli_fetch_all($res_frais, MYSQLI_ASSOC);

// Calculer totaux
$total_a_payer = 0;
$total_paye = 0;
foreach ($frais_list as $idx => $f) {
    // Normaliser le statut de paiement en `est_paye` (0/1)
    $est_paye = 0;
    if (array_key_exists('est_paye', $f)) {
        $est_paye = (int)$f['est_paye'];
    } elseif (array_key_exists('statut', $f)) {
        $est_paye = ($f['statut'] === 'paye' || $f['statut'] === '1') ? 1 : 0;
    }
    // Sauvegarder la valeur normalisée dans le tableau pour réutilisation en affichage
    $frais_list[$idx]['est_paye'] = $est_paye;

    if ($est_paye === 0) {
        $total_a_payer += (float)$f['montant'];
    } else {
        $total_paye += (float)$f['montant'];
    }
}

include __DIR__ . '/includes/header.php';
?>

<h1>Mes Frais</h1>

<?php if (empty($frais_list)): ?>
    <p style="text-align: center; color: #666; margin-top: 2rem;">✓ Vous n'avez aucun frais à régler. C'est excellent !</p>
<?php else: ?>
    <div class="stats-summary" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div class="stat-card" style="padding: 1rem; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
            <div style="font-size: 0.9rem; color: #856404;">À payer</div>
            <div style="font-size: 1.8rem; font-weight: bold; color: #856404;"><?php echo number_format($total_a_payer, 2, ',', ' '); ?> FCFA</div>
        </div>
        <div class="stat-card" style="padding: 1rem; background: #d4edda; border-radius: 8px; border-left: 4px solid #28a745;">
            <div style="font-size: 0.9rem; color: #155724;">Payé</div>
            <div style="font-size: 1.8rem; font-weight: bold; color: #155724;"><?php echo number_format($total_paye, 2, ',', ' '); ?> FCFA</div>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Raison</th>
                <th style="text-align: right;">Montant</th>
                <th style="text-align: center;">Statut</th>
                <th style="text-align: center;">Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($frais_list as $f): ?>
                <tr>
                    <td><?php echo esc($f['raison']); ?></td>
                    <td style="text-align: right; font-weight: bold;"><?php echo number_format($f['montant'], 2, ',', ' '); ?> FCFA</td>
                    <td style="text-align: center;">
                        <span class="badge badge-<?php echo ($f['est_paye'] == 1) ? 'success' : 'warning'; ?>">
                            <?php echo ($f['est_paye'] == 1) ? 'Payé' : 'Non payé'; ?>
                        </span>
                    </td>
                    <td style="text-align: center; font-size: 0.9rem;"><?php echo date('d/m/Y', strtotime($f['date_creation'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<style>
table.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
table.table th {
    background: #f8f9fa;
    padding: 1rem;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}
table.table td {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
}
table.table tbody tr:hover {
    background: #f8f9fa;
}
.badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
}
.badge-success {
    background: #d4edda;
    color: #155724;
}
.badge-warning {
    background: #fff3cd;
    color: #856404;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
