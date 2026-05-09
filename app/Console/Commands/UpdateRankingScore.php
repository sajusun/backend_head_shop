<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateRankingScore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:rank {product?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate ranking scores for products';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\RankingService $rankingService)
    {
        $productId = $this->argument('product');
        
        $this->info($productId ? "Updating rank for product #{$productId}..." : "Updating ranks for all products...");
        
        $rankingService->updateRankingScores($productId);
        
        $this->info("Ranking scores updated successfully!");
    }
}
