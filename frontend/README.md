# Ticket Track — Frontend (React.js)

Interface utilisateur développée avec **React 18**, **Vite**, **React Router** et **Recharts**.

## Prérequis

- Node.js >= 18
- Le backend PHP démarré (voir `../backend/README.md`)

## Installation

```bash
npm install
```

Copiez `.env.example` en `.env` si votre API n'est pas sur `http://localhost:8000/api` :

```
VITE_API_URL=http://localhost:8000/api
```

## Démarrage

```bash
npm run dev
```

L'application s'ouvre sur `http://localhost:5173`.

## Compilation pour la production

```bash
npm run build      # génère le dossier dist/
npm run preview    # prévisualise le build
```

## Fonctionnalités

- **Authentification** : inscription et connexion sécurisées (JWT stocké côté client).
- **Tableau de bord** : indicateurs clés, graphiques (statuts, priorités, tendance sur 7 jours, catégories).
- **Gestion des tickets** :
  - Création de tickets avec catégorie et priorité.
  - Liste filtrable et recherche (titre, référence).
  - Vue détaillée avec fil de commentaires.
- **Attribution** : les gestionnaires assignent les tickets aux techniciens.
- **Suivi en temps réel** : mise à jour du statut (en attente → en cours → traité) et notifications rafraîchies automatiquement.
- **Interface responsive** : adaptée mobile et bureau (tableau ↔ cartes).

## Structure

```
frontend/
├── index.html
├── vite.config.js
└── src/
    ├── main.jsx            # Point d'entrée React
    ├── App.jsx             # Routage
    ├── api/client.js       # Client Axios + intercepteurs JWT
    ├── context/            # Contexte d'authentification
    ├── components/         # Layout, badges, routes protégées, UI
    ├── pages/              # Login, Register, Dashboard, Tickets…
    ├── utils/              # Libellés et helpers d'affichage
    └── styles/             # Feuilles de style CSS
```

## Rôles et accès

| Vue                 | Client | Technicien | Gestionnaire |
|---------------------|:------:|:----------:|:------------:|
| Tableau de bord     |   ✅   |     ✅     |      ✅      |
| Mes/ses tickets     |   ✅   |     ✅     |      ✅      |
| Créer un ticket     |   ✅   |     —      |      ✅      |
| Attribuer/changer statut | — |     ✅     |      ✅      |
| Liste des techniciens|   —   |     —      |      ✅      |
