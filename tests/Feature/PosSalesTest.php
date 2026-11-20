<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\SaleService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PosSalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function employee(array $permissions = []): User
    {
        $user = User::factory()->create()->assignRole('empleado');
        $user->givePermissionTo(array_merge(['pos.access', 'pos.sell'], $permissions));

        return $user;
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'name' => 'Kit de prueba',
            'sku' => 'KIT-001',
            'barcode' => '7501234567890',
            'price' => 100.00,
            'cost' => 60.00,
            'stock' => 10,
            'min_stock' => 2,
            'track_inventory' => true,
            'tax_rate' => 0.16,
        ], $overrides));
    }

    public function test_employee_can_create_paid_sale_and_deducts_stock(): void
    {
        $employee = $this->employee();
        $product = $this->createProduct(['stock' => 5]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'test-sale-001',
            'status' => 'paid',
            'amount_paid' => 200.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertOk();

        $sale = Sale::where('idempotency_key', 'test-sale-001')->first();
        $this->assertNotNull($sale);
        $this->assertNotNull($sale->folio);
        $this->assertEquals('paid', $sale->status);
        $this->assertEquals(200.00, (float) $sale->total);
        $this->assertEquals(200.00, (float) $sale->amount_paid);

        $product->refresh();
        $this->assertEquals(3, $product->stock);
    }

    public function test_idempotency_prevents_duplicate_sale(): void
    {
        $employee = $this->employee();
        $product = $this->createProduct(['stock' => 5]);

        $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'idem-001',
            'status' => 'paid',
            'amount_paid' => 200.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'idem-001',
            'status' => 'paid',
            'amount_paid' => 200.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertCount(1, Sale::where('idempotency_key', 'idem-001')->get());
        $product->refresh();
        $this->assertEquals(3, $product->stock); // Solo se descontó una vez
    }

    public function test_sale_cannot_exceed_available_stock(): void
    {
        $employee = $this->employee();
        $product = $this->createProduct(['stock' => 1]);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'stock-001',
            'status' => 'paid',
            'amount_paid' => 200.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                ],
            ],
        ]);

        $response->assertUnprocessable();
        $this->assertCount(0, Sale::where('idempotency_key', 'stock-001')->get());
        $product->refresh();
        $this->assertEquals(1, $product->stock);
    }

    public function test_pending_sale_can_be_charged_and_updates_stock(): void
    {
        $employee = $this->employee();
        $product = $this->createProduct(['stock' => 5]);

        $createResponse = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'pending-001',
            'status' => 'pending',
            'amount_paid' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $saleId = $createResponse->json('sale.id');

        $this->assertEquals(5, $product->refresh()->stock); // Stock no descontado aún

        $chargeResponse = $this->actingAs($employee)->postJson(route('pos.sales.charge', $saleId), [
            'amount_paid' => 232.00,
        ]);

        $chargeResponse->assertOk();

        $this->assertEquals(3, $product->refresh()->stock); // Ahora se descuenta

        $sale = Sale::find($saleId);
        $this->assertEquals('paid', $sale->status);
        $this->assertNotNull($sale->paid_at);
    }

    public function test_pending_catalog_sale_remains_loadable_but_charge_returns_422_after_clearing_inventory(): void
    {
        $this->freezeTime();
        $employee = $this->employee(['admin.products.manage']);
        $product = $this->createProduct(['stock' => 5, 'barcode' => '00012345']);
        $this->actingAs($employee);
        $response = $this->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'pending-cleared-inventory',
            'status' => 'pending',
            'amount_paid' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertOk();
        $sale = Sale::query()->findOrFail($response->json('sale.id'));
        $saleItem = $sale->items()->sole();
        $saleBefore = $sale->getAttributes();
        $itemBefore = $saleItem->getAttributes();
        $this->assertSame('pending', $sale->status);
        $this->assertSame('0.00', $sale->amount_paid);
        $this->assertSame(5, $product->fresh()->stock);

        $this->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Se eliminaron 1 productos del inventario.');

        $this->assertDatabaseCount('products', 0);
        $this->getJson(route('pos.sales.show', $sale))->assertOk()
            ->assertJsonPath('sale.id', $sale->id)
            ->assertJsonPath('sale.total', 200)
            ->assertJsonPath('sale.items.0.sale_item_id', $saleItem->id)
            ->assertJsonPath('sale.items.0.product_id', null)
            ->assertJsonPath('sale.items.0.is_custom', false)
            ->assertJsonPath('sale.items.0.custom_name', null)
            ->assertJsonPath('sale.items.0.custom_price', null)
            ->assertJsonPath('sale.items.0.name', 'Kit de prueba')
            ->assertJsonPath('sale.items.0.sku', 'KIT-001')
            ->assertJsonPath('sale.items.0.barcode', '00012345')
            ->assertJsonPath('sale.items.0.unit_price', 100)
            ->assertJsonPath('sale.items.0.quantity', 2);
        $this->assertSame([...$itemBefore, 'product_id' => null], $saleItem->fresh()->getAttributes());

        $this->postJson(route('pos.sales.charge', $sale), ['amount_paid' => 200])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items')
            ->assertJsonPath('errors.items.0', 'El producto «Kit de prueba» ya no existe.');

        $this->assertSame($saleBefore, $sale->fresh()->getAttributes());
        $this->assertSame([...$itemBefore, 'product_id' => null], $saleItem->fresh()->getAttributes());
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('cash_movements', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_custom_only_sale_is_paid_without_inventory_movements(): void
    {
        $employee = $this->employee(['pos.tickets.print.own']);

        $response = $this->actingAs($employee)->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'custom-only',
            'items' => [['is_custom' => true, 'custom_name' => 'Mano de obra', 'custom_price' => 25.50, 'quantity' => 2]],
        ])->assertOk();

        $sale = Sale::query()->findOrFail($response->json('sale.id'));
        $this->assertSame('51.00', $sale->total);
        $this->assertSame('paid', $sale->status);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id, 'product_id' => null, 'is_custom' => true,
            'product_name' => 'Mano de obra', 'custom_price' => 25.50, 'quantity' => 2,
        ]);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseHas('cash_movements', ['sale_id' => $sale->id, 'amount' => 51]);
        $this->get(route('pos.sales.ticket', $sale))->assertOk()->assertSee('Mano de obra');
    }

    public function test_pending_sale_with_catalog_and_distinct_custom_items_can_be_loaded_and_charged(): void
    {
        $employee = $this->employee();
        $product = $this->createProduct(['stock' => 5]);
        $this->actingAs($employee);
        $response = $this->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'custom-pending', 'status' => 'pending',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
                ['is_custom' => true, 'custom_name' => 'Instalación', 'custom_price' => 30, 'quantity' => 1],
                ['is_custom' => true, 'custom_name' => 'Revisión', 'custom_price' => 20, 'quantity' => 1],
            ],
        ])->assertOk();
        $sale = Sale::query()->findOrFail($response->json('sale.id'));
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('cash_movements', 0);
        $this->getJson(route('pos.sales.show', $sale))->assertOk()
            ->assertJsonPath('sale.items.1.is_custom', true)
            ->assertJsonPath('sale.items.1.custom_name', 'Instalación')
            ->assertJsonPath('sale.items.1.custom_price', 30)
            ->assertJsonPath('sale.items.2.custom_name', 'Revisión');

        $this->postJson(route('pos.sales.charge', $sale), ['amount_paid' => 150])->assertOk();

        $this->assertSame('paid', $sale->fresh()->status);
        $this->assertSame('150.00', $sale->fresh()->total);
        $this->assertSame(4, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertSame(['Instalación', 'Revisión'], $sale->items()->where('is_custom', true)->orderBy('id')->pluck('custom_name')->all());
        $this->assertDatabaseHas('cash_movements', ['sale_id' => $sale->id, 'amount' => 150]);
    }

    #[TestWith([['is_custom' => true, 'custom_price' => 10], 'items.0.custom_name'])]
    #[TestWith([['is_custom' => true, 'custom_name' => 'Libre'], 'items.0.custom_price'])]
    #[TestWith([['is_custom' => true, 'custom_name' => 'Libre', 'custom_price' => 0], 'items.0.custom_price'])]
    #[TestWith([['is_custom' => false], 'items.0.product_id'])]
    public function test_custom_and_catalog_items_require_their_own_fields(array $item, string $field): void
    {
        $this->actingAs($this->employee())->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'invalid-custom', 'items' => [['quantity' => 1, ...$item]],
        ])->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    #[TestWith(['paid'])]
    #[TestWith(['pending'])]
    public function test_repeated_catalog_lines_cannot_exceed_combined_available_stock(string $status): void
    {
        $product = $this->createProduct(['stock' => 5]);
        $this->actingAs($this->employee());
        $payload = [
            'idempotency_key' => 'repeated-stock', 'status' => $status,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ];

        if ($status === 'pending') {
            $created = $this->postJson(route('pos.sales.store'), $payload)->assertOk();
            $this->postJson(route('pos.sales.charge', $created->json('sale.id')), [])
                ->assertUnprocessable()->assertJsonValidationErrors('items');
            $this->assertDatabaseHas('sales', ['id' => $created->json('sale.id'), 'status' => 'pending']);
        } else {
            $this->postJson(route('pos.sales.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('items');
            $this->assertDatabaseCount('sales', 0);
        }

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    public function test_repeated_product_keeps_separate_authorized_prices_when_combined_stock_is_sufficient(): void
    {
        $product = $this->createProduct(['stock' => 5]);

        $this->actingAs($this->employee(['pos.price.edit']))->postJson(route('pos.sales.store'), [
            'idempotency_key' => 'repeated-prices',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100],
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 90],
            ],
        ])->assertOk();

        $this->assertSame(0, $product->fresh()->stock);
        $this->assertSame(['100.00', '90.00'], SaleItem::query()->orderBy('id')->pluck('unit_price')->all());
        $this->assertDatabaseHas('sales', ['total' => 470]);
    }

    public function test_day_filter_uses_selected_employee_and_preserves_employee_isolation(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create()->assignRole('admin');
        $firstEmployee = $this->employee();
        $selectedEmployee = $this->employee();
        $product = $this->createProduct();
        $service = app(SaleService::class);
        $firstSale = $service->create($firstEmployee, ['idempotency_key' => 'filter-first', 'items' => [['product_id' => $product->id, 'quantity' => 1]]]);
        $selectedSale = $service->create($selectedEmployee, ['idempotency_key' => 'filter-selected', 'items' => [['product_id' => $product->id, 'quantity' => 1]]]);

        $response = $this->actingAs($admin)->get(route('pos.sales.day', ['employee' => $selectedEmployee->id]))->assertOk();

        $this->assertSame([$selectedSale->id], $response->viewData('sales')->getCollection()->modelKeys());
        $this->assertSame(1, $response->viewData('summary')['count']);
        $ownResponse = $this->actingAs($firstEmployee)->get(route('pos.sales.day', ['employee' => $selectedEmployee->id]))->assertOk();
        $this->assertSame([$firstSale->id], $ownResponse->viewData('sales')->getCollection()->modelKeys());
    }
}
