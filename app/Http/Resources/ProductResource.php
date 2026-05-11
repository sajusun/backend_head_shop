<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'thumbnail' => $this->thumbnail,

            'description' => $this->description,
            'price' => (float) $this->price,
            'offer_price' => $this->calculateOfferPrice(),
            'effective_price' => (float) ($this->calculateOfferPrice() ?? $this->price),
            'stock' => (int) ($this->stock ?? $this->whenLoaded('variants', fn () => $this->variants->sum('stock'), 0)),
            'is_featured' => (bool) $this->is_featured,
            'status' => $this->status,
            'views_count' => (int) $this->views_count,
            'sales_count' => (int) $this->sales_count,
            'ranking_score' => (float) $this->ranking_score,
            'is_promo' => $this->relationLoaded('promotions') 
                ? $this->promotions->where('status', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->count() > 0 
                : false,
            'active_promotion' => $this->relationLoaded('promotions')
                ? $this->promotions->where('status', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->sortByDesc('discount_percentage')
                    ->first()
                : null,
            'primary_image' => $this->relationLoaded('primaryImage') && $this->primaryImage ? [
                'id' => $this->primaryImage->id,
                'image_path' => asset('storage/' . $this->primaryImage->path),
            ] : null,
            'rating' => [
                'average' => round((float) ($this->approved_reviews_avg_rating ?? 0), 2),
                'count' => (int) ($this->approved_reviews_count ?? 0),
            ],
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => $this->whenLoaded(
                'images',
                fn () => ProductImageResource::collection($this->images->where('is_primary', false)->values())->resolve($request),
                []
            ),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'amenities' => $this->relationLoaded('amenityItems')
                ? AmenityResource::collection($this->amenityItems)
                : ($this->amenities ?? []),
            'reviews' => ReviewResource::collection($this->whenLoaded('approvedReviews')),
        ];
    }

    private function calculateOfferPrice(): ?float
    {
        // 1. Manual offer price takes precedence
        if ($this->offer_price !== null) {
            return (float) $this->offer_price;
        }

        // 2. Check for active promotion discount
        if ($this->relationLoaded('promotions')) {
            $promo = $this->promotions->where('status', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->sortByDesc('discount_percentage')
                ->first();

            if ($promo && $promo->discount_percentage > 0) {
                $discounted = $this->price * (1 - ($promo->discount_percentage / 100));
                return (float) round($discounted, 2);
            }
        }

        return null;
    }

    private function thumbnailPath(): ?string
    {
        $path = $this->thumbnail;

        // Fallback to primary image from gallery if thumbnail column is empty
        if (!$path && $this->relationLoaded('primaryImage')) {
            $path = $this->primaryImage?->path;
        }

        return $path ? asset('storage/' . $path) : null;
    }
}
