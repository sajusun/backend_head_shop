<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class MediaService
{
    protected ImageManager $imageManager;
    protected string $defaultDisk;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new GdDriver());
        $this->defaultDisk = config('filesystems.default', 'public');
    }

    /**
     * Upload an image, convert to WebP, generate variants and save in media table.
     *
     * @param UploadedFile|string $file
     * @param string $folder (e.g., 'products', 'banners', 'categories')
     * @param Model|null $model
     * @param string $collectionName ('default', 'thumbnail', 'gallery', 'avatar')
     * @param array $customProperties
     * @return Media
     */
    public function uploadImage(
        UploadedFile|string $file,
        string $folder = 'uploads',
        ?Model $model = null,
        string $collectionName = 'default',
        array $customProperties = []
    ): Media {
        $diskName = $this->defaultDisk;
        $disk = Storage::disk($diskName);

        $uuid = Str::uuid()->toString();
        $baseFilename = $uuid . '.webp';
        $relativeDir = trim($folder, '/') . '/' . date('Y/m');
        $originalPath = $relativeDir . '/' . $baseFilename;

        // Process image with Intervention
        $image = $this->imageManager->read($file);
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        // Encode full-size as WebP (85% quality)
        $encodedWebp = $image->toWebp(85);
        $disk->put($originalPath, (string) $encodedWebp, 'public');

        $originalUrl = $disk->url($originalPath);
        $fileSize = strlen((string) $encodedWebp);

        // Generate responsive variants (thumbnail, medium, large)
        $variants = [];

        // 1. Thumbnail (150x150 cover/contain)
        $thumbPath = $relativeDir . '/' . $uuid . '-thumb.webp';
        $thumbImage = clone $image;
        $thumbImage->scaleDown(width: 200, height: 200);
        $disk->put($thumbPath, (string) $thumbImage->toWebp(80), 'public');
        $variants['thumbnail'] = $disk->url($thumbPath);

        // 2. Medium (600x800 max for catalog/feed)
        $mediumPath = $relativeDir . '/' . $uuid . '-medium.webp';
        $mediumImage = clone $image;
        $mediumImage->scaleDown(width: 600, height: 800);
        $disk->put($mediumPath, (string) $mediumImage->toWebp(82), 'public');
        $variants['medium'] = $disk->url($mediumPath);

        // 3. Large (1200x1600 max for zoom)
        $largePath = $relativeDir . '/' . $uuid . '-large.webp';
        $largeImage = clone $image;
        $largeImage->scaleDown(width: 1200, height: 1600);
        $disk->put($largePath, (string) $largeImage->toWebp(85), 'public');
        $variants['large'] = $disk->url($largePath);

        $originalName = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);

        return Media::create([
            'mediable_type' => $model ? get_class($model) : null,
            'mediable_id' => $model ? $model->getKey() : null,
            'collection_name' => $collectionName,
            'name' => pathinfo($originalName, PATHINFO_FILENAME),
            'file_name' => $baseFilename,
            'mime_type' => 'image/webp',
            'disk' => $diskName,
            'path' => $originalPath,
            'url' => $originalUrl,
            'size_bytes' => $fileSize,
            'width' => $originalWidth,
            'height' => $originalHeight,
            'variants' => $variants,
            'custom_properties' => $customProperties,
        ]);
    }

    /**
     * Delete a media item and all its variant files.
     */
    public function deleteMedia(Media $media): bool
    {
        return $media->delete();
    }
}
