# Assist'IA

Projet fil rouge CDA - application immobilière en Symfony.

Les visiteurs peuvent chercher des biens, discuter avec l'assistant IA, prendre rendez-vous ou contacter l'agence.
Les agents immobiliers se connectent pour gérer leurs annonces, consulter les chats clients et leur calendrier.

## Installation (développement)

Il faut Docker Desktop installé et lancé.

```bash
docker compose up --build
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console app:load-demo-data
```

Ensuite aller sur http://localhost:8000

phpMyAdmin : http://localhost:8081 (user root / mdp root)

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

# 3. Migrations et données démo (première fois)
docker compose --env-file .env.local -f docker-compose.prod.yml exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose --env-file .env.local -f docker-compose.prod.yml exec php php bin/console app:load-demo-data
```

L'application est accessible sur le port configuré (défaut : 8000).

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
- **Admin** : supervision globale via EasyAdmin (`/admin`) — tous les biens et tous les créneaux de l'agence.
- **phpMyAdmin** : outil *développeur uniquement* (port 8081), pas accessible depuis l'espace agent.

### Créneaux RDV automatiques

Les créneaux sont générés automatiquement (lun–ven, 10h et 14h, sur 14 jours) :

```bash
php bin/console app:ensure-availability-slots
```

Cette commande tourne aussi au démarrage Docker. L'agent peut toujours ajuster son planning manuellement.

## Tests

```bash
php bin/phpunit
```

CI sur GitHub Actions (dossier `.github/workflows`).

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

Activer dans `.env.local` :

```
OLLAMA_ENABLED=true
OLLAMA_BASE_URL=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.2
```

Avec Docker dev, Ollama est déjà configuré via `host.docker.internal:11434`.

Tags : **v0.5-beta** (jalon 5) → **v1.0** (jalon 6 final)
