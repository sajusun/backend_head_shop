<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class RankingService
{
    /**
     * Calculate and update the ranking score for all products or a specific product.
     */
    public function updateRankingScores($productId = null)
    {
        $query = Product::query();

        if ($productId) {
            $query->where('id', $productId);
        }

        $query->chunkById(100, function ($products) {
            foreach ($products as $product) {
                $score = $this->calculateScore($product);
                $product->update([
                    'ranking_score' => $score,
                    'last_ranked_at' => now(),
                ]);
            }
        });
    }

    /**
     * Calculate the ranking score for a single product.
     */
    public function calculateScore(Product $product)
    {
        // Weights
        $wSales = 0.40;
        $wViews = 0.20;
        $wRating = 0.20;
        $wRecency = 0.10;
        $wStock = 0.10;

        // 1. Sales Score (Log scale to prevent extreme outliers)
        $salesScore = log($product->sales_count + 1, 10);

        // 2. Popularity Score (Views)
        $viewsScore = log($product->views_count + 1, 10);

        // 3. Rating Score (Normalized 0-5)
        $avgRating = $product->reviews()->where('status', 'approved')->avg('rating') ?: 0;
        $ratingScore = $avgRating; // 0 to 5

        // 4. Recency Score (Newer is better, linear decay over 30 days)
        $daysOld = $product->created_at->diffInDays(now());
        $recencyScore = max(0, 1 - ($daysOld / 30)) * 5; // 0 to 5

        // 5. Stock Bonus
        $stockScore = $product->stock > 0 ? 5 : 0;

        // 6. Featured Bonus
        $featuredBonus = $product->is_featured ? 2 : 0;

        // Final Calculation
        // Normalizing weights for a readable score (e.g. 0 to 100)
        $finalScore = (
            ($salesScore * 10 * $wSales) + 
            ($viewsScore * 5 * $wViews) + 
            ($ratingScore * 4 * $wRating) + 
            ($recencyScore * 4 * $wRecency) + 
            ($stockScore * 4 * $wStock) +
            ($featuredBonus)
        );

        return round($finalScore, 4);
    }

    /**
     * Increment views count for a product.
     */
    public function incrementViews(Product $product)
    {
        $product->increment('views_count');
    }

    /**
     * Increment sales count for a product.
     */
    public function incrementSales(Product $product, $quantity = 1)
    {
        $product->increment('sales_count', $quantity);
    }
}
