<?php

/**
 * TacoCraft SAAS - Cache Configuration
 * Configuración optimizada para Redis y múltiples stores
 */

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    */

    'default' => env('CACHE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "apc", "array", "database", "file",
    |         "memcached", "redis", "dynamodb", "octane", "null"
    |
    */

    'stores' => [

        'apc' => [
            'driver' => 'apc',
        ],

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
            'lock_connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        /**
         * Redis Principal - Cache general de la aplicación
         */
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],

        /**
         * Redis para sesiones de usuario
         */
        'redis_sessions' => [
            'driver' => 'redis',
            'connection' => 'sessions',
        ],

        /**
         * Redis para cache de imágenes y archivos
         */
        'redis_files' => [
            'driver' => 'redis',
            'connection' => 'files',
        ],

        /**
         * Redis para cache de API y respuestas HTTP
         */
        'redis_api' => [
            'driver' => 'redis',
            'connection' => 'api',
        ],

        /**
         * Redis para cache de consultas de base de datos
         */
        'redis_queries' => [
            'driver' => 'redis',
            'connection' => 'queries',
        ],

        /**
         * Cache en memoria para desarrollo
         */
        'octane' => [
            'driver' => 'octane',
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, or DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),

    /*
    |--------------------------------------------------------------------------
    | Configuración Personalizada para TacoCraft SAAS
    |--------------------------------------------------------------------------
    |
    | Configuraciones específicas para el manejo de cache en el SAAS
    |
    */

    'tacocraft' => [

        /**
         * TTL por defecto para diferentes tipos de cache
         */
        'ttl' => [
            'default' => env('CACHE_TTL_DEFAULT', 3600), // 1 hora
            'long' => env('CACHE_TTL_LONG', 86400), // 1 día
            'short' => env('CACHE_TTL_SHORT', 300), // 5 minutos
            'permanent' => env('CACHE_TTL_PERMANENT', 604800), // 7 días
        ],

        /**
         * Configuración específica por tipo de contenido
         */
        'content_types' => [

            // Cache de menús y restaurantes
            'restaurants' => [
                'store' => 'redis',
                'ttl' => 3600, // 1 hora
                'tags' => ['restaurants', 'menus'],
                'prefix' => 'rest_',
            ],

            // Cache de imágenes y archivos
            'images' => [
                'store' => 'redis_files',
                'ttl' => 604800, // 7 días
                'tags' => ['images', 'files'],
                'prefix' => 'img_',
            ],

            // Cache de API responses
            'api' => [
                'store' => 'redis_api',
                'ttl' => 1800, // 30 minutos
                'tags' => ['api', 'responses'],
                'prefix' => 'api_',
            ],

            // Cache de consultas de base de datos
            'queries' => [
                'store' => 'redis_queries',
                'ttl' => 3600, // 1 hora
                'tags' => ['queries', 'database'],
                'prefix' => 'qry_',
            ],

            // Cache de sesiones
            'sessions' => [
                'store' => 'redis_sessions',
                'ttl' => 7200, // 2 horas
                'tags' => ['sessions', 'users'],
                'prefix' => 'sess_',
            ],

            // Cache de configuraciones
            'config' => [
                'store' => 'redis',
                'ttl' => 86400, // 1 día
                'tags' => ['config', 'settings'],
                'prefix' => 'cfg_',
            ],

            // Cache de QR codes
            'qr_codes' => [
                'store' => 'redis_files',
                'ttl' => 86400, // 1 día
                'tags' => ['qr', 'codes'],
                'prefix' => 'qr_',
            ],

            // Cache de estadísticas
            'stats' => [
                'store' => 'redis',
                'ttl' => 1800, // 30 minutos
                'tags' => ['stats', 'analytics'],
                'prefix' => 'stat_',
            ],

            // Cache de notificaciones
            'notifications' => [
                'store' => 'redis',
                'ttl' => 3600, // 1 hora
                'tags' => ['notifications', 'alerts'],
                'prefix' => 'notif_',
            ],

            // Cache de traducciones
            'translations' => [
                'store' => 'redis',
                'ttl' => 86400, // 1 día
                'tags' => ['i18n', 'translations'],
                'prefix' => 'i18n_',
            ],
        ],

        /**
         * Configuración de tags para invalidación masiva
         */
        'tags' => [
            'enabled' => env('CACHE_TAGS_ENABLED', true),
            'separator' => ':',
            'global_tags' => ['app', 'tacocraft'],
        ],

        /**
         * Configuración de warming (precalentamiento)
         */
        'warming' => [
            'enabled' => env('CACHE_WARMING_ENABLED', true),
            'schedule' => [
                'restaurants' => '*/30 * * * *', // Cada 30 minutos
                'config' => '0 */6 * * *', // Cada 6 horas
                'translations' => '0 0 * * *', // Diario
            ],
        ],

        /**
         * Configuración de limpieza automática
         */
        'cleanup' => [
            'enabled' => env('CACHE_CLEANUP_ENABLED', true),
            'schedule' => '0 2 * * *', // Diario a las 2 AM
            'max_memory_usage' => env('CACHE_MAX_MEMORY_MB', 512), // MB
            'cleanup_threshold' => env('CACHE_CLEANUP_THRESHOLD', 80), // Porcentaje
        ],

        /**
         * Configuración de compresión
         */
        'compression' => [
            'enabled' => env('CACHE_COMPRESSION_ENABLED', true),
            'algorithm' => env('CACHE_COMPRESSION_ALGO', 'gzip'), // gzip, lz4, snappy
            'level' => env('CACHE_COMPRESSION_LEVEL', 6), // 1-9 para gzip
            'min_size' => env('CACHE_COMPRESSION_MIN_SIZE', 1024), // bytes
        ],

        /**
         * Configuración de serialización
         */
        'serialization' => [
            'method' => env('CACHE_SERIALIZATION', 'php'), // php, json, igbinary
            'options' => [
                'json_flags' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ],
        ],

        /**
         * Configuración de monitoreo
         */
        'monitoring' => [
            'enabled' => env('CACHE_MONITORING_ENABLED', true),
            'metrics' => [
                'hit_rate' => true,
                'memory_usage' => true,
                'key_count' => true,
                'response_time' => true,
            ],
            'alerts' => [
                'low_hit_rate' => env('CACHE_ALERT_HIT_RATE', 70), // Porcentaje
                'high_memory' => env('CACHE_ALERT_MEMORY', 90), // Porcentaje
            ],
        ],

        /**
         * Configuración de desarrollo
         */
        'development' => [
            'debug' => env('CACHE_DEBUG', false),
            'log_queries' => env('CACHE_LOG_QUERIES', false),
            'disable_in_testing' => env('CACHE_DISABLE_TESTING', true),
            'fake_driver' => env('CACHE_FAKE_DRIVER', 'array'),
        ],

        /**
         * Configuración de distribución (para múltiples servidores)
         */
        'distribution' => [
            'enabled' => env('CACHE_DISTRIBUTION_ENABLED', false),
            'strategy' => env('CACHE_DISTRIBUTION_STRATEGY', 'consistent_hash'), // round_robin, consistent_hash
            'replication_factor' => env('CACHE_REPLICATION_FACTOR', 2),
            'failover_enabled' => env('CACHE_FAILOVER_ENABLED', true),
        ],

        /**
         * Configuración de seguridad
         */
        'security' => [
            'encryption' => [
                'enabled' => env('CACHE_ENCRYPTION_ENABLED', false),
                'key' => env('CACHE_ENCRYPTION_KEY'),
                'cipher' => env('CACHE_ENCRYPTION_CIPHER', 'AES-256-CBC'),
            ],
            'access_control' => [
                'enabled' => env('CACHE_ACCESS_CONTROL', false),
                'allowed_ips' => explode(',', env('CACHE_ALLOWED_IPS', '')),
                'rate_limiting' => env('CACHE_RATE_LIMITING', false),
            ],
        ],

        /**
         * Configuración de backup
         */
        'backup' => [
            'enabled' => env('CACHE_BACKUP_ENABLED', false),
            'schedule' => env('CACHE_BACKUP_SCHEDULE', '0 3 * * *'), // Diario a las 3 AM
            'retention_days' => env('CACHE_BACKUP_RETENTION', 7),
            'compress' => env('CACHE_BACKUP_COMPRESS', true),
        ],
    ],

];
