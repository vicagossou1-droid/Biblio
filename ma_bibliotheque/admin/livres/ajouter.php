<?php
include __DIR__ . '/../../includes/auth.php';
include __DIR__ . '/../../config/db.php';
require_admin();

$errors = [];

// Vérifie si la colonne 'url_image_couverture' existe
$has_url_image_col = false;
$r = mysqli_query($conn, "SHOW COLUMNS FROM livres LIKE 'url_image_couverture'");
if ($r && mysqli_num_rows($r) > 0) $has_url_image_col = true;

// Vérifier les colonnes optionnelles
$has_date_publication_col = false;
$r = mysqli_query($conn, "SHOW COLUMNS FROM livres LIKE 'date_publication'");
if ($r && mysqli_num_rows($r) > 0) $has_date_publication_col = true;

$has_resume_col = false;
$r = mysqli_query($conn, "SHOW COLUMNS FROM livres LIKE 'resume'");
if ($r && mysqli_num_rows($r) > 0) $has_resume_col = true;

$has_maison_edition_col = false;
$r = mysqli_query($conn, "SHOW COLUMNS FROM livres LIKE 'maison_edition'");
if ($r && mysqli_num_rows($r) > 0) $has_maison_edition_col = true;

$has_biographie_auteur_col = false;
$r = mysqli_query($conn, "SHOW COLUMNS FROM auteurs LIKE 'biographie'");
if ($r && mysqli_num_rows($r) > 0) $has_biographie_auteur_col = true;

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $auteur_input = trim($_POST['auteur'] ?? '');
    $categorie_input = trim($_POST['categorie'] ?? '');
    $total = (int)($_POST['total_exemplaires'] ?? 0);
    $url_couverture = trim($_POST['url_couverture'] ?? '');
    $date_publication = trim($_POST['date_publication'] ?? '');
    $resume = trim($_POST['resume'] ?? '');
    $maison_edition = trim($_POST['maison_edition'] ?? '');
    $biographie_auteur = trim($_POST['biographie_auteur'] ?? '');

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
        // Auteur: chercher par nom complet, sinon créer
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

        // Catégorie: chercher par nom, sinon créer
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

        // Construire l'INSERT dynamiquement selon les colonnes disponibles
        $insert_cols = ['titre', 'auteur_id', 'categorie_id', 'total_exemplaires', 'exemplaires_disponibles'];
        $insert_vals = ['?', '?', '?', '?', '?'];
        $bind_params = 'siiii';
        $bind_values = [&$titre, &$auteur_id, &$categorie_id, &$total, &$total];

        if ($url_image && $has_url_image_col) {
            $insert_cols[] = 'url_image_couverture';
            $insert_vals[] = '?';
            $bind_params .= 's';
            $bind_values[] = &$url_image;
        } elseif ($url_image && !$has_url_image_col) {
            // Extraire le chemin du fichier depuis l'URL et le supprimer
            $file_path = __DIR__ . '/../../public/images/covers/' . basename($url_image);
            if (file_exists($file_path)) @unlink($file_path);
            $url_image = null;
            $errors[] = 'La colonne "url_image_couverture" n\'existe pas dans la base ; la couverture n\'a pas été enregistrée.';
        }

        if ($date_publication && $has_date_publication_col) {
            $insert_cols[] = 'date_publication';
            $insert_vals[] = '?';
            $bind_params .= 's';
            $bind_values[] = &$date_publication;
        }

        if ($resume && $has_resume_col) {
            $insert_cols[] = 'resume';
            $insert_vals[] = '?';
            $bind_params .= 's';
            $bind_values[] = &$resume;
        }

        if ($maison_edition && $has_maison_edition_col) {
            $insert_cols[] = 'maison_edition';
            $insert_vals[] = '?';
            $bind_params .= 's';
            $bind_values[] = &$maison_edition;
        }

        $sql = 'INSERT INTO livres (' . implode(',', $insert_cols) . ') VALUES (' . implode(',', $insert_vals) . ')';
        $stmt = mysqli_prepare($conn, $sql);
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $bind_params], $bind_values));
        $ok = mysqli_stmt_execute($stmt);
        if ($ok) {
            // Si la biographie existe et doit être mise à jour
            if ($biographie_auteur && $has_biographie_auteur_col) {
                $stmt = mysqli_prepare($conn, 'UPDATE auteurs SET biographie = ? WHERE auteur_id = ?');
                mysqli_stmt_bind_param($stmt, 'si', $biographie_auteur, $auteur_id);
                mysqli_stmt_execute($stmt);
            }
            
            flash_set('success', 'Livre ajouté avec succès.');
            header('Location: ' . BASE_URL . '/admin/livres/index.php');
            exit;
        } else {
            $errors[] = 'Échec lors de l\'ajout du livre.';
            // si une image a été uploadée mais que l'insertion a échoué, supprimer l'image
            if ($url_image) {
                $file_path = __DIR__ . '/../../public/images/covers/' . basename($url_image);
                if (file_exists($file_path)) @unlink($file_path);
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<h1>Ajouter un livre</h1>

<?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?php echo esc($e); ?></div>
<?php endforeach; ?>

<form method="post" class="form">
    <div class="form-group">
        <label for="titre">Titre</label>
        <input id="titre" name="titre" placeholder="Titre du livre" required value="<?php echo esc($_POST['titre'] ?? ''); ?>">
    </div>

    <div class="form-group">
        <label for="auteur">Auteur</label>
        <input list="auteurs" id="auteur" name="auteur" placeholder="Nom Prénom" required value="<?php echo esc($_POST['auteur'] ?? ''); ?>">
        <datalist id="auteurs">
            <?php foreach ($auteurs as $a): ?>
                <option value="<?php echo esc($a['prenom'].' '.$a['nom']); ?>">
            <?php endforeach; ?>
        </datalist>
    </div>

    <div class="form-group">
        <label for="categorie">Catégorie</label>
        <input list="categories" id="categorie" name="categorie" placeholder="Nom de la catégorie" required value="<?php echo esc($_POST['categorie'] ?? ''); ?>">
        <datalist id="categories">
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo esc($c['nom']); ?>">
            <?php endforeach; ?>
        </datalist>
    </div>

    <div class="form-group">
        <label for="total_exemplaires">Total d'exemplaires</label>
        <input type="number" class="small-input" id="total_exemplaires" name="total_exemplaires" min="1" required value="<?php echo esc($_POST['total_exemplaires'] ?? '1'); ?>">
    </div>

    <div class="form-group">
        <label for="url_couverture">URL de la couverture</label>
        <input type="url" id="url_couverture" name="url_couverture" placeholder="https://..." value="<?php echo esc($_POST['url_couverture'] ?? ''); ?>">
    </div>

    <div class="form-group">
        <label for="date_publication">Année de publication</label>
        <input type="text" id="date_publication" name="date_publication" placeholder="YYYY" value="<?php echo esc($_POST['date_publication'] ?? ''); ?>">
    </div>

    <div class="form-group">
        <label for="maison_edition">Maison d'édition</label>
        <input type="text" id="maison_edition" name="maison_edition" placeholder="Nom de la maison d'édition" value="<?php echo esc($_POST['maison_edition'] ?? ''); ?>">
    </div>

    <div class="form-group">
        <label for="resume">Résumé</label>
        <textarea id="resume" name="resume" placeholder="Résumé du livre" rows="4"><?php echo esc($_POST['resume'] ?? ''); ?></textarea>
    </div>

    <div class="form-group">
        <label for="biographie_auteur">Biographie de l'auteur</label>
        <textarea id="biographie_auteur" name="biographie_auteur" placeholder="Biographie de l'auteur" rows="4"><?php echo esc($_POST['biographie_auteur'] ?? ''); ?></textarea>
    </div>

    <div class="form-group form-actions">
        <button class="btn" type="submit">Ajouter</button>
        <a class="btn" href="index.php">Annuler</a>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
