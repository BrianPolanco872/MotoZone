<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\TicketSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TicketSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
    }

    private function payload(array $ticket = []): array
    {
        return [
            'business' => ['name' => 'Taller Norte', 'address' => 'Calle 123', 'phone' => '5512345678', 'tax_id' => 'AAA010101AAA'],
            'ticket' => array_replace([
                'width_mm' => 58,
                'footer' => 'Vuelve pronto.',
                'policy' => 'Garantía de 30 días.',
                'show_logo' => true,
                'show_tax_id' => true,
                'show_sku' => false,
                'show_cashier' => false,
                'show_tax' => false,
                'show_qr' => false,
            ], $ticket),
        ];
    }

    private function sale(User $user, string $productName = 'Aceite prueba', float $price = 148): Sale
    {
        $product = Product::factory()->create(['name' => $productName, 'sku' => 'SKU-OCULTO', 'price' => $price, 'stock' => 10]);
        $response = $this->actingAs($user)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'ticket-settings-sale',
            'status' => 'paid',
            'payment_method' => 'card',
            'amount_paid' => $price,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertOk();

        return Sale::findOrFail($response->json('sale.id'));
    }

    public function test_guests_must_log_in_to_configure_tickets(): void
    {
        $this->get(route('pos.tickets.edit'))->assertRedirect(route('login'));
        $this->put(route('pos.tickets.update'), $this->payload())->assertRedirect(route('login'));
        Storage::disk('local')->assertMissing('pos/ticket-settings.json');
    }

    public function test_employee_cannot_read_or_change_global_ticket_settings(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        $this->actingAs($employee)->get(route('pos.tickets.edit'))->assertForbidden();
        $this->put(route('pos.tickets.update'), $this->payload())->assertForbidden();
        Storage::disk('local')->assertMissing('pos/ticket-settings.json');
    }

    public function test_admin_sees_tickets_below_daily_cut(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('pos.tickets.edit'))
            ->assertOk()->assertSeeInOrder(['Corte del día', 'Tickets', 'Empleados'])
            ->assertSee('Conexión')->assertSee('Campos')->assertSee('Vista previa', false);
    }

    public function test_saved_settings_reach_both_pos_printing_paths_without_changing_sale_totals(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $sale = $this->sale($admin);

        $this->put(route('pos.tickets.update'), $this->payload())
            ->assertRedirect(route('pos.tickets.edit'))->assertSessionHasNoErrors();

        $settings = app(TicketSettings::class)->current();
        $this->assertSame('Taller Norte', $settings['business']['name']);
        $this->assertSame(58, $settings['ticket']['width_mm']);
        $this->assertFalse($settings['ticket']['show_cashier']);

        $this->get(route('pos.sales.ticket', $sale))->assertOk()
            ->assertSee('Taller Norte')->assertSee('Calle 123')->assertSee('5512345678')
            ->assertSee('AAA010101AAA')->assertSee('Vuelve pronto.')->assertSee('Garantía de 30 días.')
            ->assertSee('Tarjeta')->assertSee('MZ')->assertSee('58mm')
            ->assertDontSee('SKU-OCULTO')->assertDontSee('Atendió:')->assertDontSee('IVA incluido');

        $response = $this->getJson(route('pos.sales.ticketData', $sale))->assertOk()
            ->assertJsonPath('settings.ticket.width_mm', 58)
            ->assertJsonPath('settings.business.name', 'Taller Norte');
        $html = $response->json('ticket_html');
        $this->assertStringContainsString('Vuelve pronto.', $html);
        $this->assertStringContainsString('Método de pago: Tarjeta', $html);
        $this->assertStringNotContainsString('SKU-OCULTO', $html);
        $this->assertStringNotContainsString('Atendió:', $html);
        $this->assertSame('148.00', $sale->refresh()->total);
        $this->get(route('pos.index'))->assertOk()->assertViewHas('settings', fn (array $settings): bool => $settings['ticket']['width_mm'] === 58);
    }

    public function test_enabled_fields_and_uploaded_qr_reach_the_printed_receipt(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $sale = $this->sale($admin);
        $payload = $this->payload(['width_mm' => 80, 'show_sku' => true, 'show_cashier' => true, 'show_tax' => true, 'show_qr' => true]);
        $payload['qr_image'] = UploadedFile::fake()->createWithContent('support.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));

        $this->put(route('pos.tickets.update'), $payload)->assertSessionHasNoErrors();

        $this->get(route('pos.sales.ticket', $sale))->assertOk()
            ->assertSee('SKU-OCULTO')->assertSee('Atendió:')->assertSee('IVA incluido')
            ->assertSee('data:image/png;base64,', false)->assertSee('80mm');
        $this->put(route('pos.tickets.update'), $this->payload(['show_qr' => true]))->assertSessionHasNoErrors();
        $this->assertStringStartsWith('data:image/png;base64,', app(TicketSettings::class)->current()['ticket']['qr_image_url']);
    }

    #[TestWith([58])]
    #[TestWith([80])]
    public function test_printing_keeps_the_configured_width_and_all_receipt_data(int $width): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $productName = str_repeat('CUBIERTA REFORZADA ', 6);
        $sale = $this->sale($admin, trim($productName), 123456.78);
        $this->put(route('pos.tickets.update'), $this->payload(['width_mm' => $width, 'policy' => '']))->assertSessionHasNoErrors();

        $this->get(route('pos.sales.ticket', $sale))->assertOk()
            ->assertSee('--ticket-width: '.$width.'mm', false)
            ->assertSee('width: calc(var(--ticket-width, 80mm) - 8mm)', false)
            ->assertSee('max-width: 100%', false)->assertSee('size: auto', false)
            ->assertDontSee('mm auto', false)->assertDontSee('width: auto; max-width: none', false)
            ->assertSeeInOrder(['Producto', trim($productName), 'Subtotal', 'Total', 'Método de pago: Tarjeta', 'Vuelve pronto.'])
            ->assertSee('$123,456.78')->assertDontSee('Conserve este ticket');

        $response = $this->getJson(route('pos.sales.ticketData', $sale))->assertOk()
            ->assertJsonPath('settings.ticket.width_mm', $width);
        $this->assertStringContainsString('--ticket-width: '.$width.'mm', $response->json('ticket_html'));
        $this->assertStringContainsString(trim($productName), $response->json('ticket_html'));
        $this->assertStringContainsString('$123,456.78', $response->json('ticket_html'));
        $this->assertSame('123456.78', $sale->refresh()->total);

        $this->get(route('pos.index'))->assertOk()
            ->assertSee('width: calc(var(--ticket-width, 80mm) - 8mm)', false)
            ->assertSee('margin: 0 auto', false)->assertSee('size: auto', false)->assertDontSee('mm auto', false);
        $this->get(route('pos.tickets.edit'))->assertOk()
            ->assertSee('width: calc(var(--ticket-width, 80mm) - 8mm)', false)
            ->assertSee('margin: 0 auto', false)->assertSee('size: auto', false)->assertDontSee('mm auto', false);
    }

    public function test_default_receipt_has_no_returns_policy_and_keeps_the_thank_you_message(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $sale = $this->sale($admin);

        $this->get(route('pos.sales.ticket', $sale))->assertOk()
            ->assertSee('Gracias por su compra.')->assertDontSee('Conserve este ticket');
        $html = $this->getJson(route('pos.sales.ticketData', $sale))->assertOk()->json('ticket_html');
        $this->assertStringContainsString('Gracias por su compra.', $html);
        $this->assertStringNotContainsString('Conserve este ticket', $html);
        $this->assertSame('', app(TicketSettings::class)->current()['ticket']['policy']);
    }

    public function test_clearing_a_saved_returns_policy_preserves_other_ticket_settings(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $sale = $this->sale($admin);
        $payload = $this->payload(['policy' => 'Conserve este ticket para cambios y devoluciones.']);
        $this->put(route('pos.tickets.update'), $payload)->assertSessionHasNoErrors();
        $expected = app(TicketSettings::class)->current();
        $expected['ticket']['policy'] = '';
        $payload['ticket']['policy'] = '';

        $this->put(route('pos.tickets.update'), $payload)->assertSessionHasNoErrors();

        $this->assertSame($expected, app(TicketSettings::class)->current());
        $this->get(route('pos.sales.ticket', $sale))->assertOk()
            ->assertSee('Vuelve pronto.')->assertDontSee('Conserve este ticket');
        $html = $this->getJson(route('pos.sales.ticketData', $sale))->assertOk()->json('ticket_html');
        $this->assertStringContainsString('Vuelve pronto.', $html);
        $this->assertStringNotContainsString('Conserve este ticket', $html);
    }

    public function test_ticket_settings_are_escaped_in_both_printing_paths(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $sale = $this->sale($admin);
        $payload = $this->payload();
        $payload['business']['name'] = '<script>alert(1)</script>';
        $payload['ticket']['footer'] = '<img src=x onerror=alert(2)>';

        $this->put(route('pos.tickets.update'), $payload)->assertSessionHasNoErrors();

        $this->get(route('pos.sales.ticket', $sale))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $html = $this->getJson(route('pos.sales.ticketData', $sale))->assertOk()->json('ticket_html');
        $this->assertStringContainsString('&lt;img src=x onerror=alert(2)&gt;', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
    }

    #[TestWith(['width_mm', 72])]
    #[TestWith(['show_cashier', 'invalid'])]
    #[TestWith(['show_qr', true])]
    #[TestWith(['show_tax_id', true, ''])]
    public function test_invalid_settings_do_not_overwrite_saved_configuration(string $key, mixed $value, ?string $taxId = null): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin)->put(route('pos.tickets.update'), $this->payload())->assertSessionHasNoErrors();
        $original = Storage::disk('local')->get('pos/ticket-settings.json');
        $payload = $this->payload([$key => $value]);

        if ($taxId !== null) {
            $payload['business']['tax_id'] = $taxId;
        }

        $this->putJson(route('pos.tickets.update'), $payload)->assertUnprocessable();
        $this->assertSame($original, Storage::disk('local')->get('pos/ticket-settings.json'));
    }

    public function test_non_image_qr_upload_is_rejected(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $payload = $this->payload();
        $payload['qr_image'] = UploadedFile::fake()->create('unsafe.png', 1, 'text/html');

        $this->actingAs($admin)->put(route('pos.tickets.update'), $payload)->assertSessionHasErrors('qr_image');
        Storage::disk('local')->assertMissing('pos/ticket-settings.json');
    }

    public function test_optional_empty_fields_and_hidden_header_fields_stay_hidden(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $sale = $this->sale($admin);
        $payload = $this->payload(['show_logo' => false, 'show_tax_id' => false, 'footer' => '', 'policy' => '']);
        $payload['business']['address'] = '';
        $payload['business']['phone'] = '';

        $this->put(route('pos.tickets.update'), $payload)->assertSessionHasNoErrors();

        $this->get(route('pos.sales.ticket', $sale))->assertOk()
            ->assertDontSee('RFC AAA010101AAA')->assertDontSee('>MZ<', false)
            ->assertDontSee('Tel.')->assertDontSee('Gracias por su compra.')
            ->assertDontSee('Conserve este ticket');
        $this->assertSame('', app(TicketSettings::class)->current()['business']['address']);
    }

    public function test_employee_receipts_use_global_configuration_without_granting_configuration_access(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo(['pos.access', 'pos.sell', 'pos.tickets.print.own']);
        $sale = $this->sale($employee);
        $this->actingAs($admin)->put(route('pos.tickets.update'), $this->payload())->assertSessionHasNoErrors();

        $this->actingAs($employee)->get(route('pos.sales.ticket', $sale))->assertOk()->assertSee('Taller Norte');
        $this->getJson(route('pos.sales.ticketData', $sale))->assertOk()->assertJsonPath('settings.ticket.width_mm', 58);
        $this->get(route('pos.tickets.edit'))->assertForbidden();
    }
}
