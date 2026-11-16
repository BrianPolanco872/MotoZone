<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode2')->nullable()->after('barcode')->unique();
        });

        DB::table('products')
            ->where(function (QueryBuilder $query): void {
                $query->whereNull('barcode')->orWhere('barcode', '');
            })
            ->whereNotNull('sku')
            ->select(['id', 'sku'])
            ->orderBy('id')
            ->chunkById(500, function (Collection $products): void {
                foreach ($products as $product) {
                    $codeUsedByAnotherProduct = DB::table('products')
                        ->where('barcode', $product->sku)
                        ->where('id', '!=', $product->id)
                        ->exists();

                    if ($codeUsedByAnotherProduct) {
                        continue;
                    }

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['barcode2' => $product->sku]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['barcode2']);
            $table->dropColumn('barcode2');
        });
    }
};
