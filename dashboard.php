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
    
    $stmt = $conn->prepare("SELECT created_at FROM users WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Dashboard Error: ".$e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord | Virtual Trader</title>
    <script src="https://kit.fontawesome.com/0f2e19a0b0.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="theo_css.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .welcome-banner {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            animation: fadeIn 0.6s;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h1>Virtual Trader</h1>
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
                        <h3><i class="fa-solid fa-wallet"></i> Portefeuille</h3>
                        <p>0.00 €</p>
                    </div>
                    <div class="stat-card">
                        <h3><i class="fa-solid fa-chart-simple"></i> Performance</h3>
                        <p>+0%</p>
                    </div>
                    <div class="stat-card">
                        <h3><i class="fa-solid fa-money-bill-transfer"></i> Transactions</h3>
                        <p>Aucune</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>