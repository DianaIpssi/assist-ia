# Assist'IA

Projet fil rouge CDA - application immobilière en Symfony.

Les visiteurs peuvent chercher des biens, discuter avec l'assistant IA, prendre rendez-vous ou contacter l'agence.
Les agents immobiliers se connectent pour gérer leurs annonces, consulter les chats clients et leur calendrier.

## Installation

Il faut Docker Desktop installé et lancé.

```bash
docker compose up --build
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console app:load-demo-data
```

Ensuite aller sur http://localhost:8000

phpMyAdmin : http://localhost:8081 (user root / mdp root)

## Comptes de test

- **Agent** : agent@assist-ia.fr / Agent123!
- **Admin** : admin@assist-ia.fr / Admin123!

## Tests

```bash
php bin/phpunit
```

16 tests au vert. CI sur GitHub Actions (dossier `.github/workflows`).

## Pages principales

**Visiteur :**
- `/` - accueil avec chat IA intégré
- `/biens` - catalogue public
- `/contact` - formulaire de contact
- `/rdv` - prise de rendez-vous
- `/politique-confidentialite` - page RGPD

**Agent (connecté) :**
- `/login` - connexion
- `/agent` - tableau de bord
- `/agent/chats` - historique des conversations
- `/agent/calendrier` - créneaux et RDV
- `/property` - gestion des biens
- `/admin` - back office EasyAdmin (admin)

## API externe

Autocomplétion d'adresse avec l'API du gouvernement (data.gouv.fr) sur le formulaire de création de bien.

## Chatbot IA (Ollama local)

Le chatbot utilise **Ollama** en local pour comprendre le langage naturel.
S'il n'est pas lancé, le bot basique (budget / type de bien) prend le relais.

1. Installer Ollama : https://ollama.com
2. Télécharger un modèle :
   ```bash
   ollama pull llama3.2
   ```
3. Ollama tourne sur le port 11434 (l'app le lance automatiquement sur Mac)
4. Activer dans `.env.local` :
   ```
   OLLAMA_ENABLED=true
   OLLAMA_BASE_URL=http://127.0.0.1:11434
   OLLAMA_MODEL=llama3.2
   ```

Avec Docker, c'est déjà activé dans `docker-compose.yml` (`host.docker.internal:11434`).
Il faut juste qu'Ollama tourne sur ton Mac.

Tag jalon 5 : **v0.5-beta**
