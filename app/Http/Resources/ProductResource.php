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
            'thumbnail_url' => $this->thumbnail ? asset('storage/' . $this->thumbnail) : null,
            'description' => $this->description,
            'price' => (float) $this->price,
            'offer_price' => $this->offer_price !== null ? (float) $this->offer_price : null,
            'effective_price' => (float) ($this->offer_price ?? $this->price),
            'is_featured' => (bool) $this->is_featured,
            'status' => $this->status,
            'rating' => [
                'average' => round((float) ($this->approved_reviews_avg_rating ?? 0), 2),
                'count' => (int) ($this->approved_reviews_count ?? 0),
            ],
            'category' => new CategoryResource($this->whenLoaded('category')),
            'primary_image' => new ProductImageResource($this->whenLoaded('primaryImage')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'amenities' => $this->relationLoaded('amenityItems')
                ? AmenityResource::collection($this->amenityItems)
                : ($this->amenities ?? []),
            'reviews' => ReviewResource::collection($this->whenLoaded('approvedReviews')),
        ];
    }
}
