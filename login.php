<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="theo_css.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <h1>Bienvenue</h1>
        <h2>Connexion</h2>


        <form action="process_login.php" method="POST">
            <div class="input-group">
                <label for="email">Adresse email</label>
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="input-group">
                <label for="password">Mot de passe</label>
                <i class="fas fa-lock input-icon"></i>
                <input type="password" id="password" name="password" required>
            </div>

            <input type="submit" value="Se connecter">
        </form>

        <p>Pas encore inscrit ? <a href="register.php">Créer un compte</a></p>
    </div>
</body>
</html>
