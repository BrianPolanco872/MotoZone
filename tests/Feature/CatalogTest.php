<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_only_active_products(): void
    {
        $category = Category::factory()->create(['name' => 'Frenos', 'slug' => 'frenos']);

        Product::factory()->for($category)->create(['name' => 'Balata de prueba']);
        Product::factory()->for($category)->create(['name' => 'Pieza descontinuada', 'is_active' => false]);

        $response = $this->get('/catalogo');

        $response->assertOk();
        $response->assertViewIs('catalog.index');
        $response->assertSee('Balata de prueba');
        $response->assertDontSee('Pieza descontinuada');
    }

    public function test_catalog_filters_by_category(): void
    {
        $brakes = Category::factory()->create(['name' => 'Frenos', 'slug' => 'frenos']);
        $tires = Category::factory()->create(['name' => 'Llantas', 'slug' => 'llantas']);

        Product::factory()->for($brakes)->create(['name' => 'Balata de prueba']);
        Product::factory()->for($tires)->create(['name' => 'Neumático de prueba']);

        $response = $this->get('/catalogo?categoria=frenos');

        $response->assertOk();
        $response->assertSee('Balata de prueba');
        $response->assertDontSee('Neumático de prueba');
    }

    public function test_catalog_searches_by_sku(): void
    {
        $category = Category::factory()->create(['name' => 'Frenos', 'slug' => 'frenos']);

        Product::factory()->for($category)->create(['name' => 'Balata de prueba', 'sku' => 'BAL-TEST-01', 'brand' => 'Yamaha']);
        Product::factory()->for($category)->create(['name' => 'Disco de prueba', 'sku' => 'DIS-TEST-02', 'brand' => 'Honda']);

        $response = $this->get('/catalogo?search=BAL-TEST-01');

        $response->assertOk();
        $response->assertSee('Balata de prueba');
        $response->assertDontSee('Disco de prueba');
    }

    public function test_catalog_sorts_products_by_price_ascending(): void
    {
        $category = Category::factory()->create(['name' => 'Frenos', 'slug' => 'frenos']);

        Product::factory()->for($category)->create(['name' => 'Pieza economica', 'price' => 100]);
        Product::factory()->for($category)->create(['name' => 'Pieza costosa', 'price' => 5000]);

        $response = $this->get('/catalogo?orden=precio-asc');

        $response->assertOk();
        $response->assertSeeInOrder(['Pieza economica', 'Pieza costosa']);
    }

    public function test_product_detail_is_rendered(): void
    {
        $category = Category::factory()->create(['name' => 'Frenos', 'slug' => 'frenos']);
        $product = Product::factory()->for($category)->create([
            'name' => 'Balata de prueba',
            'sku' => 'BAL-TEST-01',
        ]);

        $response = $this->get(route('catalog.show', $product));

        $response->assertOk();
        $response->assertViewIs('catalog.show');
        $response->assertSee('Balata de prueba');
        $response->assertSee('BAL-TEST-01');
    }

    public function test_inactive_product_detail_returns_not_found(): void
    {
        $category = Category::factory()->create(['name' => 'Frenos', 'slug' => 'frenos']);
        $product = Product::factory()->for($category)->create(['is_active' => false]);

        $this->get(route('catalog.show', $product))->assertNotFound();
    }
}
