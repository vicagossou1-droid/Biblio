<?php
include __DIR__ . '/../../includes/auth.php';
include __DIR__ . '/../../config/db.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    flash_set('error', 'ID de livre invalide.');
    header('Location: ' . BASE_URL . '/admin/livres/index.php');
    exit;
}

// Récup livre
$stmt = mysqli_prepare($conn, 'SELECT * FROM livres WHERE livre_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$livre = mysqli_fetch_assoc($res);
if (!$livre) {
    flash_set('error', 'Livre introuvable.');
    header('Location: ' . BASE_URL . '/admin/livres/index.php');
    exit;
}

// Récup auteurs et catégories
$auteurs = [];
$stmt = mysqli_prepare($conn, 'SELECT auteur_id, prenom, nom FROM auteurs ORDER BY nom');
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) $auteurs[] = $r;

$categories = [];
$stmt = mysqli_prepare($conn, 'SELECT categorie_id, nom FROM categories ORDER BY nom');
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) $categories[] = $r;

// valeurs actuelles
$current_auteur_name = '';
if (!empty($livre['auteur_id'])) {
    $s = mysqli_prepare($conn, 'SELECT prenom, nom FROM auteurs WHERE auteur_id = ? LIMIT 1');
    mysqli_stmt_bind_param($s, 'i', $livre['auteur_id']);
    mysqli_stmt_execute($s);
    $r = mysqli_stmt_get_result($s);
    $ar = mysqli_fetch_assoc($r);
    if ($ar) $current_auteur_name = $ar['prenom'] . ' ' . $ar['nom'];
}

