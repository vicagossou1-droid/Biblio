<?php
include __DIR__ . '/includes/auth.php';
include __DIR__ . '/config/db.php';

$livre_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($livre_id <= 0) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Récupérer les détails du livre - Colonnes optionnelles
$sql = "SELECT l.livre_id, l.titre, l.url_image_couverture,
               l.exemplaires_disponibles, l.total_exemplaires,
               a.auteur_id, a.prenom, a.nom,
               c.categorie_id, c.nom AS categorie_nom
        FROM livres l
        LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id
        LEFT JOIN categories c ON l.categorie_id = c.categorie_id
        WHERE l.livre_id = ?";

// Vérifier les colonnes optionnelles dans livres
$check_cols_livres = mysqli_query($conn, "SHOW COLUMNS FROM livres WHERE Field IN ('resume', 'maison_edition', 'date_publication')");
$optional_cols = [];
while ($col = mysqli_fetch_assoc($check_cols_livres)) {
    $optional_cols[] = $col['Field'];
}

// Vérifier les colonnes optionnelles dans auteurs
$check_cols_auteurs = mysqli_query($conn, "SHOW COLUMNS FROM auteurs WHERE Field IN ('biographie')");
$has_biographie = false;
while ($col = mysqli_fetch_assoc($check_cols_auteurs)) {
    if ($col['Field'] === 'biographie') {
        $has_biographie = true;
    }
}

// Reconstruire la requête avec les colonnes disponibles
$sql = "SELECT l.livre_id, l.titre, l.url_image_couverture,
               l.exemplaires_disponibles, l.total_exemplaires,
               a.auteur_id, a.prenom, a.nom" . 
               ($has_biographie ? ", a.biographie" : "") . ",
               c.categorie_id, c.nom AS categorie_nom" .
               (in_array('resume', $optional_cols) ? ", l.resume" : ", NULL AS resume") .
               (in_array('maison_edition', $optional_cols) ? ", l.maison_edition" : ", NULL AS maison_edition") .
               (in_array('date_publication', $optional_cols) ? ", l.date_publication" : ", NULL AS date_publication") . "
        FROM livres l
        LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id
        LEFT JOIN categories c ON l.categorie_id = c.categorie_id
        WHERE l.livre_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $livre_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($res) === 0) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$livre = mysqli_fetch_assoc($res);

// Vérifier si l'utilisateur a déjà emprunté ce livre
$has_borrowed = false;
$can_reserve = false;
if (is_logged_in()) {
    $check_borrow = "SELECT COUNT(*) as count FROM emprunts 
                     WHERE utilisateur_id = ? AND livre_id = ? AND statut = 'en_cours'";
    $stmt_borrow = mysqli_prepare($conn, $check_borrow);
    mysqli_stmt_bind_param($stmt_borrow, 'ii', $_SESSION['user_id'], $livre_id);
    mysqli_stmt_execute($stmt_borrow);
    $res_borrow = mysqli_stmt_get_result($stmt_borrow);
    $borrow_count = mysqli_fetch_assoc($res_borrow);
    $has_borrowed = $borrow_count['count'] > 0;
    
    // Vérifier si peut emprunter
    $can_reserve = !$has_borrowed && $livre['exemplaires_disponibles'] > 0;
}

