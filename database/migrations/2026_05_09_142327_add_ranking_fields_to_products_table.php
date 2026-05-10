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
            $table->unsignedBigInteger('views_count')->default(0)->after('status');
            $table->unsignedBigInteger('sales_count')->default(0)->after('views_count');
            $table->decimal('ranking_score', 12, 4)->default(0)->after('sales_count');
            $table->timestamp('last_ranked_at')->nullable()->after('ranking_score');
            $table->index('ranking_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['views_count', 'sales_count', 'ranking_score', 'last_ranked_at']);
        });
    }
};
