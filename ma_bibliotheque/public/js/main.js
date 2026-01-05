document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.querySelector('.nav-toggle');
    const mainNav = document.querySelector('#main-nav');

    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function() {
            mainNav.classList.toggle('open');
        });
    }

    // Compteur de caractères pour les avis
    const commentInput = document.getElementById('commentaire');
    if (commentInput) {
        commentInput.addEventListener('input', function() {
            const charCount = this.value.length;
            const countDisplay = document.getElementById('char-count');
            if (countDisplay) {
                countDisplay.textContent = charCount;
            }
        });
    }
});

// Fonction pour réserver un livre via AJAX
function reserver(event, element) {
    event.preventDefault();
    
    const href = element.getAttribute('href');
    const url = new URL(href, window.location.origin);
    const livreId = url.searchParams.get('livre_id');
    
    // Désactiver le lien pendant la requête
    element.style.pointerEvents = 'none';
    element.style.opacity = '0.6';
    const originalText = element.textContent;
    element.textContent = 'Chargement...';

    // Déterminer l'URL de l'API
    const baseUrl = href.substring(0, href.lastIndexOf('/'));
    const apiUrl = baseUrl.substring(0, baseUrl.lastIndexOf('/')) + '/api/reserver.php';
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'livre_id=' + livreId
    })
    .then(response => response.json())
    .then(data => {
        // Afficher le message d'alerte
        const alertDiv = document.createElement('div');
        alertDiv.className = data.success ? 'alert alert-success' : 'alert alert-error';
        alertDiv.textContent = data.message;
        
        const mainContent = document.querySelector('main');
        if (mainContent) {
            mainContent.insertBefore(alertDiv, mainContent.firstChild);
        }

        // Rendre le message invisible après 5 secondes
        setTimeout(() => {
            alertDiv.style.opacity = '0';
            alertDiv.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alertDiv.remove(), 500);
        }, 5000);

        if (data.success) {
            // Remplacer le bouton par "Déjà réservé"
            element.replaceWith(document.createTextNode('Déjà réservé'));
        } else {
            // Restaurer le bouton en cas d'erreur
            element.style.pointerEvents = 'auto';
            element.style.opacity = '1';
            element.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        element.style.pointerEvents = 'auto';
        element.style.opacity = '1';
        element.textContent = originalText;
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-error';
        alertDiv.textContent = 'Une erreur est survenue.';
        const mainContent = document.querySelector('main');
        if (mainContent) {
            mainContent.insertBefore(alertDiv, mainContent.firstChild);
        }
    });
}