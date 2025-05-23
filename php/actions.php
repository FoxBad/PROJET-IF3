<?php
// Démarrage de la session pour gérer les données utilisateur
session_start();

// Inclusion du fichier de configuration de la base de données
require_once 'db_config.php';

// Vérification si l'utilisateur est connecté, sinon redirection vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Récupération de l'ID utilisateur depuis la session
$user_id = $_SESSION['user_id'];

try {
    // Connexion à la base de données avec PDO
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération de l'argent total de l'utilisateur
    $stmt = $conn->prepare("SELECT total_money FROM user WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $total_money = $stmt->fetchColumn();

    // Gestion des paramètres de recherche et de filtre
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filter = isset($_GET['filter']) ? $_GET['filter'] : '';

    // Construction de la requête SQL pour récupérer les actions disponibles
    $query = "SELECT a.id, a.nom, a.description, a.valeur AS prix, a.variation, COALESCE(p.nombre_action, 0) AS nombre_action
              FROM action a
              LEFT JOIN portefeuille p ON a.id = p.id_action AND p.id_user = :user_id";

    $conditions = [];
    $params = [':user_id' => $user_id];

    // Ajout de la condition de recherche si un terme est fourni
    if ($search !== '') {
        $conditions[] = "a.nom LIKE :search";
        $params[':search'] = "%$search%";
    }

    // Application des filtres selon le choix de l'utilisateur
    switch ($filter) {
        case 'name':
            $query .= " ORDER BY a.nom ASC";
            break;
        case 'price_asc':
            $query .= " ORDER BY a.valeur ASC";
            break;
        case 'price_desc':
            $query .= " ORDER BY a.valeur DESC";
            break;
        case 'progress_1m':
            $query .= " ORDER BY a.variation_1m DESC";
            break;
        case 'progress_1y':
            $query .= " ORDER BY a.variation_1y DESC";
            break;
    }

    // Ajout des conditions à la requête si nécessaire
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    // Exécution de la requête pour récupérer les actions
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupération de la date actuelle du jeu
    $stmt = $conn->prepare("SELECT actual_date FROM date ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $game_date = $stmt->fetchColumn();

} catch (PDOException $e) {
    // Gestion des erreurs en cas de problème avec la base de données
    error_log("Error fetching actions: " . $e->getMessage());
    $total_money = 0;
    $actions = [];
    $game_date = null;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Métadonnées de la page -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actions disponibles - Virtual Trader</title>

    <!-- Inclusion des bibliothèques externes -->
    <script src="https://kit.fontawesome.com/0f2e19a0b0.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../style/actions.css">
</head>
<body>
    <div class="stocks-container">
        <!-- En-tête de la section des actions -->
        <div class="stocks-header">
            <h1 class="stocks-title">Actions disponibles</h1>

            <!-- Filtres de recherche et de tri -->
            <div class="stocks-filters">
                <input type="text" id="search-input" placeholder="Rechercher par nom..." class="quantity-input">
                <select id="filter-select" class="quantity-input">
                    <option value="">Trier par</option>
                    <option value="name">Nom (A-Z)</option>
                    <option value="price_asc">Prix (croissant)</option>
                    <option value="price_desc">Prix (décroissant)</option>
                    <option value="progress_1m">Progression (1 mois)</option>
                    <option value="progress_1y">Progression (1 an)</option>
                </select>
                <button id="apply-filters-btn" class="dashboard-btn">Appliquer</button>
            </div>

            <!-- Affichage de l'argent disponible de l'utilisateur -->
            <div class="user-money">
                <strong>Argent disponible :</strong> <?= number_format($total_money, 2) ?> €
            </div>

            <!-- Affichage de la date actuelle du jeu -->
            <div class="game-date">
                <strong>Date du jeu :</strong> <?= $game_date ? htmlspecialchars($game_date) : 'Non disponible' ?>
            </div>
        </div>

        <!-- Lien vers le tableau de bord -->
        <div class="dashboard-link">
            <a href="dashboard.php" class="dashboard-btn">Retour au Tableau de Bord</a>
        </div>

        <!-- Tableau des actions disponibles -->
        <table class="stocks-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Prix</th>
                    <th>Variation</th>
                    <th>Quantité possédée</th>
                    <th>Quantité</th>
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
                            <!-- Formulaire pour acheter ou vendre des actions -->
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

        <!-- Sélecteur et graphique pour les actions -->
        <div class="chart-selector">
            <label for="action-select">Choisir une action :</label>
            <select id="action-select" class="quantity-input">
                <option value="">???</option>
                <?php foreach ($actions as $action): ?>
                    <option value="<?= $action['id'] ?>"><?= htmlspecialchars($action['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <button id="view-chart-btn" class="dashboard-btn">Voir le graphique</button>
        </div>

        <div class="chart-container">
            <!-- Conteneur pour afficher le graphique -->
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chartContainer = document.querySelector('.chart-container');
            const chartCanvas = document.createElement('canvas');
            chartCanvas.id = 'priceChart';
            chartContainer.appendChild(chartCanvas);

            let currentChart = null; // Variable pour stocker le graphique actuel

            // Fonction pour récupérer l'historique des prix d'une action
            const fetchPriceHistory = async (actionId) => {
                try {
                    const response = await fetch(`get_action_history.php?action_id=${actionId}`);
                    const data = await response.json();

                    if (data.error) {
                        console.error(data.error);
                        return;
                    }

                    const labels = data.map(entry => entry.date);
                    const prices = data.map(entry => entry.prix);

                    // Détruire le graphique existant s'il y en a un
                    if (currentChart) {
                        currentChart.destroy();
                    }

                    // Créer un nouveau graphique
                    currentChart = new Chart(chartCanvas, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Prix sur 12 mois',
                                data: prices,
                                borderColor: 'rgba(75, 192, 192, 1)',
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: true,
                                    text: 'Évolution du prix de l\'action'
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error fetching price history:', error);
                }
            };

            // Gestion des événements pour afficher le graphique
            const actionSelect = document.getElementById('action-select');
            const viewChartBtn = document.getElementById('view-chart-btn');

            viewChartBtn.addEventListener('click', () => {
                const selectedActionId = actionSelect.value;
                if (selectedActionId) {
                    fetchPriceHistory(selectedActionId);
                } else {
                    alert('Veuillez sélectionner une action.');
                }
            });

            // Gestion des filtres de recherche et de tri
            const searchInput = document.getElementById('search-input');
            const filterSelect = document.getElementById('filter-select');
            const applyFiltersBtn = document.getElementById('apply-filters-btn');

            applyFiltersBtn.addEventListener('click', () => {
                const searchQuery = searchInput.value;
                const filterOption = filterSelect.value;

                const url = new URL(window.location.href);
                url.searchParams.set('search', searchQuery);
                url.searchParams.set('filter', filterOption);

                window.location.href = url;
            });
        });
    </script>
</body>
</html>