$current_categorie_name = '';
if (!empty($livre['categorie_id'])) {
    $s = mysqli_prepare($conn, 'SELECT nom FROM categories WHERE categorie_id = ? LIMIT 1');
    mysqli_stmt_bind_param($s, 'i', $livre['categorie_id']);
    mysqli_stmt_execute($s);
    $r = mysqli_stmt_get_result($s);
    $cr = mysqli_fetch_assoc($r);
    if ($cr) $current_categorie_name = $cr['nom'];
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $auteur_input = trim($_POST['auteur'] ?? '');
    $categorie_input = trim($_POST['categorie'] ?? '');
    $total = (int)($_POST['total_exemplaires'] ?? 0);
    $url_couverture = trim($_POST['url_couverture'] ?? '');
    $maison_edition = trim($_POST['maison_edition'] ?? '');
    $date_publication = trim($_POST['date_publication'] ?? '');
    $resume = trim($_POST['resume'] ?? '');
    $biographie = trim($_POST['biographie'] ?? '');

    if ($titre === '' || $total <= 0) {
        $errors[] = 'Veuillez renseigner le titre et un nombre d\'exemplaires valide.';
    }
    if ($auteur_input === '') {
        $errors[] = 'Veuillez renseigner un auteur.';
    }
    if ($categorie_input === '') {
        $errors[] = 'Veuillez renseigner une catégorie.';
    }

    if (empty($errors)) {
        // Trouver ou créer l'auteur
        $auteur_id = 0;
        $stmt = mysqli_prepare($conn, "SELECT auteur_id FROM auteurs WHERE CONCAT(prenom, ' ', nom) = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $auteur_input);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if ($row) {
            $auteur_id = (int)$row['auteur_id'];
        } else {
            $parts = preg_split('/\s+/', $auteur_input);
            $nom = array_pop($parts);
            $prenom = trim(implode(' ', $parts));
            if ($prenom === '') $prenom = 'Inconnu';
            $stmt = mysqli_prepare($conn, 'INSERT INTO auteurs (prenom, nom) VALUES (?, ?)');
            mysqli_stmt_bind_param($stmt, 'ss', $prenom, $nom);
            mysqli_stmt_execute($stmt);
            $auteur_id = (int)mysqli_insert_id($conn);
        }

        // Trouver ou créer la catégorie
        $categorie_id = 0;
        $stmt = mysqli_prepare($conn, 'SELECT categorie_id FROM categories WHERE nom = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $categorie_input);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if ($row) {
            $categorie_id = (int)$row['categorie_id'];
        } else {
            $stmt = mysqli_prepare($conn, 'INSERT INTO categories (nom) VALUES (?)');
            mysqli_stmt_bind_param($stmt, 's', $categorie_input);
            mysqli_stmt_execute($stmt);
            $categorie_id = (int)mysqli_insert_id($conn);
        }

        // Calculer exemplaires disponibles
        $borrowed = max(0, $livre['total_exemplaires'] - $livre['exemplaires_disponibles']);
        $new_available = max(0, $total - $borrowed);

        // URL de la couverture
        $new_url_couverture = $url_couverture ?: $livre['url_image_couverture'];

        // Construire la requête UPDATE
        $update_parts = ['titre = ?', 'auteur_id = ?', 'categorie_id = ?', 'total_exemplaires = ?', 'exemplaires_disponibles = ?', 'url_image_couverture = ?'];
        $bind_types = 'siiisis';
        $bind_values = [$titre, $auteur_id, $categorie_id, $total, $new_available, $new_url_couverture];

        // Vérifier les colonnes optionnelles
        $check_cols = mysqli_query($conn, "SHOW COLUMNS FROM livres WHERE Field IN ('maison_edition', 'date_publication', 'resume')");
        $has_optional = [];
        while ($col = mysqli_fetch_assoc($check_cols)) {
            $has_optional[$col['Field']] = true;
        }

        if (!empty($has_optional['maison_edition'])) {
            $update_parts[] = 'maison_edition = ?';
            $bind_types .= 's';
            $bind_values[] = $maison_edition ?: null;
        }
        if (!empty($has_optional['date_publication'])) {
            $update_parts[] = 'date_publication = ?';
            $bind_types .= 's';
            $bind_values[] = $date_publication ?: null;
        }
        if (!empty($has_optional['resume'])) {
            $update_parts[] = 'resume = ?';
            $bind_types .= 's';
            $bind_values[] = $resume ?: null;
        }

        $bind_types .= 'i';
        $bind_values[] = $id;

        $sql = 'UPDATE livres SET ' . implode(', ', $update_parts) . ' WHERE livre_id = ?';
        $stmt = mysqli_prepare($conn, $sql);
        
        $refs = [];
        foreach ($bind_values as &$val) {
            $refs[] = &$val;
        }
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $bind_types], $refs));

        if (mysqli_stmt_execute($stmt)) {
            // Mettre à jour la biographie de l'auteur
            $check_author_cols = mysqli_query($conn, "SHOW COLUMNS FROM auteurs WHERE Field = 'biographie'");
            if (mysqli_num_rows($check_author_cols) > 0 && !empty($biographie)) {
                $stmt_bio = mysqli_prepare($conn, 'UPDATE auteurs SET biographie = ? WHERE auteur_id = ?');
                mysqli_stmt_bind_param($stmt_bio, 'si', $biographie, $auteur_id);
                mysqli_stmt_execute($stmt_bio);
            }
            flash_set('success', 'Livre modifié avec succès.');
            header('Location: ' . BASE_URL . '/admin/livres/index.php');
            exit;
        } else {
            $errors[] = 'Échec lors de la modification.';
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<h1>Modifier le livre</h1>

<?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?php echo esc($e); ?></div>
<?php endforeach; ?>

<form method="post" class="form">
    <div class="form-group">
        <label for="titre">Titre</label>
        <input id="titre" name="titre" placeholder="Titre du livre" required value="<?php echo esc($_POST['titre'] ?? $livre['titre']); ?>">
    </div>

    <div class="form-group">
        <label for="auteur">Auteur</label>
        <input list="auteurs" id="auteur" name="auteur" placeholder="Nom Prénom" required value="<?php echo esc($_POST['auteur'] ?? $current_auteur_name); ?>">
        <datalist id="auteurs">
            <?php foreach ($auteurs as $a): ?>
                <option value="<?php echo esc($a['prenom'].' '.$a['nom']); ?>">
            <?php endforeach; ?>
        </datalist>
    </div>

    <div class="form-group">
        <label for="categorie">Catégorie</label>
        <input list="categories" id="categorie" name="categorie" placeholder="Nom de la catégorie" required value="<?php echo esc($_POST['categorie'] ?? $current_categorie_name); ?>">
        <datalist id="categories">
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo esc($c['nom']); ?>">
            <?php endforeach; ?>
        </datalist>
    </div>

    <div class="form-group">
        <label for="total_exemplaires">Total d'exemplaires</label>
        <input type="number" class="small-input" id="total_exemplaires" name="total_exemplaires" min="1" required value="<?php echo esc($_POST['total_exemplaires'] ?? $livre['total_exemplaires']); ?>">
    </div>

    <div class="form-group">
        <label for="url_couverture">URL de la couverture</label>
        <input id="url_couverture" name="url_couverture" type="url" placeholder="https://..." value="<?php echo esc($_POST['url_couverture'] ?? $livre['url_image_couverture']); ?>">
        <?php if (!empty($livre['url_image_couverture'])): ?>
            <div class="thumb"><img src="<?php echo esc($livre['url_image_couverture']); ?>" alt="Couverture du livre" style="max-height:150px;"></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="maison_edition">Maison d'édition</label>
        <input id="maison_edition" name="maison_edition" placeholder="Nom de la maison d'édition" value="<?php echo esc($_POST['maison_edition'] ?? $livre['maison_edition'] ?? ''); ?>">
    </div>

    <div class="form-group">
        <label for="date_publication">Année de publication</label>
        <input id="date_publication" name="date_publication" type="text" placeholder="YYYY" value="<?php echo esc($_POST['date_publication'] ?? $livre['date_publication'] ?? ''); ?>">
    </div>

    <div class="form-group">
        <label for="resume">Résumé</label>
        <textarea id="resume" name="resume" placeholder="Résumé du livre" rows="4"><?php echo esc($_POST['resume'] ?? $livre['resume'] ?? ''); ?></textarea>
    </div>

    <div class="form-group">
        <label for="biographie">Biographie de l'auteur</label>
        <textarea id="biographie" name="biographie" placeholder="Biographie de l'auteur" rows="4"><?php echo esc($_POST['biographie'] ?? ''); ?></textarea>
    </div>

    <div class="form-group form-actions">
        <button class="btn" type="submit">Enregistrer</button>
        <a class="btn" href="index.php">Annuler</a>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
