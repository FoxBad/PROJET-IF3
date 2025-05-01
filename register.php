<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>
    <div class="auth-container register-container">
        <h1>Virtual Trader</h1>
        <h2>Créez votre compte</h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php
                $errors = [
                    'invalid_email' => 'Veuillez entrer une adresse email valide',
                    'password_length' => 'Le mot de passe doit contenir au moins 8 caractères',
                    'email_exists' => 'Cette adresse email est déjà utilisée',
                    'db_error' => 'Erreur système, veuillez réessayer'
                ];
                echo $errors[$_GET['error']] ?? 'Erreur lors de l\'inscription';
                ?>
            </div>
        <?php endif; ?>

        <form action="process_register.php" method="POST" novalidate>
            <div class="input-group">
                <label for="email">Adresse email</label>
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" id="email" name="email" required 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="exemple@email.com">
            </div>

            <div class="input-group">
                <label for="password">Mot de passe</label>
                <i class="fas fa-lock input-icon"></i>
                <input type="password" id="password" name="password" required
                       placeholder="••••••••" minlength="8">
            </div>

            <input type="submit" value="S'inscrire gratuitement" class="register-submit">
        </form>

        <p>Déjà membre ? <a href="login.php">Connectez-vous ici</a></p>
    </div>
</body>
</html>