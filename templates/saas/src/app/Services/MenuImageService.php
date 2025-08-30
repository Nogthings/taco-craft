<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

/**
 * TacoCraft SAAS - Menu Image Service
 *
 * Servicio optimizado para el manejo de imágenes de menús con MinIO
 * Incluye optimización automática, múltiples tamaños y cache
 */
class MenuImageService
{
    private $disk;

    private $cacheDuration = 7200; // 2 horas

    private $bucket;

    /**
     * Tamaños de imagen configurables
     */
    private $imageSizes = [
        'thumb' => [150, 150],
        'medium' => [500, 500],
        'large' => [1200, 1200],
    ];

    /**
     * Formatos de imagen permitidos
     */
    private $allowedFormats = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Calidad de compresión por defecto
     */
    private $defaultQuality = 85;

    public function __construct()
    {
        $this->disk = Storage::disk('minio');
        $this->bucket = config('filesystems.disks.minio.bucket');

        // Configurar tamaños desde config si existe
        if (config('app.image_sizes')) {
            $this->imageSizes = $this->parseImageSizes(config('app.image_sizes'));
        }

        $this->defaultQuality = config('app.image_quality', 85);
    }

    /**
     * Subir imagen de menú con optimización automática
     */
    public function uploadMenuImage(UploadedFile $file, int $restaurantId, ?string $category = null): array
    {
        // Validar archivo
        $this->validateImage($file);

        // Generar nombre único
        $fileName = $this->generateFileName($file);

        // Generar múltiples tamaños
        $paths = $this->generateImageSizes($file, $restaurantId, $fileName, $category);

        // Limpiar cache relacionado
        $this->clearImageCache($restaurantId);

        return [
            'original_name' => $file->getClientOriginalName(),
            'file_name' => $fileName,
            'paths' => $paths,
            'urls' => $this->generateImageUrls($paths),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }

    /**
     * Obtener URL de imagen con cache
     */
    public function getImageUrl(string $path, string $size = 'medium'): string
    {
        $cacheKey = "image_url:{$path}:{$size}";

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($path, $size) {
            $fullPath = $this->buildPath($path, $size);

            // Si tienes CDN configurado
            if (config('filesystems.disks.minio.cdn_url')) {
                return config('filesystems.disks.minio.cdn_url').'/'.$fullPath;
            }

            // Si usas URLs pre-firmadas
            if (config('filesystems.disks.minio.use_presigned')) {
                return $this->disk->temporaryUrl($fullPath, now()->addHours(3));
            }

            // URL pública directa
            return $this->disk->url($fullPath);
        });
    }

    /**
     * Generar URLs para todos los tamaños de una imagen
     */
    public function generateImageUrls(array $paths): array
    {
        $urls = [];

        foreach ($paths as $size => $path) {
            $urls[$size] = $this->getImageUrl($path, $size);
        }

        return $urls;
    }

    /**
     * Precargar URLs de imágenes para múltiples items
     *
     * @param  \Illuminate\Support\Collection  $menuItems
     */
    public function preloadMenuImages($menuItems): array
    {
        $urls = [];

        foreach ($menuItems as $item) {
            if ($item->image_path) {
                $urls[$item->id] = [];

                foreach (array_keys($this->imageSizes) as $size) {
                    $urls[$item->id][$size] = $this->getImageUrl($item->image_path, $size);
                }
            }
        }

        return $urls;
    }

    /**
     * Eliminar imagen y todos sus tamaños
     */
    public function deleteImage(string $path, int $restaurantId): bool
    {
        try {
            $deleted = true;

            // Eliminar todos los tamaños
            foreach (array_keys($this->imageSizes) as $size) {
                $fullPath = $this->buildPath($path, $size);

                if ($this->disk->exists($fullPath)) {
                    $deleted = $deleted && $this->disk->delete($fullPath);
                }
            }

            // Limpiar cache
            $this->clearImageCache($restaurantId, $path);

            return $deleted;

        } catch (Exception $e) {
            \Log::error('Error eliminando imagen: '.$e->getMessage(), [
                'path' => $path,
                'restaurant_id' => $restaurantId,
            ]);

            return false;
        }
    }

    /**
     * Optimizar imagen existente
     */
    public function optimizeExistingImage(string $path): bool
    {
        try {
            foreach (array_keys($this->imageSizes) as $size) {
                $fullPath = $this->buildPath($path, $size);

                if ($this->disk->exists($fullPath)) {
                    $imageContent = $this->disk->get($fullPath);
                    $optimizedImage = $this->optimizeImage($imageContent, $size);
                    $this->disk->put($fullPath, $optimizedImage);
                }
            }

            return true;

        } catch (Exception $e) {
            \Log::error('Error optimizando imagen: '.$e->getMessage(), [
                'path' => $path,
            ]);

            return false;
        }
    }

