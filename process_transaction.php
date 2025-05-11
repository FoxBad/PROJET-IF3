<?php
session_start();
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: actions.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$action_id = filter_input(INPUT_POST, 'action_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);

if (!$action_id || !$quantity || !in_array($type, ['buy', 'sell'])) {
    header("Location: actions.php?error=invalid_input");
    exit();
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT valeur AS prix FROM action WHERE id = :action_id");
    $stmt->bindParam(':action_id', $action_id, PDO::PARAM_INT);
    $stmt->execute();
    $price = $stmt->fetchColumn();

    if (!$price) {
        throw new Exception("Action introuvable.");
    }

    // Récupérer la date actuelle du jeu
    $stmt = $conn->prepare("SELECT actual_date FROM date ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $game_date = $stmt->fetchColumn();

    if (!$game_date) {
        throw new Exception("Date actuelle du jeu introuvable.");
    }

    if ($type === 'buy') {
        $stmt = $conn->prepare("SELECT total_money FROM user WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $total_money = $stmt->fetchColumn();

        $total_cost = $price * $quantity;
        if ($total_cost > $total_money) {
            throw new Exception("Fonds insuffisants.");
        }

        $stmt = $conn->prepare("UPDATE user SET total_money = total_money - :total_cost WHERE id = :user_id");
        $stmt->bindParam(':total_cost', $total_cost);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO portefeuille (id_user, id_action, date, nombre_action) VALUES (:user_id, :action_id, :game_date, :quantity) ON DUPLICATE KEY UPDATE nombre_action = nombre_action + :quantity");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action_id', $action_id);
        $stmt->bindParam(':game_date', $game_date);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->execute();

    } elseif ($type === 'sell') {
        $stmt = $conn->prepare("SELECT nombre_action FROM portefeuille WHERE id_user = :user_id AND id_action = :action_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action_id', $action_id);
        $stmt->execute();
        $owned_quantity = $stmt->fetchColumn();

        if ($quantity > $owned_quantity) {
            throw new Exception("Quantité insuffisante pour vendre.");
        }

        $stmt = $conn->prepare("UPDATE portefeuille SET nombre_action = nombre_action - :quantity WHERE id_user = :user_id AND id_action = :action_id");
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action_id', $action_id);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE user SET total_money = total_money + :total_income WHERE id = :user_id");
        $total_income = $price * $quantity;
        $stmt->bindParam(':total_income', $total_income);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }

    $stmt = $conn->prepare("INSERT INTO transaction (id_user, id_action, date, nombre_action, prix_act, type) VALUES (:user_id, :action_id, :game_date, :quantity, :price, :type)");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':action_id', $action_id);
    $stmt->bindParam(':game_date', $game_date);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':type', $type);
    $stmt->execute();

    $conn->commit();

    header("Location: actions.php?success=transaction_completed");
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Transaction Error: " . $e->getMessage());
    header("Location: actions.php?error=transaction_failed");
    exit();
}