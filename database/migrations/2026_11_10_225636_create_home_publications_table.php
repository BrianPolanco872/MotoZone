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
        Schema::create('home_publications', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('link_url', 500)->nullable();
            $table->boolean('is_visible')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->index(['is_visible', 'position', 'id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_publications');
    }
};
