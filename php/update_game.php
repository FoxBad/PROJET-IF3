<?php
require_once 'db_config.php';

try {
    // Connexion à la base de données
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier s'il y a une date existante dans la table `date`
    echo "Vérification de la date existante...\n";
    $stmt = $conn->query("SELECT * FROM date ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        // Aucune date trouvée : insérer la date du jour
        $today = date('Y-m-d');
        echo "Aucune date trouvée. Insertion de la date du jour : $today\n";
        $stmt = $conn->prepare("INSERT INTO date (actual_date) VALUES (:today)");
        $stmt->bindParam(':today', $today, PDO::PARAM_STR);
        $stmt->execute();
        $current_date = $today;
    } else {
        // Une date existe : incrémenter d'un mois
        echo "Date existante trouvée. Incrémentation d'un mois...\n";
        $stmt = $conn->prepare("UPDATE date SET actual_date = DATE_ADD(actual_date, INTERVAL 1 MONTH)");
        $stmt->execute();

        // Récupérer la date mise à jour
        $stmt = $conn->prepare("SELECT actual_date FROM date LIMIT 1");
        $stmt->execute();
        $current_date = $stmt->fetchColumn();
    }

    echo "Date actuelle : $current_date\n";

    // Traiter les dividendes pour les joueurs
    echo "Traitement des dividendes pour les joueurs...\n";
    // Récupérer les dividendes pour le mois actuel
    $stmt = $conn->prepare(
        "SELECT p.id_user, p.id_action, a.dividende, a.date_dividende, p.nombre_action
         FROM portefeuille p
         JOIN action a ON p.id_action = a.id
         WHERE MONTH(a.date_dividende) = MONTH(:actual_date)"
    );
    $stmt->bindParam(':actual_date', $current_date, PDO::PARAM_STR);
    $stmt->execute();
    $dividends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Dividendes récupérés : " . json_encode($dividends) . "\n";

    // Mettre à jour les comptes des utilisateurs avec les dividendes
    foreach ($dividends as $dividend) {
        $total_dividend = $dividend['dividende'] * $dividend['nombre_action'];
        echo "Mise à jour de l'utilisateur {$dividend['id_user']} avec un dividende de : $total_dividend\n";
        $stmt = $conn->prepare("UPDATE user SET total_money = total_money + :total_dividend WHERE id = :user_id");
        $stmt->bindParam(':total_dividend', $total_dividend);
        $stmt->bindParam(':user_id', $dividend['id_user']);
        $stmt->execute();
    }

    // Vérifier si les prix historiques existent pour la date actuelle
    $stmt = $conn->prepare("SELECT COUNT(*) FROM history_price WHERE date = :actual_date");
    $stmt->bindParam(':actual_date', $current_date, PDO::PARAM_STR);
    $stmt->execute();
    $history_count = $stmt->fetchColumn();

    if ($history_count == 0) {
        // Initialiser les prix historiques pour la date actuelle
        echo "Initialisation des prix historiques pour la date actuelle...\n";
        $stmt = $conn->query("SELECT id, valeur FROM action");
        $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($actions as $action) {
            $stmt = $conn->prepare("INSERT INTO history_price (id_action, date, prix) VALUES (:id_action, :date, :prix)");
            $stmt->bindParam(':id_action', $action['id'], PDO::PARAM_INT);
            $stmt->bindParam(':date', $current_date, PDO::PARAM_STR);
            $stmt->bindParam(':prix', $action['valeur'], PDO::PARAM_STR);
            $stmt->execute();
        }
        echo "Prix historiques initialisés.\n";
    }

    // Mettre à jour les prix des actions
    echo "Mise à jour des prix des actions...\n";
    $stmt = $conn->prepare("SELECT a.id AS id, a.variation, h.prix FROM action a JOIN history_price h ON a.id = h.id_action WHERE h.date = :actual_date");
    $stmt->bindParam(':actual_date', $current_date, PDO::PARAM_STR);
    $stmt->execute();
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Actions récupérées : " . json_encode($actions) . "\n";

    foreach ($actions as $action) {
        // Calculer une nouvelle variation aléatoire
        $random_variation = rand(-3, 3);
        $new_variation = $action['variation'] + $random_variation;
        $new_variation = max(-10, min(10, $new_variation));
        echo "Action ID {$action['id']} - Nouvelle variation : $new_variation\n";

        // Calculer le nouveau prix basé sur la variation
        $new_price = $action['prix'] * (1 + $new_variation / 100);
        $new_price = max(1, $new_price);
        echo "Action ID {$action['id']} - Nouveau prix : $new_price\n";

        // Mettre à jour les variations et les prix dans la table `action`
        $stmt = $conn->prepare("UPDATE action SET variation = :new_variation, valeur = :new_price WHERE id = :id_action");
        $stmt->bindParam(':new_variation', $new_variation);
        $stmt->bindParam(':new_price', $new_price);
        $stmt->bindParam(':id_action', $action['id']);
        $stmt->execute();
    }

    echo "Mise à jour du jeu effectuée avec succès.\n";

} catch (PDOException $e) {
    // Gérer les erreurs de connexion ou d'exécution
    error_log("Erreur lors de la mise à jour du jeu : " . $e->getMessage());
    echo "Une erreur s'est produite lors de la mise à jour du jeu.";
}
?>
