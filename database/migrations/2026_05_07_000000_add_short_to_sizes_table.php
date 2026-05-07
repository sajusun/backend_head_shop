<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sizes') && ! Schema::hasColumn('sizes', 'short')) {
            Schema::table('sizes', function (Blueprint $table) {
                $table->string('short', 10)->unique()->after('slug');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sizes') && Schema::hasColumn('sizes', 'short')) {
            Schema::table('sizes', function (Blueprint $table) {
                $table->dropUnique(['short']);
                $table->dropColumn('short');
            });
        }
    }
};
