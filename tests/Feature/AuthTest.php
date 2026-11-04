<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('Acceso al panel')
            ->assertSee('Nombre completo')
            ->assertSee('Correo electrónico');
    }

    public function test_admin_can_login_and_is_redirected_to_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@motozone.test',
            'password' => 'password',
        ])->assignRole('admin');

        $response = $this->post(route('login.store'), [
            'name' => $admin->name,
            'email' => 'admin@motozone.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_employee_can_login_and_is_redirected_to_pos(): void
    {
        $employee = User::factory()->create([
            'email' => 'empleado@motozone.test',
            'password' => 'password',
        ])->assignRole('empleado');

        $employee->givePermissionTo('pos.access');

        $response = $this->post(route('login.store'), [
            'name' => $employee->name,
            'email' => 'empleado@motozone.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('pos.index'));
        $this->assertAuthenticatedAs($employee);
    }

    public function test_employee_with_products_permission_is_redirected_to_products(): void
    {
        $employee = User::factory()->create([
            'email' => 'inventario@motozone.test',
            'password' => 'password',
        ])->assignRole('empleado');

        $employee->givePermissionTo('admin.products.manage');

        $response = $this->post(route('login.store'), [
            'name' => $employee->name,
            'email' => 'inventario@motozone.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertAuthenticatedAs($employee);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@motozone.test',
            'password' => 'password',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'name' => $user->name,
            'email' => 'admin@motozone.test',
            'password' => 'incorrecta',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_when_name_does_not_match_the_account(): void
    {
        User::factory()->create([
            'name' => 'Ana Ramírez',
            'email' => 'ana@motozone.test',
            'password' => 'password',
        ])->assignRole('admin');

        $response = $this->from(route('login'))->post(route('login.store'), [
            'name' => 'Otra Persona',
            'email' => 'ana@motozone.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('name');
        $this->assertGuest();
    }

    public function test_login_requires_name(): void
    {
        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'ana@motozone.test',
            'password' => 'password',
        ])->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    public function test_name_match_ignores_casing_and_extra_whitespace(): void
    {
        $user = User::factory()->create([
            'name' => 'Ana Ramírez',
            'email' => 'ana@motozone.test',
            'password' => 'password',
        ])->assignRole('admin');

        $response = $this->post(route('login.store'), [
            'name' => '  ana   RAMÍREZ ',
            'email' => 'ana@motozone.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactivo@motozone.test',
            'password' => 'password',
            'is_active' => false,
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'name' => $user->name,
            'email' => 'inactivo@motozone.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_guests_are_redirected_to_login_from_protected_routes(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/pos')->assertRedirect(route('login'));
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@motozone.test',
            'password' => 'password',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'name' => $user->name,
                'email' => 'admin@motozone.test',
                'password' => 'incorrecta',
            ]);
        }

        $this->post(route('login.store'), [
            'name' => $user->name,
            'email' => 'admin@motozone.test',
            'password' => 'incorrecta',
        ])->assertStatus(429);
    }

    public function test_inactive_user_session_is_closed_on_next_request(): void
    {
        $admin = User::factory()->create(['is_active' => false])->assignRole('admin');

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
