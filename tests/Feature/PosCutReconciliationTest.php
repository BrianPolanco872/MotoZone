<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\Category;
use App\Models\CustomerPayment;
use App\Models\DailyCut;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\DailyCutService;
use App\Services\ReturnService;
use App\Services\SaleService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PosCutReconciliationTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['card'])]
    #[TestWith(['transfer'])]
    public function test_real_electronic_sale_does_not_add_physical_cash(string $method): void
    {
        $this->freezeTime();
        $user = $this->admin();
        $product = $this->product();
        $sale = app(SaleService::class)->create($user, [
            'idempotency_key' => 'electronic-real', 'payment_method' => $method,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $totals = app(DailyCutService::class)->build(today(), $user);

        $this->assertSame(100.0, $totals['sales']['total']);
        $this->assertSame(0.0, $totals['computer_panel']['subtotal']);
        $this->assertSame(0.0, $totals['cash_box_panel']['subtotal']);
        $this->assertTrue($totals['reconciliation']['is_reconciled']);
        $this->assertDatabaseHas('cash_movements', ['sale_id' => $sale->id, 'amount' => 100]);
    }

    public function test_navidad_cash_sale_never_registers_money_in_the_cash_box(): void
    {
        $this->freezeTime();
        $user = $this->admin();
        $category = Category::factory()->create(['name' => 'Navidad']);
        $product = $this->product(['category_id' => $category->id]);

        $sale = app(SaleService::class)->create($user, [
            'idempotency_key' => 'navidad-real-cash', 'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $this->assertDatabaseMissing('cash_movements', ['sale_id' => $sale->id]);
        $totals = app(DailyCutService::class)->build(today(), $user);
        $this->assertSame(0.0, $totals['cash_box_panel']['sales_movements']);
        $this->assertSame(0.0, $totals['computer_panel']['cash_sales']);
        $this->assertSame(100.0, $totals['navidad_total']);
        $this->assertSame(100.0, $totals['sales']['total']);
        $this->assertTrue($totals['reconciliation']['is_reconciled']);
    }

    public function test_mixed_navidad_sale_only_registers_the_non_navidad_cash(): void
    {
        $this->freezeTime();
        $user = $this->admin();
        $category = Category::factory()->create(['name' => 'navidad']);
        $navidadProduct = $this->product(['category_id' => $category->id]);
        $otherProduct = $this->product();

        $sale = app(SaleService::class)->create($user, [
            'idempotency_key' => 'navidad-real-mixed-items', 'payment_method' => 'cash',
            'items' => [
                ['product_id' => $navidadProduct->id, 'quantity' => 1],
                ['product_id' => $otherProduct->id, 'quantity' => 1],
            ],
        ]);

        $this->assertDatabaseHas('cash_movements', ['sale_id' => $sale->id, 'amount' => 100]);
        $totals = app(DailyCutService::class)->build(today(), $user);
        $this->assertSame(100.0, $totals['cash_box_panel']['sales_movements']);
        $this->assertSame(100.0, $totals['navidad_total']);
        $this->assertSame(200.0, $totals['sales']['total']);
    }

    public function test_mixed_payment_without_breakdown_is_not_reported_as_reconciled_or_purely_electronic(): void
    {
        $this->freezeTime();
        $user = $this->admin();
        $product = $this->product();
        app(SaleService::class)->create($user, [
            'idempotency_key' => 'unallocated-mixed', 'payment_method' => 'mixed',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $totals = app(DailyCutService::class)->build(today(), $user);

        $this->assertSame(100.0, $totals['sales']['total']);
        $this->assertSame(100.0, $totals['sales']['total_mixed']);
        $this->assertSame(0.0, $totals['sales']['total_non_cash']);
        $this->assertFalse($totals['reconciliation']['is_reconciled']);
        $this->assertTrue($totals['reconciliation']['has_unallocated_mixed']);
        $this->assertStringContainsString('no está determinado', $totals['reconciliation']['warning']);
        $service = app(DailyCutService::class);
        $cut = $service->generate(today(), $user)->firstWhere('scope', DailyCut::SCOPE_GLOBAL);
        $this->assertFalse($service->recordCashCounted($cut, 0)->is_reconciled);
    }

    #[TestWith(['card'])]
    #[TestWith(['transfer'])]
    public function test_electronic_supplier_payment_does_not_reduce_physical_cash(string $method): void
    {
        $this->freezeTime();
        $user = $this->admin();
        $this->cashMovement($user, 'initial', 500);
        SupplierPayment::create([
            'user_id' => $user->id, 'amount' => 200, 'method' => $method,
            'supplier_name' => 'Proveedor', 'reason' => 'Pago electrónico', 'happened_at' => now(),
        ]);

        $totals = app(DailyCutService::class)->build(today(), $user);

        $this->assertSame(200.0, $totals['supplier_payments']);
        $this->assertSame(500.0, $totals['computer_panel']['subtotal']);
        $this->assertSame(500.0, $totals['cash_box_panel']['subtotal']);
        $this->assertSame(0.0, $totals['cash_out']['suppliers']);
    }

    public function test_creator_keeps_commercial_sale_but_cash_belongs_to_the_employee_who_charged_it(): void
    {
        $this->freezeTime();
        $creator = $this->employee();
        $cashier = $this->admin();
        $product = $this->product();
        $service = app(SaleService::class);
        $sale = $service->create($creator, [
            'idempotency_key' => 'another-cashier', 'status' => 'pending',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
        $service->chargePending($sale, $cashier);
        $cuts = app(DailyCutService::class);

        $creatorTotals = $cuts->build(today(), $creator);
        $cashierTotals = $cuts->build(today(), $cashier);

        $this->assertSame($creator->id, $sale->fresh()->user_id);
        $this->assertSame(100.0, $creatorTotals['sales']['total']);
        $this->assertSame(0.0, $cashierTotals['sales']['total']);
        $this->assertSame(0.0, $creatorTotals['computer_panel']['subtotal']);
        $this->assertSame(0.0, $creatorTotals['cash_box_panel']['subtotal']);
        $this->assertSame(100.0, $cashierTotals['computer_panel']['subtotal']);
        $this->assertSame(100.0, $cashierTotals['cash_box_panel']['subtotal']);
        $rows = $cuts->employeeBreakdown(today()->toDateString())['employees']->keyBy('id');
        $this->assertSame(0.0, $rows[$creator->id]['other_payments']);
        $this->assertSame(100.0, $rows[$cashier->id]['cash']);
        $this->assertSame(100.0, $rows[$cashier->id]['should_have']);
    }

    public function test_commercial_return_stays_with_seller_and_physical_refund_with_approver(): void
    {
        $this->freezeTime();
        $seller = $this->employee();
        $approver = $this->admin();
        $product = $this->product();
        $sale = app(SaleService::class)->create($seller, [
            'idempotency_key' => 'other-approver',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
        $returns = app(ReturnService::class);
        $return = $returns->request($seller, [
            'sale_id' => $sale->id, 'reason' => 'Cambio',
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]],
        ]);
        $returns->approve($return, $approver);
        $cuts = app(DailyCutService::class);

        $sellerTotals = $cuts->build(today(), $seller);
        $approverTotals = $cuts->build(today(), $approver);

        $this->assertSame(0.0, $sellerTotals['totals']['net_sales']);
        $this->assertSame(100.0, $sellerTotals['computer_panel']['subtotal']);
        $this->assertSame(100.0, $sellerTotals['cash_box_panel']['subtotal']);
        $this->assertSame(-100.0, $approverTotals['computer_panel']['subtotal']);
        $this->assertSame(-100.0, $approverTotals['cash_box_panel']['subtotal']);
        $this->assertSame(0.0, $cuts->build(today())['cash_box_panel']['subtotal']);
    }

    public function test_missing_cash_movement_remains_a_real_global_reconciliation_difference(): void
    {
        $this->freezeTime();
        $user = $this->admin();
        $sale = $this->paidSale($user, 100, 'missing-movement');
        CashMovement::query()->where('sale_id', $sale->id)->forceDelete();

        $totals = app(DailyCutService::class)->build(today());

        $this->assertSame(100.0, $totals['computer_panel']['subtotal']);
        $this->assertSame(0.0, $totals['cash_box_panel']['subtotal']);
        $this->assertFalse($totals['reconciliation']['is_reconciled']);
    }

    public function test_payment_only_users_receive_individual_cuts_and_snapshot_breakdown(): void
    {
        $this->freezeTime();
        $admin = $this->admin();
        $customerOperator = User::factory()->create();
        $supplierOperator = User::factory()->create();
        CustomerPayment::create([
            'user_id' => $customerOperator->id, 'amount' => 50, 'method' => 'cash',
            'customer_name' => 'Cliente', 'happened_at' => now(),
        ]);
        SupplierPayment::create([
            'user_id' => $supplierOperator->id, 'amount' => 20, 'method' => 'cash',
            'supplier_name' => 'Proveedor', 'reason' => 'Compra', 'happened_at' => now(),
        ]);

        $cuts = app(DailyCutService::class)->generate(today(), $admin);

        $global = $cuts->firstWhere('scope', DailyCut::SCOPE_GLOBAL);
        $rows = collect($global->totals['employee_breakdown']['employees'] ?? [])->keyBy('id');
        $this->assertSame(50.0, (float) ($rows[$customerOperator->id]['should_have'] ?? -1));
        $this->assertSame(-20.0, (float) ($rows[$supplierOperator->id]['should_have'] ?? -1));
        $this->assertDatabaseHas('daily_cuts', ['scope' => 'employee', 'user_id' => $customerOperator->id]);
        $this->assertDatabaseHas('daily_cuts', ['scope' => 'employee', 'user_id' => $supplierOperator->id]);
        $this->assertSame(30.0, (float) $global->totals['cash_box_panel']['subtotal']);
    }

    public function test_generated_snapshot_keeps_frozen_scalar_breakdown_without_nested_cut_models(): void
    {
        $this->freezeTime();
        $admin = $this->admin();
        $this->paidSale($admin, 100, 'snapshot-first');
        $service = app(DailyCutService::class);
        $first = $service->generate(today(), $admin)->firstWhere('scope', 'global');
        $firstTotals = $first->totals;
        $this->paidSale($admin, 200, 'snapshot-second');

        $service->generate(today(), $admin);

        $this->assertSame($firstTotals, $first->fresh()->totals);
        $this->assertSame(100.0, (float) ($firstTotals['employee_breakdown']['total']['should_have'] ?? -1));
        foreach ($firstTotals['employee_breakdown']['employees'] as $row) {
            $this->assertArrayNotHasKey('latest_cut', $row);
            $this->assertTrue($row['closed_at'] === null || is_string($row['closed_at']));
        }
    }

    public function test_cash_monitor_table_includes_customer_payments_in_drawer_amount(): void
    {
        $this->freezeTime();
        $admin = $this->admin();
        $this->cashMovement($admin, 'initial', 500);
        CustomerPayment::create([
            'user_id' => $admin->id, 'amount' => 20, 'method' => 'cash',
            'customer_name' => 'Cliente', 'happened_at' => now(),
        ]);

        $html = $this->actingAs($admin)->getJson(route('pos.cash.cut-board'))->assertOk()->json('html');

        $this->assertSame(1, preg_match('/<tbody[^>]*>(.*?)<\/tbody>/s', $html, $body));
        $this->assertStringContainsString('$520.00', $body[1]);
        $this->assertSame(1, preg_match('/<tfoot[^>]*>(.*?)<\/tfoot>/s', $html, $foot));
        $this->assertStringContainsString('$520.00', $foot[1]);
    }

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

    public function test_efectivo_contado_se_registra_por_http(): void
    {
        $admin = $this->admin();
        $today = Carbon::now()->toDateString();

        $this->cashMovement($admin, 'initial', 500.00, 'in');
        $this->paidSale($admin, 100.00, 'http-001');

        $cuts = app(DailyCutService::class)->generate($today, $admin);
        $globalCut = $cuts->firstWhere('scope', 'global');

        $response = $this->actingAs($admin)->post(route('pos.cuts.record-cash', $globalCut), [
            'cash_counted' => 610.00,
            'reason' => 'Conteo físico desde el panel',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $globalCut->refresh();
        $this->assertEquals(610.00, (float) $globalCut->cash_counted);
        $this->assertEquals(10.00, (float) $globalCut->difference);
        $this->assertSame('Conteo físico desde el panel', $globalCut->reason);
    }

    public function test_efectivo_contado_acepta_separador_de_miles(): void
    {
        $admin = $this->admin();
        $today = Carbon::now()->toDateString();

        $cuts = app(DailyCutService::class)->generate($today, $admin);
        $globalCut = $cuts->firstWhere('scope', 'global');

        $response = $this->actingAs($admin)->post(route('pos.cuts.record-cash', $globalCut), [
            'cash_counted' => '14,325',
            'reason' => 'Conteo con separador de miles',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertEquals(14325.00, (float) $globalCut->refresh()->cash_counted);
    }

    public function test_efectivo_contado_se_compara_contra_el_total_de_caja_mostrado(): void
    {
        $admin = $this->admin();
        $today = Carbon::now()->toDateString();

        $this->cashMovement($admin, 'initial', 500.00, 'in');
        $sale = $this->paidSale($admin, 100.00, 'caja-001');

        // Devolución aprobada sin movimiento de caja: el panel "Dinero en caja"
        // sigue en 600 aunque el panel de computadora reste 50.
        SaleReturn::create([
            'sale_id' => $sale->id,
            'requested_by' => $admin->id,
            'approved_by' => $admin->id,
            'status' => 'approved',
            'reason' => 'Prueba',
            'total' => 50.00,
            'approved_at' => now(),
        ]);

        $cuts = app(DailyCutService::class)->generate($today, $admin);
        $globalCut = $cuts->firstWhere('scope', 'global');

        $response = $this->actingAs($admin)->post(route('pos.cuts.record-cash', $globalCut), [
            'cash_counted' => '600',
            'reason' => 'Conteo igual al total de caja',
        ]);

        $response->assertSessionHasNoErrors();

        $globalCut->refresh();
        $this->assertEquals(600.00, (float) $globalCut->cash_counted);
        $this->assertEquals(0.00, (float) $globalCut->difference);
        $this->assertTrue($globalCut->is_reconciled);
    }
}
