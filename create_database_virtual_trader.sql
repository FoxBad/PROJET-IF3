
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    total_money DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

CREATE TABLE action (
    id INT AUTO_INCREMENT PRIMARY KEY,
    valeur DECIMAL(15,2),
    nom VARCHAR(100),
    description TEXT,
    date_dividende DATE,
    dividende DECIMAL(15,2)
);

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
