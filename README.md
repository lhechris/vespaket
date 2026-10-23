# Sport App

Sport App est une application web destinée à faciliter l’organisation des équipes sportives : matchs, entraînements, événements et informations des membres sont regroupés au même endroit. L’application peut aussi être installée sur un téléphone ou un ordinateur et envoyer des notifications push.

## Fonctionnalités

- Gérer plusieurs équipes, leurs membres et leurs entraîneurs.
- Créer et suivre les matchs, les entraînements et les autres événements.
- Recueillir les disponibilités des membres et préparer les sélections de match.
- Saisir les scores, des statistiques et des options propres à une équipe.
- Gérer les gymnases et leurs coordonnées.
- Inviter des utilisateurs et associer un ou plusieurs membres à leur compte.
- Se connecter avec un identifiant et un mot de passe ou, si configuré, avec Google.
- Installer l’application comme PWA et s’abonner aux notifications push.

Un **utilisateur** est une personne qui se connecte à l’application. Un **membre** est un joueur ou un membre du staff ; il peut être associé à un ou plusieurs utilisateurs, par exemple un parent.

Le guide fonctionnel illustré est disponible dans [`docs/coachs/`](docs/coachs/).

## Technologies

- PHP 8.3 ou supérieur et Laravel 13
- Livewire 3 et Volt
- SQLite par défaut ; MySQL/MariaDB sont également configurables
- Node.js et npm, Vite et Tailwind CSS pour les ressources front-end
- PHPUnit pour les tests

## Prérequis

- PHP **8.3+**, Composer **2** et Node.js/npm
- Extensions PHP utilisées par le projet et ses dépendances : BCMath, cURL, DOM, Fileinfo, GD, OpenSSL, session, SimpleXML, XML et ZIP
- Pour SQLite (configuration par défaut), PDO avec le pilote `pdo_sqlite`
- Pour MySQL/MariaDB à la place de SQLite, PDO avec le pilote `pdo_mysql`

Pour vérifier les prérequis Composer après installation de PHP :

```bash
composer check-platform-reqs
```

## Installation locale

### SQLite (par défaut)

Depuis la racine du dépôt :

```bash
cp .env.example .env
mkdir -p database
touch database/database.sqlite
```

La configuration de `.env.example` utilise SQLite. Laissez `DB_CONNECTION=sqlite` et `DB_DATABASE` non défini pour utiliser `database/database.sqlite`. Vérifiez que le fichier SQLite existe avant de lancer les migrations.

La commande de configuration fournie installe les dépendances PHP et JavaScript, génère la clé de l’application, crée le schéma de base de données et compile les ressources front-end :

```bash
composer run setup
```

Pour effectuer ces étapes séparément :

```bash
composer install
php artisan key:generate
php artisan migrate
npm ci
npm run build
```

`composer run setup` inclut déjà les commandes d’installation et de compilation ci-dessus ; ne les relancez séparément que si nécessaire.

### MySQL ou MariaDB (alternative)

Créez une base de données vide et un compte disposant des droits nécessaires, puis configurez `.env` :

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sportapp
DB_USERNAME=utilisateur
DB_PASSWORD=mot_de_passe
```

Activez l’extension PHP `pdo_mysql`, puis appliquez les migrations après l’installation Composer et la génération de la clé :

```bash
composer install
php artisan key:generate
php artisan migrate
npm ci
npm run build
```

Les migrations créent les tables nécessaires ; il n’est normalement pas nécessaire d’importer un fichier SQL de sauvegarde pour initialiser une base locale.

## Développement

Lancez les services de développement avec :

```bash
composer run dev
```

Cette commande démarre le serveur Laravel, le worker de file d’attente, les logs Pail et Vite. L’application est généralement disponible sur <http://localhost:8000>. Arrêtez l’ensemble avec `Ctrl+C`.

Commandes utiles :

```bash
php artisan migrate          # Appliquer les migrations en attente
php artisan migrate:status   # Consulter leur état
php artisan route:list       # Lister les routes
composer test                # Lancer les tests PHPUnit
npm run dev                  # Démarrer uniquement Vite
npm run build                # Compiler les ressources pour la production
```

Les tests utilisent SQLite en mémoire, défini dans `phpunit.xml` ; ils ne nécessitent pas de modifier la base de données locale.

### Repères dans le code

- `app/Models/` : modèles Eloquent
- `app/Livewire/` : composants et logique des interfaces
- `app/Http/Controllers/` et `app/Http/Middleware/` : contrôleurs et middleware
- `routes/web.php` et `routes/auth.php` : routes web et authentification
- `database/migrations/`, `database/seeders/` et `database/factories/` : schéma et données de test
- `resources/` : vues, CSS et JavaScript
- `tests/Feature/` et `tests/Unit/` : tests
- `docs/coachs/` : documentation fonctionnelle illustrée

Les pages de gestion des équipes sont protégées par authentification et réservées aux entraîneurs autorisés. Lors d’une modification du schéma, ajoutez une migration plutôt que d’éditer une base locale ou un fichier de sauvegarde.

## Configuration des services facultatifs

- **Connexion Google** : renseignez `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` et `GOOGLE_REDIRECT_URI` dans `.env`.
- **Notifications push** : renseignez `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` et `VAPID_SUBJECT`.

Ces réglages sont documentés dans [`.env.example`](.env.example). Ils ne sont nécessaires que pour activer les fonctionnalités correspondantes. Ne partagez ni ne versionnez les secrets de votre fichier `.env`.
