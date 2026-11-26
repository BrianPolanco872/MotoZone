<?php

namespace Tests\Feature;

use App\Models\Sale;
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

    #[TestWith(['pos.tickets.print.own'])]
    #[TestWith(['pos.tickets.print.any'])]
    public function test_pos_recovers_only_the_cashiers_latest_paid_ticket(string $printPermission): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', $printPermission]);
        $other = User::factory()->create()->assignRole('empleado');
        $other->givePermissionTo('pos.access');
        $samePaymentTime = now()->subHour();

        foreach ([
            ['V-SAME-TIME', $employee, Sale::STATUS_PAID, $samePaymentTime],
            ['V-LAST', $employee, Sale::STATUS_PAID, $samePaymentTime],
            ['V-OLDER', $employee, Sale::STATUS_PAID, now()->subHours(2)],
            ['V-PENDING', $employee, Sale::STATUS_PENDING, null],
            ['V-CANCELLED', $employee, Sale::STATUS_CANCELLED, now()],
            ['V-OTHER', $other, Sale::STATUS_PAID, now()],
        ] as [$folio, $cashier, $status, $paidAt]) {
            Sale::query()->create([
                'folio' => $folio,
                'user_id' => $cashier->id,
                'idempotency_key' => $folio,
                'status' => $status,
                'paid_at' => $paidAt,
                'total' => 70,
                'amount_paid' => 100,
                'change_amount' => 30,
            ]);
        }

        $this->actingAs($employee)->get(route('pos.index'))->assertOk()
            ->assertViewHas('lastSale', fn (array $sale): bool => $sale['folio'] === 'V-LAST'
                && $sale['amount_paid'] === '100.00' && $sale['change_amount'] === '30.00');

        $this->actingAs($other)->get(route('pos.index'))->assertOk()
            ->assertViewHas('lastSale', fn (?array $sale): bool => $sale === null)
            ->assertDontSee('V-OTHER');
    }

    public function test_ticket_auto_printing_is_opt_in_and_keeps_the_manual_button(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', 'pos.tickets.print.own']);
        $sale = Sale::query()->create([
            'user_id' => $employee->id,
            'idempotency_key' => 'opt-in-ticket-print',
            'paid_at' => now(),
        ]);

        $this->actingAs($employee)->get(route('pos.sales.ticket', $sale))->assertOk()
            ->assertDontSee("window.addEventListener('load'", false);

        $this->get(route('pos.sales.ticket', ['sale' => $sale, 'print' => 1]))->assertOk()
            ->assertSee("window.addEventListener('load'", false)
            ->assertSee('onclick="window.print()"', false);

        $other = User::factory()->create()->assignRole('empleado');
        $other->givePermissionTo(['pos.access', 'pos.tickets.print.own']);
        $this->actingAs($other)->get(route('pos.sales.ticket', ['sale' => $sale, 'print' => 1]))->assertForbidden();
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
