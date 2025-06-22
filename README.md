# Back-end OWASP - Projet Symfony

Ce projet est un back-end d'application e-commerce construit avec Symfony, conçu avec un focus sur les bonnes pratiques de sécurité inspirées de l'OWASP.

## Prérequis

- PHP 8.1 ou supérieur
- [Composer](https://getcomposer.org/)
- Un serveur de base de données (ex: MySQL, MariaDB)
- L'extension PHP `pdo_mysql` (ou équivalent pour votre SGBD)

## Instructions d'installation

1.  **Cloner le projet**
    ```bash
    git clone git@github.com:Dev02JL/my-back-owasp.git
    cd back-owasp
    ```

2.  **Installer les dépendances**
    Installez les dépendances PHP avec Composer.
    ```bash
    composer install
    ```

3.  **Configurer l'environnement**
    Créez une copie locale du fichier d'environnement.
    ```bash
    cp .env .env.local
    ```
    Ouvrez le fichier `.env.local` et configurez la variable `DATABASE_URL` pour correspondre à votre configuration de base de données.
    
    Exemple pour MariaDB :
    ```env
    # .env.local
    DATABASE_URL="mysql://VOTRE_USER:VOTRE_MOT_DE_PASSE@127.0.0.1:3306/NOM_DE_LA_BASE?serverVersion=mariadb-10.4.32&charset=utf8mb4"
    ```

4.  **Mettre en place la base de données**
    Les commandes suivantes vont créer la base de données et appliquer le schéma des tables.
    ```bash
    # Crée la base de données si elle n'existe pas
    php bin/console doctrine:database:create

    # Applique les migrations pour créer les tables
    php bin/console doctrine:migrations:migrate --no-interaction
    ```

5.  **Peupler la base de données (via AppFixtures)**
    Pour que l'application soit fonctionnelle dès le départ, vous devez la peupler avec des données de test. C'est le rôle du fichier `src/DataFixtures/AppFixtures.php`.

    *   **Étape importante :** Avant de continuer, ajoutez des fichiers image (par ex. `.jpg`, `.png`) dans le dossier `public/`. Le script de fixtures les utilisera pour créer les produits.
    *   Exécutez la commande suivante pour charger les données :
    ```bash
    php bin/console doctrine:fixtures:load --no-interaction
    ```
    Cette commande va **purger** la base de données et la remplir avec les utilisateurs, produits et avis définis dans les fixtures.

6.  **Lancer le serveur de développement**
    ```bash
    php -S localhost:8000 -t public/
    ```
    Le back-end sera accessible à l'adresse `http://localhost:8000`.

## Lancer les tests

Pour vous assurer que tout est correctement configuré, vous pouvez exécuter la suite de tests automatisés.

1.  **Rendre les scripts exécutables** (une seule fois)
    ```bash
    chmod +x *.sh
    ```

2.  **Lancer tous les tests**
    ```bash
    ./test_all.sh
    ```
    Si tous les tests passent, votre environnement est prêt ! 