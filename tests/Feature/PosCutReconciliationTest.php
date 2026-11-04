<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\DailyCut;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\DailyCutService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCutReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(array $permissions = []): User
    {
        $user = User::factory()->create()->assignRole('admin');
        $user->givePermissionTo(array_merge([
            'pos.access', 'pos.sell', 'pos.cash.manage', 'pos.cut.manage',
            'pos.sales.day.any', 'pos.returns.request', 'pos.returns.approve',
        ], $permissions));

        return $user;
    }

    private function employee(array $permissions = []): User
    {
        $user = User::factory()->create()->assignRole('empleado');
        $user->givePermissionTo(array_merge(['pos.access', 'pos.sell'], $permissions));

        return $user;
    }

    private function product(array $overrides = []): Product
    {
        static $counter = 0;
        $counter++;

        return Product::factory()->create(array_merge([
            'name' => 'Producto prueba '.$counter,
            'sku' => 'PRU-'.$counter,
            'barcode' => '750'.str_pad((string) $counter, 10, '0', STR_PAD_LEFT),
            'price' => 100.00,
            'cost' => 60.00,
            'stock' => 100,
            'min_stock' => 2,
            'track_inventory' => true,
            'tax_rate' => 0.16,
        ], $overrides));
    }

    private function paidSale(User $user, float $total, string $key, string $method = 'cash', ?string $paidAt = null): Sale
    {
        $product = $this->product(['stock' => 100]);

        $sale = Sale::create([
            'user_id' => $user->id,
            'folio' => 'V-'.str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT),
            'status' => 'paid',
            'payment_method' => $method,
            'subtotal' => round($total / 1.16, 2),
            'tax_total' => round($total - $total / 1.16, 2),
            'total' => $total,
            'amount_paid' => $total,
            'change_amount' => 0.00,
            'idempotency_key' => $key,
            'paid_at' => $paidAt ?? now(),
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'category_id' => $product->category_id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'category_name' => $product->category?->name,
            'quantity' => 1,
            'unit_price' => $total,
            'cost_price' => 60.00,
            'tax_rate' => 0.16,
            'line_subtotal' => round($total / 1.16, 2),
            'tax_amount' => round($total - $total / 1.16, 2),
            'line_total' => $total,
            'line_cost' => 60.00,
            'price_overridden' => false,
        ]);

        if ($method === 'cash') {
            CashMovement::create([
                'user_id' => $user->id,
                'sale_id' => $sale->id,
                'type' => 'in',
                'category' => 'sale',
                'amount' => $total,
                'concept' => 'Venta '.$sale->folio,
                'happened_at' => $sale->paid_at,
            ]);
        }

        return $sale;
    }

    private function cashMovement(User $user, string $category, float $amount, string $type = 'in', ?string $happenedAt = null): CashMovement
    {
        return CashMovement::create([
            'user_id' => $user->id,
            'type' => $type,
            'category' => $category,
            'amount' => $amount,
            'concept' => 'Prueba '.$category,
            'happened_at' => $happenedAt ?? now(),
        ]);
    }

    public function test_dia_sin_movimientos_da_cero_y_cuadra(): void
    {
        $admin = $this->admin();
        $today = Carbon::now()->toDateString();

        $response = $this->actingAs($admin)->get(route('pos.cuts.index', ['date' => $today]));

        $response->assertOk();
        $response->assertSee('$0.00');
    }

    public function test_dinero_inicial_ventas_efectivo_y_salida_cuadran_en_ambos_paneles(): void
    {
        $admin = $this->admin();
        $today = Carbon::now()->toDateString();

        $this->cashMovement($admin, 'initial', 500.00, 'in');
        $this->paidSale($admin, 100.00, 'rec-001');
        $this->paidSale($admin, 250.50, 'rec-002');
        $this->paidSale($admin, 80.00, 'rec-003');
        $this->cashMovement($admin, 'expense', 200.00, 'out');

        // 500 + 100 + 250.50 + 80 - 200 = 730.50
        $response = $this->actingAs($admin)->get(route('pos.cuts.index', ['date' => $today]));

        $response->assertOk();
        $response->assertSee('$730.50');
    }

    public function test_venta_con_tarjeta_no_suma_a_efectivo_pero_si_a_ventas_totales(): void
    {
        $admin = $this->admin();
        $today = Carbon::now()->toDateString();

        $this->cashMovement($admin, 'initial', 500.00, 'in');
        $this->paidSale($admin, 100.00, 'card-001', 'card');

        $response = $this->actingAs($admin)->get(route('pos.cuts.index', ['date' => $today]));

        $response->assertOk();
        // Ventas totales incluye la de tarjeta
        $response->assertSee('$100.00');
        // Pero el efectivo en computadora no incluye la de tarjeta (solo inicial $500)
        $response->assertSee('$500.00');
    }

    public function test_devolucion_en_efectivo_resta_de_caja_y_ventas_netas(): void
    {
        $admin = $this->admin();
        $today = Carbon::now()->toDateString();

        $this->cashMovement($admin, 'initial', 500.00, 'in');
        $sale = $this->paidSale($admin, 200.00, 'ret-001');

        // Devolución aprobada de $100
        SaleReturn::create([
            'sale_id' => $sale->id,
            'requested_by' => $admin->id,
            'approved_by' => $admin->id,
            'status' => 'approved',
            'reason' => 'Prueba',
            'total' => 100.00,
            'approved_at' => now(),
        ]);

        CashMovement::create([
            'user_id' => $admin->id,
            'sale_id' => $sale->id,
            'type' => 'out',
            'category' => 'refund',
            'amount' => 100.00,
            'concept' => 'Devolución '.$sale->folio,
            'happened_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('pos.cuts.index', ['date' => $today]));

        $response->assertOk();
        // 500 + 200 - 100 = 600 en caja
        $response->assertSee('$600.00');
        // Ventas netas: 200 - 100 = 100
        $response->assertSee('$100.00');
    }

    public function test_suma_de_cortes_por_empleado_igual_a_corte_global(): void
    {
        $admin = $this->admin();
        $employeeA = $this->employee(['pos.sell']);
        $employeeB = $this->employee(['pos.sell']);
        $today = Carbon::now()->toDateString();

        $this->cashMovement($employeeA, 'initial', 300.00, 'in');
        $this->paidSale($employeeA, 200.00, 'emp-a-001');

        $this->cashMovement($employeeB, 'initial', 200.00, 'in');
        $this->paidSale($employeeB, 150.00, 'emp-b-001');

        // Generar cortes individuales y global directamente por servicio
        $service = app(DailyCutService::class);
        $cuts = $service->generate($today, $admin);

        $this->assertNotEmpty($cuts);

        $globalCut = DailyCut::query()
            ->where('scope', 'global')
            ->whereDate('cut_date', $today)
            ->orderByDesc('version')
            ->first();

        $employeeACut = DailyCut::query()
            ->where('scope', 'employee')
            ->where('user_id', $employeeA->id)
            ->whereDate('cut_date', $today)
            ->orderByDesc('version')
            ->first();

        $employeeBCut = DailyCut::query()
            ->where('scope', 'employee')
            ->where('user_id', $employeeB->id)
            ->whereDate('cut_date', $today)
            ->orderByDesc('version')
            ->first();

        $this->assertNotNull($globalCut);
        $this->assertNotNull($employeeACut);
        $this->assertNotNull($employeeBCut);

        $globalSales = (float) ($globalCut->totals['totals']['sales'] ?? 0);
        $employeeASales = (float) ($employeeACut->totals['totals']['sales'] ?? 0);
        $employeeBSales = (float) ($employeeBCut->totals['totals']['sales'] ?? 0);

        $this->assertEqualsWithDelta($employeeASales + $employeeBSales, $globalSales, 0.01);
    }

    public function test_empleado_no_puede_ver_corte_de_otro(): void
    {
        $employeeA = $this->employee(['pos.sales.day.own']);
        $employeeB = $this->employee(['pos.sales.day.own']);
        $today = Carbon::now()->toDateString();

        $this->actingAs($employeeA)
            ->get(route('pos.cuts.index', ['date' => $today, 'employee' => $employeeB->id]))
            ->assertForbidden();
    }

    public function test_rehacer_corte_genera_nueva_version_sin_borrar_anterior(): void
    {
        $admin = $this->admin();
        $today = Carbon::now()->toDateString();

        $this->cashMovement($admin, 'initial', 500.00, 'in');
        $this->paidSale($admin, 100.00, 'ver-001');

        $service = app(DailyCutService::class);

        // Primera versión
        $service->storeVersion(
            $today, DailyCut::SCOPE_GLOBAL, null,
            $service->build($today), $admin,
            notes: 'Primera versión'
        );

        // Segunda versión
        $service->storeVersion(
            $today, DailyCut::SCOPE_GLOBAL, null,
            $service->build($today), $admin,
            notes: 'Segunda versión'
        );

        $versions = DailyCut::query()
            ->where('scope', 'global')
            ->whereDate('cut_date', $today)
            ->orderByDesc('version')
            ->get();

        $this->assertCount(2, $versions);
        $this->assertEquals(1, $versions->first()->previous_cut_id); // v2 apunta a v1
        $this->assertEquals('Primera versión', $versions->last()->notes);
        $this->assertEquals('Segunda versión', $versions->first()->notes);
    }

    public function test_venta_a_medianoche_merida_cae_en_dia_correcto(): void
    {
        $admin = $this->admin();
        // 23:58 hora de Mérida (UTC-6) = 05:58 UTC del día siguiente
        $meridaNight = Carbon::parse('2026-11-04 23:58:00', 'America/Merida');

        $this->paidSale($admin, 100.00, 'mid-001', 'cash', $meridaNight);

        // La venta debe aparecer en el corte del 04/11/2026
        $response = $this->actingAs($admin)->get(route('pos.cuts.index', ['date' => '2026-11-04']));

        $response->assertOk();
        $response->assertSee('$100.00');
    }

    public function test_doble_clic_en_cobrar_solo_una_venta_por_idempotencia(): void
    {
        $employee = $this->employee(['pos.sell']);
        $product = $this->product(['stock' => 10]);

        $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'doble-001',
            'status' => 'paid',
            'amount_paid' => 116.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'doble-001',
            'status' => 'paid',
            'amount_paid' => 116.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertSuccessful();
        $this->assertCount(1, Sale::where('idempotency_key', 'doble-001')->get());
    }

    public function test_efectivo_contado_registra_sobrante_faltante(): void
    {
        $admin = $this->admin();
        $today = Carbon::now()->toDateString();

        $this->cashMovement($admin, 'initial', 500.00, 'in');
        $this->paidSale($admin, 100.00, 'cont-001');

        $service = app(DailyCutService::class);
        $cuts = $service->generate($today, $admin);

        $globalCut = $cuts->firstWhere('scope', 'global');

        $this->assertNotNull($globalCut);
        $this->assertNull($globalCut->cash_counted);

        // Conteo con sobrante: 610 vs 600 esperado = +10 sobrante
        $service->recordCashCounted($globalCut, 610.00, 'Conteo físico al cierre');

        $globalCut->refresh();
        $this->assertEquals(610.00, (float) $globalCut->cash_counted);
        $this->assertEquals(10.00, (float) $globalCut->difference);
        $this->assertFalse($globalCut->is_reconciled);

        // Conteo exacto: 600 vs 600 = cuadra
        $service->recordCashCounted($globalCut, 600.00, 'Conteo corregido');

        $globalCut->refresh();
        $this->assertEquals(600.00, (float) $globalCut->cash_counted);
        $this->assertEquals(0.00, (float) $globalCut->difference);
        $this->assertTrue($globalCut->is_reconciled);
    }
}
