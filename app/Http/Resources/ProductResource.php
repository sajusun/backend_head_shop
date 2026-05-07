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
            'thumbnail' => $this->thumbnailPath(),
            'description' => $this->description,
            'price' => (float) $this->price,
            'offer_price' => $this->offer_price !== null ? (float) $this->offer_price : null,
            'effective_price' => (float) ($this->offer_price ?? $this->price),
            'stock' => (int) ($this->stock ?? $this->whenLoaded('variants', fn () => $this->variants->sum('stock'), 0)),
            'is_featured' => (bool) $this->is_featured,
            'status' => $this->status,
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

    private function thumbnailPath(): ?string
    {
        $path = $this->relationLoaded('primaryImage') ? $this->primaryImage?->path : $this->thumbnail;

        return $path ? asset('storage/' . $path) : null;
    }
}
