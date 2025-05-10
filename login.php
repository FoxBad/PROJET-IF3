<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <script src="https://kit.fontawesome.com/0f2e19a0b0.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="auth-container">
        <h1>Virtual Trader</h1>
        <h2>Connexion</h2>

        <form action="process_login.php" method="POST">
            <div class="input-group">
                <label for="email">Adresse email</label>
                <i class="fa-solid fa-envelope input-icon"></i>
                <input type="email" id="email" name="email" required 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="exemple@email.com">
            </div>

            <div class="input-group">
                <label for="password">Mot de passe</label>
                <i class="fa-solid fa-lock input-icon"></i>
                <input type="password" id="password" name="password" required
                       placeholder="••••••••" minlength="8">
            </div>

            <input type="submit" value="Se connecter">
        </form>

        <p>Pas encore inscrit ? <a href="register.php">Créer un compte</a></p>
    </div>
</body>
</html>

<!-- Ce fichier gère l'interface de connexion des utilisateurs. -->
<!-- Il inclut un formulaire pour saisir l'email et le mot de passe. -->
