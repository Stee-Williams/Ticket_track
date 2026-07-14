# Ticket Track — Backend (Symfony 7 + Doctrine)

API REST développée avec **Symfony 7.4** et **Doctrine ORM**, sur base **PostgreSQL**.
Authentification par **JWT** (implémentation HMAC-SHA256 maison, sans dépendance ni extension particulière).

## Prérequis

- PHP >= 8.2
- PostgreSQL (administré par exemple via pgAdmin)
- Composer
- L'extension `pdo_pgsql` activée

## Installation

```bash
cd backend_symfony
composer install
```

Configurez la connexion dans `.env` (ou mieux `.env.local`) :

```
DATABASE_URL="postgresql://postgres:VOTRE_MDP@127.0.0.1:5432/ticket_track?serverVersion=18&charset=utf8"
JWT_SECRET=une_cle_secrete_aleatoire
```

Créez le schéma puis les données de démonstration :

```bash
php bin/console doctrine:schema:create   # crée les tables à partir des entités
php bin/console app:seed                  # comptes et tickets de test
```

> Si la base contient déjà des tables/objets, réinitialisez d'abord le schéma
> (`DROP SCHEMA public CASCADE; CREATE SCHEMA public;`) puis relancez `schema:create`.

## Démarrage

```bash
php -S localhost:8000 -t public public/index.php
```

L'API est disponible sur `http://localhost:8000/api`.

## Comptes de démonstration

| Rôle          | Email                       | Mot de passe |
|---------------|-----------------------------|--------------|
| Gestionnaire  | manager@tickettrack.com     | password123  |
| Technicien    | technicien@tickettrack.com  | password123  |
| Client        | client@tickettrack.com      | password123  |

## Architecture

```
backend_symfony/
├── config/
│   ├── packages/          # doctrine.yaml, framework.yaml, cache.yaml
│   ├── routes.yaml        # routage par attributs
│   └── services.yaml      # configuration des services (DI)
├── public/index.php       # front controller
├── src/
│   ├── Command/           # app:seed (données de démonstration)
│   ├── Controller/        # Auth, Ticket, User, Stats, Notification
│   ├── Entity/            # User, Ticket, TicketComment, Notification (Doctrine)
│   ├── Repository/        # requêtes (filtres, statistiques, charge techniciens)
│   ├── Service/           # JwtService, AuthContext, ApiResponse, EntityNormalizer…
│   ├── EventSubscriber/   # CORS + gestion globale des exceptions (JSON)
│   └── Exception/         # ApiException
└── .env
```

## Points d'entrée de l'API

Identiques à ceux attendus par le frontend React :

| Méthode | Route                        | Description                       |
|---------|------------------------------|-----------------------------------|
| POST    | `/api/auth/register`         | Inscription (rôle client)         |
| POST    | `/api/auth/login`            | Connexion (retourne un JWT)       |
| GET     | `/api/auth/me`               | Profil courant                    |
| GET     | `/api/tickets`               | Liste filtrée selon le rôle       |
| POST    | `/api/tickets`               | Créer un ticket                   |
| GET     | `/api/tickets/{id}`          | Détail + commentaires             |
| PUT     | `/api/tickets/{id}/assign`   | Attribuer un technicien (manager) |
| PUT     | `/api/tickets/{id}/status`   | Changer le statut                 |
| POST    | `/api/tickets/{id}/comments` | Ajouter un commentaire            |
| DELETE  | `/api/tickets/{id}`          | Supprimer un ticket               |
| GET     | `/api/users`                 | Liste des utilisateurs (manager)  |
| GET     | `/api/users/technicians`     | Techniciens + charge (manager)    |
| PUT     | `/api/users/{id}/role`       | Modifier un rôle (manager)        |
| GET     | `/api/stats`                 | Rapports et analyses              |
| GET     | `/api/notifications`         | Notifications de l'utilisateur    |
| PUT     | `/api/notifications/read`    | Marquer comme lues                |

## Note sur l'authentification

Le projet utilise une implémentation **JWT maison** (HMAC-SHA256) plutôt que
`lexik/jwt-authentication-bundle`, car ce dernier requiert l'extension PHP
`ext-sodium` (non activée dans l'environnement). Le mécanisme est équivalent et
sans dépendance fragile. Pour activer Lexik ultérieurement : activez `extension=sodium`
dans `php.ini`, puis `composer require lexik/jwt-authentication-bundle`.
