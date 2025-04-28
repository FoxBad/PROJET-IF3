<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Trader - Accueil</title>
    <link rel="stylesheet" href="theo_css.css?v=2">
    <!-- Style spécifique à l'index -->
    <link rel="stylesheet" href="index.css">
</head>
<body class="index-page">
    <div class="auth-container">
        <h1>Virtual Trader</h1>
        <div class="auth-options">
            <a href="login.php" class="auth-btn">Connexion</a>
            <a href="register.php" class="auth-btn">Inscription</a>
        </div>
    </div>
</body>
</html>