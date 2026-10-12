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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('mediable_type')->nullable()->index();
            $table->unsignedBigInteger('mediable_id')->nullable()->index();
            $table->string('collection_name')->default('default')->index();
            $table->string('name')->nullable();
            $table->string('file_name');
            $table->string('mime_type')->default('image/webp');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->text('url');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->json('variants')->nullable(); // thumbnail, medium, large URLs
            $table->json('custom_properties')->nullable(); // alt_text, caption, etc.
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
