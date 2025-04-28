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
                <a href="dashboard.php" class="active"><i class="fa-solid fa-table"></i></a>
            </div>
        </div>

        <table class="stocks-table">
            <thead>
                <tr>
                    <th>Symbole</th>
                    <th>Nom</th>
                    <th>Prix</th>
                    <th>Variation</th>
                    <th>Quantité</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="stock-symbol">AAPL</td>
                    <td class="stock-name">Apple Inc.</td>
                    <td class="stock-price">$175.50</td>
                    <td class="stock-change positive">+2.5%</td>
                    <td><input type="number" min="1" value="1" class="quantity-input"></td>
                    <td><button class="buy-btn">Acheter</button></td>
                </tr>
                <tr>
                    <td class="stock-symbol">GOOGL</td>
                    <td class="stock-name">Google Inc.</td>
                    <td class="stock-price">$135.25</td>
                    <td class="stock-change positive">+1.2%</td>
                    <td><input type="number" min="1" value="1" class="quantity-input"></td>
                    <td><button class="buy-btn">Acheter</button></td>
                </tr>
                <tr>
                    <td class="stock-symbol">MSFT</td>
                    <td class="stock-name">Microsoft Corp.</td>
                    <td class="stock-price">$310.80</td>
                    <td class="stock-change negative">-0.8%</td>
                    <td><input type="number" min="1" value="1" class="quantity-input"></td>
                    <td><button class="buy-btn">Acheter</button></td>
                </tr>
                <tr>
                    <td class="stock-symbol">AMZN</td>
                    <td class="stock-name">Amazon Inc.</td>
                    <td class="stock-price">$145.75</td>
                    <td class="stock-change positive">+3.1%</td>
                    <td><input type="number" min="1" value="1" class="quantity-input"></td>
                    <td><button class="buy-btn">Acheter</button></td>
                </tr>
                <tr>
                    <td class="stock-symbol">TSLA</td>
                    <td class="stock-name">Tesla Inc.</td>
                    <td class="stock-price">$180.30</td>
                    <td class="stock-change negative">-2.3%</td>
                    <td><input type="number" min="1" value="1" class="quantity-input"></td>
                    <td><button class="buy-btn">Acheter</button></td>
                </tr>
            </tbody>
        </table>

        <div class="chart-container">
            <!-- Ici vous pourriez ajouter un graphique si vous le souhaitez -->
        </div>
    </div>
</body>
</html>