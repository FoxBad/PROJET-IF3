<?php

// Démarre une session pour pouvoir la détruire
session_start();

// Supprime toutes les variables de session
session_unset();

// Détruit la session
session_destroy();

// Redirige vers la page de connexion
header("Location: login.php");

// Termine le script
exit();
?>