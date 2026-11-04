<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('sku');
            $table->string('image_path')->nullable()->after('description');
            $table->decimal('tax_rate', 5, 4)->default(0.1600)->after('price');
        });

        DB::table('products')
            ->whereNull('barcode')
            ->update(['barcode' => DB::raw('sku')]);

        Schema::table('products', function (Blueprint $table) {
            $table->unique('barcode');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
            $table->dropColumn(['barcode', 'image_path', 'tax_rate']);
        });
    }
};
