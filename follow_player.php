<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target_user_id = filter_input(INPUT_POST, 'target_user_id', FILTER_VALIDATE_INT);

    error_log("Action: $action, Target User ID: $target_user_id"); // Log des données reçues

    if (!$target_user_id || !in_array($action, ['follow', 'unfollow'])) {
        error_log("Invalid input: Action - $action, Target User ID - $target_user_id"); // Log des erreurs d'entrée
        echo json_encode(['error' => 'Invalid input']);
        exit();
    }

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($action === 'follow') {
            $stmt = $conn->prepare("INSERT IGNORE INTO follow (follower_id, followed_id) VALUES (:follower_id, :followed_id)");
        } else {
            $stmt = $conn->prepare("DELETE FROM follow WHERE follower_id = :follower_id AND followed_id = :followed_id");
        }

        $stmt->bindParam(':follower_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':followed_id', $target_user_id, PDO::PARAM_INT);
        $stmt->execute();

        error_log("SQL executed successfully: Action - $action, Follower ID - $user_id, Followed ID - $target_user_id"); // Log de succès

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Error in follow/unfollow: " . $e->getMessage()); // Log des erreurs SQL
        echo json_encode(['error' => 'Database error']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = trim($_GET['search'] ?? '');

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT id, email FROM user WHERE email LIKE :search AND id != :user_id LIMIT 10");
        $search = "%$search%";
        $stmt->bindParam(':search', $search, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($results);
    } catch (PDOException $e) {
        error_log("Error in search: " . $e->getMessage());
        echo json_encode(['error' => 'Database error']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
