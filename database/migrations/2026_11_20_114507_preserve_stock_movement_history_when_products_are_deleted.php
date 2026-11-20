<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
            $table->foreignId('product_id')->nullable()->change();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->json('product_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('stock_movements')->whereNull('product_id')->orWhereNotNull('product_snapshot')->exists()) {
            throw new RuntimeException('No se puede revertir la migración porque se perderían referencias o identificación histórica de productos.');
        }

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
            $table->foreignId('product_id')->nullable(false)->change();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->dropColumn('product_snapshot');
        });
    }
};
