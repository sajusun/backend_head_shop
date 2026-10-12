<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'collection_name',
        'name',
        'file_name',
        'mime_type',
        'disk',
        'path',
        'url',
        'size_bytes',
        'width',
        'height',
        'variants',
        'custom_properties',
        'order_column',
    ];

    protected $casts = [
        'variants' => 'array',
        'custom_properties' => 'array',
        'size_bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'order_column' => 'integer',
    ];

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get specific variant URL (thumbnail, medium, large, original)
     */
    public function getVariantUrl(string $variant = 'medium'): string
    {
        if (!empty($this->variants) && isset($this->variants[$variant])) {
            return $this->variants[$variant];
        }

        return $this->url;
    }

    /**
     * Delete physical files from disk when model is deleted.
     */
    protected static function booted()
    {
        static::deleting(function (Media $media) {
            $disk = Storage::disk($media->disk);
            if ($disk->exists($media->path)) {
                $disk->delete($media->path);
            }

            if (!empty($media->variants) && is_array($media->variants)) {
                foreach ($media->variants as $variantPath) {
                    // Extract relative path from variant URL or if path is stored
                    $relPath = str_replace(url('storage/'), '', $variantPath);
                    $relPath = ltrim(parse_url($relPath, PHP_URL_PATH) ?? '', '/');
                    if ($disk->exists($relPath)) {
                        $disk->delete($relPath);
                    }
                }
            }
        });
    }
}
