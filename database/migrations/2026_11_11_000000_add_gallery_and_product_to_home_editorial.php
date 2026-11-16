<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['home_pages', 'categories', 'home_publications'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->json('gallery')->nullable();
            });
        }

        Schema::table('home_publications', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('body')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('home_publications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });

        foreach (['home_pages', 'categories', 'home_publications'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('gallery');
            });
        }
    }
};
