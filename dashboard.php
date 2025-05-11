<?php
session_start();
require_once 'db_config.php';

// Redirection si non connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Message de bienvenue pour les nouveaux utilisateurs
$welcome_message = '';
if (isset($_SESSION['new_user'])) {
    $welcome_message = 'Bienvenue parmi nous !';
    unset($_SESSION['new_user']);
}

// Récupération des infos utilisateur
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("SELECT created_at FROM user WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Dashboard Error: ".$e->getMessage());
}

// Ajout des données du portefeuille, de l'argent et des transactions
$portfolio_value = 0.00;
$cash_available = 0.00;
$recent_transactions = [];

try {
    // Récupération de l'argent disponible
    $stmt = $conn->prepare("SELECT total_money FROM user WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $cash_available = $stmt->fetchColumn();

    // Calcul de la valeur totale du portefeuille
    $stmt = $conn->prepare(
        "SELECT p.nombre_action, a.valeur, a.nom 
         FROM portefeuille p 
         JOIN action a ON p.id_action = a.id 
         WHERE p.id_user = :id"
    );
    $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $portfolio = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($portfolio as $item) {
        $portfolio_value += $item['nombre_action'] * $item['valeur'];
    }

    // Récupération des dernières transactions
    $stmt = $conn->prepare(
        "SELECT t.date, t.nombre_action, t.prix_act, t.type, a.nom 
         FROM transaction t 
         JOIN action a ON t.id_action = a.id 
         WHERE t.id_user = :id 
         ORDER BY t.date DESC 
         LIMIT 5"
    );
    $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard Data Error: " . $e->getMessage());
}

// Ajout de la récupération du classement des joueurs
$leaderboard = [];
try {
    $stmt = $conn->prepare(
        "SELECT u.email, 
                (COALESCE(SUM(p.nombre_action * a.valeur), 0) + u.total_money) AS total_value
         FROM user u
         LEFT JOIN portefeuille p ON u.id = p.id_user
         LEFT JOIN action a ON p.id_action = a.id
         GROUP BY u.id
         ORDER BY total_value DESC"
    );
    $stmt->execute();
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Leaderboard Error: " . $e->getMessage());
}

// Récupération des joueurs suivis et leurs deux dernières transactions
$followed_players = [];
try {
    $stmt = $conn->prepare(
        "SELECT f.followed_id, u.email, t.date, t.nombre_action, t.prix_act, t.type, a.nom
         FROM follow f
         JOIN user u ON f.followed_id = u.id
         LEFT JOIN transaction t ON t.id_user = u.id
         LEFT JOIN action a ON t.id_action = a.id
         WHERE f.follower_id = :user_id
         ORDER BY t.date DESC"
    );
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Regrouper les transactions par joueur
    foreach ($raw_data as $row) {
        $followed_id = $row['followed_id'];
        if (!isset($followed_players[$followed_id])) {
            $followed_players[$followed_id] = [
                'email' => $row['email'],
                'transactions' => []
            ];
        }
        if (count($followed_players[$followed_id]['transactions']) < 2) {
            $followed_players[$followed_id]['transactions'][] = [
                'date' => $row['date'],
                'nombre_action' => $row['nombre_action'],
                'prix_act' => $row['prix_act'],
                'type' => $row['type'],
                'nom' => $row['nom']
            ];
        }
    }
} catch (PDOException $e) {
    error_log("Followed Players Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord | Virtual Trader</title>
    <script src="https://kit.fontawesome.com/0f2e19a0b0.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <nav class="dashboard-nav">
                <a href="actions.php"><i class="fa-solid fa-chart-line"></i> Actions</a>
                <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
            </nav>
            <div class="user-profile">
                <span><?= htmlspecialchars($_SESSION['email']) ?></span>
                <small>Membre depuis <?= date('d/m/Y', strtotime($user['created_at'] ?? 'now')) ?></small>
            </div>
        </header>
        
        <main>
            <?php if ($welcome_message): ?>
                <div class="welcome-banner">
                    <h2><?= $welcome_message ?></h2>
                    <p>Commencez par explorer les actions disponibles.</p>
                    <a href="actions.php" class="cta-button">Voir les actions</a>
                </div>
            <?php endif; ?>
            
            <section class="dashboard-content">
                <h2>Votre activité</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><i class="fa-solid fa-wallet"></i> Portefeuille d'action</h3>
                        <?php if (empty($portfolio)): ?>
                            <p>Vous ne possédez aucune action.</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($portfolio as $item): ?>
                                    <li>
                                        <?= htmlspecialchars($item['nombre_action']) ?> x <?= htmlspecialchars($item['nom']) ?> 
                                        à <?= number_format($item['valeur'], 2, ',', ' ') ?> € chacun
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <p><strong>Total :</strong> <?= number_format($portfolio_value, 2, ',', ' ') ?> €</p>
                        <?php endif; ?>
                    </div>
                    <div class="stat-card">
                        <h3><i class="fa-solid fa-money-bill"></i> Argent possédé</h3>
                        <p><?= number_format($cash_available, 2, ',', ' ') ?> €</p>
                    </div>
                    <div class="stat-card">
                        <h3><i class="fa-solid fa-money-bill-transfer"></i> Transactions récentes</h3>
                        <?php if (empty($recent_transactions)): ?>
                            <p>Aucune transaction récente</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($recent_transactions as $transaction): ?>
                                    <li>
                                        <?= htmlspecialchars($transaction['date']) ?> : 
                                        <?= htmlspecialchars($transaction['type']) ?> 
                                        <?= htmlspecialchars($transaction['nombre_action']) ?> 
                                        actions de <?= htmlspecialchars($transaction['nom']) ?> 
                                        à <?= number_format($transaction['prix_act'], 2, ',', ' ') ?> €
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Ajout de la section pour afficher le classement des joueurs -->
            <section class="dashboard-content">
                <h2>Classement des joueurs</h2>
                <div class="stat-card">
                    <h3><i class="fa-solid fa-trophy"></i> Top joueurs</h3>
                    <?php if (empty($leaderboard)): ?>
                        <p>Aucun joueur dans le classement.</p>
                    <?php else: ?>
                        <ol>
                            <?php foreach ($leaderboard as $player): ?>
                                <li>
                                    <?= htmlspecialchars($player['email']) ?> : 
                                    <?= number_format($player['total_value'], 2, ',', ' ') ?> €
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Ajout de la section pour rechercher et suivre des joueurs -->
            <section class="dashboard-content">
                <div class="follow-section">
                    <h2>Rechercher un joueur</h2>
                    <form id="search-player-form">
                        <input type="text" id="search-player-input" placeholder="Entrez un email...">
                        <button type="submit">Rechercher</button>
                    </form>
                    <ul id="search-results"></ul>
                </div>
            </section>

            <!-- Ajout de la section pour afficher les joueurs suivis et leurs deux dernières transactions -->
            <section class="dashboard-content">
                <h2>Joueurs suivis</h2>
                <div class="stat-card">
                    <h3><i class="fa-solid fa-user-friends"></i> Joueurs suivis</h3>
                    <?php if (empty($followed_players)): ?>
                        <p>Vous ne suivez aucun joueur.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($followed_players as $player): ?>
                                <li>
                                    <strong><?= htmlspecialchars($player['email']) ?></strong>
                                    <ul>
                                        <?php if (empty($player['transactions'])): ?>
                                            <li>Aucune transaction récente</li>
                                        <?php else: ?>
                                            <?php foreach ($player['transactions'] as $transaction): ?>
                                                <li>
                                                    <?= htmlspecialchars($transaction['date']) ?> :
                                                    <?= htmlspecialchars($transaction['type']) ?>
                                                    <?= htmlspecialchars($transaction['nombre_action']) ?> actions de
                                                    <?= htmlspecialchars($transaction['nom']) ?> à
                                                    <?= number_format($transaction['prix_act'], 2, ',', ' ') ?> €
                                                </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </ul>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        document.getElementById('search-player-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const query = document.getElementById('search-player-input').value;

            fetch(`follow_player.php?search=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const resultsContainer = document.getElementById('search-results');
                    resultsContainer.innerHTML = '';

                    if (data.error) {
                        resultsContainer.innerHTML = `<li>${data.error}</li>`;
                    } else {
                        data.forEach(user => {
                            const li = document.createElement('li');
                            li.textContent = user.email;

                            const followButton = document.createElement('button');
                            followButton.textContent = 'Suivre';
                            followButton.addEventListener('click', () => {
                                const formData = new URLSearchParams();
                                formData.append('action', 'follow');
                                formData.append('target_user_id', user.id);

                                fetch('follow_player.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: formData.toString()
                                }).then(() => alert('Joueur suivi !'));
                            });

                            li.appendChild(followButton);
                            resultsContainer.appendChild(li);
                        });
                    }
                });
        });
    </script>
</body>
</html>