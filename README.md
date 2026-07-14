#  Ticket Track — Gestion de tickets de pannes

Application **cross-plateforme** de gestion de tickets de pannes permettant de suivre et résoudre rapidement les pannes signalées par les utilisateurs ou clients.

Le projet est organisé en **dossiers distincts** :

```
Ticket_track/
├── backend_symfony/   → API REST en Symfony 7 + Doctrine + PostgreSQL (JWT)  [ACTIF]
├── backend/           → Ancienne API en PHP natif (conservée comme sauvegarde)
└── frontend/          → Interface utilisateur en React.js (Vite)
```

> Le backend a été migré de **PHP natif** vers **Symfony 7 / Doctrine**.
> Le dossier `backend/` (PHP natif) est conservé en secours et peut être supprimé.

##  Fonctionnalités

- **Création de tickets** — les utilisateurs soumettent des demandes détaillées (titre, description, catégorie, priorité).
- **Attribution des tickets** — les gestionnaires assignent chaque ticket à un technicien.
- **Suivi en temps réel** — statuts *En attente → En cours → Traité*, avec notifications automatiques.
- **Rapports et analyses** — tableau de bord avec statistiques et graphiques (taux de résolution, temps moyen, tendances…).
- **Commentaires** — échanges entre le demandeur et le technicien sur chaque ticket.
- **Gestion des rôles** — Client, Technicien, Gestionnaire.

##  Processus de gestion d'un ticket

1. L'utilisateur **s'inscrit** et crée un compte.
2. Il **soumet une demande** décrivant son problème → statut **En attente**.
3. Le **gestionnaire** analyse la demande et **attribue un technicien** → statut **En cours**.
4. Le technicien intervient et échange via les commentaires.
5. Une fois la panne résolue → statut **Traité**.

## 🛠️ Stack technique

| Couche      | Technologies                                   |
|-------------|------------------------------------------------|
| Frontend    | React 18, Vite, React Router, Recharts, Axios  |
| Backend     | Symfony 7.4, Doctrine ORM, JWT (HMAC-SHA256)   |
| Base de données | PostgreSQL                                 |

##  Démarrage rapide

### 1. Backend

```bash
cd backend_symfony
composer install
# 1. Créez la base "ticket_track" dans pgAdmin.
# 2. Renseignez DATABASE_URL dans .env (utilisateur, mot de passe, base).
php bin/console doctrine:schema:create   # crée les tables depuis les entités
php bin/console app:seed                  # données de démonstration
php -S localhost:8000 -t public public/index.php   # démarre l'API
```

### 2. Frontend

```bash
cd frontend
npm install
npm run dev                               # démarre sur http://localhost:5173
```

### 3. Se connecter

Ouvrez `http://localhost:5173` et utilisez un compte de démonstration :

| Rôle          | Email                       | Mot de passe |
|---------------|-----------------------------|--------------|
| Gestionnaire  | manager@tickettrack.com     | password123  |
| Technicien    | technicien@tickettrack.com  | password123  |
| Client        | client@tickettrack.com      | password123  |

## 📖 Documentation détaillée

- [Documentation du backend Symfony](backend_symfony/README.md) — installation, endpoints de l'API, architecture.
- [Documentation du frontend](frontend/README.md) — scripts, structure, fonctionnalités.

##  Sécurité

- Mots de passe hachés avec **bcrypt** (`password_hash`).
- Authentification par **jeton JWT** signé (HMAC-SHA256).
- **Doctrine ORM / requêtes paramétrées** contre les injections SQL.
- Contrôle d'accès par rôle sur chaque endpoint sensible.
