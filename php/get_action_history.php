<?php
// Inclut le fichier de configuration de la base de données
require_once 'db_config.php';

// Vérifie si la méthode de requête est GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Récupère et valide l'ID de l'action depuis les paramètres GET
    $action_id = filter_input(INPUT_GET, 'action_id', FILTER_VALIDATE_INT);

    // Si l'ID de l'action est invalide, retourne une erreur au format JSON
    if (!$action_id) {
        echo json_encode(['error' => 'ID d\'action invalide']);
        exit();
    }

    try {
        // Crée une connexion à la base de données avec PDO
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prépare une requête SQL pour récupérer l'historique des prix de l'action
        $stmt = $conn->prepare(
            "SELECT date, prix FROM history_price WHERE id_action = :action_id AND date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) ORDER BY date ASC"
        );

        // Lie l'ID de l'action au paramètre de la requête
        $stmt->bindParam(':action_id', $action_id, PDO::PARAM_INT);

        // Exécute la requête
        $stmt->execute();

        // Récupère les résultats sous forme de tableau associatif
        $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Retourne les résultats au format JSON
        echo json_encode($prices);
    } catch (PDOException $e) {
        // Enregistre l'erreur dans le journal et retourne une erreur au format JSON
        error_log("Erreur lors de la récupération de l'historique des actions : " . $e->getMessage());
        echo json_encode(['error' => 'Erreur de base de données']);
    }
} else {
    // Retourne une erreur si la méthode de requête n'est pas GET
    echo json_encode(['error' => 'Méthode de requête invalide']);
}