// Récupérer les avis pour ce livre
$stmt_avis = mysqli_prepare($conn, '
    SELECT a.avis_id, a.note, a.commentaire, a.cree_le,
           u.prenom, u.nom
    FROM avis a
    INNER JOIN utilisateurs u ON a.utilisateur_id = u.utilisateur_id
    WHERE a.livre_id = ?
    ORDER BY a.avis_id DESC
');
mysqli_stmt_bind_param($stmt_avis, 'i', $livre_id);
mysqli_stmt_execute($stmt_avis);
$res_avis = mysqli_stmt_get_result($stmt_avis);
$avis_list = mysqli_fetch_all($res_avis, MYSQLI_ASSOC);

// Vérifier si l'utilisateur a déjà posté un avis
$has_posted_review = false;
if (is_logged_in()) {
    $check_avis = mysqli_prepare($conn, '
        SELECT avis_id FROM avis 
        WHERE livre_id = ? AND utilisateur_id = ?
    ');
    mysqli_stmt_bind_param($check_avis, 'ii', $livre_id, $_SESSION['user_id']);
    mysqli_stmt_execute($check_avis);
    $res_check = mysqli_stmt_get_result($check_avis);
    $has_posted_review = mysqli_num_rows($res_check) > 0;
}

// Traiter l'ajout d'un avis
$avis_errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_review') {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
    
    $note = (int)$_POST['note'] ?? 0;
    $commentaire = trim($_POST['commentaire'] ?? '');
    
    if ($note < 1 || $note > 5) {
        $avis_errors[] = 'La note doit être entre 1 et 5.';
    }
    if (strlen($commentaire) > 1000) {
        $avis_errors[] = 'Le commentaire ne peut pas dépasser 1000 caractères.';
    }
    
    if (empty($avis_errors)) {
        if ($has_posted_review) {
            // Mettre à jour l'avis existant
            $stmt_update = mysqli_prepare($conn, '
                UPDATE avis 
                SET note = ?, commentaire = ?
                WHERE livre_id = ? AND utilisateur_id = ?
            ');
            mysqli_stmt_bind_param($stmt_update, 'isii', $note, $commentaire, $livre_id, $_SESSION['user_id']);
            mysqli_stmt_execute($stmt_update);
        } else {
            // Créer un nouvel avis
            $stmt_insert = mysqli_prepare($conn, '
                INSERT INTO avis (livre_id, utilisateur_id, note, commentaire)
                VALUES (?, ?, ?, ?)
            ');
            mysqli_stmt_bind_param($stmt_insert, 'iiis', $livre_id, $_SESSION['user_id'], $note, $commentaire);
            mysqli_stmt_execute($stmt_insert);
        }
        header('Location: ' . BASE_URL . '/livre.php?id=' . $livre_id);
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="book-detail-container">
    
    <div class="book-detail-content">
        <div class="book-detail-image">
            <?php if ($livre['url_image_couverture']): ?>
                <img src="<?php echo esc($livre['url_image_couverture']); ?>" alt="<?php echo esc($livre['titre']); ?>">
            <?php else: ?>
                <div class="no-cover">Pas d'image</div>
            <?php endif; ?>
            
            <div class="book-availability">
                <?php if ($livre['exemplaires_disponibles'] > 0): ?>
                    <p class="available"><span class="badge">Disponible</span></p>
                <?php else: ?>
                    <p class="unavailable"><span class="badge">Indisponible</span></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="book-detail-info">
            <h1><?php echo esc($livre['titre']); ?></h1>
            <?php if ($livre['prenom'] || $livre['nom']): ?>
                <div class="book-author">
                    <p class="author-name">
                        <strong>Auteur :</strong>
                        <a href="<?php echo BASE_URL; ?>/index.php?search=<?php echo urlencode(trim($livre['prenom'] . ' ' . $livre['nom'])); ?>">
                            <?php echo esc(trim($livre['prenom'] . ' ' . $livre['nom'])); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
            <div class="book-metadata">
                <div class="metadata-item">
                    <strong>Catégorie :</strong> <?php echo $livre['categorie_nom'] ? esc($livre['categorie_nom']) : 'Non défini'; ?>
                </div>
                <?php if (!empty($livre['maison_edition'])): ?>
                    <div class="metadata-item">
                        <strong>Maison d'édition :</strong> <?php echo esc($livre['maison_edition']); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($livre['date_publication'])): ?>
                    <div class="metadata-item">
                        <strong>Date de publication :</strong> <?php echo date('Y', strtotime($livre['date_publication'])); ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($livre['resume']): ?>
                <div class="book-resume">
                    <h2>Résumé</h2>
                    <p><?php echo esc($livre['resume']); ?></p>
                </div>
            <?php endif; ?>
            <?php if ($livre['prenom'] || $livre['nom']): ?>
                <div class="author-bio">
                    <h2>À propos de l'auteur</h2>
                    <p class="author-name"><?php echo esc(trim($livre['prenom'] . ' ' . $livre['nom'])); ?></p>
                    <?php if (!empty($livre['biographie'])): ?>
                        <p><?php echo esc($livre['biographie']); ?></p>
                    <?php else: ?>
                        <p><em>Aucune biographie disponible</em></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            </div>
            
            <div class="book-actions">
                <?php if (!is_logged_in()): ?>
                    <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn primary">Se connecter pour emprunter</a>
                <?php elseif ($has_borrowed): ?>
                    <p class="info">Vous avez déjà emprunté ce livre.</p>
                    <a href="<?php echo BASE_URL; ?>/mes_emprunts.php" class="btn secondary">Voir mes emprunts</a>
                <?php elseif ($can_reserve): ?>
                    <a href="<?php echo BASE_URL; ?>/emprunter.php?livre_id=<?php echo (int)$livre_id; ?>" class="btn primary">Emprunter ce livre</a>
                    <a href="<?php echo BASE_URL; ?>/reserver.php?livre_id=<?php echo (int)$livre_id; ?>" class="btn secondary">Réserver ce livre</a>
                <?php else: ?>
                    <p class="warning">Ce livre n'est pas disponible pour le moment.</p>
                    <a href="<?php echo BASE_URL; ?>/reserver.php?livre_id=<?php echo (int)$livre_id; ?>" class="btn secondary">Réserver ce livre</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- SECTION DES AVIS -->
    <div class="reviews-section">
        <h2>Avis des lecteurs</h2>
        
        <?php if (is_logged_in()): ?>
            <!-- FORMULAIRE D'AVIS -->
            <div class="review-form-container">
                <h3><?php echo $has_posted_review ? 'Modifier votre avis' : 'Partager votre avis'; ?></h3>
                
                <?php foreach ($avis_errors as $e): ?>
                    <div class="alert alert-error"><?php echo esc($e); ?></div>
                <?php endforeach; ?>
                
                <form method="post" class="review-form">
                    <input type="hidden" name="action" value="add_review">
                    
                    <div class="form-group">
                        <label for="note">Note</label>
                        <div class="star-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="note" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>" class="star" title="<?php echo $i; ?> étoile(s)">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="commentaire">Votre avis (optionnel, max 1000 caractères)</label>
                        <textarea id="commentaire" name="commentaire" rows="4" maxlength="1000" placeholder="Partagez votre opinion..."></textarea>
                        <div class="char-count"><span id="char-count">0</span>/1000</div>
                    </div>
                    
                    <button type="submit" class="btn">Envoyer mon avis</button>
                </form>
            </div>
        <?php else: ?>
            <p style="text-align: center; padding: 1rem; background: #f0f0f0; border-radius: 8px;">
                <a href="<?php echo BASE_URL; ?>/auth/login.php">Connectez-vous</a> pour partager votre avis
            </p>
        <?php endif; ?>
        
        <!-- AFFICHAGE DES AVIS -->
        <div class="reviews-list">
            <h3>Les avis des lecteurs (<?php echo count($avis_list); ?>)</h3>
            
            <?php if (empty($avis_list)): ?>
                <p style="text-align: center; color: #666; padding: 2rem;">Aucun avis pour le moment. Soyez le premier à partager votre opinion!</p>
            <?php else: ?>
                <?php foreach ($avis_list as $av): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div>
                                <strong><?php echo esc($av['prenom'] . ' ' . $av['nom']); ?></strong>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star-filled" style="color: <?php echo $i <= $av['note'] ? '#ffc107' : '#ddd'; ?>;">★</span>
                                    <?php endfor; ?>
                                    <span class="rating-value"><?php echo (int)$av['note']; ?>/5</span>
                                </div>
                            </div>
                            <div class="review-date"><?php echo !empty($av['cree_le']) ? date('d/m/Y', strtotime($av['cree_le'])) : 'N/A'; ?></div>
                        </div>
                        <?php if ($av['commentaire']): ?>
                            <p class="review-comment"><?php echo esc($av['commentaire']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div></div>

<style>
.book-detail-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.back-link {
    display: inline-block;
    margin-bottom: 1.5rem;
    color: var(--primary, #2c3e50);
    text-decoration: none;
    font-weight: 500;
}

.back-link:hover {
    text-decoration: underline;
}

.book-detail-content {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 3rem;
    margin-bottom: 3rem;
}

.book-detail-image {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.book-detail-image img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.no-cover {
    width: 100%;
    aspect-ratio: 2/3;
    background: var(--bg, #f5f5f5);
    border: 2px dashed #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    color: #999;
}

.book-availability {
    text-align: center;
    padding: 1rem;
    background: var(--light, #f9f9f9);
    border-radius: 8px;
}

.book-availability .badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.book-availability .available .badge {
    background: #d4edda;
    color: #155724;
}

.book-availability .unavailable .badge {
    background: #f8d7da;
    color: #721c24;
}

.book-detail-info h1 {
    margin-bottom: 1rem;
    font-size: 2rem;
    color: var(--primary, #2c3e50);
}

.book-author,
.book-resume {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #eee;
}

.book-author h2,
.book-resume h2 {
    font-size: 1.3rem;
    color: var(--primary, #2c3e50);
    margin-bottom: 1rem;
}

.author-name a {
    color: var(--primary, #2c3e50);
    text-decoration: none;
    font-weight: 600;
    font-size: 1.1rem;
}

.author-name a:hover {
    text-decoration: underline;
}

.author-bio {
    margin-top: 1rem;
}

.author-bio h3 {
    font-size: 1rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.author-bio p {
    color: #555;
    line-height: 1.6;
}

.book-resume p {
    color: #555;
    line-height: 1.8;
}

.book-metadata {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--light, #f9f9f9);
    border-radius: 8px;
}

.metadata-item {
    margin-bottom: 1rem;
}

.metadata-item:last-child {
    margin-bottom: 0;
}

.metadata-item strong {
    color: var(--primary, #2c3e50);
    display: inline;
}

.metadata-item span {
    color: #555;
}

.book-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.book-actions .btn {
    display: inline-block;
}

.info, .warning {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

@media (max-width: 768px) {
    .book-detail-content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .book-detail-info h1 {
        font-size: 1.5rem;
    }
    
    .book-actions {
        flex-direction: column;
    }
    
    .book-actions .btn {
        width: 100%;
    }
}

/* STYLES POUR LES AVIS */
.reviews-section {
    max-width: 1200px;
    margin: 3rem auto;
    padding: 0 1rem 2rem;
}

.reviews-section h2 {
    font-size: 1.8rem;
    color: var(--primary, #2c3e50);
    margin-bottom: 2rem;
    border-bottom: 2px solid #007bff;
    padding-bottom: 1rem;
}

.reviews-section h3 {
    font-size: 1.2rem;
    color: var(--primary, #2c3e50);
    margin-bottom: 1.5rem;
    margin-top: 2rem;
}

.review-form-container {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border: 1px solid #dee2e6;
}

.review-form {
    max-width: 600px;
}

.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 0.5rem;
    font-size: 2rem;
}

.star-rating input {
    display: none;
}

.star-rating .star {
    cursor: pointer;
    color: #ddd;
    transition: color 0.2s;
}

.star-rating input:checked ~ .star,
.star-rating .star:hover,
.star-rating .star:hover ~ .star {
    color: #ffc107;
}

.char-count {
    font-size: 0.85rem;
    color: #666;
    margin-top: 0.5rem;
    text-align: right;
}

.reviews-list {
    margin-top: 3rem;
}

.review-item {
    background: white;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: box-shadow 0.3s;
}

.review-item:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 1rem;
}

.review-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    font-size: 1.1rem;
}

.star-filled {
    font-size: 1.2rem;
}

.rating-value {
    font-size: 0.9rem;
    color: #666;
    font-weight: 600;
    margin-left: 0.5rem;
}

.review-date {
    font-size: 0.85rem;
    color: #999;
    min-width: 80px;
    text-align: right;
}

.review-comment {
    color: #555;
    line-height: 1.6;
    margin: 0;
}

@media (max-width: 768px) {
    .review-header {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .review-form-container {
        padding: 1.5rem;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
