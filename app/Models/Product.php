<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'thumbnail',
        'description',
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
        'views_count' => 'integer',
        'sales_count' => 'integer',
        'ranking_score' => 'decimal:4',
        'last_ranked_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
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

    public function scopeOrderByRank($query)
    {
        return $query->orderBy('ranking_score', 'desc');
    }

    protected function thumbnail(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value) {
                if (!$value && $this->relationLoaded('primaryImage')) {
                    $value = $this->primaryImage?->path;
                }
                return $value ? asset('storage/' . $value) : null;
            },
        );
    }
}
