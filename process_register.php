<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// Vérification méthode POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.php");
    exit();
}

// Validation des données
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$pass = trim($_POST['password'] ?? '');

// Validation email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: register.php?error=invalid_email&email=".urlencode($_POST['email'] ?? ''));
    exit();
}

// Validation mot de passe
if (strlen($pass) < 8) {
    header("Location: register.php?error=password_length&email=".urlencode($email));
    exit();
}

require_once 'db_config.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8mb4");
    
    // Vérification existence email
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        header("Location: register.php?error=email_exists&email=".urlencode($email));
        exit();
    }
    
    // Hachage mot de passe
    $password_hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Insertion utilisateur
    $stmt = $conn->prepare("INSERT INTO user (email, password, created_at) VALUES (:email, :password, NOW())");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password', $password_hash, PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        $_SESSION['user_id'] = $conn->lastInsertId();
        $_SESSION['email'] = $email;
        $_SESSION['new_user'] = true; // Pour afficher un message de bienvenue
        
        // Redirection vers actions.php
        header("Location: actions.php");
        exit();
    }
    
    header("Location: register.php?error=db_error");
    exit();

} catch(PDOException $e) {
    error_log("Registration Error: ".$e->getMessage());
    header("Location: register.php?error=db_error");
    exit();
}