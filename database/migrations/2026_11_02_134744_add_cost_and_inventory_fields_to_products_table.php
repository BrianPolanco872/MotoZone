<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost', 10, 2)->default(0)->after('price');
            $table->decimal('wholesale_price', 10, 2)->nullable()->after('cost');
            $table->unsignedInteger('min_stock')->default(0)->after('stock');
            $table->boolean('track_inventory')->default(true)->after('min_stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['cost', 'wholesale_price', 'min_stock', 'track_inventory']);
        });
    }
};
