# TaskFlow API

API RESTful multi-tenant de gestion de tâches, construite avec Laravel 11.

## Stack technique

- **PHP** 8.4+
- **Laravel** 11
- **MySQL** (MariaDB)
- **Laravel Sanctum** - Authentification par tokens
- **Spatie Laravel Permission** - Gestion des rôles et permissions
- **Scribe** - Documentation API auto-générée

## Installation

```bash
# Cloner le projet
git clone https://github.com/Bovisaloukou/TaskFlow-API.git
cd TaskFlow-API

# Installer les dépendances
composer install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate
```

Configurer la base de données dans `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taskflow
DB_USERNAME=taskflow
DB_PASSWORD=taskflow
```

```bash
# Lancer les migrations et seeders
php artisan migrate
php artisan db:seed

# Démarrer le serveur
php artisan serve
```

## Architecture multi-tenant

Chaque utilisateur appartient à une organisation. L'isolation des données est assurée par 3 couches :

1. **`BelongsToOrganization` trait** - Applique un scope global et auto-remplit `organization_id` à la création
2. **`OrganizationScope`** - Ajoute `WHERE organization_id = ?` à toutes les requêtes
3. **`EnsureTenant` middleware** - Rejette les requêtes sans contexte d'organisation

## Rôles et permissions

| Rôle | Description |
|------|-------------|
| **admin** | Toutes les permissions (gestion org, membres, projets, tâches) |
| **manager** | Gestion projets, tâches, invitations (pas de gestion org/membres) |
| **user** | Lecture projets, CRUD tâches/commentaires/attachments basique |

## Routes API

Toutes les routes sont préfixées par `/api/v1/`.

### Authentification

| Méthode | Route | Description | Auth |
|---------|-------|-------------|------|
| POST | `auth/register` | Créer une organisation + compte admin | Non |
| POST | `auth/login` | Se connecter | Non |
| POST | `auth/logout` | Se déconnecter | Oui |
| POST | `auth/refresh` | Rafraîchir le token | Oui |
| GET | `auth/me` | Profil utilisateur courant | Oui |

### Organisation

| Méthode | Route | Description | Rôle |
|---------|-------|-------------|------|
| GET | `organization` | Voir l'organisation | auth |
| PUT | `organization` | Modifier l'organisation | admin |
| GET | `organization/members` | Lister les membres | auth |
| DELETE | `organization/members/{id}` | Supprimer un membre | admin |
| PUT | `organization/members/{id}/role` | Changer le rôle | admin |

### Invitations

| Méthode | Route | Description | Rôle |
|---------|-------|-------------|------|
| GET | `invitations` | Lister les invitations | admin, manager |
| POST | `invitations` | Envoyer une invitation | admin, manager |
| DELETE | `invitations/{id}` | Supprimer une invitation | admin, manager |
| POST | `invitations/{token}/accept` | Accepter une invitation | Public |

### Projets

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `projects` | Lister les projets |
| POST | `projects` | Créer un projet |
| GET | `projects/{id}` | Voir un projet |
| PUT | `projects/{id}` | Modifier un projet |
| DELETE | `projects/{id}` | Supprimer un projet |

### Tâches

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `projects/{id}/tasks` | Lister les tâches d'un projet |
| POST | `projects/{id}/tasks` | Créer une tâche |
| GET | `projects/{id}/tasks/{id}` | Voir une tâche |
| PUT | `projects/{id}/tasks/{id}` | Modifier une tâche |
| DELETE | `projects/{id}/tasks/{id}` | Supprimer une tâche |
| GET | `tasks` | Toutes les tâches (cross-projet) |
| GET | `tasks/my` | Mes tâches assignées |

### Commentaires

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `tasks/{id}/comments` | Lister les commentaires |
| POST | `tasks/{id}/comments` | Ajouter un commentaire |
| PUT | `tasks/{id}/comments/{id}` | Modifier un commentaire |
| DELETE | `tasks/{id}/comments/{id}` | Supprimer un commentaire |

### Pièces jointes

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `tasks/{id}/attachments` | Lister les pièces jointes |
| POST | `tasks/{id}/attachments` | Uploader un fichier (max 10MB) |
| GET | `tasks/{id}/attachments/{id}` | Télécharger un fichier |
| DELETE | `tasks/{id}/attachments/{id}` | Supprimer un fichier |

## Filtres et pagination

### Filtres disponibles sur les tâches

| Paramètre | Description | Exemple |
|-----------|-------------|---------|
| `status` | Filtrer par statut | `?status=todo` |
| `priority` | Filtrer par priorité | `?priority=high` |
| `assigned_to` | Filtrer par assignation | `?assigned_to=1` |
| `due_before` | Tâches dues avant | `?due_before=2026-12-31` |
| `due_after` | Tâches dues après | `?due_after=2026-01-01` |
| `search` | Recherche titre/description | `?search=homepage` |

### Tri et pagination

| Paramètre | Description | Défaut |
|-----------|-------------|--------|
| `sort_by` | Colonne de tri | `created_at` |
| `sort_dir` | Direction (`asc`, `desc`) | `desc` |
| `per_page` | Éléments par page (max 100) | `15` |

## Notifications email

- **InvitationSent** - Email à l'invité avec lien d'acceptation
- **TaskAssigned** - Email à l'utilisateur assigné
- **TaskDueReminder** - Rappel pour les tâches dues dans 24h (planifié à 08:00)
- **CommentAdded** - Email au créateur/assigné de la tâche

## Tests

```bash
# Lancer tous les tests
php artisan test

# Lancer un fichier spécifique
php artisan test --filter=ProjectCrudTest
```

Structure des tests :

```
tests/Feature/Auth/          - Register, Login, Logout
tests/Feature/Project/       - CRUD, Filtres, Autorisations
tests/Feature/Task/          - CRUD, Filtres, Cross-projet
tests/Feature/Comment/       - CRUD
tests/Feature/Invitation/    - Flow complet
tests/Feature/Attachment/    - Upload/Download
tests/Feature/Tenant/        - Isolation entre organisations
```

## Documentation API

La documentation est générée automatiquement par Scribe :

```bash
php artisan scribe:generate
```

Accessible à `http://localhost:8000/docs` après `php artisan serve`.

### Postman / OpenAPI

Des fichiers prêts à importer sont disponibles dans le dossier `docs/` :

- **`docs/postman-collection.json`** — Importer dans Postman pour tester tous les endpoints
- **`docs/openapi.yaml`** — Spec OpenAPI 3.0 compatible Swagger UI

## Commandes artisan utiles

```bash
# Envoyer les rappels de tâches dues
php artisan tasks:send-due-reminders

# Régénérer la documentation API
php artisan scribe:generate

# Relancer les migrations
php artisan migrate:fresh --seed
```

## License

[MIT](https://opensource.org/licenses/MIT)
