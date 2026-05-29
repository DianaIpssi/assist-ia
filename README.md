# Assist'IA

Projet fil rouge CDA - application immobilière en Symfony.

Les visiteurs peuvent voir les biens disponibles et utiliser un chatbot pour chercher selon leur budget.
Les agents immobiliers peuvent se connecter pour gérer leurs annonces.

## Installation

Il faut Docker Desktop installé et lancé.

```bash
docker compose up --build
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

J'ai aussi mis une CI sur GitHub Actions (dossier .github/workflows).

## Pages principales

- `/biens` - liste des biens publiés
- `/login` - connexion agent
- `/agent` - tableau de bord agent
- `/property` - gestion des biens (agent connecté)
- `/admin` - back office EasyAdmin (admin)
- `/politique-confidentialite` - page RGPD

## API externe

Autocomplétion d'adresse avec l'API du gouvernement (data.gouv.fr) sur le formulaire de création de bien.

Tag jalon 5 : **v0.5-beta**
