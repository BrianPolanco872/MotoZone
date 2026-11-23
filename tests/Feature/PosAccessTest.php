<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PosAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_employee_can_access_pos_shell(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        $response = $this->actingAs($employee)->get(route('pos.index'));

        $response->assertOk();
        $response->assertViewIs('pos.index');
        $response->assertSee('Punto de venta');
        $response->assertSee('La venta está vacía');
        $response->assertSee('Total');
        $response->assertSee('Cobrar');
        $response->assertDontSee('Devoluciones');
    }

    public function test_employee_with_sell_permission_can_charge(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', 'pos.sell']);

        $response = $this->actingAs($employee)->get(route('pos.index'));

        $response->assertOk();
        $response->assertSee('Cobrar');
    }

    public function test_admin_can_access_pos_shell(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('pos.index'))->assertOk();
    }

    public function test_pos_uses_full_width_and_keeps_authorized_navigation_in_desktop_menu(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', 'admin.products.manage', 'pos.caja.view', 'pos.cut.own']);

        $response = $this->actingAs($employee)->get(route('pos.index'))->assertOk()
            ->assertDontSee('Mi turno de hoy')
            ->assertDontSee('Atajos de teclado')
            ->assertDontSee('id="pos-subtotal"', false)
            ->assertDontSee('id="pos-tax"', false);

        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $cartBody = $xpath->query('//tbody[@id="pos-cart-body"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $cartBody);
        $viewport = $cartBody->parentNode->parentNode;
        $this->assertInstanceOf(DOMElement::class, $viewport);
        $main = $xpath->query('//main')->item(0);
        $this->assertInstanceOf(DOMElement::class, $main);
        $this->assertStringContainsString('min-h-[calc(100dvh-4rem-1px)]', $main->getAttribute('class'));
        $this->assertStringContainsString('pb-2', $main->getAttribute('class'));
        $this->assertStringContainsString('flex-1', $viewport->getAttribute('class'));
        $this->assertStringContainsString('height: 0;', $viewport->getAttribute('style'));
        $this->assertStringContainsString('min-height: 388px', $viewport->getAttribute('style'));
        $this->assertStringNotContainsString('max-height', $viewport->getAttribute('style'));
        $this->assertStringContainsString('overflow: auto', $viewport->getAttribute('style'));
        $emptyCell = $xpath->query('//tbody[@id="pos-empty"]/tr/td[@id="pos-empty-cell"][@colspan="10"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $emptyCell);
        $this->assertStringContainsString('h-full', $emptyCell->getAttribute('class'));
        $this->assertStringContainsString('vertical-align: middle', $emptyCell->getAttribute('style'));
        $headers = $xpath->query('//tbody[@id="pos-cart-body"]/preceding-sibling::thead/tr/th');
        $this->assertSame([
            'Foto', 'Código', 'Producto', 'Categoría', 'Precio unitario', 'Cantidad', 'Importe',
            'Existencia en tienda', 'Existencia en bodega', 'Existencia Tienda 2', 'Quitar',
        ], array_map(fn (DOMElement $header): string => trim($header->textContent), iterator_to_array($headers)));
        $store2Heading = $xpath->query('//th[@id="pos-cart-store2-heading"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $store2Heading);
        $this->assertTrue($store2Heading->hasAttribute('hidden'));
        $this->assertSame(1, $xpath->query('//dialog[@id="product-image-dialog"]//img[@id="product-image-preview"]')->length);
        $this->assertSame(1, $xpath->query('//dialog[@id="product-image-dialog"]//button[@data-close-dialog="product-image-dialog"]')->length);
        $this->assertSame(0, $xpath->query('//aside')->length);
        $this->assertSame(0, $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " lg:pl-64 ")]')->length);
        $menu = $xpath->query('//header//details[summary[@aria-label="Abrir menú del panel"]]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $menu);
        $this->assertNotContains('lg:hidden', explode(' ', $menu->getAttribute('class')));
        foreach (['admin.products.index', 'pos.cash.index', 'pos.cuts.mine', 'admin.profile.edit'] as $routeName) {
            $this->assertSame(1, $xpath->query('.//a[@href="'.route($routeName).'"]', $menu)->length);
        }
    }

    #[TestWith(['admin.profile.edit'])]
    #[TestWith(['pos.sales.pending'])]
    public function test_other_admin_and_pos_screens_keep_sidebar_and_desktop_margin(string $routeName): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        $response = $this->actingAs($employee)->get(route($routeName))->assertOk();

        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $this->assertSame(1, $xpath->query('//aside')->length);
        $this->assertSame(1, $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " lg:pl-64 ")]')->length);
        $main = $xpath->query('//main')->item(0);
        $this->assertInstanceOf(DOMElement::class, $main);
        $this->assertStringNotContainsString('min-h-[calc(100dvh-4rem-1px)]', $main->getAttribute('class'));
    }

    public function test_user_without_pos_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('pos.index'))->assertForbidden();
    }

    public function test_employee_cannot_access_admin_panel(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');

        $this->actingAs($employee)->get('/admin')->assertForbidden();
    }

    public function test_pos_is_not_accessible_to_guests(): void
    {
        $this->get(route('pos.index'))->assertRedirect(route('login'));
    }
}
