# Assist'IA

Projet fil rouge CDA - application immobilière en Symfony.

Les visiteurs peuvent chercher des biens, discuter avec l'assistant IA, prendre rendez-vous ou contacter l'agence.
Les agents immobiliers se connectent pour gérer leurs annonces, consulter les chats clients et leur calendrier.

## Installation (développement)

Il faut Docker Desktop installé et lancé.

```bash
docker compose up --build
```

Au démarrage, le conteneur PHP applique les migrations, charge les données de démo, génère les créneaux et purge les vieux messages (`docker/php/entrypoint.sh`). Pour les relancer à la main si besoin :

```bash
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console app:load-demo-data
```

Ensuite aller sur http://localhost:8000

phpMyAdmin : http://localhost:8081 (user root / mdp root)

## Démarrer, arrêter, changer d'environnement

```bash
# Dev (4 conteneurs : MySQL, PHP, Nginx, phpMyAdmin)
docker compose up -d
docker compose ps
docker compose down

# Prod (3 conteneurs : MySQL, PHP, Nginx)
docker compose --env-file .env.local -f docker-compose.prod.yml up -d --build
docker compose --env-file .env.local -f docker-compose.prod.yml ps
docker compose --env-file .env.local -f docker-compose.prod.yml down
```

Dev et prod utilisent le même port 8000 : arrêter l'un avant de lancer l'autre. En prod, toutes les commandes (`up`, `ps`, `down`, `exec`) ont besoin de `--env-file .env.local` : sans les secrets, Docker Compose refuse de lire le fichier.

```bash
# Passer de la prod au dev
docker compose --env-file .env.local -f docker-compose.prod.yml down
docker compose up -d

# Revenir à la prod
docker compose down
docker compose --env-file .env.local -f docker-compose.prod.yml up -d
```

`down` arrête et supprime les conteneurs mais garde les données : elles sont dans les volumes `db_data` (dev) et `db_data_prod` (prod). Seul `down -v` les efface.

Les conteneurs peuvent aussi être démarrés et arrêtés depuis Docker Desktop (onglet Containers).

## Mise en production

Fichier dédié : `docker-compose.prod.yml` (sans phpMyAdmin, `APP_ENV=prod`).

```bash
# 1. Créer un fichier .env.local avec les secrets (non versionné)
APP_SECRET=une-cle-secrete-longue
MYSQL_ROOT_PASSWORD=mot-de-passe-fort
MAILER_DSN=smtp://user:pass@smtp.example.com:587
OLLAMA_ENABLED=true
# Données fictives (comptes et biens de démo) : pour une démonstration uniquement
LOAD_DEMO_DATA=1

# 2. Lancer en production : --env-file indique à Docker Compose où lire les secrets
docker compose --env-file .env.local -f docker-compose.prod.yml up --build -d

# 3. Migrations et données démo : lancées automatiquement au démarrage
#    (données démo seulement si LOAD_DEMO_DATA=1). Pour les relancer à la main :
docker compose --env-file .env.local -f docker-compose.prod.yml exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose --env-file .env.local -f docker-compose.prod.yml exec php php bin/console app:load-demo-data
```

L'application est accessible sur le port configuré (défaut : 8000).

Sans `MAILER_DSN` dans `.env.local`, les e-mails ne sont pas envoyés (`null://null`).

### Accéder à la base en production

Pas de phpMyAdmin en production : la base se consulte dans le conteneur MySQL (le mot de passe est celui de `.env.local`).

```bash
docker exec -it assist_db_prod mysql -uroot -p assist_ia
```

### CI / CD

- **CI** : à chaque push sur `main`, les tests PHPUnit et les lints tournent (`.github/workflows/ci.yml`).
- **CD** : à chaque tag `v*` (ex: `v1.0`), l'image Docker est construite et validée (`.github/workflows/cd.yml`).

Pour créer une release :

```bash
git tag -a v1.0 -m "Version finale Jalon 6"
git push origin v1.0
```

## Comptes de test

| Rôle | Email | Mot de passe | Accès |
|------|-------|--------------|-------|
| **Agent** | agent@assist-ia.fr | Agent123! | `/agent`, biens, chats, calendrier |
| **Admin** | admin@assist-ia.fr | Admin123! | Tout l'agent + `/admin` (EasyAdmin global) |

### Agent vs Admin

- **Agent** : gère *ses* annonces, *ses* RDV, les conversations clients. Interface métier sur `/agent`.
- **Admin** : supervision globale via EasyAdmin (`/admin`) — tous les biens et tous les créneaux de l'agence, et création des comptes agents (pas d'inscription libre).
- **phpMyAdmin** : outil *développeur uniquement* (port 8081), pas accessible depuis l'espace agent.

### Créneaux RDV automatiques

Les créneaux sont générés automatiquement (lun–ven, 10h et 14h, sur 14 jours) :

```bash
docker compose exec php php bin/console app:ensure-availability-slots
```

Cette commande tourne aussi au démarrage Docker. L'agent peut toujours ajuster son planning manuellement.

## Tests

Les tests utilisent une base SQLite séparée, recréée à chaque test :

```bash
docker compose exec -e APP_ENV=test -e APP_DEBUG=1 -e 'DATABASE_URL=sqlite:///%kernel.project_dir%/var/test.db' php php bin/phpunit
```

En production, remplacer `docker compose exec … php` par `docker exec … assist_php_prod`.

CI sur GitHub Actions (dossier `.github/workflows`) : `php bin/phpunit` à chaque push.

## Pages principales

**Visiteur :**
- `/` - accueil avec chat IA intégré
- `/biens` - catalogue public
- `/contact` - formulaire de contact
- `/rdv` - prise de rendez-vous
- `/politique-confidentialite` - page RGPD

**Agent (connecté) :**
- `/login` - connexion
- `/forgot-password` - réinitialisation mot de passe
- `/agent` - tableau de bord
- `/agent/chats` - historique des conversations
- `/agent/calendrier` - créneaux et RDV
- `/property` - gestion des biens
- `/admin` - back office EasyAdmin (admin)

## API externe

- Autocomplétion d'adresse : API gouvernementale (data.gouv.fr)
- Chatbot IA : Ollama local (optionnel)

## Chatbot IA (Ollama local)

```bash
ollama pull llama3.2
```

Ollama tourne sur la machine (application ouverte, port 11434). Les conteneurs le joignent via `host.docker.internal:11434`, déjà configuré dans `docker-compose.yml` et `docker-compose.prod.yml`.

En production, l'activer dans `.env.local` (désactivé par défaut) :

```
OLLAMA_ENABLED=true
```

Si Ollama ne répond pas, le chat répond avec des règles métier (budget, type de bien).

Tags : **v0.5-beta** (jalon 5) → **v1.0** (jalon 6 final)
