<?php

/**
 * TacoCraft SAAS - Database Configuration
 * Configuración optimizada para MySQL con Redis
 */

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        /**
         * MySQL Principal - Base de datos principal del SAAS
         */
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'tacocraft_saas'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => 'InnoDB',

            // Configuraciones de optimización para SAAS
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::ATTR_TIMEOUT => env('DB_TIMEOUT', 60),
                PDO::ATTR_PERSISTENT => env('DB_PERSISTENT', false),
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_LOCAL_INFILE => false,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]) : [],

            // Pool de conexiones
            'pool' => [
                'min_connections' => env('DB_POOL_MIN', 5),
                'max_connections' => env('DB_POOL_MAX', 20),
                'wait_timeout' => env('DB_POOL_WAIT_TIMEOUT', 30),
                'idle_timeout' => env('DB_POOL_IDLE_TIMEOUT', 600),
            ],

            // Configuración de retry
            'retry' => [
                'times' => env('DB_RETRY_TIMES', 3),
                'sleep' => env('DB_RETRY_SLEEP', 1000), // milliseconds
            ],
        ],

        /**
         * MySQL para lectura (Read Replica)
         */
        'mysql_read' => [
            'driver' => 'mysql',
            'read' => [
                'host' => [
                    env('DB_READ_HOST_1', env('DB_HOST', '127.0.0.1')),
                    env('DB_READ_HOST_2', env('DB_HOST', '127.0.0.1')),
                ],
            ],
            'write' => [
                'host' => [
                    env('DB_WRITE_HOST', env('DB_HOST', '127.0.0.1')),
                ],
            ],
            'sticky' => true,
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'tacocraft_saas'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::ATTR_TIMEOUT => env('DB_READ_TIMEOUT', 30),
                PDO::ATTR_PERSISTENT => env('DB_READ_PERSISTENT', true),
            ]) : [],
        ],

        /**
         * MySQL para analytics y reportes
         */
        'mysql_analytics' => [
            'driver' => 'mysql',
            'host' => env('DB_ANALYTICS_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('DB_ANALYTICS_PORT', env('DB_PORT', '3306')),
            'database' => env('DB_ANALYTICS_DATABASE', 'tacocraft_analytics'),
            'username' => env('DB_ANALYTICS_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('DB_ANALYTICS_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::ATTR_TIMEOUT => env('DB_ANALYTICS_TIMEOUT', 120),
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false, // Para consultas grandes
            ]) : [],
        ],

        /**
         * PostgreSQL (alternativa)
         */
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'tacocraft_saas'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        /**
         * SQL Server (para integraciones empresariales)
         */
        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'tacocraft_saas'),
            'username' => env('DB_USERNAME', 'sa'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        /**
         * Redis Principal - Cache y sesiones
         */
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),

            // Configuraciones de optimización
            'options' => [
                'tcp_keepalive' => env('REDIS_TCP_KEEPALIVE', 60),
                'compression' => env('REDIS_COMPRESSION', 'none'), // none, gzip, lz4
                'serialization' => env('REDIS_SERIALIZATION', 'php'), // php, json, igbinary
            ],

            // Pool de conexiones
            'pool' => [
                'min_connections' => env('REDIS_POOL_MIN', 5),
                'max_connections' => env('REDIS_POOL_MAX', 20),
                'wait_timeout' => env('REDIS_POOL_WAIT_TIMEOUT', 30),
                'idle_timeout' => env('REDIS_POOL_IDLE_TIMEOUT', 300),
            ],
        ],

        /**
         * Redis para Cache
         */
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'options' => [
                'tcp_keepalive' => 60,
                'compression' => 'gzip',
            ],
        ],

        /**
         * Redis para Sesiones
         */
        'sessions' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_SESSION_DB', '2'),
            'options' => [
                'tcp_keepalive' => 60,
            ],
        ],

        /**
         * Redis para Colas (Queues)
         */
        'queues' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_QUEUE_DB', '3'),
            'options' => [
                'tcp_keepalive' => 60,
                'serialization' => 'php',
            ],
        ],

        /**
         * Redis para archivos e imágenes
         */
        'files' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_FILES_DB', '4'),
            'options' => [
                'tcp_keepalive' => 60,
                'compression' => 'gzip',
            ],
        ],

        /**
         * Redis para API y respuestas HTTP
         */
        'api' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_API_DB', '5'),
            'options' => [
                'tcp_keepalive' => 60,
                'compression' => 'gzip',
            ],
        ],

        /**
         * Redis para consultas de base de datos
         */
        'queries' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_QUERIES_DB', '6'),
            'options' => [
                'tcp_keepalive' => 60,
                'compression' => 'gzip',
            ],
        ],

        /**
         * Redis para broadcasting y websockets
         */
        'broadcasting' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_BROADCAST_DB', '7'),
            'options' => [
                'tcp_keepalive' => 60,
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración Personalizada para TacoCraft SAAS
    |--------------------------------------------------------------------------
    |
    | Configuraciones específicas para el manejo de base de datos en el SAAS
    |
    */

    'tacocraft' => [

        /**
         * Configuración de sharding (para escalabilidad)
         */
        'sharding' => [
            'enabled' => env('DB_SHARDING_ENABLED', false),
            'strategy' => env('DB_SHARDING_STRATEGY', 'tenant_id'), // tenant_id, hash, range
            'shards' => [
                'shard1' => [
                    'connection' => 'mysql',
                    'range' => [1, 1000],
                ],
                'shard2' => [
                    'connection' => 'mysql_read',
                    'range' => [1001, 2000],
                ],
            ],
        ],

        /**
         * Configuración de multi-tenancy
         */
        'tenancy' => [
            'enabled' => env('TENANCY_ENABLED', true),
            'strategy' => env('TENANCY_STRATEGY', 'single_db'), // single_db, multi_db, multi_schema
            'tenant_column' => env('TENANCY_COLUMN', 'tenant_id'),
            'automatic_scoping' => env('TENANCY_AUTO_SCOPE', true),
        ],

        /**
         * Configuración de backup automático
         */
        'backup' => [
            'enabled' => env('DB_BACKUP_ENABLED', true),
            'schedule' => env('DB_BACKUP_SCHEDULE', '0 2 * * *'), // Diario a las 2 AM
            'retention_days' => env('DB_BACKUP_RETENTION', 7),
            'compress' => env('DB_BACKUP_COMPRESS', true),
            'encrypt' => env('DB_BACKUP_ENCRYPT', false),
            'destinations' => [
                'local' => storage_path('backups/database'),
                'minio' => env('DB_BACKUP_MINIO_ENABLED', false),
                's3' => env('DB_BACKUP_S3_ENABLED', false),
            ],
        ],

        /**
         * Configuración de monitoreo
         */
        'monitoring' => [
            'enabled' => env('DB_MONITORING_ENABLED', true),
            'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 2000), // milliseconds
            'log_queries' => env('DB_LOG_QUERIES', false),
            'metrics' => [
                'connection_count' => true,
                'query_time' => true,
                'memory_usage' => true,
                'deadlocks' => true,
            ],
            'alerts' => [
                'high_connections' => env('DB_ALERT_HIGH_CONNECTIONS', 80), // Porcentaje
                'slow_queries' => env('DB_ALERT_SLOW_QUERIES', 10), // Por minuto
                'deadlocks' => env('DB_ALERT_DEADLOCKS', 5), // Por hora
            ],
        ],

        /**
         * Configuración de optimización
         */
        'optimization' => [
            'query_cache' => [
                'enabled' => env('DB_QUERY_CACHE_ENABLED', true),
                'ttl' => env('DB_QUERY_CACHE_TTL', 3600), // 1 hora
                'store' => 'redis_queries',
            ],
            'connection_pooling' => [
                'enabled' => env('DB_POOLING_ENABLED', true),
                'min_connections' => env('DB_POOL_MIN', 5),
                'max_connections' => env('DB_POOL_MAX', 20),
            ],
            'read_write_splitting' => [
                'enabled' => env('DB_READ_WRITE_SPLIT', false),
                'read_percentage' => env('DB_READ_PERCENTAGE', 70),
            ],
        ],

        /**
         * Configuración de seguridad
         */
        'security' => [
            'ssl' => [
                'enabled' => env('DB_SSL_ENABLED', false),
                'ca_cert' => env('DB_SSL_CA'),
                'client_cert' => env('DB_SSL_CERT'),
                'client_key' => env('DB_SSL_KEY'),
                'verify_server_cert' => env('DB_SSL_VERIFY', true),
            ],
            'encryption' => [
                'enabled' => env('DB_ENCRYPTION_ENABLED', false),
                'key' => env('DB_ENCRYPTION_KEY'),
                'cipher' => env('DB_ENCRYPTION_CIPHER', 'AES-256-CBC'),
            ],
            'audit' => [
                'enabled' => env('DB_AUDIT_ENABLED', false),
                'log_queries' => env('DB_AUDIT_LOG_QUERIES', false),
                'log_connections' => env('DB_AUDIT_LOG_CONNECTIONS', true),
            ],
        ],

        /**
         * Configuración de desarrollo
         */
        'development' => [
            'debug' => env('DB_DEBUG', false),
            'explain_queries' => env('DB_EXPLAIN_QUERIES', false),
            'fake_data' => [
                'enabled' => env('DB_FAKE_DATA_ENABLED', false),
                'locale' => env('DB_FAKE_LOCALE', 'es_ES'),
                'seed_count' => env('DB_FAKE_SEED_COUNT', 100),
            ],
        ],
    ],

];
