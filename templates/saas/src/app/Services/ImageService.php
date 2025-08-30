<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

/**
 * ImageService - Servicio para manejo de imágenes con MinIO
 *
 * Características:
 * - Subida optimizada a MinIO
 * - Redimensionamiento automático
 * - Generación de thumbnails
 * - Cache de URLs
 * - Compresión inteligente
 * - Soporte para WebP
 * - Validación de archivos
 * - Limpieza automática
 */
class ImageService
{
    protected $disk;

    protected $publicDisk;

    protected $privateDisk;

    protected $maxFileSize;

    protected $allowedTypes;

    protected $imageSizes;

    protected $quality;

    protected $cacheTime;

    public function __construct()
    {
        $this->disk = Storage::disk(config('filesystems.default'));
        $this->publicDisk = Storage::disk('minio_public');
        $this->privateDisk = Storage::disk('minio_private');
        $this->maxFileSize = config('app.max_image_size_mb', 5) * 1024 * 1024; // MB to bytes
        $this->allowedTypes = explode(',', config('app.allowed_image_types', 'jpg,jpeg,png,webp'));
        $this->imageSizes = $this->parseImageSizes(config('app.image_sizes', 'thumb:150x150,medium:500x500,large:1200x1200'));
        $this->quality = config('app.image_quality', 85);
        $this->cacheTime = config('app.image_cache_ttl', 604800); // 1 week
    }

