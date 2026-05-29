# Assist'IA

Application Symfony full-stack de mise en relation entre clients et agents immobiliers, avec chatbot de recherche par budget et autocomplétion d'adresses via l'API gouvernementale française.

## Prérequis

- Docker et Docker Compose
- PHP 8.3+ et Composer (pour le développement local sans Docker)

## Lancement avec Docker (recommandé)

```bash
docker compose up --build
```

Services disponibles :

| Service | URL |
|---------|-----|
| Application | http://localhost:8000 |
| phpMyAdmin | http://localhost:8081 |
| MySQL | localhost:3307 |

Au premier démarrage, le conteneur PHP exécute automatiquement les migrations Doctrine et charge les données de démonstration.

## Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Agent | agent@assist-ia.fr | Agent123! |
| Admin | admin@assist-ia.fr | Admin123! |

## Développement local (sans Docker)

```bash
composer install
cp .env .env.local
# Configurer DATABASE_URL vers votre MySQL local
php bin/console doctrine:migrations:migrate
php bin/console app:load-demo-data
symfony serve
```

## Fonctionnalités (version bêta – Jalon 5)

- Catalogue public des biens publiés (`/biens`)
- Chatbot de recherche par budget (`POST /api/chat`)
- Autocomplétion d'adresses via l'API Adresse (data.gouv.fr)
- Espace agent : dashboard, CRUD biens, statut brouillon/publié
- Back-office EasyAdmin (`/admin`, accès admin)
- Authentification sécurisée (hachage bcrypt/Argon2, CSRF, limitation des tentatives de connexion)
- Politique de confidentialité RGPD (`/politique-confidentialite`)

## Tests

```bash
composer install
php bin/phpunit
```

La suite comprend des tests unitaires (services) et fonctionnels (contrôleurs, sécurité, API).

## CI

À chaque push sur `main`, GitHub Actions exécute :

- validation Composer
- lint YAML / Twig / container Symfony
- audit de sécurité Composer
- suite PHPUnit

Voir `.github/workflows/ci.yml`.

## Structure

```
src/
  Controller/     # MVC : routes HTTP
  Entity/         # Modèle Doctrine
  Form/           # Formulaires Symfony
  Repository/     # Accès aux données
  Service/        # Logique métier (API adresse, chatbot)
tests/
  Unit/           # Tests unitaires
  Functional/     # Tests fonctionnels WebTestCase
docker/           # Configuration Nginx + PHP
```

## Tag de référence bêta

Créer le tag de livraison Jalon 5 :

```bash
git tag -a v0.5-beta -m "Jalon 5 – version bêta Mai 2026"
git push origin v0.5-beta
```
