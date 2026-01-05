<?php
include __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../config/db.php';
require_admin();

$filter_type = $_GET['type'] ?? 'tous'; // 'tous' ou 'utilisateur'
$utilisateur_id = (int)($_GET['user'] ?? 0);

// Récupérer les emprunts (tous les statuts sauf 'rendu')
$sql = "SELECT e.emprunt_id, l.titre, u.prenom, u.nom, u.utilisateur_id, e.date_echeance, e.statut, e.date_emprunt
        FROM emprunts e
        JOIN livres l ON e.livre_id = l.livre_id
        JOIN utilisateurs u ON e.utilisateur_id = u.utilisateur_id
        WHERE e.statut != 'rendu'";

if ($filter_type === 'utilisateur' && $utilisateur_id > 0) {
    $sql .= " AND e.utilisateur_id = " . (int)$utilisateur_id;
}

$sql .= " ORDER BY e.date_echeance ASC";
$res = mysqli_query($conn, $sql);

// Traiter la modification des dates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emprunt_id = (int)($_POST['emprunt_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($emprunt_id <= 0) {
        flash_set('error', 'Données invalides');
    } else if ($action === 'modifier_date') {
        $nouvelle_date = $_POST['nouvelle_date'] ?? '';
        
        if (empty($nouvelle_date)) {
            flash_set('error', 'La date est obligatoire');
        } else {
            $update_sql = "UPDATE emprunts SET date_echeance = ? WHERE emprunt_id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, 'si', $nouvelle_date, $emprunt_id);
            
            if (mysqli_stmt_execute($stmt)) {
                flash_set('success', 'Date de retour modifiée avec succès');
            } else {
                flash_set('error', 'Erreur lors de la mise à jour');
            }
        }
    } else if ($action === 'marquer_rendu') {
        // Mettre le statut à 'rendu' et augmenter les exemplaires disponibles
        $get_sql = "SELECT e.livre_id FROM emprunts e WHERE e.emprunt_id = ?";
        $get_stmt = mysqli_prepare($conn, $get_sql);
        mysqli_stmt_bind_param($get_stmt, 'i', $emprunt_id);
        mysqli_stmt_execute($get_stmt);
        $get_res = mysqli_stmt_get_result($get_stmt);
        $emprunt = mysqli_fetch_assoc($get_res);
        
        if ($emprunt) {
            $livre_id = $emprunt['livre_id'];
            
            // Mettre à jour le statut de l'emprunt
            $update_emp = "UPDATE emprunts SET statut = 'rendu' WHERE emprunt_id = ?";
            $stmt_emp = mysqli_prepare($conn, $update_emp);
            mysqli_stmt_bind_param($stmt_emp, 'i', $emprunt_id);
            mysqli_stmt_execute($stmt_emp);
            
            // Augmenter les exemplaires disponibles
            $update_liv = "UPDATE livres SET exemplaires_disponibles = exemplaires_disponibles + 1 WHERE livre_id = ?";
            $stmt_liv = mysqli_prepare($conn, $update_liv);
            mysqli_stmt_bind_param($stmt_liv, 'i', $livre_id);
            mysqli_stmt_execute($stmt_liv);
            
            flash_set('success', 'Livre marqué comme rendu');
        }
    }
    
    // Recharger la page
    header('Location: ' . BASE_URL . '/admin/modifier_retour.php?type=' . $filter_type . '&user=' . $utilisateur_id);
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<h1>Modifier les dates de retour</h1>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo $msg; ?></div>
<?php endif; ?>
<?php if ($err = flash_get('error')): ?>
    <div class="alert alert-error"><?php echo $err; ?></div>
<?php endif; ?>

