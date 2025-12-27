<?php
// config/config.php

/**
 * Configuration générale de l'application e-FME API
 */

return [
    /**
     * Application
     */
    'app' => [
        'name' => 'e-FME REST API',
        'version' => '1.0.0',
        'env' => getenv('APP_ENV') ?: 'development', // development, production, testing
        'debug' => getenv('APP_DEBUG') !== 'false', // Active les erreurs détaillées
        'timezone' => 'Africa/Casablanca', // ou votre timezone
        'locale' => 'fr',
    ],

    /**
     * Base de données
     */
    'database' => require __DIR__ . '/database.php',

    /**
     * JWT Configuration
     */
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production',
        'algorithm' => 'HS256',
        'expires_in' => 86400, // 24 heures en secondes
        'refresh_expires_in' => 604800, // 7 jours
        'issuer' => 'e-fme-api',
    ],

    /**
     * CORS Configuration
     */
    'cors' => [
        'enabled' => true,
        'allowed_origins' => getenv('CORS_ORIGINS') 
            ? explode(',', getenv('CORS_ORIGINS'))
            : ['*'], // En production, spécifier les domaines autorisés
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin'],
        'exposed_headers' => ['Content-Length', 'X-Total-Count'],
        'allow_credentials' => true,
        'max_age' => 86400, // 24 heures
    ],

    /**
     * Pagination
     */
    'pagination' => [
        'default_limit' => 100,
        'max_limit' => 1000,
    ],

    /**
     * Upload de fichiers
     */
    'upload' => [
        'max_size' => 5242880, // 5MB en octets
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'upload_path' => __DIR__ . '/../storage/uploads/',
        'reports_path' => __DIR__ . '/../storage/reports/',
    ],

    /**
     * Email Configuration (pour notifications futures)
     */
    'mail' => [
        'driver' => 'smtp', // smtp, sendmail, mailgun
        'host' => getenv('MAIL_HOST') ?: 'smtp.mailtrap.io',
        'port' => getenv('MAIL_PORT') ?: 587,
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'from' => [
            'address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@efme.com',
            'name' => getenv('MAIL_FROM_NAME') ?: 'e-FME System',
        ],
    ],

    /**
     * Logging
     */
    'logging' => [
        'enabled' => true,
        'level' => getenv('LOG_LEVEL') ?: 'info', // debug, info, warning, error
        'path' => __DIR__ . '/../storage/logs/',
        'filename' => 'app.log',
        'max_files' => 30, // Garder 30 jours de logs
    ],

    /**
     * Cache
     */
    'cache' => [
        'enabled' => false,
        'driver' => 'file', // file, redis, memcached
        'ttl' => 3600, // 1 heure par défaut
        'path' => __DIR__ . '/../storage/cache/',
    ],

    /**
     * Sécurité
     */
    'security' => [
        // Rate limiting (requêtes par minute)
        'rate_limit' => [
            'enabled' => true,
            'max_requests' => 60, // 60 requêtes par minute
            'decay_minutes' => 1,
        ],
        
        // Tentatives de connexion
        'login_attempts' => [
            'max_attempts' => 5,
            'decay_minutes' => 15,
        ],
        
        // Password policy
        'password' => [
            'min_length' => 8,
            'require_uppercase' => false,
            'require_lowercase' => false,
            'require_numbers' => false,
            'require_special_chars' => false,
        ],

        // IP Whitelist (vide = tous autorisés)
        'ip_whitelist' => [],
        
        // IP Blacklist
        'ip_blacklist' => [],
    ],

    /**
     * Alertes et Notifications
     */
    'alerts' => [
        'enabled' => true,
        'channels' => ['database', 'email'], // database, email, sms
        'task_reminder_days' => 3, // Alerter 3 jours avant l'échéance
        'overdue_check_interval' => 3600, // Vérifier les tâches en retard chaque heure
    ],

    /**
     * Rapports
     */
    'reports' => [
        'enabled' => true,
        'formats' => ['pdf', 'excel', 'csv'],
        'default_format' => 'pdf',
        'auto_cleanup_days' => 30, // Supprimer les rapports après 30 jours
    ],

    /**
     * Audit
     */
    'audit' => [
        'enabled' => true,
        'log_level' => 'all', // all, write_only, important_only
        'retention_days' => 90, // Garder les logs d'audit 90 jours
    ],

    /**
     * Géolocalisation
     */
    'geolocation' => [
        'enabled' => true,
        'enforce_on_execution' => false, // Rendre obligatoire la géolocalisation pour exécuter une tâche
        'max_distance_alert' => 500, // Alerte si > 500m du site
    ],

    /**
     * API externe (si besoin)
     */
    'external_apis' => [
        'weather' => [
            'enabled' => false,
            'api_key' => getenv('WEATHER_API_KEY') ?: '',
            'base_url' => 'https://api.openweathermap.org/data/2.5/',
        ],
        'sms' => [
            'enabled' => false,
            'provider' => 'twilio', // twilio, nexmo
            'api_key' => getenv('SMS_API_KEY') ?: '',
            'api_secret' => getenv('SMS_API_SECRET') ?: '',
        ],
    ],

    /**
     * Modes de maintenance
     */
    'maintenance' => [
        'enabled' => false,
        'message' => 'System under maintenance. Please try again later.',
        'allowed_ips' => [], // IPs qui peuvent accéder pendant la maintenance
        'retry_after' => 3600, // En-tête Retry-After (secondes)
    ],

    /**
     * Tâches planifiées (Cron)
     */
    'cron' => [
        'enabled' => false,
        'jobs' => [
            'check_overdue_tasks' => '0 * * * *', // Chaque heure
            'send_task_reminders' => '0 8 * * *', // Tous les jours à 8h
            'cleanup_old_reports' => '0 2 * * 0', // Dimanche à 2h du matin
            'cleanup_old_audit_logs' => '0 3 * * 0', // Dimanche à 3h
        ],
    ],

    /**
     * Mobile App Configuration
     */
    'mobile' => [
        'offline_mode' => true,
        'sync_interval' => 300, // 5 minutes
        'force_update_version' => '1.0.0',
    ],

    /**
     * Features Flags (Activer/Désactiver des fonctionnalités)
     */
    'features' => [
        'task_postponement' => true,
        'task_recurring' => false, // Fonctionnalité future
        'equipment_qr_code' => true,
        'photo_attachments' => true,
        'voice_notes' => false,
        'analytics_dashboard' => true,
        'export_data' => true,
        'multi_language' => false,
    ],

    /**
     * Développement
     */
    'dev' => [
        'show_sql_queries' => false,
        'fake_delays' => false, // Simuler des délais réseau
        'seed_database' => false, // Remplir avec des données de test
    ],
];