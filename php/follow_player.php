<?php
// Démarrage de la session utilisateur
session_start();

// Inclusion du fichier de configuration de la base de données
require_once 'db_config.php';

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Redirection vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: login.php");
    exit();
}

// Récupération de l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Vérification si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération de l'action et de l'ID de l'utilisateur cible
    $action = $_POST['action'] ?? '';
    $target_user_id = filter_input(INPUT_POST, 'target_user_id', FILTER_VALIDATE_INT);

    // Log des données reçues pour le débogage
    error_log("Action: $action, Target User ID: $target_user_id");

    // Validation des entrées
    if (!$target_user_id || !in_array($action, ['follow', 'unfollow'])) {
        // Log des erreurs d'entrée
        error_log("Invalid input: Action - $action, Target User ID - $target_user_id");
        echo json_encode(['error' => 'Invalid input']);
        exit();
    }

    try {
        // Connexion à la base de données
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Préparation de la requête SQL en fonction de l'action (suivre ou ne plus suivre)
        if ($action === 'follow') {
            $stmt = $conn->prepare("INSERT IGNORE INTO follow (follower_id, followed_id) VALUES (:follower_id, :followed_id)");
        } else {
            $stmt = $conn->prepare("DELETE FROM follow WHERE follower_id = :follower_id AND followed_id = :followed_id");
        }

        // Liaison des paramètres
        $stmt->bindParam(':follower_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':followed_id', $target_user_id, PDO::PARAM_INT);

        // Exécution de la requête
        $stmt->execute();

        // Log de succès de l'exécution SQL
        error_log("SQL executed successfully: Action - $action, Follower ID - $user_id, Followed ID - $target_user_id");

        // Réponse JSON indiquant le succès
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // Log des erreurs SQL
        error_log("Error in follow/unfollow: " . $e->getMessage());
        echo json_encode(['error' => 'Database error']);
    }

// Vérification si la requête est de type GET
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Récupération et nettoyage du paramètre de recherche
    $search = trim($_GET['search'] ?? '');

    try {
        // Connexion à la base de données
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Préparation de la requête SQL pour rechercher des utilisateurs
        $stmt = $conn->prepare("SELECT id, email FROM user WHERE email LIKE :search AND id != :user_id LIMIT 10");
        $search = "%$search%";
        $stmt->bindParam(':search', $search, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        // Exécution de la requête
        $stmt->execute();

        // Récupération des résultats et envoi en réponse JSON
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($results);
    } catch (PDOException $e) {
        // Log des erreurs SQL
        error_log("Error in search: " . $e->getMessage());
        echo json_encode(['error' => 'Database error']);
    }

// Gestion des méthodes de requête non supportées
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