<div class="filter-section">
    <h2>Filtrer par :</h2>
    <form method="get" class="filter-form">
        <div class="form-group">
            <label for="type">Filtre utilisateur :</label>
            <select id="type" name="type" onchange="handleFilterChange()">
                <option value="tous" <?php echo ($filter_type === 'tous') ? 'selected' : ''; ?>>Tous les utilisateurs</option>
                <option value="utilisateur" <?php echo ($filter_type === 'utilisateur') ? 'selected' : ''; ?>>Un utilisateur spécifique</option>
            </select>
        </div>

        <div id="userGroup" style="display: <?php echo ($filter_type === 'utilisateur') ? 'block' : 'none'; ?>;">
            <div class="form-group">
                <label for="user">Sélectionner l'utilisateur :</label>
                <select id="user" name="user">
                    <option value="">-- Sélectionner --</option>
                    <?php
                    $users_result = mysqli_query($conn, "SELECT DISTINCT u.utilisateur_id, u.prenom, u.nom FROM utilisateurs u JOIN emprunts e ON u.utilisateur_id = e.utilisateur_id WHERE e.statut != 'rendu' ORDER BY u.nom");
                    while ($user = mysqli_fetch_assoc($users_result)) {
                        $selected = ($utilisateur_id === $user['utilisateur_id']) ? 'selected' : '';
                        echo '<option value="' . (int)$user['utilisateur_id'] . '" ' . $selected . '>' . esc($user['prenom'] . ' ' . $user['nom']) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>

        <button type="submit" class="btn">Filtrer</button>
    </form>
</div>

<script>
function handleFilterChange() {
    const type = document.getElementById('type').value;
    const userGroup = document.getElementById('userGroup');
    const userSelect = document.getElementById('user');
    
    if (type === 'utilisateur') {
        userGroup.style.display = 'block';
    } else {
        userGroup.style.display = 'none';
        userSelect.value = '';
    }
}
</script>

<table class="table">
    <thead>
        <tr>
            <th>Livre</th>
            <th>Utilisateur</th>
            <th>Date d'emprunt</th>
            <th>Date d'échéance</th>
            <th>Statut</th>
            <th>Nouvelle date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (mysqli_num_rows($res) > 0): ?>
        <?php while ($emprunt = mysqli_fetch_assoc($res)): ?>
            <tr>
                <td><?php echo esc($emprunt['titre']); ?></td>
                <td><?php echo esc($emprunt['prenom'] . ' ' . $emprunt['nom']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($emprunt['date_emprunt'])); ?></td>
                <td><?php echo date('d/m/Y', strtotime($emprunt['date_echeance'])); ?></td>
                <td>
                    <span class="badge" style="background-color: <?php 
                        echo (($emprunt['statut'] === 'validee') ? '#007bff' : 
                             (($emprunt['statut'] === 'emprunte') ? '#28a745' : '#ffc107'));
                    ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $emprunt['statut'])); ?>
                    </span>
                </td>
                <td>
                    <form method="post" style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="hidden" name="emprunt_id" value="<?php echo (int)$emprunt['emprunt_id']; ?>">
                        <input type="hidden" name="action" value="modifier_date">
                        <input type="date" name="nouvelle_date" value="<?php echo $emprunt['date_echeance']; ?>" required>
                        <button type="submit" class="btn small">Modifier</button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="emprunt_id" value="<?php echo (int)$emprunt['emprunt_id']; ?>">
                        <input type="hidden" name="action" value="marquer_rendu">
                        <button type="submit" class="btn small" style="background-color: #28a745;">Rendu</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" style="text-align: center; padding: 2rem;">Aucun emprunt à afficher</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<style>
.filter-section {
    background: #f9f9f9;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.filter-form {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.filter-form .form-group {
    margin-bottom: 0;
    flex: 1;
    min-width: 200px;
}

.filter-form select {
    padding: 0.45rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    height: 40px;
    width: 100%;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    color: white;
    font-size: 0.85rem;
    font-weight: 500;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1.5rem;
}

.table thead {
    background-color: #f5f5f5;
    border-bottom: 2px solid #ddd;
}

.table th, .table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.table tbody tr:hover {
    background-color: #f9f9f9;
}

.btn.small {
    padding: 0.35rem 0.75rem;
    font-size: 0.85rem;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
