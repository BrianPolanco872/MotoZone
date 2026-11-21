<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HomePage;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['/catalogo'])]
    #[TestWith(['/catalogo?categoria=frenos'])]
    #[TestWith(['/catalogo?search=BAL-TEST-01'])]
    #[TestWith(['/catalogo?orden=precio-asc'])]
    public function test_removed_catalog_returns_not_found(string $url): void
    {
        $this->get($url)->assertNotFound();
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
        $response->assertDontSee('href="'.url('/catalogo'), false);
        $response->assertDontSee('Frenos');
    }

    public function test_active_product_without_category_remains_accessible_outside_home(): void
    {
        $product = Product::factory()->create([
            'name' => 'Refacción fuera de portada',
            'is_active' => true,
            'home_visible' => false,
            'featured' => true,
            'category_id' => null,
        ]);

        $home = $this->get(route('home'));
        $detail = $this->get(route('catalog.show', $product));

        $home->assertOk();
        $home->assertDontSee($product->name);
        $detail->assertOk();
        $detail->assertSee($product->name);
    }

    public function test_inactive_product_detail_returns_not_found(): void
    {
        $category = Category::factory()->create(['name' => 'Frenos', 'slug' => 'frenos']);
        $product = Product::factory()->for($category)->create(['is_active' => false, 'home_visible' => true]);

        $this->get(route('catalog.show', $product))->assertNotFound();
        $this->get(route('home'))->assertOk()->assertDontSee($product->name);
    }

    #[TestWith([true, 0.124, 'Agotado / No disponible'])]
    #[TestWith([true, 0.125, 'Disponible'])]
    #[TestWith([false, 0, 'Disponible'])]
    public function test_kit_detail_uses_component_availability_instead_of_parent_stock(bool $deductComponents, int|float $stock, string $label): void
    {
        $kit = Product::factory()->kit()->create(['kit_deduct_components' => $deductComponents]);
        $component = $kit->kitItems()->firstOrFail();
        $component->update(['sale_mode' => Product::MODE_BULK, 'stock' => $stock]);
        $kit->kitItems()->updateExistingPivot($component->id, ['quantity' => 0.125]);

        $response = $this->get(route('catalog.show', $kit));

        $response->assertOk()->assertSee($label)->assertSee('Precio por paquete (kit)');
        $this->assertTrue($response->viewData('product')->relationLoaded('kitItems'));
    }

    public function test_bulk_detail_explains_that_the_price_is_per_unit_of_measure(): void
    {
        $product = Product::factory()->bulk()->create(['stock' => 0.125]);

        $this->get(route('catalog.show', $product))->assertOk()
            ->assertSee('A granel · precio por unidad de medida')->assertSee('Disponible');
    }

    public function test_home_style_and_contact_do_not_change_product_detail(): void
    {
        HomePage::factory()->create(['content' => ['store_name' => 'Mostrador exclusivo de home', 'whatsapp' => '+525576543210', 'email' => 'home@example.test', 'show_whatsapp_bubble' => true, 'show_email_bubble' => true]]);
        $product = Product::factory()->create(['is_active' => true]);

        $this->get(route('catalog.show', $product))->assertOk()
            ->assertDontSee('home-storefront', false)
            ->assertDontSee('Mostrador exclusivo de home')
            ->assertDontSee('wa.me/525576543210', false)
            ->assertDontSee('mailto:home@example.test', false)
            ->assertSee('wa.me/525512345678', false);
    }
}
