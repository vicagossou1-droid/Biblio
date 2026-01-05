<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Ma Bibliothèque</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Montserrat:wght@600&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css">
	<script>
		// Autocomplétion de recherche
		document.addEventListener('DOMContentLoaded', function() {
			const searchInput = document.getElementById('search-input');
			const suggestionsBox = document.getElementById('search-suggestions');
			
			if (!searchInput) return;
			
			searchInput.addEventListener('input', function() {
				const query = this.value.trim();
				
				if (query.length < 3) {
					suggestionsBox.innerHTML = '';
					return;
				}
				
				// Requête AJAX pour les suggestions
				fetch('<?php echo BASE_URL; ?>/search.php?suggest=1&q=' + encodeURIComponent(query))
					.then(response => response.json())
					.then(data => {
						if (data.length === 0) {
							suggestionsBox.innerHTML = '';
							return;
						}
						
						let html = '<ul>';
						data.forEach(item => {
							const disponibilite = item.disponible ? 
								'<span style="color: green;">Disponible</span>' : 
								'<span style="color: red;">Indisponible</span>';
							html += `<li onclick="window.location.href = '<?php echo BASE_URL; ?>/search.php?q=' + encodeURIComponent('${item.titre}')">
								<strong>${item.titre}</strong><br>
								<small>${item.auteur} - ${item.categorie} ${disponibilite}</small>
							</li>`;
						});
						html += '</ul>';
						suggestionsBox.innerHTML = html;
					})
					.catch(err => console.error('Erreur:', err));
			});
			
			// Fermer les suggestions en cliquant ailleurs
			document.addEventListener('click', function(e) {
				if (!e.target.closest('#search-form')) {
					suggestionsBox.innerHTML = '';
				}
			});
		});
	</script>
</head>
<body>
<header class="site-header">
	<div class="container header-inner">
		<div class="brand">
			<a href="<?php echo BASE_URL; ?>/index.php" class="logo">
				<img src="<?php echo BASE_URL; ?>/public/images/logo/logo.png" alt="Logo Ma Bibliothèque" class="logo-img">
				<span class="logo-text">Ma Bibliothèque</span>
			</a>
		</div>

		<nav id="main-nav" class="main-nav">
			<div class="nav-links">
			<?php if (!empty($_SESSION['user_id'])): ?>
				<span class="nav-welcome">Bienvenue, <?php echo htmlspecialchars($_SESSION['prenom'] ?? ''); ?></span>
				<?php endif; ?>
				<?php if (!empty($_SESSION['user_id'])): ?>
					<a href="<?php echo BASE_URL; ?>/index.php">Accueil</a>
				<?php endif; ?>
				<?php if (!empty($_SESSION['user_id']) && $_SESSION['role_id'] != 1): // Membre uniquement ?> 
					<a href="<?php echo BASE_URL; ?>/mes_reservations.php">Ma Liste de Lecture</a>
					<a href="<?php echo BASE_URL; ?>/mes_frais.php">Mes Frais</a>
				<?php endif; ?>
				<?php if (!empty($_SESSION['user_id']) && $_SESSION['role_id'] == 1): // Admin uniquement ?>

					<a href="<?php echo BASE_URL; ?>/admin/livres/index.php">Livres</a>
					<a href="<?php echo BASE_URL; ?>/admin/utilisateurs/index.php">Utilisateurs</a>
					<a href="<?php echo BASE_URL; ?>/admin/frais/index.php">Frais</a>
				<?php endif; ?>
				<?php if (!empty($_SESSION['user_id'])): ?>
					<a href="<?php echo BASE_URL; ?>/auth/logout.php" class="btn">Se déconnecter</a>
				<?php else: ?>
					<a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn">Se connecter</a>
				<?php endif; ?>
			</div>
		</nav>

		<button class="nav-toggle" aria-controls="main-nav" aria-expanded="false" aria-label="Ouvrir le menu">
			<span class="hamburger">☰</span>
		</button>
	</div>
</header>
<main class="container">
