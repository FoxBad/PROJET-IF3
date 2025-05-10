<?php
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action_id = filter_input(INPUT_GET, 'action_id', FILTER_VALIDATE_INT);

    if (!$action_id) {
        echo json_encode(['error' => 'Invalid action ID']);
        exit();
    }

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare(
            "SELECT date, prix FROM history_price WHERE id_action = :action_id AND date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) ORDER BY date ASC"
        );
        $stmt->bindParam(':action_id', $action_id, PDO::PARAM_INT);
        $stmt->execute();
        $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($prices);
    } catch (PDOException $e) {
        error_log("Error fetching action history: " . $e->getMessage());
        echo json_encode(['error' => 'Database error']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
