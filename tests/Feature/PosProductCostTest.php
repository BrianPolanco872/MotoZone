<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosProductCostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    /** @test */
    public function product_can_be_created_with_cost_wholesale_and_min_stock(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $admin->givePermissionTo(['admin.products.manage']);

        $category = Category::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Producto con costo',
            'brand' => 'Marca',
            'description' => 'Descripción',
            'sku' => 'SKU-001',
            'barcode' => '7501234567891',
            'cost' => 50.00,
            'wholesale_price' => 75.00,
            'price' => 100.00,
            'tax_rate' => 16,
            'stock' => 20,
            'min_stock' => 5,
            'track_inventory' => true,
            'featured' => false,
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $product = Product::where('sku', 'SKU-001')->first();
        $this->assertNotNull($product);
        $this->assertEquals(50.00, (float) $product->cost);
        $this->assertEquals(75.00, (float) $product->wholesale_price);
        $this->assertEquals(100.00, (float) $product->price);
        $this->assertEquals(20, $product->stock);
        $this->assertEquals(5, $product->min_stock);
        $this->assertTrue($product->track_inventory);
        $this->assertTrue($product->hasCost());
        $this->assertEquals(100.00, $product->marginPercentage());
    }

    /** @test */
    public function product_without_cost_shows_no_cost_in_index(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $admin->givePermissionTo(['admin.products.manage']);

        Product::factory()->create([
            'name' => 'Sin costo',
            'cost' => 0,
            'stock' => 10,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.products.index'));

        $response->assertOk();
        $response->assertSee('Sin costo capturado');
    }
}
