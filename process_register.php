<?php
session_start(); // Démarre une session pour stocker des données utilisateur
header('Content-Type: text/html; charset=utf-8'); // Définit l'encodage des caractères

// Vérifie que la méthode HTTP est POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.php"); // Redirige si la méthode n'est pas POST
    exit();
}

// Validation des données utilisateur
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL); // Nettoie l'email
$pass = trim($_POST['password'] ?? ''); // Supprime les espaces autour du mot de passe

// Vérifie si l'email est valide
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: register.php?error=invalid_email&email=".urlencode($_POST['email'] ?? ''));
    exit();
}

// Vérifie la longueur du mot de passe
if (strlen($pass) < 8) {
    header("Location: register.php?error=password_length&email=".urlencode($email));
    exit();
}

require_once 'db_config.php'; // Inclut la configuration de la base de données

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password); // Connexion à la base de données
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Active les exceptions pour les erreurs PDO
    $conn->exec("SET NAMES utf8mb4"); // Définit l'encodage UTF-8

    // Vérifie si l'email existe déjà
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        header("Location: register.php?error=email_exists&email=".urlencode($email));
        exit();
    }

    // Hachage du mot de passe
    $password_hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);

    // Insertion de l'utilisateur dans la base de données
    $stmt = $conn->prepare("INSERT INTO user (email, password, created_at) VALUES (:email, :password, NOW())");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password', $password_hash, PDO::PARAM_STR);

    if ($stmt->execute()) {
        $_SESSION['user_id'] = $conn->lastInsertId(); // Stocke l'ID utilisateur en session
        $_SESSION['email'] = $email; // Stocke l'email en session
        $_SESSION['new_user'] = true; // Indique qu'il s'agit d'un nouvel utilisateur

        header("Location: actions.php"); // Redirige vers la page des actions
        exit();
    }

    header("Location: register.php?error=db_error"); // Redirige en cas d'erreur
    exit();

} catch(PDOException $e) {
    error_log("Registration Error: ".$e->getMessage()); // Enregistre l'erreur dans les logs
    header("Location: register.php?error=db_error"); // Redirige en cas d'erreur
    exit();
}

// Ce fichier gère l'inscription des utilisateurs en validant les données et en les insérant dans la base de données.