<?php
require_once 'db_config.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Increment the game date by one month
    $stmt = $conn->prepare("SELECT MAX(date) AS current_date FROM history_price");
    $stmt->execute();
    $current_date = $stmt->fetchColumn();

    if ($current_date) {
        $new_date = date('Y-m-d', strtotime('+1 month', strtotime($current_date)));
    } else {
        $new_date = date('Y-m-01'); // Default to the first day of the current month
    }

    // 2. Distribute dividends to eligible players
    $stmt = $conn->prepare(
        "SELECT p.id_user, p.id_action, a.dividende FROM portefeuille p
         JOIN action a ON p.id_action = a.id
         WHERE a.date_dividende = :new_date"
    );
    $stmt->bindParam(':new_date', $new_date);
    $stmt->execute();
    $dividends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dividends as $dividend) {
        $stmt = $conn->prepare(
            "UPDATE user SET total_money = total_money + :dividende
             WHERE id = :id_user"
        );
        $total_dividend = $dividend['dividende'];
        $stmt->bindParam(':dividende', $total_dividend);
        $stmt->bindParam(':id_user', $dividend['id_user']);
        $stmt->execute();
    }

    // 3. Update action prices
    $stmt = $conn->prepare("SELECT id, prix FROM history_price WHERE date = :current_date");
    $stmt->bindParam(':current_date', $current_date);
    $stmt->execute();
    $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($prices as $price) {
        $random_change = rand(-3, 3); // Random change between -3 and +3
        $new_price = $price['prix'] * (1 + ($random_change / 100));

        // Ensure the price stays within bounds
        $new_price = max(1, $new_price); // Minimum price is 1€
        $new_price = min($price['prix'] * 1.1, max($price['prix'] * 0.9, $new_price));

        $stmt = $conn->prepare(
            "INSERT INTO history_price (id_action, date, prix) VALUES (:id_action, :new_date, :new_price)"
        );
        $stmt->bindParam(':id_action', $price['id']);
        $stmt->bindParam(':new_date', $new_date);
        $stmt->bindParam(':new_price', $new_price);
        $stmt->execute();
    }

    echo "Game updated successfully for date: $new_date\n";

} catch (PDOException $e) {
    error_log("Game Update Error: " . $e->getMessage());
    echo "Error updating the game: " . $e->getMessage();
}
?>