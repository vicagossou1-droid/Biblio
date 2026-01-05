<?php
// Paramètres de connexion
$host     = "localhost";
$username = "root";
$password = "";
$db = "ma_bibliothèque";

// Activer les rapports d'erreurs mysqli (utile en dev)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Connexion avec mysqli
$conn = mysqli_connect($host, $username, $password, $db);

// Vérification de la connexion
if (!$conn) {
    die("La connexion a échoué : " . mysqli_connect_error());
}

// Forcer l'encodage en utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Base URL du projet (modifiez si nécessaire)
if (!defined('BASE_URL')) {
    define('BASE_URL', '/ma_bibliotheque');
}
?>