<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_dashboard_shows_inventory_metrics(): void
    {
        $category = Category::factory()->create(['name' => 'Frenos', 'slug' => 'frenos']);

        Product::factory()->for($category)->create(['name' => 'Balata critica', 'stock' => 2, 'price' => 100]);
        Product::factory()->for($category)->create(['name' => 'Pieza sana', 'stock' => 30, 'price' => 200]);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertSee('Inventario por categoría');
        $response->assertSee('Balata critica');
        $response->assertSee('Productos activos');
    }

    public function test_admin_products_can_be_filtered_by_low_stock(): void
    {
        $category = Category::factory()->create(['name' => 'Frenos', 'slug' => 'frenos']);

        Product::factory()->for($category)->create(['name' => 'Balata critica', 'stock' => 2]);
        Product::factory()->for($category)->create(['name' => 'Pieza sana', 'stock' => 30]);

        $response = $this->actingAs($this->admin())->get('/admin/productos?stock=bajo');

        $response->assertOk();
        $response->assertViewIs('admin.products.index');
        $response->assertSee('Balata critica');
        $response->assertDontSee('Pieza sana');
    }

    public function test_admin_products_can_be_searched_by_sku(): void
    {
        $category = Category::factory()->create(['name' => 'Frenos', 'slug' => 'frenos']);

        Product::factory()->for($category)->create(['name' => 'Balata de prueba', 'sku' => 'BAL-TEST-01']);
        Product::factory()->for($category)->create(['name' => 'Disco de prueba', 'sku' => 'DIS-TEST-02']);

        $response = $this->actingAs($this->admin())->get('/admin/productos?search=BAL-TEST-01');

        $response->assertOk();
        $response->assertSee('Balata de prueba');
        $response->assertDontSee('Disco de prueba');
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }
}
