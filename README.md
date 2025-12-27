# e-FME REST API

API REST complète pour le système de gestion de maintenance préventive e-FME.

## 🚀 Installation

### Prérequis
- PHP >= 7.4
- MySQL >= 5.7
- Composer
- Apache/Nginx avec mod_rewrite activé

### Étapes d'installation

1. **Cloner le projet**
```bash
git clone <repo-url>
cd e-FME-API
```

2. **Installer les dépendances**
```bash
composer install
```

3. **Configurer la base de données**
```bash
# Créer la base de données
mysql -u root -p < database.sql

# Ou manuellement :
mysql -u root -p
CREATE DATABASE efme_db;
USE efme_db;
SOURCE database.sql;
```

4. **Configurer les variables d'environnement**

Créer un fichier `.env` ou configurer directement dans `config/database.php` :
```env
DB_HOST=localhost
DB_NAME=efme_db
DB_USER=root
DB_PASS=votre_mot_de_passe
```

5. **Configurer Apache**

Assurez-vous que mod_rewrite est activé et que le DocumentRoot pointe vers le dossier racine du projet.

6. **Changer la clé secrète JWT**

Dans `src/Utils/JWT.php`, modifiez la variable `$secret` avec une clé sécurisée.

## 📚 Endpoints API

### Authentification

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "admin@efme.com",
  "password": "password"
}

Response:
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": "user_admin",
      "name": "Admin User",
      "email": "admin@efme.com",
      "role": "admin"
    }
  }
}
```

### Users

**Tous les endpoints nécessitent un token JWT dans le header :**
```http
Authorization: Bearer <token>
```

#### Lister les utilisateurs
```http
GET /api/users?limit=50&offset=0
```

#### Obtenir un utilisateur
```http
GET /api/users/{id}
```

#### Créer un utilisateur
```http
POST /api/users
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepassword",
  "role": "technician",
  "is_active": true
}
```

#### Modifier un utilisateur
```http
PUT /api/users/{id}
Content-Type: application/json

{
  "name": "John Doe Updated",
  "role": "manager"
}
```

#### Supprimer un utilisateur
```http
DELETE /api/users/{id}
```

### Sites

#### Lister les sites
```http
GET /api/sites?limit=50&offset=0
```

#### Obtenir un site
```http
GET /api/sites/{id}
```

#### Créer un site
```http
POST /api/sites
Content-Type: application/json

{
  "name": "Nouveau Site",
  "code_site": "SITE-003",
  "latitude": 48.8566,
  "longitude": 2.3522,
  "radius_meters": 100
}
```

#### Modifier un site
```http
PUT /api/sites/{id}
Content-Type: application/json

{
  "name": "Site Modifié",
  "radius_meters": 200
}
```

#### Supprimer un site
```http
DELETE /api/sites/{id}
```

### Tâches PM (Maintenance Préventive)

#### Lister les tâches
```http
GET /api/tasks?status=pending&site_id=site_001&assigned_to=user_tech1&limit=50&offset=0
```

Filtres disponibles :
- `status` : pending, in_progress, completed, cancelled
- `site_id` : ID du site
- `assigned_to` : ID de l'utilisateur assigné

#### Obtenir une tâche
```http
GET /api/tasks/{id}
```

#### Créer une tâche
```http
POST /api/tasks
Content-Type: application/json

{
  "title": "Maintenance mensuelle",
  "description": "Vérification complète de l'équipement",
  "site_id": "site_001",
  "equipment_id": "equip_001",
  "assigned_to": "user_tech1",
  "planned_date": "2024-12-30",
  "status": "pending",
  "priority": "high",
  "created_by": "user_admin"
}
```

#### Modifier une tâche
```http
PUT /api/tasks/{id}
Content-Type: application/json

{
  "status": "in_progress",
  "assigned_to": "user_tech2"
}
```

#### Supprimer une tâche
```http
DELETE /api/tasks/{id}
```

## 🔐 Sécurité

### JWT Token
- Les tokens expirent après 24 heures
- Incluez le token dans chaque requête protégée : `Authorization: Bearer <token>`

### Rôles disponibles
- `admin` : Accès complet
- `manager` : Gestion des tâches et sites
- `technician` : Exécution des tâches
- `user` : Lecture seule

## 📝 Format des réponses

### Succès
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Erreur
```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }
}
```

## 🧪 Tests

Utilisateurs de test créés automatiquement :
- **Admin** : admin@efme.com / password
- **Technicien** : tech1@efme.com / password

## 📋 Structure des tables

- **users** : Utilisateurs du système
- **sites** : Sites de maintenance
- **equipment** : Équipements par site
- **pm_tasks** : Tâches de maintenance préventive
- **task_execution** : Exécutions des tâches
- **task_postponement** : Reports de tâches
- **alerts** : Notifications
- **reports** : Rapports générés
- **audit_logs** : Journaux d'audit

## 🛠️ Développement futur

Pour ajouter de nouveaux endpoints :
1. Créer le modèle dans `src/Models/`
2. Créer le contrôleur dans `src/Controllers/`
3. Ajouter les routes dans `public/index.php`

## 📞 Support

Pour toute question ou problème, consultez la documentation ou contactez l'équipe de développement.

