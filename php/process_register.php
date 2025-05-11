
<?php

// Ce fichier gère l'inscription des utilisateurs en validant les données et en les insérant dans la base de données.


// Démarre une session pour stocker des données utilisateur
session_start();

// Définit l'encodage des caractères
header('Content-Type: text/html; charset=utf-8');

// Vérifie que la méthode HTTP est POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Redirige si la méthode n'est pas POST
    header("Location: register.php");
    exit();
}

// Validation des données utilisateur
// Nettoie l'email
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

// Supprime les espaces autour du mot de passe
$pass = trim($_POST['password'] ?? '');

// Vérifie si l'email est valide
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Redirige en cas d'email invalide
    header("Location: register.php?error=invalid_email&email=".urlencode($_POST['email'] ?? ''));
    exit();
}

// Vérifie la longueur du mot de passe
if (strlen($pass) < 8) {
    // Redirige si le mot de passe est trop court
    header("Location: register.php?error=password_length&email=".urlencode($email));
    exit();
}

// Inclut la configuration de la base de données
require_once 'db_config.php';

try {
    // Connexion à la base de données
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);

    // Active les exceptions pour les erreurs PDO
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Définit l'encodage UTF-8
    $conn->exec("SET NAMES utf8mb4");

    // Vérifie si l'email existe déjà
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = :email LIMIT 1");

    // Lie l'email à la requête préparée
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);

    // Exécute la requête
    $stmt->execute();

    // Redirige si l'email existe déjà
    if ($stmt->rowCount() > 0) {
        header("Location: register.php?error=email_exists&email=".urlencode($email));
        exit();
    }

    // Hachage du mot de passe
    $password_hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);

    // Insertion de l'utilisateur dans la base de données
    $stmt = $conn->prepare("INSERT INTO user (email, password, created_at) VALUES (:email, :password, NOW())");

    // Lie les paramètres à la requête préparée
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password', $password_hash, PDO::PARAM_STR);

    // Exécute la requête et vérifie si l'insertion a réussi
    if ($stmt->execute()) {
        // Stocke l'ID utilisateur en session
        $_SESSION['user_id'] = $conn->lastInsertId();

        // Stocke l'email en session
        $_SESSION['email'] = $email;

        // Indique qu'il s'agit d'un nouvel utilisateur
        $_SESSION['new_user'] = true;

        // Redirige vers la page des actions
        header("Location: actions.php");
        exit();
    }

    // Redirige en cas d'erreur
    header("Location: register.php?error=db_error");
    exit();

} catch(PDOException $e) {
    // Enregistre l'erreur dans les logs
    error_log("Registration Error: ".$e->getMessage());

    // Redirige en cas d'erreur
    header("Location: register.php?error=db_error");
    exit();
}

