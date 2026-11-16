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
        foreach (['products', 'categories'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->boolean('home_visible')->default(false);
                $table->unsignedInteger('home_position')->default(0);
                $table->index(['home_visible', 'home_position', 'id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['products', 'categories'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['home_visible', 'home_position', 'id']);
                $table->dropColumn(['home_visible', 'home_position']);
            });
        }
    }
};