    /**
     * Obtener estadísticas de almacenamiento
     */
    public function getStorageStats(int $restaurantId): array
    {
        $cacheKey = "storage_stats:{$restaurantId}";

        return Cache::remember($cacheKey, 3600, function () use ($restaurantId) {
            $basePath = "restaurants/{$restaurantId}/";
            $files = $this->disk->allFiles($basePath);

            $totalSize = 0;
            $imageCount = 0;
            $sizesByType = [];

            foreach ($files as $file) {
                $size = $this->disk->size($file);
                $totalSize += $size;
                $imageCount++;

                // Determinar tipo de imagen por path
                foreach (array_keys($this->imageSizes) as $sizeType) {
                    if (strpos($file, "/{$sizeType}/") !== false) {
                        $sizesByType[$sizeType] = ($sizesByType[$sizeType] ?? 0) + $size;
                        break;
                    }
                }
            }

            return [
                'total_size' => $totalSize,
                'total_size_human' => $this->formatBytes($totalSize),
                'image_count' => $imageCount,
                'sizes_breakdown' => $sizesByType,
                'average_size' => $imageCount > 0 ? $totalSize / $imageCount : 0,
            ];
        });
    }

    /**
     * Validar archivo de imagen
     *
     * @throws \InvalidArgumentException
     */
    private function validateImage(UploadedFile $file): void
    {
        // Validar extensión
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, $this->allowedFormats)) {
            throw new \InvalidArgumentException(
                'Formato de imagen no permitido. Formatos válidos: '.implode(', ', $this->allowedFormats)
            );
        }

        // Validar tamaño
        $maxSize = config('app.max_image_size_mb', 5) * 1024 * 1024; // MB a bytes
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException(
                'La imagen es demasiado grande. Tamaño máximo: '.config('app.max_image_size_mb', 5).'MB'
            );
        }

        // Validar que sea una imagen válida
        if (! getimagesize($file->getPathname())) {
            throw new \InvalidArgumentException('El archivo no es una imagen válida');
        }
    }

    /**
     * Generar nombre único para archivo
     */
    private function generateFileName(UploadedFile $file): string
    {
        $extension = config('app.image_format', 'webp');

        return Str::uuid().'.'.$extension;
    }

    /**
     * Generar múltiples tamaños de imagen
     */
    private function generateImageSizes(UploadedFile $file, int $restaurantId, string $fileName, ?string $category = null): array
    {
        $paths = [];
        $baseDir = $category ? "restaurants/{$restaurantId}/{$category}" : "restaurants/{$restaurantId}/menu";

        foreach ($this->imageSizes as $size => $dimensions) {
            $optimizedImage = $this->createOptimizedImage($file, $dimensions, $size);
            $path = "{$baseDir}/{$size}/{$fileName}";

            $this->disk->put($path, $optimizedImage);
            $paths[$size] = $path;
        }

        return $paths;
    }

    /**
     * Crear imagen optimizada
     */
    private function createOptimizedImage(UploadedFile $file, array $dimensions, string $size): string
    {
        $image = Image::make($file)
            ->resize($dimensions[0], $dimensions[1], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

        // Aplicar filtros según el tamaño
        if ($size === 'thumb') {
            $image->sharpen(10);
        }

        // Codificar en formato optimizado
        $format = config('app.image_format', 'webp');
        $quality = $this->getQualityForSize($size);

        return $image->encode($format, $quality);
    }

    /**
     * Obtener calidad según tamaño
     */
    private function getQualityForSize(string $size): int
    {
        $qualities = [
            'thumb' => 75,
            'medium' => $this->defaultQuality,
            'large' => 90,
        ];

        return $qualities[$size] ?? $this->defaultQuality;
    }

    /**
     * Construir path completo
     */
    private function buildPath(string $basePath, string $size): string
    {
        $pathInfo = pathinfo($basePath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['basename'];

        return "{$directory}/{$size}/{$filename}";
    }

    /**
     * Limpiar cache de imágenes
     */
    private function clearImageCache(int $restaurantId, ?string $specificPath = null): void
    {
        if ($specificPath) {
            // Limpiar cache específico
            foreach (array_keys($this->imageSizes) as $size) {
                Cache::forget("image_url:{$specificPath}:{$size}");
            }
        }

        // Limpiar cache de estadísticas
        Cache::forget("storage_stats:{$restaurantId}");
    }

    /**
     * Parsear configuración de tamaños
     */
    private function parseImageSizes(string $sizesConfig): array
    {
        $sizes = [];
        $pairs = explode(',', $sizesConfig);

        foreach ($pairs as $pair) {
            [$name, $dimensions] = explode(':', $pair);
            [$width, $height] = explode('x', $dimensions);
            $sizes[trim($name)] = [(int) $width, (int) $height];
        }

        return $sizes;
    }

    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * Optimizar imagen existente
     */
    private function optimizeImage(string $imageContent, string $size): string
    {
        $image = Image::make($imageContent);
        $dimensions = $this->imageSizes[$size];

        $image->resize($dimensions[0], $dimensions[1], function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $format = config('app.image_format', 'webp');
        $quality = $this->getQualityForSize($size);

        return $image->encode($format, $quality);
    }
}
