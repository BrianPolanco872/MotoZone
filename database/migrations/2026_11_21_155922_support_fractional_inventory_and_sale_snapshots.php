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
            $table->decimal('stock', 18, 3)->unsigned()->default(0)->change();
            $table->decimal('min_stock', 18, 3)->unsigned()->default(0)->change();
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            $table->decimal('quantity', 18, 3)->unsigned()->change();
            $table->decimal('returned_quantity', 18, 3)->unsigned()->default(0)->change();
            $table->string('sale_mode')->default('unit');
            $table->json('inventory_snapshot')->nullable();
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->decimal('quantity', 18, 3)->change();
            $table->decimal('stock_before', 18, 3)->change();
            $table->decimal('stock_after', 18, 3)->change();
        });

        Schema::table('sale_return_items', function (Blueprint $table): void {
            $table->decimal('quantity', 18, 3)->unsigned()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('sale_items')->where('sale_mode', '!=', 'unit')->orWhereNotNull('inventory_snapshot')->exists()
            || DB::table('products')->where('sale_mode', '!=', 'unit')->orWhere('kit_deduct_components', true)->exists()
            || DB::table('product_kit_items')->exists()) {
            throw new RuntimeException('No se puede revertir porque se perderían modalidades, recetas o snapshots de inventario.');
        }

        foreach ([
            ['products', ['stock', 'min_stock'], 0, 4294967295],
            ['sale_items', ['quantity', 'returned_quantity'], 0, 4294967295],
            ['stock_movements', ['quantity', 'stock_before', 'stock_after'], -2147483648, 2147483647],
            ['sale_return_items', ['quantity'], 0, 4294967295],
        ] as [$table, $columns, $minimum, $maximum]) {
            foreach ($columns as $column) {
                if (DB::table($table)->whereRaw("{$column} <> ROUND({$column}, 0)")
                    ->orWhere($column, '<', $minimum)->orWhere($column, '>', $maximum)->exists()) {
                    throw new RuntimeException("No se puede revertir porque {$table}.{$column} contiene fracciones o valores fuera del rango original.");
                }
            }
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('stock')->default(0)->change();
            $table->unsignedInteger('min_stock')->default(0)->change();
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            $table->unsignedInteger('quantity')->change();
            $table->unsignedInteger('returned_quantity')->default(0)->change();
            $table->dropColumn(['sale_mode', 'inventory_snapshot']);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->integer('quantity')->change();
            $table->integer('stock_before')->change();
            $table->integer('stock_after')->change();
        });

        Schema::table('sale_return_items', function (Blueprint $table): void {
            $table->unsignedInteger('quantity')->change();
        });
    }
};
