<?php
include __DIR__ . '/../../includes/auth.php';
include __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] !== 1) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$success = '';
$errors = [];

// SUPPRIMER UN FRAIS
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['frais_id'])) {
    $frais_id = (int)$_GET['frais_id'];
    $stmt = mysqli_prepare($conn, 'DELETE FROM frais WHERE frais_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $frais_id);
    if (mysqli_stmt_execute($stmt)) {
        flash_set('success', 'Frais supprimé avec succès.');
        header('Location: ' . BASE_URL . '/admin/frais/');
        exit;
    } else {
        $errors[] = 'Erreur lors de la suppression.';
    }
}

// AJOUTER UN FRAIS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $emprunt_id = (int)$_POST['emprunt_id'] ?? 0;
    $montant = (float)$_POST['montant'] ?? 0;
    $raison = trim($_POST['raison'] ?? '');
    $est_paye = !empty($_POST['est_paye']) ? 1 : 0;
    // Détecter la présence de la colonne `est_paye` en base
    $res_chk = mysqli_query($conn, "SHOW COLUMNS FROM frais LIKE 'est_paye'");
    $has_est_paye = (mysqli_num_rows($res_chk) > 0);

    if ($emprunt_id === 0 || $montant <= 0 || empty($raison)) {
        $errors[] = 'Veuillez remplir tous les champs correctement.';
    } else {
        if ($has_est_paye) {
            $stmt = mysqli_prepare($conn, '
                INSERT INTO frais (emprunt_id, montant, raison, est_paye, genere_le)
                VALUES (?, ?, ?, ?, NOW())
            ');
            mysqli_stmt_bind_param($stmt, 'idsi', $emprunt_id, $montant, $raison, $est_paye);
        } else {
            $statut = $est_paye ? 'paye' : 'non_paye';
            $stmt = mysqli_prepare($conn, '
                INSERT INTO frais (emprunt_id, montant, raison, statut, genere_le)
                VALUES (?, ?, ?, ?, NOW())
            ');
            mysqli_stmt_bind_param($stmt, 'idss', $emprunt_id, $montant, $raison, $statut);
        }
        if (mysqli_stmt_execute($stmt)) {
            flash_set('success', 'Frais ajouté avec succès.');
            header('Location: ' . BASE_URL . '/admin/frais/');
            exit;
        } else {
            $errors[] = 'Erreur lors de l\'ajout du frais.';
        }
    }
}

// MODIFIER UN FRAIS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $frais_id = (int)$_POST['frais_id'] ?? 0;
    $montant = (float)$_POST['montant'] ?? 0;
    $raison = trim($_POST['raison'] ?? '');
    $est_paye = !empty($_POST['est_paye']) ? 1 : 0;
    // Détecter la présence de la colonne `est_paye` en base
    $res_chk = mysqli_query($conn, "SHOW COLUMNS FROM frais LIKE 'est_paye'");
    $has_est_paye = (mysqli_num_rows($res_chk) > 0);

    if ($frais_id === 0 || $montant <= 0 || empty($raison)) {
        $errors[] = 'Veuillez remplir tous les champs correctement.';
    } else {
        if ($has_est_paye) {
            $stmt = mysqli_prepare($conn, '
                UPDATE frais 
                SET montant = ?, raison = ?, est_paye = ?
                WHERE frais_id = ?
            ');
            mysqli_stmt_bind_param($stmt, 'dsii', $montant, $raison, $est_paye, $frais_id);
        } else {
            $statut = $est_paye ? 'paye' : 'non_paye';
            $stmt = mysqli_prepare($conn, '
                UPDATE frais 
                SET montant = ?, raison = ?, statut = ?
                WHERE frais_id = ?
            ');
            mysqli_stmt_bind_param($stmt, 'dssi', $montant, $raison, $statut, $frais_id);
        }
        if (mysqli_stmt_execute($stmt)) {
            flash_set('success', 'Frais modifié avec succès.');
            header('Location: ' . BASE_URL . '/admin/frais/');
            exit;
        } else {
            $errors[] = 'Erreur lors de la modification.';
        }
    }
}

// Vérifier et créer les colonnes manquantes si nécessaire
$res = mysqli_query($conn, "SHOW COLUMNS FROM frais LIKE 'statut'");
if (mysqli_num_rows($res) === 0) {
    mysqli_query($conn, "ALTER TABLE frais ADD COLUMN statut VARCHAR(20) DEFAULT 'non_paye'");
}
$res = mysqli_query($conn, "SHOW COLUMNS FROM frais LIKE 'raison'");
if (mysqli_num_rows($res) === 0) {
    mysqli_query($conn, "ALTER TABLE frais ADD COLUMN raison VARCHAR(255) NULL");
}
$res = mysqli_query($conn, "SHOW COLUMNS FROM frais LIKE 'date_creation'");
if (mysqli_num_rows($res) === 0) {
    mysqli_query($conn, "ALTER TABLE frais ADD COLUMN date_creation DATETIME DEFAULT CURRENT_TIMESTAMP");
}

// Récupérer tous les frais avec infos utilisateur
$stmt = mysqli_prepare($conn, '
    SELECT f.frais_id, f.montant, f.statut, f.raison, f.date_creation,
           u.utilisateur_id, u.prenom, u.nom, u.email
    FROM frais f
    INNER JOIN emprunts e ON f.emprunt_id = e.emprunt_id
    INNER JOIN utilisateurs u ON e.utilisateur_id = u.utilisateur_id
    ORDER BY f.frais_id DESC
');
mysqli_stmt_execute($stmt);
$res_frais = mysqli_stmt_get_result($stmt);
$frais_list = mysqli_fetch_all($res_frais, MYSQLI_ASSOC);

// Normaliser la présence de `est_paye` : certaines bases utilisent `statut` ('paye'/'non_paye')
// alors que d'autres avaient une colonne `est_paye` (0/1). On calcule `est_paye` ici.
foreach ($frais_list as $i => $f) {
    $est_paye = 0;
    if (array_key_exists('est_paye', $f)) {
        $est_paye = (int)$f['est_paye'];
    } elseif (array_key_exists('statut', $f)) {
        $est_paye = ($f['statut'] === 'paye' || $f['statut'] === '1') ? 1 : 0;
    }
    $frais_list[$i]['est_paye'] = $est_paye;
}

// Récupérer les utilisateurs pour le select
$stmt_users = mysqli_prepare($conn, '
    SELECT utilisateur_id, prenom, nom, email FROM utilisateurs WHERE role_id = 2 ORDER BY prenom, nom
');
mysqli_stmt_execute($stmt_users);
$res_users = mysqli_stmt_get_result($stmt_users);
$users = mysqli_fetch_all($res_users, MYSQLI_ASSOC);

// Récupérer les emprunts en cours pour le select
$stmt_emprunts = mysqli_prepare($conn, '
    SELECT e.emprunt_id, l.titre, u.prenom, u.nom, e.statut
    FROM emprunts e
    JOIN livres l ON e.livre_id = l.livre_id
    JOIN utilisateurs u ON e.utilisateur_id = u.utilisateur_id
    ORDER BY u.nom, l.titre
');
mysqli_stmt_execute($stmt_emprunts);
$res_emprunts = mysqli_stmt_get_result($stmt_emprunts);
$emprunts = mysqli_fetch_all($res_emprunts, MYSQLI_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>

<h1>Gestion des Frais</h1>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo esc($msg); ?></div>
<?php endif; ?>

<?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?php echo esc($e); ?></div>
<?php endforeach; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <!-- FORMULAIRE D'AJOUT -->
    <div class="form-section">
        <h2 style="margin-top: 0; font-size: 1.2rem; border-bottom: 2px solid #007bff; padding-bottom: 0.5rem;">Ajouter un frais</h2>
        <form method="post" class="form">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="emprunt_id">Emprunt</label>
                <select id="emprunt_id" name="emprunt_id" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($emprunts as $e): ?>
                        <option value="<?php echo (int)$e['emprunt_id']; ?>">
                            <?php echo esc($e['prenom'] . ' ' . $e['nom'] . ' - ' . $e['titre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="montant">Montant (FCFA)</label>
                <input id="montant" name="montant" type="number" step="0.01" min="0" required placeholder="0.00">
            </div>
            <div class="form-group">
                <label for="raison">Raison</label>
                <input id="raison" name="raison" type="text" required placeholder="Ex: Retard de restitution">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="est_paye" value="1"> Payé</label>
            </div>
            <button type="submit" class="btn" style="width: 100%;">Ajouter le frais</button>
        </form>
    </div>

    <!-- STATISTIQUES -->
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <?php
        $total_a_payer = 0;
        $total_paye = 0;
        $nb_non_paye = 0;
        $nb_paye = 0;
        foreach ($frais_list as $f) {
            if ($f['est_paye'] == 0) {
                $total_a_payer += (float)$f['montant'];
                $nb_non_paye++;
            } else {
                $total_paye += (float)$f['montant'];
                $nb_paye++;
            }
        }
        ?>
        <div style="padding: 1rem; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
            <div style="font-size: 0.9rem; color: #856404;">Total à payer</div>
            <div style="font-size: 1.8rem; font-weight: bold; color: #856404;"><?php echo number_format($total_a_payer, 2, ',', ' '); ?> FCFA</div>
            <div style="font-size: 0.85rem; color: #856404; margin-top: 0.5rem;"><?php echo $nb_non_paye; ?> frais</div>
        </div>
        <div style="padding: 1rem; background: #d4edda; border-radius: 8px; border-left: 4px solid #28a745;">
            <div style="font-size: 0.9rem; color: #155724;">Total payé</div>
            <div style="font-size: 1.8rem; font-weight: bold; color: #155724;"><?php echo number_format($total_paye, 2, ',', ' '); ?> FCFA</div>
            <div style="font-size: 0.85rem; color: #155724; margin-top: 0.5rem;"><?php echo $nb_paye; ?> frais</div>
        </div>
        <div style="padding: 1rem; background: #e2e3e5; border-radius: 8px; border-left: 4px solid #6c757d;">
            <div style="font-size: 0.9rem; color: #383d41;">Total général</div>
            <div style="font-size: 1.8rem; font-weight: bold; color: #383d41;"><?php echo number_format($total_a_payer + $total_paye, 2, ',', ' '); ?> FCFA</div>
            <div style="font-size: 0.85rem; color: #383d41; margin-top: 0.5rem;"><?php echo count($frais_list); ?> frais</div>
        </div>
    </div>
</div>

<!-- TABLEAU DES FRAIS -->
<h2 style="font-size: 1.1rem; margin-top: 2rem; margin-bottom: 1rem;">Liste des frais</h2>

<?php if (empty($frais_list)): ?>
    <p style="text-align: center; color: #666; padding: 2rem;">Aucun frais enregistré.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Utilisateur</th>
                <th style="text-align: right;">Montant</th>
                <th>Raison</th>
                <th style="text-align: center;">Statut</th>
                <th style="text-align: center;">Date</th>
                <th style="text-align: center; width: 100px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($frais_list as $f): ?>
                <tr>
                    <td>
                        <div style="font-weight: 600;"><?php echo esc($f['prenom'] . ' ' . $f['nom']); ?></div>
                        <div style="font-size: 0.85rem; color: #666;"><?php echo esc($f['email']); ?></div>
                    </td>
                    <td style="text-align: right; font-weight: bold;"><?php echo number_format($f['montant'], 2, ',', ' '); ?> FCFA</td>
                    <td><?php echo esc($f['raison']); ?></td>
                    <td style="text-align: center;">
                        <span class="badge badge-<?php echo ($f['est_paye'] == 1) ? 'success' : 'warning'; ?>">
                            <?php echo ($f['est_paye'] == 1) ? 'Payé' : 'Non payé'; ?>
                        </span>
                    </td>
                    <td style="text-align: center; font-size: 0.9rem;"><?php echo date('d/m/Y', strtotime($f['date_creation'])); ?></td>
                    <td style="text-align: center;">
                        <a href="#" onclick="editFrais(<?php echo (int)$f['frais_id']; ?>, <?php echo (int)$f['montant']; ?>, '<?php echo esc($f['raison']); ?>', <?php echo (int)$f['est_paye']; ?>); return false;" class="btn-icon">✎</a>
                        <a href="?action=delete&frais_id=<?php echo (int)$f['frais_id']; ?>" onclick="return confirm('Êtes-vous sûr?');" class="btn-icon" style="color: #dc3545;">✕</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- FORMULAIRE ÉDITION (CACHÉ) -->
<div id="edit-modal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2); z-index: 1000; min-width: 400px;">
    <h2 style="margin-top: 0; margin-bottom: 1rem;">Modifier le frais</h2>
    <form method="post" class="form">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="frais_id" id="edit_frais_id">
        <div class="form-group">
            <label for="edit_montant">Montant (FCFA)</label>
            <input id="edit_montant" name="montant" type="number" step="0.01" min="0" required>
        </div>
        <div class="form-group">
            <label for="edit_raison">Raison</label>
            <input id="edit_raison" name="raison" type="text" required>
        </div>
        <div class="form-group">
            <label><input type="checkbox" id="edit_est_paye" name="est_paye" value="1"> Payé</label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn">Enregistrer</button>
            <button type="button" class="btn" onclick="closeEditModal()" style="background: #6c757d;">Annuler</button>
        </div>
    </form>
</div>

<div id="overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 999;" onclick="closeEditModal()"></div>

<style>
table.table {
    width: 100%;
    border-collapse: collapse;
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
.btn-icon {
    display: inline-block;
    width: 32px;
    height: 32px;
    line-height: 32px;
    text-align: center;
    background: #007bff;
    color: white;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 1.1rem;
    transition: background 0.3s;
}
.btn-icon:hover {
    background: #0056b3;
}
.form-section {
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}
.form-actions {
    display: flex;
    gap: 0.5rem;
}
.form-actions button {
    flex: 1;
}
</style>

<script>
function editFrais(fraisId, montant, raison, estPaye) {
    document.getElementById('edit_frais_id').value = fraisId;
    document.getElementById('edit_montant').value = montant;
    document.getElementById('edit_raison').value = raison;
    document.getElementById('edit_est_paye').checked = (estPaye == 1);
    document.getElementById('edit-modal').style.display = 'block';
    document.getElementById('overlay').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
    document.getElementById('overlay').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
