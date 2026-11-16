<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosProductSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pos_search_matches_both_barcodes_but_not_internal_sku(): void
    {
        $product = Product::factory()->create([
            'sku' => 'INTERNAL-ONLY-01',
            'barcode' => '0001234567890',
            'barcode2' => 'A(02)+#',
        ]);
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        foreach (['0001234567890', 'A(02)+#'] as $code) {
            $this->actingAs($employee)
                ->getJson(route('pos.products.search', ['q' => $code]))
                ->assertOk()
                ->assertJsonPath('products.0.id', $product->id);
        }

        $this->actingAs($employee)
            ->getJson(route('pos.products.search', ['q' => 'INTERNAL-ONLY-01']))
            ->assertOk()
            ->assertJsonCount(0, 'products');
    }

    public function test_pos_search_still_finds_products_by_name_and_brand(): void
    {
        $product = Product::factory()->create([
            'name' => 'Balata especial',
            'brand' => 'Marca única',
            'sku' => 'INTERNAL-SEARCH-02',
            'barcode' => null,
            'barcode2' => null,
        ]);
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        foreach (['Balata especial', 'Marca única'] as $term) {
            $this->actingAs($employee)
                ->getJson(route('pos.products.search', ['q' => $term]))
                ->assertOk()
                ->assertJsonPath('products.0.id', $product->id);
        }
    }
}
