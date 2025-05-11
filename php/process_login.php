<?php

// Ce fichier gère la connexion des utilisateurs en vérifiant leurs identifiants dans la base de données.


// Démarre une session pour stocker des données utilisateur
session_start();

// Vérifie que la méthode HTTP est POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupère l'email envoyé par le formulaire
    $email = $_POST['email'] ?? '';

    // Récupère le mot de passe envoyé par le formulaire
    $pass = $_POST['password'] ?? '';

    // Inclut la configuration de la base de données
    require_once 'db_config.php';

    try {
        // Connexion à la base de données
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);

        // Active les exceptions pour les erreurs PDO
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prépare une requête pour récupérer l'utilisateur
        $stmt = $conn->prepare("SELECT id, password FROM user WHERE email = :email");

        // Lie l'email à la requête préparée
        $stmt->bindParam(':email', $email);

        // Exécute la requête
        $stmt->execute();

        // Vérifie si un utilisateur est trouvé
        if ($stmt->rowCount() == 1) {
            // Récupère les données utilisateur
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Vérifie le mot de passe
            if (password_verify($pass, $user['password'])) {
                // Stocke l'ID utilisateur en session
                $_SESSION['user_id'] = $user['id'];

                // Stocke l'email en session
                $_SESSION['email'] = $email;

                // Redirige vers le tableau de bord
                header("Location: dashboard.php");
                exit();
            }
        }

        // Redirige en cas d'identifiants incorrects
        header("Location: login.php?error=1");
        exit();

    } catch(PDOException $e) {
        // Enregistre l'erreur dans les logs
        error_log("Erreur connexion: " . $e->getMessage());

        // Redirige en cas d'erreur
        header("Location: login.php?error=1");
        exit();
    }
} else {
    // Redirige si la méthode n'est pas POST
    header("Location: login.php");
    exit();
}