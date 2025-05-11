# Virtual Trader

## Instructions pour faire fonctionner le projet

### Étape 1 : Télécharger et extraire le fichier ZIP
Téléchargez le fichier ZIP contenant le projet et extrayez son contenu.

### Étape 2 : Placer le dossier dans XAMPP
Placez le dossier `PROJET-IF3` dans le répertoire `C:\xampp\htdocs`.

### Étape 3 : Lancer Apache et MySQL
1. Ouvrez XAMPP.
2. Démarrez les services Apache et MySQL.
3. Accédez à la page d'administration MySQL via le tableau de bord XAMPP.

### Étape 4 : Créer la base de données
1. Créez une base de données nommée `virtual_trader`.
2. Allez dans l'onglet **Importer**.
3. Sélectionnez le fichier `virtual_trader.sql`.
4. Vérifiez que toutes les tables et les enregistrements ont été créés avec succès.

### Étape 5 : Configurer une tâche planifiée
1. Ouvrez le Planificateur de tâches de Windows.
2. Ajoutez une nouvelle tâche de base qui s'exécutera toutes les minutes.
3. Pour l'action, choisissez **Démarrer un programme**.
4. Configurez les paramètres suivants :
   - **Programme/script** : `C:\xampp\php\php.exe`
   - **Argument** : `-f C:\xampp\htdocs\PROJET-IF3\php\update_game.php`
5. Cliquez sur **OK** puis activez la tâche depuis le menu d'action à droite.

### Étape 6 : Accéder au jeu
1. Ouvrez votre navigateur et accédez au lien : [http://localhost/PROJET-IF3/php/login.php].
2. Créez un compte ou connectez-vous avec le compte administrateur déjà créé.

### Compte administrateur
- **Email** : admin@mail.com
- **Mot de passe** : password

### Étape 7 : Jouer au jeu
Vous pouvez maintenant jouer au jeu Virtual Trader.
