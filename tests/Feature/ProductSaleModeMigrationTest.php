<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ProductSaleModeMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sin transacción envolvente: SQLite solo aplica PRAGMA foreign_keys fuera de una.
        $this->artisan('migrate:fresh');
    }

    public function test_upgrade_preserves_existing_quantities_identity_prices_and_piece_defaults(): void
    {
        $data = $this->history();
        $modes = require database_path('migrations/2026_11_21_155900_add_product_sale_modes_and_kit_items.php');
        $quantities = require database_path('migrations/2026_11_21_155922_support_fractional_inventory_and_sale_snapshots.php');
        $quantities->down();
        $modes->down();

        $this->assertFalse(Schema::hasColumn('products', 'sale_mode'));
        $this->assertFalse(Schema::hasColumn('sale_items', 'inventory_snapshot'));

        try {
            $modes->up();
            $quantities->up();

            $product = $data['product']->fresh();
            $item = $data['item']->fresh();
            $movement = $data['movement']->fresh();
            $returned = $data['returned']->fresh();
            $this->assertSame(Product::MODE_UNIT, $product->sale_mode);
            $this->assertSame(Product::MODE_UNIT, $item->sale_mode);
            $this->assertFalse($product->kit_deduct_components);
            $this->assertNull($item->inventory_snapshot);
            $this->assertSame('Kit histórico ordinario', $product->name);
            $this->assertSame('00012345', $product->barcode);
            $this->assertSame('14.10', $product->price);
            $this->assertSame('7.50', $product->cost);
            $this->assertSame([8, 2], [$product->stock, $product->min_stock]);
            $this->assertSame([3, 1], [$item->quantity, $item->returned_quantity]);
            $this->assertSame([-2, 10, 8], [$movement->quantity, $movement->stock_before, $movement->stock_after]);
            $this->assertSame(1, $returned->quantity);
            $this->assertDatabaseCount('product_kit_items', 0);
            $this->assertDatabaseCount('sales', 1);
            $this->assertDatabaseCount('sale_returns', 1);
        } finally {
            if (! Schema::hasColumn('products', 'sale_mode')) {
                $modes->up();
            }
            if (! Schema::hasColumn('sale_items', 'inventory_snapshot')) {
                $quantities->up();
            }
        }
    }

    public function test_fractional_stock_prevents_rollback_without_changing_any_data(): void
    {
        $product = Product::factory()->create(['stock' => 0.125, 'min_stock' => 0.001]);
        $migration = require database_path('migrations/2026_11_21_155922_support_fractional_inventory_and_sale_snapshots.php');

        try {
            $migration->down();
            $this->fail('El rollback no debe truncar existencias fraccionarias.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('products.stock', $exception->getMessage());
        }

        $this->assertSame(0.125, $product->fresh()->stock);
        $this->assertSame(0.001, $product->fresh()->min_stock);
        $this->assertTrue(Schema::hasColumn('sale_items', 'inventory_snapshot'));
    }

    public function test_paid_inventory_snapshot_prevents_rollback_even_for_piece_sales(): void
    {
        $data = $this->history();
        $snapshot = ['version' => 1, 'mode' => Product::MODE_UNIT, 'kit_deduct_components' => false, 'components' => [], 'effects' => []];
        $data['item']->update(['inventory_snapshot' => $snapshot]);
        $migration = require database_path('migrations/2026_11_21_155922_support_fractional_inventory_and_sale_snapshots.php');

        try {
            $migration->down();
            $this->fail('El rollback no debe eliminar la evidencia del consumo real.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('snapshots', $exception->getMessage());
        }

        $this->assertSame($snapshot, $data['item']->fresh()->inventory_snapshot);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_kit_configuration_prevents_removing_mode_columns_and_recipe(): void
    {
        $kit = Product::factory()->kit()->create(['kit_deduct_components' => true]);
        $componentId = $kit->kitItems()->sole()->id;
        $migration = require database_path('migrations/2026_11_21_155900_add_product_sale_modes_and_kit_items.php');

        try {
            $migration->down();
            $this->fail('El rollback no debe eliminar una receta vigente.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('recetas', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasTable('product_kit_items'));
        $this->assertDatabaseHas('product_kit_items', ['kit_product_id' => $kit->id, 'component_product_id' => $componentId]);
        $this->assertSame(Product::MODE_KIT, $kit->fresh()->sale_mode);
    }

    public function test_decimal_columns_preserve_fractional_movements_and_return_balances(): void
    {
        $data = $this->history();
        $data['product']->update(['sale_mode' => Product::MODE_BULK, 'stock' => 2.75, 'min_stock' => 0.5]);
        $data['item']->update(['sale_mode' => Product::MODE_BULK, 'quantity' => 0.5, 'returned_quantity' => 0.125]);
        $data['movement']->update(['quantity' => -0.5, 'stock_before' => 3.25, 'stock_after' => 2.75]);
        $data['returned']->update(['quantity' => 0.125]);

        $this->assertSame(2.75, $data['product']->fresh()->stock);
        $this->assertSame(0.5, $data['product']->fresh()->min_stock);
        $this->assertSame(0.375, $data['item']->fresh()->availableReturnQuantity());
        $this->assertSame(-0.5, $data['movement']->fresh()->quantity);
        $this->assertSame([3.25, 2.75], [$data['movement']->fresh()->stock_before, $data['movement']->fresh()->stock_after]);
        $this->assertSame(0.125, $data['returned']->fresh()->quantity);
    }

    /**
     * @return array{product: Product, item: SaleItem, movement: StockMovement, returned: SaleReturnItem}
     */
    private function history(): array
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Kit histórico ordinario', 'barcode' => '00012345',
            'price' => 14.10, 'cost' => 7.50, 'stock' => 8, 'min_stock' => 2,
        ]);
        $sale = Sale::create([
            'user_id' => $user->id, 'status' => Sale::STATUS_PAID,
            'idempotency_key' => 'migration-history', 'total' => 42.30, 'paid_at' => now(),
        ]);
        $item = $sale->items()->create([
            'product_id' => $product->id, 'product_name' => $product->name,
            'quantity' => 3, 'returned_quantity' => 1, 'unit_price' => 14.10, 'line_total' => 42.30,
        ]);
        $movement = StockMovement::create([
            'product_id' => $product->id, 'sale_id' => $sale->id, 'user_id' => $user->id,
            'type' => StockMovement::TYPE_SALE, 'quantity' => -2, 'stock_before' => 10, 'stock_after' => 8,
        ]);
        $return = $sale->returns()->create([
            'requested_by' => $user->id, 'status' => 'approved', 'reason' => 'Historia previa', 'total' => 14.10,
        ]);
        $returned = $return->items()->create(['sale_item_id' => $item->id, 'quantity' => 1, 'unit_price' => 14.10, 'line_total' => 14.10]);

        return compact('product', 'item', 'movement', 'returned');
    }
}
