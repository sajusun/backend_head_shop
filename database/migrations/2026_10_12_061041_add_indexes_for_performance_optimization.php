<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'department'], 'products_status_department_idx');
            $table->index(['status', 'is_featured'], 'products_status_featured_idx');
            $table->index(['status', 'fit_type'], 'products_status_fit_idx');
            $table->index('price', 'products_price_idx');
            $table->index('ranking_score', 'products_ranking_score_idx');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->index(['size_id', 'stock'], 'variants_size_stock_idx');
            $table->index(['color_id', 'stock'], 'variants_color_stock_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index(['status', 'department'], 'categories_status_dept_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_status_department_idx');
            $table->dropIndex('products_status_featured_idx');
            $table->dropIndex('products_status_fit_idx');
            $table->dropIndex('products_price_idx');
            $table->dropIndex('products_ranking_score_idx');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('variants_size_stock_idx');
            $table->dropIndex('variants_color_stock_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_status_dept_idx');
        });
    }
};
