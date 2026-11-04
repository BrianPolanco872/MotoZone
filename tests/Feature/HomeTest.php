<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_categories_and_featured_products(): void
    {
        $category = Category::factory()->create([
            'name' => 'Frenos',
            'slug' => 'frenos',
            'sort_order' => 1,
        ]);

        Product::factory()->for($category)->create([
            'name' => 'Balatas delanteras DT175',
            'featured' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('home');
        $response->assertSee('Compra por categoría');
        $response->assertSee('Frenos');
        $response->assertSee('Balatas delanteras DT175');
    }

    public function test_guests_see_login_access_from_the_home(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Acceso al panel');
        $response->assertSee('Iniciar sesión');
        $response->assertSee(route('login'), false);
    }

    public function test_authenticated_admin_sees_panel_access_instead_of_login(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('admin.dashboard'), false);
        $response->assertDontSee('Iniciar sesión');
    }
}
