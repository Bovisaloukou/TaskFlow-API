# TaskFlow API

API RESTful multi-tenant de gestion de taches, construite avec Laravel 11.

## Stack technique

- **PHP** 8.3
- **Laravel** 11
- **MySQL** (MariaDB)
- **Laravel Sanctum** - Authentification par tokens
- **Spatie Laravel Permission** - Gestion des roles et permissions
- **Scribe** - Documentation API auto-generee

## Installation

```bash
# Cloner le projet
git clone https://github.com/Bovisaloukou/TaskFlow-API.git
cd TaskFlow-API

# Installer les dependances
composer install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate
```

Configurer la base de donnees dans `.env` :

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

# Demarrer le serveur
php artisan serve
```

## Architecture multi-tenant

Chaque utilisateur appartient a une organisation. L'isolation des donnees est assuree par 3 couches :

1. **`BelongsToOrganization` trait** - Applique un scope global et auto-remplit `organization_id` a la creation
2. **`OrganizationScope`** - Ajoute `WHERE organization_id = ?` a toutes les requetes
3. **`EnsureTenant` middleware** - Rejette les requetes sans contexte d'organisation

## Roles et permissions

| Role | Description |
|------|-------------|
| **admin** | Toutes les permissions (gestion org, membres, projets, taches) |
| **manager** | Gestion projets, taches, invitations (pas de gestion org/membres) |
| **user** | Lecture projets, CRUD taches/commentaires/attachments basique |

## Routes API

Toutes les routes sont prefixees par `/api/v1/`.

### Authentification

| Methode | Route | Description | Auth |
|---------|-------|-------------|------|
| POST | `auth/register` | Creer une organisation + compte admin | Non |
| POST | `auth/login` | Se connecter | Non |
| POST | `auth/logout` | Se deconnecter | Oui |
| POST | `auth/refresh` | Rafraichir le token | Oui |
| GET | `auth/me` | Profil utilisateur courant | Oui |

### Organisation

| Methode | Route | Description | Role |
|---------|-------|-------------|------|
| GET | `organization` | Voir l'organisation | auth |
| PUT | `organization` | Modifier l'organisation | admin |
| GET | `organization/members` | Lister les membres | auth |
| DELETE | `organization/members/{id}` | Supprimer un membre | admin |
| PUT | `organization/members/{id}/role` | Changer le role | admin |

### Invitations

| Methode | Route | Description | Role |
|---------|-------|-------------|------|
| GET | `invitations` | Lister les invitations | admin, manager |
| POST | `invitations` | Envoyer une invitation | admin, manager |
| DELETE | `invitations/{id}` | Supprimer une invitation | admin, manager |
| POST | `invitations/{token}/accept` | Accepter une invitation | Public |

### Projets

| Methode | Route | Description |
|---------|-------|-------------|
| GET | `projects` | Lister les projets |
| POST | `projects` | Creer un projet |
| GET | `projects/{id}` | Voir un projet |
| PUT | `projects/{id}` | Modifier un projet |
| DELETE | `projects/{id}` | Supprimer un projet |

### Taches

| Methode | Route | Description |
|---------|-------|-------------|
| GET | `projects/{id}/tasks` | Lister les taches d'un projet |
| POST | `projects/{id}/tasks` | Creer une tache |
| GET | `projects/{id}/tasks/{id}` | Voir une tache |
| PUT | `projects/{id}/tasks/{id}` | Modifier une tache |
| DELETE | `projects/{id}/tasks/{id}` | Supprimer une tache |
| GET | `tasks` | Toutes les taches (cross-projet) |
| GET | `tasks/my` | Mes taches assignees |

### Commentaires

| Methode | Route | Description |
|---------|-------|-------------|
| GET | `tasks/{id}/comments` | Lister les commentaires |
| POST | `tasks/{id}/comments` | Ajouter un commentaire |
| PUT | `tasks/{id}/comments/{id}` | Modifier un commentaire |
| DELETE | `tasks/{id}/comments/{id}` | Supprimer un commentaire |

### Pieces jointes

| Methode | Route | Description |
|---------|-------|-------------|
| GET | `tasks/{id}/attachments` | Lister les pieces jointes |
| POST | `tasks/{id}/attachments` | Uploader un fichier (max 10MB) |
| GET | `tasks/{id}/attachments/{id}` | Telecharger un fichier |
| DELETE | `tasks/{id}/attachments/{id}` | Supprimer un fichier |

## Filtres et pagination

### Filtres disponibles sur les taches

| Parametre | Description | Exemple |
|-----------|-------------|---------|
| `status` | Filtrer par statut | `?status=todo` |
| `priority` | Filtrer par priorite | `?priority=high` |
| `assigned_to` | Filtrer par assignation | `?assigned_to=1` |
| `due_before` | Taches dues avant | `?due_before=2026-12-31` |
| `due_after` | Taches dues apres | `?due_after=2026-01-01` |
| `search` | Recherche titre/description | `?search=homepage` |

### Tri et pagination

| Parametre | Description | Defaut |
|-----------|-------------|--------|
| `sort_by` | Colonne de tri | `created_at` |
| `sort_dir` | Direction (`asc`, `desc`) | `desc` |
| `per_page` | Elements par page (max 100) | `15` |

## Notifications email

- **InvitationSent** - Email a l'invite avec lien d'acceptation
- **TaskAssigned** - Email a l'utilisateur assigne
- **TaskDueReminder** - Rappel pour les taches dues dans 24h (planifie a 08:00)
- **CommentAdded** - Email au createur/assigne de la tache

## Tests

```bash
# Lancer tous les tests
php artisan test

# Lancer un fichier specifique
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

La documentation est generee automatiquement par Scribe :

```bash
php artisan scribe:generate
```

Accessible a `http://localhost:8000/docs` apres `php artisan serve`.

### Postman / OpenAPI

Des fichiers prets a importer sont disponibles dans le dossier `docs/` :

- **`docs/postman-collection.json`** — Importer dans Postman pour tester tous les endpoints
- **`docs/openapi.yaml`** — Spec OpenAPI 3.0 compatible Swagger UI

## Commandes artisan utiles

```bash
# Envoyer les rappels de taches dues
php artisan tasks:send-due-reminders

# Regenerer la documentation API
php artisan scribe:generate

# Relancer les migrations
php artisan migrate:fresh --seed
```

## License

[MIT](https://opensource.org/licenses/MIT)
