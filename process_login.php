<?php
session_start(); // Démarre une session pour stocker des données utilisateur

if ($_SERVER["REQUEST_METHOD"] == "POST") { // Vérifie que la méthode HTTP est POST
    $email = $_POST['email'] ?? ''; // Récupère l'email envoyé par le formulaire
    $pass = $_POST['password'] ?? ''; // Récupère le mot de passe envoyé par le formulaire

    require_once 'db_config.php'; // Inclut la configuration de la base de données

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password); // Connexion à la base de données
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Active les exceptions pour les erreurs PDO

        $stmt = $conn->prepare("SELECT id, password FROM user WHERE email = :email"); // Prépare une requête pour récupérer l'utilisateur
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() == 1) { // Vérifie si un utilisateur est trouvé
            $user = $stmt->fetch(PDO::FETCH_ASSOC); // Récupère les données utilisateur
            if (password_verify($pass, $user['password'])) { // Vérifie le mot de passe
                $_SESSION['user_id'] = $user['id']; // Stocke l'ID utilisateur en session
                $_SESSION['email'] = $email; // Stocke l'email en session
                header("Location: dashboard.php"); // Redirige vers le tableau de bord
                exit();
            }
        }

        header("Location: login.php?error=1"); // Redirige en cas d'identifiants incorrects
        exit();

    } catch(PDOException $e) {
        error_log("Erreur connexion: " . $e->getMessage()); // Enregistre l'erreur dans les logs
        header("Location: login.php?error=1"); // Redirige en cas d'erreur
        exit();
    }
} else {
    header("Location: login.php"); // Redirige si la méthode n'est pas POST
    exit();
}

// Ce fichier gère la connexion des utilisateurs en vérifiant leurs identifiants.