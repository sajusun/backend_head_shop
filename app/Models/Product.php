<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'department',
        'name',
        'slug',
        'sku',
        'thumbnail',
        'description',
        'fabric',
        'gsm',
        'fit_type',
        'care_instructions',
        'model_info',
        'size_chart_id',
        'outfit_product_ids',
        'price',
        'offer_price',
        'stock',
        'amenities',
        'is_featured',
        'status',
        'views_count',
        'sales_count',
        'ranking_score',
        'last_ranked_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
        'offer_price' => 'decimal:2',
        'stock' => 'integer',
        'amenities' => 'array',
        'care_instructions' => 'array',
        'model_info' => 'array',
        'outfit_product_ids' => 'array',
        'views_count' => 'integer',
        'sales_count' => 'integer',
        'ranking_score' => 'decimal:4',
        'last_ranked_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sizeChart(): BelongsTo
    {
        return $this->belongsTo(SizeChart::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('order_column');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true)->oldest('order');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(Review::class)->where('status', 'approved')->whereNull('parent_id');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function amenityItems()
    {
        return $this->belongsToMany(Amenity::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class);
    }

    public function scopeOrderByRank($query)
    {
        return $query->orderBy('ranking_score', 'desc');
    }

    /**
     * Resolve thumbnail url from media, primaryImage or legacy path.
     */
    protected function thumbnail(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value) {
                if ($this->relationLoaded('media')) {
                    $thumbMedia = $this->media->firstWhere('collection_name', 'thumbnail') ?? $this->media->first();
                    if ($thumbMedia) {
                        return $thumbMedia->getVariantUrl('medium');
                    }
                }

                if (!$value && $this->relationLoaded('primaryImage')) {
                    $value = $this->primaryImage?->path;
                }

                if (!$value) {
                    return null;
                }

                if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                    return $value;
                }

                return asset('storage/' . $value);
            },
        );
    }

    /**
     * Get related bundle outfit products.
     */
    public function getOutfitProductsAttribute()
    {
        if (empty($this->outfit_product_ids) || !is_array($this->outfit_product_ids)) {
            return collect();
        }

        return self::whereIn('id', $this->outfit_product_ids)
            ->where('status', 'published')
            ->get();
    }
}
