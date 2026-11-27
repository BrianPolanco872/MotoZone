<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\TicketSettings;
use Database\Seeders\RolePermissionSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Process\Process;
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

    private function assertPrintedColumns(TestResponse $response, int $columns): void
    {
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $receipt = $document->getElementById('ticket')->getAttribute('data-receipt');
        $formatter = new Process(['node', '--input-type=module', '--eval', <<<'JS'
            import { readFileSync } from 'node:fs';
            import { formatearTicket } from './resources/js/ticket-text.js';
            process.stdout.write(formatearTicket(JSON.parse(readFileSync(0, 'utf8'))));
            JS], base_path(), input: $receipt);
        $lines = explode("\n", $formatter->mustRun()->getOutput());
        $this->assertContains(str_repeat('=', $columns), $lines);
        $this->assertSame($columns, max(array_map('strlen', $lines)));
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

        $response = $this->actingAs($admin)->get(route('pos.tickets.edit'));

        $response->assertOk()
            ->assertSeeInOrder(['Punto de venta', 'Corte del día', 'Caja', 'Abonos / Créditos', 'Empleados', 'Tickets', 'Bitácora', 'Mi perfil'])
            ->assertSee('Conexión')->assertSee('Campos')->assertSee('Vista previa', false);
    }

    public function test_ticket_settings_default_to_58_mm_without_a_saved_preference(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $sale = $this->sale($admin, 'Cadena reforzada con una descripcion suficientemente larga para un ticket');
        $this->assertSame(58, config('pos.ticket.width_mm'));
        Storage::disk('local')->assertMissing('pos/ticket-settings.json');

        $this->get(route('pos.tickets.edit'))->assertOk()
            ->assertSee('value="58" selected', false)
            ->assertSee('value="80"', false)
            ->assertSee('value="text" selected', false)
            ->assertSee('style="--ticket-width: 58mm"', false)
            ->assertSee('data-ticket-text-preview data-paper-width="58"', false)
            ->assertSee('data-ticket-width-label>58</span>', false);
        $ticket = $this->get(route('pos.sales.ticket', ['sale' => $sale, 'embed' => 1]))->assertOk()
            ->assertViewIs('pos.tickets.text-print')->assertSee('data-paper-width="58"', false);
        $this->assertPrintedColumns($ticket, 32);
        Storage::disk('local')->assertMissing('pos/ticket-settings.json');
    }

    public function test_printed_cash_cut_keeps_80_mm_when_sales_tickets_default_to_58_mm(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $cut = $this->actingAs($admin)->get(route('pos.cuts.print'))->assertOk()->assertViewIs('pos.cuts.print');

        $this->assertMatchesRegularExpression('/\.sheet\s*{\s*width:\s*80mm;/', $cut->getContent());
        $cut->assertSee('padding: 0; width: 80mm;', false)->assertDontSee('width: 58mm;', false);
    }

    public function test_legacy_settings_default_to_text_and_updates_keep_new_preferences(): void
    {
        Storage::disk('local')->put('pos/ticket-settings.json', json_encode($this->payload(), JSON_THROW_ON_ERROR));
        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin)->get(route('pos.tickets.edit'))->assertOk()
            ->assertSee('Modo de impresión')->assertSee('value="text" selected', false);
        $preferences = [
            'print_mode' => 'graphic', 'uppercase' => true, 'lines_above' => 2,
            'lines_below' => 7, 'show_unit_price' => true, 'full_description' => true,
        ];

        $this->put(route('pos.tickets.update'), $this->payload(array_map(
            fn (mixed $value): string => (string) $value, $preferences,
        )))->assertRedirect(route('pos.tickets.edit'))->assertSessionHasNoErrors();
        $this->put(route('pos.tickets.update'), $this->payload(['footer' => 'Actualizado']))
            ->assertSessionHasNoErrors();

        $saved = json_decode(Storage::disk('local')->get('pos/ticket-settings.json'), true, 512, JSON_THROW_ON_ERROR)['ticket'];
        foreach ($preferences as $key => $value) {
            $this->assertSame($value, $saved[$key], $key);
        }
        $this->assertSame('Actualizado', $saved['footer']);
        $this->get(route('pos.tickets.edit'))->assertOk()->assertSee('value="graphic" selected', false);
    }

    #[TestWith([58])]
    #[TestWith([80])]
    public function test_text_print_has_one_pre_with_sale_snapshots_and_no_graphics(int $width): void
    {
        $admin = User::factory()->create(['name' => 'Cajero Ñ'])->assignRole('admin');
        $sale = $this->sale($admin, 'Aceite <especial>');
        $sale->update(['folio' => 'V-000050', 'paid_at' => '2026-11-26 14:38:00']);
        $sale->items()->firstOrFail()->update([
            'sale_mode' => Product::MODE_BULK, 'quantity' => 1.5, 'returned_quantity' => 0.125, 'line_total' => 147.99,
        ]);
        $settings = $this->payload(['width_mm' => $width, 'show_cashier' => true, 'show_qr' => true, 'footer' => '<img src=x onerror=alert(2)>']);
        $settings['ticket']['qr_image_url'] = 'data:image/png;base64,test';
        app(TicketSettings::class)->save($settings);

        $response = $this->get(route('pos.sales.ticket', ['sale' => $sale, 'print' => 1]))->assertOk()
            ->assertViewIs('pos.tickets.text-print')
            ->assertSee('@page { size: auto; margin: 0; }', false)
            ->assertSee('data-paper-width="'.$width.'"', false)
            ->assertDontSee('data:image/png;base64,', false)
            ->assertDontSee('<img', false);
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $this->assertSame(1, $document->getElementsByTagName('pre')->length);
        $receipt = json_decode($document->getElementById('ticket')->getAttribute('data-receipt'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('<img src=x onerror=alert(2)>', $receipt['ticket']['footer']);
        $this->assertArrayNotHasKey('qr_image_url', $receipt['ticket']);
        $this->assertSame('Taller Norte', $receipt['business']['name']);
        $this->assertSame('V-000050', $receipt['sale']['folio']);
        $this->assertSame('26/11/2026', $receipt['sale']['date']);
        $this->assertSame('14:38', $receipt['sale']['time']);
        $this->assertSame('Cajero Ñ', $receipt['sale']['cashier']);
        $this->assertSame('Tarjeta', $receipt['sale']['payment_method']);
        $this->assertSame(['148.00', '148.00', '0.00'], [$receipt['sale']['total'], $receipt['sale']['amount_paid'], $receipt['sale']['change_amount']]);
        $this->assertSame([
            'name' => 'Aceite <especial>',
            'quantity' => '1.5',
            'unit_price' => '148.00',
            'line_total' => '147.99',
            'sku' => 'SKU-OCULTO',
            'sale_mode' => Product::MODE_BULK,
            'returned_quantity' => '0.125',
        ], $receipt['sale']['items'][0]);
    }

    public function test_embedded_sale_print_uses_the_saved_mode_without_autoprint_and_limits_58_mm_to_32_columns(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $sale = $this->sale($admin, 'Cadena reforzada con una descripcion suficientemente larga para envolverla en el ticket');
        $url = route('pos.sales.ticket', ['sale' => $sale, 'embed' => 1]);
        $this->put(route('pos.tickets.update'), $this->payload(['print_mode' => 'text', 'width_mm' => 80]))
            ->assertSessionHasNoErrors();
        $wideTicket = $this->get($url)->assertOk()->assertSee('data-paper-width="80"', false);
        $this->assertPrintedColumns($wideTicket, 42);
        $this->put(route('pos.tickets.update'), $this->payload(['print_mode' => 'text', 'width_mm' => 58, 'full_description' => true]))
            ->assertSessionHasNoErrors();

        $text = $this->get($url)->assertOk()->assertViewIs('pos.tickets.text-print')
            ->assertSee('data-ticket-embedded', false)->assertSee('data-paper-width="58"', false)
            ->assertDontSee('data-ticket-auto-print', false)->assertDontSee('window.print()', false);
        $this->assertStringContainsString('no-store', $text->headers->get('Cache-Control'));
        $this->get($url.'&print=1')->assertOk()->assertDontSee('data-ticket-auto-print', false);
        $this->assertPrintedColumns($text, 32);

        $this->put(route('pos.tickets.update'), $this->payload(['print_mode' => 'graphic']))
            ->assertSessionHasNoErrors();
        foreach ([$url, $url.'&print=1'] as $embeddedUrl) {
            $this->get($embeddedUrl)->assertOk()->assertViewIs('pos.tickets.ticket')
                ->assertSee('data-ticket-embedded', false)
                ->assertSee('class="ticket ticket-sheet"', false)->assertSee('class="receipt-logo">MZ', false)
                ->assertDontSee('data-ticket-print-document', false)->assertDontSee('window.print()', false);
        }
        $this->actingAs(User::factory()->create()->assignRole('empleado')->givePermissionTo(['pos.access', 'pos.tickets.print.own']))
            ->get($url)->assertForbidden();
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
            ->assertJsonPath('settings.business.name', 'Taller Norte')
            ->assertJsonPath('sale.items.0.quantity', 1)
            ->assertJsonPath('sale.items.0.returned_quantity', 0);
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
            ->assertSee('data:image/png;base64,', false)->assertSee('80mm')
            ->assertSee('alt="Código QR" class="receipt-qr"', false)
            ->assertDontSee('Facturación / Soporte');
        $this->get(route('pos.tickets.edit'))->assertOk()
            ->assertSee('data-ticket-qr hidden alt="Código QR"', false)
            ->assertDontSee('Facturación / Soporte');

        $this->put(route('pos.tickets.update'), $this->payload(['print_mode' => 'graphic', 'show_qr' => true]))->assertSessionHasNoErrors();
        $this->get(route('pos.sales.ticket', ['sale' => $sale, 'print' => 1]))->assertOk()
            ->assertSee('class="receipt-logo">MZ', false)->assertSee('class="receipt-qr"', false)
            ->assertDontSee('data-ticket-print-document', false);

        $this->put(route('pos.tickets.update'), $this->payload(['show_qr' => true]))->assertSessionHasNoErrors();
        $this->assertStringStartsWith('data:image/png;base64,', app(TicketSettings::class)->current()['ticket']['qr_image_url']);

        $this->put(route('pos.tickets.update'), $this->payload(['show_qr' => false]))->assertSessionHasNoErrors();
        $this->get(route('pos.sales.ticket', $sale))->assertOk()
            ->assertDontSee('class="receipt-qr"', false);
    }

    public function test_bulk_ticket_payload_and_receipt_keep_fractional_sold_and_returned_quantities(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $sale = $this->sale($admin);
        $sale->items()->firstOrFail()->update([
            'sale_mode' => Product::MODE_BULK,
            'quantity' => 0.625,
            'returned_quantity' => 0.125,
        ]);

        $response = $this->getJson(route('pos.sales.ticketData', $sale));

        $response->assertOk()
            ->assertJsonPath('sale.items.0.quantity', 0.625)
            ->assertJsonPath('sale.items.0.returned_quantity', 0.125)
            ->assertJsonPath('sale.items.0.sale_mode', Product::MODE_BULK);
        $this->assertStringContainsString('Cantidad devuelta a granel: 0.125', $response->json('ticket_html'));
        $this->assertStringContainsString('>0.625</td>', $response->json('ticket_html'));
        $this->assertStringNotContainsString('pieza(s)', $response->json('ticket_html'));
        $this->get(route('pos.sales.ticket', $sale))->assertOk()
            ->assertSee('Cantidad devuelta a granel: 0.125')->assertSee('por unidad')
            ->assertSeeInOrder(['N.º de artículos: 0', 'Partidas a granel: 1']);
    }

    public function test_receipt_counts_sold_pieces_and_packages_without_subtracting_returns_or_adding_bulk(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $sale = $this->sale($admin);
        $sale->items()->firstOrFail()->update(['quantity' => 2, 'returned_quantity' => 1]);
        $sale->items()->createMany([
            ['product_name' => 'Paquete', 'sale_mode' => Product::MODE_KIT, 'quantity' => 3, 'unit_price' => 25],
            ['product_name' => 'A granel', 'sale_mode' => Product::MODE_BULK, 'quantity' => 0.5, 'unit_price' => 30],
        ]);

        $this->get(route('pos.sales.ticket', $sale))->assertOk()
            ->assertSeeInOrder(['Cantidad devuelta en piezas: 1', 'N.º de artículos: 5', 'Partidas a granel: 1']);
    }

    public function test_return_form_displays_fractional_balance_and_uses_steps_for_each_sale_mode(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $sale = $this->sale($admin);
        $sale->items()->firstOrFail()->update([
            'sale_mode' => Product::MODE_BULK,
            'quantity' => 0.625,
            'returned_quantity' => 0.125,
        ]);
        $sale->items()->create([
            'product_name' => 'Paquete entero',
            'sale_mode' => Product::MODE_KIT,
            'quantity' => 1,
            'unit_price' => 10,
        ]);

        $response = $this->get(route('pos.returns.create', $sale));

        $response->assertOk()->assertSee('A granel')->assertSee('Paquete (kit)')
            ->assertSee('0.625')->assertSee('0.125')->assertSee('>0.5</span>', false)
            ->assertSee('step="0.001"', false)->assertSee('step="1"', false)
            ->assertSee('min="0"', false)->assertSee('max="0.5"', false);
    }

    public function test_return_form_disables_both_fields_of_fully_returned_lines_and_keeps_other_lines_available(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $sale = $this->sale($admin);
        $sale->items()->firstOrFail()->update(['returned_quantity' => 1]);
        $remaining = $sale->items()->create([
            'product_name' => 'Granel restante', 'sale_mode' => Product::MODE_BULK,
            'quantity' => 0.5, 'unit_price' => 14.10, 'line_total' => 7.05,
        ]);

        $response = $this->get(route('pos.returns.create', $sale))->assertOk();

        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $this->assertSame(2, $xpath->query('//input[starts-with(@name, "items[0]") and @disabled]')->length);
        $this->assertSame(2, $xpath->query('//input[starts-with(@name, "items[1]") and not(@disabled)]')->length);
        $this->postJson(route('pos.returns.store'), [
            'sale_id' => $sale->id, 'reason' => 'Solo el producto restante',
            'items' => [1 => ['sale_item_id' => $remaining->id, 'quantity' => 0.125]],
        ])->assertRedirect(route('pos.returns.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sale_return_items', ['sale_item_id' => $remaining->id, 'quantity' => 0.125]);
    }

    #[TestWith([58])]
    #[TestWith([80])]
    public function test_printing_keeps_the_configured_width_and_all_receipt_data(int $width): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $productName = str_repeat('CUBIERTA REFORZADA ', 6);
        $sale = $this->sale($admin, trim($productName), 123456.78);
        $this->put(route('pos.tickets.update'), $this->payload(['width_mm' => $width, 'policy' => '']))->assertSessionHasNoErrors();

        $graphic = $this->get(route('pos.sales.ticket', $sale))->assertOk()
            ->assertSee('--ticket-width: '.$width.'mm', false)
            ->assertSee('width: calc(var(--ticket-width, 58mm) - 10mm)', false)
            ->assertSee('max-width: 100%', false)->assertSee('size: auto', false)
            ->assertDontSee('width: auto; max-width: none', false)
            ->assertSeeInOrder(['Cant.', 'Producto', 'Importe', trim($productName), '$123,456.78', 'por unidad', '$123,456.78', 'N.º de artículos: 1', 'Total', 'Pago con', 'Cambio', 'Método de pago: Tarjeta', 'Vuelve pronto.'])
            ->assertSee('class="unit-amount">$123,456.78</span>', false)
            ->assertDontSee('Subtotal')->assertDontSee($sale->folio)
            ->assertSee('$123,456.78')->assertDontSee('Conserve este ticket');
        $this->assertDoesNotMatchRegularExpression('/\bsize:\s*\d+mm\s+auto\b/', $graphic->getContent());

        $response = $this->getJson(route('pos.sales.ticketData', $sale))->assertOk()
            ->assertJsonPath('settings.ticket.width_mm', $width)
            ->assertJsonPath('sale.folio', $sale->folio);
        $this->assertStringContainsString('--ticket-width: '.$width.'mm', $response->json('ticket_html'));
        $this->assertStringContainsString(trim($productName), $response->json('ticket_html'));
        $this->assertStringContainsString('$123,456.78', $response->json('ticket_html'));
        $this->assertSame('123456.78', $sale->refresh()->total);

        $pos = $this->get(route('pos.index'))->assertOk()->assertDontSee('id="ticket-print-container"', false);
        $this->assertDoesNotMatchRegularExpression('/\bsize:\s*\d+mm\s+auto\b/', $pos->getContent());
        $preview = $this->get(route('pos.tickets.edit'))->assertOk()
            ->assertSee('--ticket-width: '.$width.'mm', false)
            ->assertSee('value="'.$width.'" selected', false)
            ->assertSee('data-ticket-width-label>'.$width.'</span>', false)
            ->assertSee('width: calc(var(--ticket-width, 58mm) - 10mm)', false)
            ->assertSee('body.ticket-example-print > :not(#ticket-print-root)', false)
            ->assertSeeInOrder(['Cant.', 'Producto', 'Importe', 'Aceite', '$148.00', 'por unidad', '$148.00', 'N.º de artículos: 1', 'Total', 'Pago con', 'Cambio', 'Método de pago: Efectivo'])
            ->assertSee('class="unit-amount">$148.00</span>', false)
            ->assertDontSee('Subtotal')->assertDontSee('V-000019')
            ->assertSee('margin: 0 auto', false)->assertSee('size: auto', false);
        $this->assertDoesNotMatchRegularExpression('/\bsize:\s*\d+mm\s+auto\b/', $preview->getContent());
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
    #[TestWith(['print_mode', 'unsupported'])]
    #[TestWith(['lines_above', -1])]
    #[TestWith(['lines_below', 21])]
    #[TestWith(['show_unit_price', 'invalid'])]
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
