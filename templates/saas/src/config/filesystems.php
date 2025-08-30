<?php

/**
 * TacoCraft SAAS - Filesystem Configuration
 * Configuración optimizada para MinIO y almacenamiento local
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'minio'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        /**
         * MinIO S3-Compatible Storage
         * Configuración principal para almacenamiento de imágenes
         */
        'minio' => [
            'driver' => 's3',
            'key' => env('MINIO_KEY'),
            'secret' => env('MINIO_SECRET'),
            'region' => env('MINIO_REGION', 'us-east-1'),
            'bucket' => env('MINIO_BUCKET'),
            'endpoint' => env('MINIO_ENDPOINT'),
            'use_path_style_endpoint' => env('MINIO_USE_PATH_STYLE_ENDPOINT', true),
            'url' => env('MINIO_URL'),
            'visibility' => 'public',
            'throw' => false,

            // Configuraciones adicionales para optimización
            'options' => [
                'CacheControl' => 'max-age=604800', // 7 días
                'Metadata' => [
                    'app' => 'tacocraft-saas',
                    'version' => '1.0.0',
                ],
            ],

            // Configuración de CDN (opcional)
            'cdn_url' => env('CDN_URL'),
            'use_presigned' => env('MINIO_USE_PRESIGNED_URLS', false),
            'presigned_ttl' => env('MINIO_PRESIGNED_TTL', 3600), // 1 hora
        ],

        /**
         * MinIO Privado para archivos sensibles
         */
        'minio_private' => [
            'driver' => 's3',
            'key' => env('MINIO_KEY'),
            'secret' => env('MINIO_SECRET'),
            'region' => env('MINIO_REGION', 'us-east-1'),
            'bucket' => env('MINIO_PRIVATE_BUCKET', env('MINIO_BUCKET').'-private'),
            'endpoint' => env('MINIO_ENDPOINT'),
            'use_path_style_endpoint' => env('MINIO_USE_PATH_STYLE_ENDPOINT', true),
            'visibility' => 'private',
            'throw' => false,
        ],

        /**
         * Backup Storage (para respaldos)
         */
        'backup' => [
            'driver' => 'local',
            'root' => storage_path('backups'),
            'throw' => false,
        ],

        /**
         * Configuración para AWS S3 (migración futura)
         */
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        /**
         * Configuración para Azure Blob Storage (alternativa)
         */
        'azure' => [
            'driver' => 'azure',
            'name' => env('AZURE_STORAGE_NAME'),
            'key' => env('AZURE_STORAGE_KEY'),
            'container' => env('AZURE_STORAGE_CONTAINER'),
            'url' => env('AZURE_STORAGE_URL'),
            'prefix' => env('AZURE_STORAGE_PREFIX', ''),
        ],

        /**
         * Configuración para Google Cloud Storage (alternativa)
         */
        'gcs' => [
            'driver' => 'gcs',
            'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
            'key_file' => env('GOOGLE_CLOUD_KEY_FILE'),
            'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET'),
            'path_prefix' => env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX', ''),
            'storage_api_uri' => env('GOOGLE_CLOUD_STORAGE_API_URI'),
        ],

        /**
         * Configuración para FTP (legacy)
         */
        'ftp' => [
            'driver' => 'ftp',
            'host' => env('FTP_HOST'),
            'username' => env('FTP_USERNAME'),
            'password' => env('FTP_PASSWORD'),
            'port' => env('FTP_PORT', 21),
            'root' => env('FTP_ROOT'),
            'passive' => true,
            'ssl' => true,
            'timeout' => 30,
        ],

        /**
         * Configuración para SFTP
         */
        'sftp' => [
            'driver' => 'sftp',
            'host' => env('SFTP_HOST'),
            'username' => env('SFTP_USERNAME'),
            'password' => env('SFTP_PASSWORD'),
            'privateKey' => env('SFTP_PRIVATE_KEY'),
            'passphrase' => env('SFTP_PASSPHRASE'),
            'port' => env('SFTP_PORT', 22),
            'root' => env('SFTP_ROOT'),
            'timeout' => 30,
            'useAgent' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración Personalizada para TacoCraft SAAS
    |--------------------------------------------------------------------------
    |
    | Configuraciones específicas para el manejo de archivos en el SAAS
    |
    */

    'tacocraft' => [

        /**
         * Configuración de imágenes
         */
        'images' => [
            'disk' => 'minio',
            'path_prefix' => 'restaurants',
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_size' => env('MAX_IMAGE_SIZE_MB', 5) * 1024 * 1024, // MB a bytes
            'quality' => env('IMAGE_QUALITY', 85),
            'format' => env('IMAGE_FORMAT', 'webp'),

            'sizes' => [
                'thumb' => [150, 150],
                'medium' => [500, 500],
                'large' => [1200, 1200],
            ],

            'cache_ttl' => env('IMAGE_CACHE_TTL', 604800), // 7 días
        ],

        /**
         * Configuración de documentos
         */
        'documents' => [
            'disk' => 'minio_private',
            'path_prefix' => 'documents',
            'allowed_extensions' => ['pdf', 'doc', 'docx', 'txt'],
            'max_size' => 10 * 1024 * 1024, // 10MB
        ],

        /**
         * Configuración de QR codes
         */
        'qr_codes' => [
            'disk' => 'minio',
            'path_prefix' => 'qr-codes',
            'size' => env('QR_CODE_SIZE', 300),
            'margin' => env('QR_CODE_MARGIN', 2),
            'format' => env('QR_CODE_FORMAT', 'png'),
            'cache_ttl' => 86400, // 1 día
        ],

        /**
         * Configuración de backups
         */
        'backups' => [
            'disk' => 'backup',
            'retention_days' => env('BACKUP_RETENTION_DAYS', 7),
            'compress' => true,
            'encrypt' => false,
        ],

        /**
         * Configuración de logs
         */
        'logs' => [
            'disk' => 'local',
            'path_prefix' => 'logs',
            'retention_days' => 30,
            'compress_after_days' => 7,
        ],

        /**
         * Configuración de cache de archivos
         */
        'file_cache' => [
            'enabled' => env('FILE_CACHE_ENABLED', true),
            'ttl' => [
                'images' => 604800, // 7 días
                'documents' => 3600, // 1 hora
                'qr_codes' => 86400, // 1 día
            ],
        ],

        /**
         * Configuración de CDN
         */
        'cdn' => [
            'enabled' => env('CDN_ENABLED', false),
            'url' => env('CDN_URL'),
            'zones' => [
                'images' => env('CDN_IMAGES_ZONE'),
                'static' => env('CDN_STATIC_ZONE'),
            ],
        ],

        /**
         * Configuración de optimización
         */
        'optimization' => [
            'enabled' => env('IMAGE_OPTIMIZATION', true),
            'lazy_loading' => env('LAZY_LOADING_ENABLED', true),
            'webp_conversion' => env('WEBP_CONVERSION', true),
            'progressive_jpeg' => true,
            'strip_metadata' => true,
        ],

        /**
         * Configuración de seguridad
         */
        'security' => [
            'scan_uploads' => env('SCAN_UPLOADS', false),
            'allowed_mime_types' => [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
                'application/pdf',
            ],
            'virus_scan' => env('VIRUS_SCAN_ENABLED', false),
        ],

        /**
         * Configuración de monitoreo
         */
        'monitoring' => [
            'track_usage' => env('TRACK_STORAGE_USAGE', true),
            'alert_threshold' => env('STORAGE_ALERT_THRESHOLD', 80), // Porcentaje
            'cleanup_enabled' => env('AUTO_CLEANUP_ENABLED', true),
            'cleanup_schedule' => env('CLEANUP_SCHEDULE', '0 2 * * *'), // Diario a las 2 AM
        ],
    ],

];
