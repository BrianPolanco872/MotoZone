<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false)->after('product_id');
            $table->string('custom_name')->nullable()->after('is_custom');
            $table->decimal('custom_price', 12, 2)->nullable()->after('custom_name');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['is_custom', 'custom_name', 'custom_price']);
        });
    }
};
