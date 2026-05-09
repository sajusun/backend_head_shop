<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'featured_image' => $this->featured_image,
            'status' => $this->status,
            'likes_count' => $this->likes_count,
            'views_count' => $this->views_count,
            'is_liked' => $this->when(isset($this->is_liked), $this->is_liked),
            'published_at' => $this->published_at ? $this->published_at->format('M d, Y') : null,
            'created_at' => $this->created_at->format('M d, Y'),
            
            // Relationships
            'author' => [
                'id' => $this->author->id,
                'name' => $this->author->name,
                'avatar' => $this->author->avatar,
            ],
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            
            // Conditional data for single view (comments)
            'comments' => $this->whenLoaded('comments', function() {
                return $this->comments->map(fn($comment) => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'created_at' => $comment->created_at->diffForHumans(),
                    'user' => [
                        'name' => $comment->user->name,
                        'avatar' => $comment->user->avatar,
                    ],
                ]);
            }),
        ];
    }
}
