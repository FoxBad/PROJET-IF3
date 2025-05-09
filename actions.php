<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT total_money FROM user WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $total_money = $stmt->fetchColumn();

    $stmt = $conn->prepare(
        "SELECT a.id, a.nom, a.description, h.prix, 
                (h.prix - LAG(h.prix) OVER (PARTITION BY h.id_action ORDER BY h.date)) / LAG(h.prix) OVER (PARTITION BY h.id_action ORDER BY h.date) * 100 AS variation,
                COALESCE(p.nombre_action, 0) AS nombre_action
         FROM action a
         JOIN history_price h ON a.id = h.id_action
         LEFT JOIN portefeuille p ON a.id = p.id_action AND p.id_user = :user_id
         WHERE h.date = (SELECT MAX(date) FROM history_price)"
    );
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching actions: " . $e->getMessage());
    $total_money = 0;
    $actions = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actions disponibles - Virtual Trader</title>
    <link rel="stylesheet" href="actions.css">
    <script src="https://kit.fontawesome.com/0f2e19a0b0.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="stocks-container">
        <div class="stocks-header">
            <h1 class="stocks-title">Actions disponibles</h1>
            <div class="stocks-filters">
                <input type="text" placeholder="Rechercher..." class="quantity-input">
                <select class="quantity-input">
                    <option>Trier par</option>
                    <option>Nom (A-Z)</option>
                    <option>Prix (croissant)</option>
                    <option>Prix (décroissant)</option>
                </select>
            </div>
            <div class="user-money">
                <strong>Argent disponible :</strong> <?= number_format($total_money, 2) ?> €
            </div>
        </div>

        <div class="dashboard-link">
            <a href="dashboard.php" class="dashboard-btn">Retour au Tableau de Bord</a>
        </div>

        <table class="stocks-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Prix</th>
                    <th>Variation</th>
                    <th>Quantité possédée</th>
                    <th>Quantité</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($actions as $action): ?>
                    <tr>
                        <td class="stock-name"><?= htmlspecialchars($action['nom']) ?></td>
                        <td class="stock-description"><?= htmlspecialchars($action['description']) ?></td>
                        <td class="stock-price"><?= number_format($action['prix'], 2) ?> €</td>
                        <td class="stock-change <?= $action['variation'] >= 0 ? 'positive' : 'negative' ?>">
                            <?= number_format($action['variation'], 2) ?>%
                        </td>
                        <td class="stock-owned">
                            <?= $action['nombre_action'] ?>
                        </td>
                        <td>
                            <form action="process_transaction.php" method="POST">
                                <input type="hidden" name="action_id" value="<?= $action['id'] ?>">
                                <input type="number" name="quantity" min="1" value="1" class="quantity-input">
                                <button type="submit" name="type" value="buy" class="buy-btn">Acheter</button>
                                <button type="submit" name="type" value="sell" class="sell-btn">Vendre</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="chart-container">
            <!-- Ici vous pourriez ajouter un graphique si vous le souhaitez -->
        </div>
    </div>
</body>
</html>