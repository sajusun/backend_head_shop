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
            'department' => $this->department ?? 'unisex',
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'thumbnail' => $this->thumbnail,

            'description' => $this->description,
            'fabric' => $this->fabric,
            'gsm' => $this->gsm,
            'fit_type' => $this->fit_type,
            'care_instructions' => $this->care_instructions ?? [],
            'model_info' => $this->model_info ?? null,
            'size_chart' => $this->whenLoaded('sizeChart'),

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
            'media' => $this->whenLoaded('media', fn () => $this->media->map(fn ($m) => [
                'id' => $m->id,
                'collection' => $m->collection_name,
                'url' => $m->url,
                'variants' => $m->variants,
            ])),
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
            'outfit_products' => $this->relationLoaded('outfit_products') || !empty($this->outfit_product_ids)
                ? $this->outfit_products->map(fn ($op) => [
                    'id' => $op->id,
                    'name' => $op->name,
                    'slug' => $op->slug,
                    'price' => (float) $op->price,
                    'offer_price' => (float) ($op->offer_price ?? $op->price),
                    'thumbnail' => $op->thumbnail,
                ])
                : [],
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
}