    /**
     * Sube una imagen con múltiples tamaños
     */
    public function uploadImage(UploadedFile $file, string $folder = 'images', bool $isPublic = true): array
    {
        try {
            // Validar archivo
            $this->validateImage($file);

            // Generar nombre único
            $filename = $this->generateFilename($file);
            $basePath = trim($folder, '/').'/'.$filename;

            // Seleccionar disco según visibilidad
            $disk = $isPublic ? $this->publicDisk : $this->privateDisk;

            // Procesar imagen original
            $image = Image::make($file->getRealPath());
            $this->optimizeImage($image);

            // Subir imagen original
            $originalPath = 'original/'.$basePath;
            $disk->put($originalPath, $image->encode('webp', $this->quality));

            // Generar y subir diferentes tamaños
            $sizes = [];
            foreach ($this->imageSizes as $sizeName => $dimensions) {
                $resizedImage = clone $image;
                $resizedImage->fit($dimensions['width'], $dimensions['height'], function ($constraint) {
                    $constraint->upsize();
                });

                $sizePath = $sizeName.'/'.$basePath;
                $disk->put($sizePath, $resizedImage->encode('webp', $this->quality));

                $sizes[$sizeName] = [
                    'path' => $sizePath,
                    'url' => $this->getImageUrl($sizePath, $isPublic),
                    'width' => $resizedImage->width(),
                    'height' => $resizedImage->height(),
                    'size' => $disk->size($sizePath),
                ];
            }

            // Información de la imagen original
            $result = [
                'original' => [
                    'path' => $originalPath,
                    'url' => $this->getImageUrl($originalPath, $isPublic),
                    'width' => $image->width(),
                    'height' => $image->height(),
                    'size' => $disk->size($originalPath),
                ],
                'sizes' => $sizes,
                'filename' => $filename,
                'folder' => $folder,
                'is_public' => $isPublic,
                'disk' => $isPublic ? 'minio_public' : 'minio_private',
            ];

            // Cache de URLs
            $this->cacheImageUrls($result);

            Log::info('Imagen subida exitosamente', [
                'filename' => $filename,
                'folder' => $folder,
                'sizes' => count($sizes),
                'is_public' => $isPublic,
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('Error al subir imagen', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'folder' => $folder,
            ]);
            throw $e;
        }
    }

    /**
     * Obtiene la URL de una imagen con cache
     */
    public function getImageUrl(string $path, bool $isPublic = true, ?int $ttl = null): string
    {
        $cacheKey = 'image_url_'.md5($path.($isPublic ? 'public' : 'private'));

        return Cache::remember($cacheKey, $ttl ?? $this->cacheTime, function () use ($path, $isPublic) {
            $disk = $isPublic ? $this->publicDisk : $this->privateDisk;

            if ($isPublic) {
                // URL pública directa
                return $disk->url($path);
            } else {
                // URL temporal para archivos privados (24 horas)
                return $disk->temporaryUrl($path, now()->addDay());
            }
        });
    }

    /**
     * Elimina una imagen y todos sus tamaños
     */
    public function deleteImage(array $imageData): bool
    {
        try {
            $disk = Storage::disk($imageData['disk']);
            $deleted = 0;

            // Eliminar imagen original
            if (isset($imageData['original']['path']) && $disk->exists($imageData['original']['path'])) {
                $disk->delete($imageData['original']['path']);
                $deleted++;
            }

            // Eliminar todos los tamaños
            if (isset($imageData['sizes'])) {
                foreach ($imageData['sizes'] as $size) {
                    if ($disk->exists($size['path'])) {
                        $disk->delete($size['path']);
                        $deleted++;
                    }
                }
            }

            // Limpiar cache
            $this->clearImageCache($imageData);

            Log::info('Imagen eliminada', [
                'filename' => $imageData['filename'] ?? 'unknown',
                'files_deleted' => $deleted,
            ]);

            return $deleted > 0;

        } catch (Exception $e) {
            Log::error('Error al eliminar imagen', [
                'error' => $e->getMessage(),
                'image_data' => $imageData,
            ]);

            return false;
        }
    }

    /**
     * Obtiene información de una imagen
     */
    public function getImageInfo(string $path, bool $isPublic = true): ?array
    {
        try {
            $disk = $isPublic ? $this->publicDisk : $this->privateDisk;

            if (! $disk->exists($path)) {
                return null;
            }

            $size = $disk->size($path);
            $lastModified = $disk->lastModified($path);
            $url = $this->getImageUrl($path, $isPublic);

            // Obtener dimensiones si es posible
            $dimensions = null;
            try {
                $imageContent = $disk->get($path);
                $image = Image::make($imageContent);
                $dimensions = [
                    'width' => $image->width(),
                    'height' => $image->height(),
                ];
            } catch (Exception $e) {
                // No se pudieron obtener las dimensiones
            }

            return [
                'path' => $path,
                'url' => $url,
                'size' => $size,
                'last_modified' => $lastModified,
                'dimensions' => $dimensions,
                'is_public' => $isPublic,
            ];

        } catch (Exception $e) {
            Log::error('Error al obtener información de imagen', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);

            return null;
        }
    }

    /**
     * Lista imágenes en una carpeta
     */
    public function listImages(string $folder = '', bool $isPublic = true, int $limit = 100): array
    {
        try {
            $disk = $isPublic ? $this->publicDisk : $this->privateDisk;
            $files = $disk->files($folder);

            $images = [];
            $count = 0;

            foreach ($files as $file) {
                if ($count >= $limit) {
                    break;
                }

                $extension = pathinfo($file, PATHINFO_EXTENSION);
                if (in_array(strtolower($extension), $this->allowedTypes)) {
                    $images[] = $this->getImageInfo($file, $isPublic);
                    $count++;
                }
            }

            return $images;

        } catch (Exception $e) {
            Log::error('Error al listar imágenes', [
                'error' => $e->getMessage(),
                'folder' => $folder,
            ]);

            return [];
        }
    }

    /**
     * Limpia imágenes huérfanas (sin referencias en BD)
     */
    public function cleanupOrphanedImages(array $referencedPaths = []): int
    {
        try {
            $deleted = 0;

            foreach (['minio_public', 'minio_private'] as $diskName) {
                $disk = Storage::disk($diskName);
                $allFiles = $disk->allFiles();

                foreach ($allFiles as $file) {
                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    if (in_array(strtolower($extension), $this->allowedTypes)) {
                        if (! in_array($file, $referencedPaths)) {
                            $disk->delete($file);
                            $deleted++;
                        }
                    }
                }
            }

            Log::info('Limpieza de imágenes huérfanas completada', [
                'deleted_count' => $deleted,
            ]);

            return $deleted;

        } catch (Exception $e) {
            Log::error('Error en limpieza de imágenes', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Valida un archivo de imagen
     */
    protected function validateImage(UploadedFile $file): void
    {
        // Validar tamaño
        if ($file->getSize() > $this->maxFileSize) {
            throw new Exception('El archivo es demasiado grande. Máximo: '.($this->maxFileSize / 1024 / 1024).'MB');
        }

        // Validar tipo
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, $this->allowedTypes)) {
            throw new Exception('Tipo de archivo no permitido. Permitidos: '.implode(', ', $this->allowedTypes));
        }

        // Validar que sea una imagen real
        $imageInfo = getimagesize($file->getRealPath());
        if (! $imageInfo) {
            throw new Exception('El archivo no es una imagen válida');
        }

        // Validar dimensiones mínimas
        if ($imageInfo[0] < 100 || $imageInfo[1] < 100) {
            throw new Exception('La imagen debe tener al menos 100x100 píxeles');
        }
    }

    /**
     * Genera un nombre único para el archivo
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);

        return "{$name}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Optimiza una imagen
     */
    protected function optimizeImage($image): void
    {
        // Orientación automática
        $image->orientate();

        // Limitar dimensiones máximas
        $maxWidth = config('app.image_max_width', 2048);
        $maxHeight = config('app.image_max_height', 2048);

        if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
            $image->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
    }

    /**
     * Parsea la configuración de tamaños de imagen
     */
    protected function parseImageSizes(string $sizesConfig): array
    {
        $sizes = [];
        $sizeStrings = explode(',', $sizesConfig);

        foreach ($sizeStrings as $sizeString) {
            if (preg_match('/^(\w+):(\d+)x(\d+)$/', trim($sizeString), $matches)) {
                $sizes[$matches[1]] = [
                    'width' => (int) $matches[2],
                    'height' => (int) $matches[3],
                ];
            }
        }

        return $sizes;
    }

    /**
     * Cachea las URLs de una imagen
     */
    protected function cacheImageUrls(array $imageData): void
    {
        $isPublic = $imageData['is_public'];

        // Cache URL original
        if (isset($imageData['original']['path'])) {
            $cacheKey = 'image_url_'.md5($imageData['original']['path'].($isPublic ? 'public' : 'private'));
            Cache::put($cacheKey, $imageData['original']['url'], $this->cacheTime);
        }

        // Cache URLs de tamaños
        if (isset($imageData['sizes'])) {
            foreach ($imageData['sizes'] as $size) {
                $cacheKey = 'image_url_'.md5($size['path'].($isPublic ? 'public' : 'private'));
                Cache::put($cacheKey, $size['url'], $this->cacheTime);
            }
        }
    }

    /**
     * Limpia el cache de una imagen
     */
    protected function clearImageCache(array $imageData): void
    {
        $isPublic = $imageData['is_public'];

        // Limpiar cache de URL original
        if (isset($imageData['original']['path'])) {
            $cacheKey = 'image_url_'.md5($imageData['original']['path'].($isPublic ? 'public' : 'private'));
            Cache::forget($cacheKey);
        }

        // Limpiar cache de URLs de tamaños
        if (isset($imageData['sizes'])) {
            foreach ($imageData['sizes'] as $size) {
                $cacheKey = 'image_url_'.md5($size['path'].($isPublic ? 'public' : 'private'));
                Cache::forget($cacheKey);
            }
        }
    }

    /**
     * Obtiene estadísticas de uso de almacenamiento
     */
    public function getStorageStats(): array
    {
        try {
            $stats = [
                'public' => $this->getDiskStats('minio_public'),
                'private' => $this->getDiskStats('minio_private'),
            ];

            $stats['total'] = [
                'files' => $stats['public']['files'] + $stats['private']['files'],
                'size' => $stats['public']['size'] + $stats['private']['size'],
            ];

            return $stats;

        } catch (Exception $e) {
            Log::error('Error al obtener estadísticas de almacenamiento', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Obtiene estadísticas de un disco específico
     */
    protected function getDiskStats(string $diskName): array
    {
        $disk = Storage::disk($diskName);
        $files = $disk->allFiles();

        $imageFiles = 0;
        $totalSize = 0;

        foreach ($files as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (in_array(strtolower($extension), $this->allowedTypes)) {
                $imageFiles++;
                $totalSize += $disk->size($file);
            }
        }

        return [
            'files' => $imageFiles,
            'size' => $totalSize,
            'size_human' => $this->formatBytes($totalSize),
        ];
    }

    /**
     * Formatea bytes en formato legible
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
