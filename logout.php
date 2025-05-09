<?php
session_start(); // Démarre une session pour pouvoir la détruire
session_unset(); // Supprime toutes les variables de session
session_destroy(); // Détruit la session
header("Location: login.php"); // Redirige vers la page de connexion
exit();

// Ce fichier gère la déconnexion des utilisateurs.
?>