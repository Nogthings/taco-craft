<?php

/**
 * TacoCraft SAAS - Queue Configuration
 * Configuración optimizada para Redis con múltiples colas
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        /**
         * Redis Principal - Cola por defecto
         */
        'redis' => [
            'driver' => 'redis',
            'connection' => 'queues',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => env('QUEUE_RETRY_AFTER', 90),
            'block_for' => env('QUEUE_BLOCK_FOR', null),
            'after_commit' => false,

            // Configuraciones adicionales para optimización
            'options' => [
                'visibility_timeout' => env('QUEUE_VISIBILITY_TIMEOUT', 60),
                'pop_timeout' => env('QUEUE_POP_TIMEOUT', 5),
                'max_jobs' => env('QUEUE_MAX_JOBS', 1000),
                'memory_limit' => env('QUEUE_MEMORY_LIMIT', 128), // MB
            ],
        ],

        /**
         * Redis para trabajos de alta prioridad
         */
        'redis_high' => [
            'driver' => 'redis',
            'connection' => 'queues',
            'queue' => env('REDIS_QUEUE_HIGH', 'high'),
            'retry_after' => 60,
            'block_for' => 1,
            'after_commit' => false,
        ],

        /**
         * Redis para trabajos de baja prioridad
         */
        'redis_low' => [
            'driver' => 'redis',
            'connection' => 'queues',
            'queue' => env('REDIS_QUEUE_LOW', 'low'),
            'retry_after' => 300, // 5 minutos
            'block_for' => 10,
            'after_commit' => false,
        ],

        /**
         * Redis para procesamiento de imágenes
         */
        'redis_images' => [
            'driver' => 'redis',
            'connection' => 'queues',
            'queue' => env('REDIS_QUEUE_IMAGES', 'images'),
            'retry_after' => 600, // 10 minutos
            'block_for' => 5,
            'after_commit' => false,
        ],

        /**
         * Redis para emails y notificaciones
         */
        'redis_notifications' => [
            'driver' => 'redis',
            'connection' => 'queues',
            'queue' => env('REDIS_QUEUE_NOTIFICATIONS', 'notifications'),
            'retry_after' => 120,
            'block_for' => 2,
            'after_commit' => false,
        ],

        /**
         * Redis para reportes y analytics
         */
        'redis_reports' => [
            'driver' => 'redis',
            'connection' => 'queues',
            'queue' => env('REDIS_QUEUE_REPORTS', 'reports'),
            'retry_after' => 1800, // 30 minutos
            'block_for' => 15,
            'after_commit' => false,
        ],

        /**
         * Redis para backups
         */
        'redis_backups' => [
            'driver' => 'redis',
            'connection' => 'queues',
            'queue' => env('REDIS_QUEUE_BACKUPS', 'backups'),
            'retry_after' => 3600, // 1 hora
            'block_for' => 30,
            'after_commit' => false,
        ],

        /**
         * Redis para trabajos de limpieza
         */
        'redis_cleanup' => [
            'driver' => 'redis',
            'connection' => 'queues',
            'queue' => env('REDIS_QUEUE_CLEANUP', 'cleanup'),
            'retry_after' => 900, // 15 minutos
            'block_for' => 10,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración Personalizada para TacoCraft SAAS
    |--------------------------------------------------------------------------
    |
    | Configuraciones específicas para el manejo de colas en el SAAS
    |
    */

    'tacocraft' => [

        /**
         * Configuración de prioridades
         */
        'priorities' => [
            'critical' => [
                'queue' => 'redis_high',
                'timeout' => 30,
                'tries' => 5,
                'backoff' => [1, 5, 10, 30, 60], // segundos
            ],
            'high' => [
                'queue' => 'redis_high',
                'timeout' => 60,
                'tries' => 3,
                'backoff' => [5, 15, 45],
            ],
            'normal' => [
                'queue' => 'redis',
                'timeout' => 120,
                'tries' => 3,
                'backoff' => [10, 30, 90],
            ],
            'low' => [
                'queue' => 'redis_low',
                'timeout' => 300,
                'tries' => 2,
                'backoff' => [30, 120],
            ],
            'background' => [
                'queue' => 'redis_low',
                'timeout' => 600,
                'tries' => 1,
                'backoff' => [60],
            ],
        ],

        /**
         * Configuración por tipo de trabajo
         */
        'job_types' => [

            // Procesamiento de imágenes
            'image_processing' => [
                'queue' => 'redis_images',
                'timeout' => 300, // 5 minutos
                'tries' => 2,
                'memory_limit' => 256, // MB
                'cpu_limit' => 80, // Porcentaje
            ],

            // Envío de emails
            'email_sending' => [
                'queue' => 'redis_notifications',
                'timeout' => 60,
                'tries' => 3,
                'rate_limit' => 100, // Por minuto
            ],

            // Notificaciones push
            'push_notifications' => [
                'queue' => 'redis_notifications',
                'timeout' => 30,
                'tries' => 3,
                'rate_limit' => 200, // Por minuto
            ],

            // Generación de reportes
            'report_generation' => [
                'queue' => 'redis_reports',
                'timeout' => 1800, // 30 minutos
                'tries' => 1,
                'memory_limit' => 512, // MB
            ],

            // Backups
            'backup_creation' => [
                'queue' => 'redis_backups',
                'timeout' => 3600, // 1 hora
                'tries' => 1,
                'memory_limit' => 1024, // MB
            ],

            // Limpieza de archivos
            'file_cleanup' => [
                'queue' => 'redis_cleanup',
                'timeout' => 900, // 15 minutos
                'tries' => 1,
            ],

            // Sincronización de datos
            'data_sync' => [
                'queue' => 'redis',
                'timeout' => 600, // 10 minutos
                'tries' => 2,
            ],

            // Procesamiento de pagos
            'payment_processing' => [
                'queue' => 'redis_high',
                'timeout' => 120,
                'tries' => 3,
                'priority' => 'critical',
            ],

            // Análisis de datos
            'data_analysis' => [
                'queue' => 'redis_reports',
                'timeout' => 1200, // 20 minutos
                'tries' => 1,
                'memory_limit' => 512, // MB
            ],

            // Optimización de imágenes
            'image_optimization' => [
                'queue' => 'redis_images',
                'timeout' => 180,
                'tries' => 2,
                'batch_size' => 10,
            ],
        ],

        /**
         * Configuración de workers
         */
        'workers' => [
            'default' => [
                'processes' => env('QUEUE_WORKERS_DEFAULT', 3),
                'timeout' => env('QUEUE_WORKER_TIMEOUT', 60),
                'sleep' => env('QUEUE_WORKER_SLEEP', 3),
                'max_jobs' => env('QUEUE_WORKER_MAX_JOBS', 1000),
                'max_time' => env('QUEUE_WORKER_MAX_TIME', 3600), // 1 hora
                'memory' => env('QUEUE_WORKER_MEMORY', 128), // MB
            ],
            'high_priority' => [
                'processes' => env('QUEUE_WORKERS_HIGH', 2),
                'timeout' => 30,
                'sleep' => 1,
                'max_jobs' => 500,
                'memory' => 256,
            ],
            'images' => [
                'processes' => env('QUEUE_WORKERS_IMAGES', 2),
                'timeout' => 300,
                'sleep' => 5,
                'max_jobs' => 100,
                'memory' => 512,
            ],
            'notifications' => [
                'processes' => env('QUEUE_WORKERS_NOTIFICATIONS', 2),
                'timeout' => 60,
                'sleep' => 2,
                'max_jobs' => 1000,
                'memory' => 128,
            ],
            'reports' => [
                'processes' => env('QUEUE_WORKERS_REPORTS', 1),
                'timeout' => 1800,
                'sleep' => 10,
                'max_jobs' => 10,
                'memory' => 1024,
            ],
        ],

        /**
         * Configuración de monitoreo
         */
        'monitoring' => [
            'enabled' => env('QUEUE_MONITORING_ENABLED', true),
            'metrics' => [
                'job_count' => true,
                'processing_time' => true,
                'failure_rate' => true,
                'memory_usage' => true,
                'queue_size' => true,
            ],
            'alerts' => [
                'high_queue_size' => env('QUEUE_ALERT_SIZE', 1000),
                'high_failure_rate' => env('QUEUE_ALERT_FAILURE_RATE', 10), // Porcentaje
                'slow_processing' => env('QUEUE_ALERT_SLOW_PROCESSING', 300), // segundos
                'worker_down' => env('QUEUE_ALERT_WORKER_DOWN', true),
            ],
            'dashboard' => [
                'enabled' => env('QUEUE_DASHBOARD_ENABLED', true),
                'refresh_interval' => env('QUEUE_DASHBOARD_REFRESH', 5), // segundos
            ],
        ],

        /**
         * Configuración de retry y backoff
         */
        'retry' => [
            'strategies' => [
                'exponential' => [
                    'base_delay' => 1, // segundos
                    'max_delay' => 300, // 5 minutos
                    'multiplier' => 2,
                ],
                'linear' => [
                    'base_delay' => 10,
                    'increment' => 10,
                    'max_delay' => 120,
                ],
                'fixed' => [
                    'delay' => 30,
                ],
            ],
            'default_strategy' => env('QUEUE_RETRY_STRATEGY', 'exponential'),
        ],

        /**
         * Configuración de batching
         */
        'batching' => [
            'enabled' => env('QUEUE_BATCHING_ENABLED', true),
            'default_batch_size' => env('QUEUE_DEFAULT_BATCH_SIZE', 100),
            'max_batch_size' => env('QUEUE_MAX_BATCH_SIZE', 1000),
            'batch_timeout' => env('QUEUE_BATCH_TIMEOUT', 3600), // 1 hora
            'cleanup_batches' => [
                'enabled' => env('QUEUE_CLEANUP_BATCHES', true),
                'retention_hours' => env('QUEUE_BATCH_RETENTION', 24),
                'schedule' => '0 2 * * *', // Diario a las 2 AM
            ],
        ],

        /**
         * Configuración de rate limiting
         */
        'rate_limiting' => [
            'enabled' => env('QUEUE_RATE_LIMITING_ENABLED', true),
            'global_limit' => env('QUEUE_GLOBAL_RATE_LIMIT', 1000), // Jobs por minuto
            'per_queue_limits' => [
                'high' => 500,
                'default' => 300,
                'low' => 100,
                'notifications' => 200,
                'images' => 50,
                'reports' => 10,
            ],
            'per_job_limits' => [
                'email_sending' => 100, // Por minuto
                'push_notifications' => 200,
                'image_processing' => 20,
                'report_generation' => 5,
            ],
        ],

        /**
         * Configuración de seguridad
         */
        'security' => [
            'encryption' => [
                'enabled' => env('QUEUE_ENCRYPTION_ENABLED', false),
                'key' => env('QUEUE_ENCRYPTION_KEY'),
                'cipher' => env('QUEUE_ENCRYPTION_CIPHER', 'AES-256-CBC'),
            ],
            'signing' => [
                'enabled' => env('QUEUE_SIGNING_ENABLED', false),
                'key' => env('QUEUE_SIGNING_KEY'),
                'algorithm' => env('QUEUE_SIGNING_ALGO', 'sha256'),
            ],
            'access_control' => [
                'enabled' => env('QUEUE_ACCESS_CONTROL', false),
                'allowed_ips' => explode(',', env('QUEUE_ALLOWED_IPS', '')),
            ],
        ],

        /**
         * Configuración de desarrollo
         */
        'development' => [
            'debug' => env('QUEUE_DEBUG', false),
            'log_jobs' => env('QUEUE_LOG_JOBS', false),
            'fake_processing' => env('QUEUE_FAKE_PROCESSING', false),
            'testing' => [
                'use_sync_driver' => env('QUEUE_TESTING_SYNC', true),
                'fake_jobs' => env('QUEUE_TESTING_FAKE', false),
            ],
        ],

        /**
         * Configuración de limpieza automática
         */
        'cleanup' => [
            'enabled' => env('QUEUE_CLEANUP_ENABLED', true),
            'schedule' => env('QUEUE_CLEANUP_SCHEDULE', '0 3 * * *'), // Diario a las 3 AM
            'retention' => [
                'completed_jobs' => env('QUEUE_RETENTION_COMPLETED', 24), // horas
                'failed_jobs' => env('QUEUE_RETENTION_FAILED', 168), // 7 días
                'job_batches' => env('QUEUE_RETENTION_BATCHES', 24), // horas
            ],
            'max_records' => [
                'completed_jobs' => env('QUEUE_MAX_COMPLETED', 10000),
                'failed_jobs' => env('QUEUE_MAX_FAILED', 1000),
            ],
        ],
    ],

];
