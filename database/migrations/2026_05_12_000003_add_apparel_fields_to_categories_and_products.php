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
        Schema::table('categories', function (Blueprint $table) {
            $table->string('department')->default('unisex')->after('type')->index();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('department')->default('unisex')->after('category_id')->index();
            $table->string('fabric')->nullable()->after('description');
            $table->string('gsm')->nullable()->after('fabric');
            $table->string('fit_type')->nullable()->after('gsm'); // Oversized, Slim Fit, Regular, Relaxed
            $table->json('care_instructions')->nullable()->after('fit_type');
            $table->json('model_info')->nullable()->after('care_instructions');
            $table->foreignId('size_chart_id')->nullable()->after('model_info')->constrained('size_charts')->nullOnDelete();
            $table->json('outfit_product_ids')->nullable()->after('size_chart_id'); // Complete the look bundle items
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['size_chart_id']);
            $table->dropColumn([
                'department',
                'fabric',
                'gsm',
                'fit_type',
                'care_instructions',
                'model_info',
                'size_chart_id',
                'outfit_product_ids',
            ]);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }
};
