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
        Schema::create('size_charts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Men's T-Shirt Standard Fit"
            $table->string('department')->default('men'); // men, women, kids, baby, unisex
            $table->string('category_type')->nullable(); // topwear, bottomwear, footwear, etc.
            $table->string('unit')->default('inch'); // inch, cm
            $table->json('columns'); // ['Size', 'Chest', 'Length', 'Shoulder', 'Sleeve']
            $table->json('rows'); // [['S', '38', '27', '17.5', '8'], ['M', '40', '28', '18.5', '8.5'], ...]
            $table->text('how_to_measure')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('size_charts');
    }
};
