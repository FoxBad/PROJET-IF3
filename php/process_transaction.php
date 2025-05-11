<?php
session_start(); // Démarre une session pour l'utilisateur
require_once 'db_config.php'; // Inclut le fichier de configuration de la base de données

// Vérifie si la méthode de requête est POST, sinon redirige vers la page des actions
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: actions.php");
    exit();
}

// Vérifie si l'utilisateur est connecté, sinon redirige vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Récupère les données de l'utilisateur et les valide
$user_id = $_SESSION['user_id'];
$action_id = filter_input(INPUT_POST, 'action_id', FILTER_VALIDATE_INT); // ID de l'action
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT); // Quantité d'actions
$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING); // Type de transaction (achat ou vente)

// Vérifie si les données sont valides, sinon redirige avec une erreur
if (!$action_id || !$quantity || !in_array($type, ['buy', 'sell'])) {
    header("Location: actions.php?error=invalid_input");
    exit();
}

try {
    // Connexion à la base de données
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->beginTransaction(); // Démarre une transaction

    // Récupère le prix de l'action
    $stmt = $conn->prepare("SELECT valeur AS prix FROM action WHERE id = :action_id");
    $stmt->bindParam(':action_id', $action_id, PDO::PARAM_INT);
    $stmt->execute();
    $price = $stmt->fetchColumn();

    if (!$price) {
        throw new Exception("Action introuvable."); // Erreur si l'action n'existe pas
    }

    // Récupère la date actuelle du jeu
    $stmt = $conn->prepare("SELECT actual_date FROM date ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $game_date = $stmt->fetchColumn();

    if (!$game_date) {
        throw new Exception("Date actuelle du jeu introuvable."); // Erreur si la date n'est pas trouvée
    }

    if ($type === 'buy') {
        // Vérifie si l'utilisateur a suffisamment d'argent pour acheter
        $stmt = $conn->prepare("SELECT total_money FROM user WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $total_money = $stmt->fetchColumn();

        $total_cost = $price * $quantity; // Coût total de l'achat
        if ($total_cost > $total_money) {
            throw new Exception("Fonds insuffisants."); // Erreur si fonds insuffisants
        }

        // Met à jour le solde de l'utilisateur
        $stmt = $conn->prepare("UPDATE user SET total_money = total_money - :total_cost WHERE id = :user_id");
        $stmt->bindParam(':total_cost', $total_cost);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        // Ajoute ou met à jour les actions dans le portefeuille de l'utilisateur
        $stmt = $conn->prepare("INSERT INTO portefeuille (id_user, id_action, date, nombre_action) VALUES (:user_id, :action_id, :game_date, :quantity) ON DUPLICATE KEY UPDATE nombre_action = nombre_action + :quantity");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action_id', $action_id);
        $stmt->bindParam(':game_date', $game_date);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->execute();

    } elseif ($type === 'sell') {
        // Vérifie si l'utilisateur possède suffisamment d'actions pour vendre
        $stmt = $conn->prepare("SELECT nombre_action FROM portefeuille WHERE id_user = :user_id AND id_action = :action_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action_id', $action_id);
        $stmt->execute();
        $owned_quantity = $stmt->fetchColumn();

        if ($quantity > $owned_quantity) {
            throw new Exception("Quantité insuffisante pour vendre."); // Erreur si quantité insuffisante
        }

        // Met à jour le portefeuille de l'utilisateur
        $stmt = $conn->prepare("UPDATE portefeuille SET nombre_action = nombre_action - :quantity WHERE id_user = :user_id AND id_action = :action_id");
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action_id', $action_id);
        $stmt->execute();

        // Met à jour le solde de l'utilisateur
        $stmt = $conn->prepare("UPDATE user SET total_money = total_money + :total_income WHERE id = :user_id");
        $total_income = $price * $quantity; // Revenu total de la vente
        $stmt->bindParam(':total_income', $total_income);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }

    // Enregistre la transaction dans l'historique
    $stmt = $conn->prepare("INSERT INTO transaction (id_user, id_action, date, nombre_action, prix_act, type) VALUES (:user_id, :action_id, :game_date, :quantity, :price, :type)");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':action_id', $action_id);
    $stmt->bindParam(':game_date', $game_date);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':type', $type);
    $stmt->execute();

    $conn->commit(); // Valide la transaction

    // Redirige avec un message de succès
    header("Location: actions.php?success=transaction_completed");
    exit();

} catch (Exception $e) {
    $conn->rollBack(); // Annule la transaction en cas d'erreur
    error_log("Transaction Error: " . $e->getMessage()); // Enregistre l'erreur dans les logs
    header("Location: actions.php?error=transaction_failed"); // Redirige avec un message d'erreur
    exit();
}