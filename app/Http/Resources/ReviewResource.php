<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'replies' => ReviewResource::collection($this->whenLoaded('approvedReplies')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
