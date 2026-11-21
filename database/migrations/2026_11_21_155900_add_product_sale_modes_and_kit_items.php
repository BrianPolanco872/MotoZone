<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('sale_mode')->default('unit');
            $table->boolean('kit_deduct_components')->default(false);
        });

        Schema::create('product_kit_items', function (Blueprint $table): void {
            $table->foreignId('kit_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 18, 3)->unsigned();
            $table->unique(['kit_product_id', 'component_product_id']);
        });
    }

    public function down(): void
    {
        if (DB::table('products')->where('sale_mode', '!=', 'unit')->orWhere('kit_deduct_components', true)->exists()
            || DB::table('product_kit_items')->exists()
            || (Schema::hasColumn('sale_items', 'sale_mode') && DB::table('sale_items')->where('sale_mode', '!=', 'unit')->exists())
            || (Schema::hasColumn('sale_items', 'inventory_snapshot') && DB::table('sale_items')->whereNotNull('inventory_snapshot')->exists())) {
            throw new RuntimeException('No se puede revertir porque se perderían modalidades, recetas o snapshots de inventario.');
        }

        Schema::dropIfExists('product_kit_items');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['sale_mode', 'kit_deduct_components']);
        });
    }
};
