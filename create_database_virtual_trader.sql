CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    total_money DECIMAL(15,2) NOT NULL DEFAULT 10000.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE action (
    id INT AUTO_INCREMENT PRIMARY KEY,
    valeur DECIMAL(15,2),
    nom VARCHAR(100),
    description TEXT,
    date_dividende DATE,
    dividende DECIMAL(15,2),
    variation DECIMAL(15,2) DEFAULT 0.00
);

INSERT INTO action (valeur, nom, description, date_dividende, dividende, variation) VALUES
(175.50, 'Apple Inc.', 'Entreprise technologique', '2025-05-10', 1.50, 0.35),
(135.25, 'Google Inc.', 'Entreprise technologique', '2025-05-15', 1.20, 0.25),
(310.80, 'Microsoft Corp.', 'Entreprise technologique', '2025-05-20', 2.00, 0.10),
(145.75, 'Amazon Inc.', 'Entreprise de commerce électronique', '2025-05-25', 1.80, 0.30),
(180.30, 'Tesla Inc.', 'Entreprise automobile', '2025-05-30', 2.50, 0.20);

CREATE TABLE history_price (
    id_action INT,
    date DATE,
    prix DECIMAL(15,2),
    PRIMARY KEY (id_action, date),
    FOREIGN KEY (id_action) REFERENCES action(id)
);

CREATE TABLE transaction (
    id_user INT,
    id_action INT,
    date DATE,
    nombre_action INT,
    prix_act DECIMAL(15,2),
    type VARCHAR(10),
    FOREIGN KEY (id_user) REFERENCES user(id),
    FOREIGN KEY (id_action) REFERENCES action(id)
);

CREATE TABLE portefeuille (
    id_user INT,
    id_action INT,
    date DATE,
    nombre_action INT,
    PRIMARY KEY (id_user, id_action),
    FOREIGN KEY (id_user) REFERENCES user(id),
    FOREIGN KEY (id_action) REFERENCES action(id)
);

CREATE TABLE classement (
    id_joueur INT PRIMARY KEY,
    valeur_total DECIMAL(15,2),
    FOREIGN KEY (id_joueur) REFERENCES user(id)
);

CREATE TABLE date (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actual_date DATE
);

CREATE TABLE follow (
    follower_id INT,
    followed_id INT,
    PRIMARY KEY (follower_id, followed_id),
    FOREIGN KEY (follower_id) REFERENCES user(id),
    FOREIGN KEY (followed_id) REFERENCES user(id)
);
