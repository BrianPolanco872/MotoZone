<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\HomeCard;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\DailyCutService;
use App\Services\ReturnService;
use App\Services\SaleService;
use Database\Seeders\RolePermissionSeeder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_create_product_with_two_barcodes_and_tax_rate(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Kit de arrastre de prueba',
            'brand' => 'DID',
            'description' => 'Kit completo para pruebas de inventario.',
            'barcode' => '0001234567890',
            'barcode2' => 'KIT(02)+#',
            'price' => 890.50,
            'tax_rate' => 16,
            'stock' => 12,
            'featured' => '1',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('barcode2', 'KIT(02)+#')->firstOrFail();

        $this->assertSame('0001234567890', $product->barcode);
        $this->assertSame('KIT(02)+#', $product->barcode2);
        $this->assertStringStartsWith('AUTO-', $product->sku);
        $this->assertSame('0.1600', $product->tax_rate);
        $this->assertSame('kit-de-arrastre-de-prueba', $product->slug);
        $this->assertTrue($product->is_active);
    }

    /**
     * @param  array{brand?: string, description?: string}  $optionalFields
     */
    #[TestWith([[]])]
    #[TestWith([['brand' => '', 'description' => '']])]
    public function test_admin_can_create_product_without_brand_and_description(array $optionalFields): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'name' => 'Producto incompleto',
            'price' => 100,
            'tax_rate' => 16,
            'stock' => 2,
            ...$optionalFields,
        ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'name' => 'Producto incompleto',
            'brand' => '',
            'description' => '',
            'stock' => 2,
        ]);
    }

    /**
     * @param  array{brand?: string, description?: string}  $optionalFields
     */
    #[TestWith([[], 'Marca original', 'Descripción original'])]
    #[TestWith([['brand' => '', 'description' => ''], '', ''])]
    #[TestWith([['brand' => 'NGK', 'description' => 'Descripción nueva'], 'NGK', 'Descripción nueva'])]
    public function test_admin_can_preserve_clear_or_fill_optional_product_fields(array $optionalFields, string $expectedBrand, string $expectedDescription): void
    {
        $product = Product::factory()->create([
            'brand' => 'Marca original',
            'description' => 'Descripción original',
        ]);

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), [
            'name' => 'Producto actualizado',
            'price' => 100,
            'tax_rate' => 16,
            'stock' => 2,
            ...$optionalFields,
        ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Producto actualizado',
            'brand' => $expectedBrand,
            'description' => $expectedDescription,
        ]);
    }

    public function test_product_slug_gets_a_suffix_when_name_is_repeated(): void
    {
        $category = Category::factory()->create();

        Product::factory()->for($category)->create([
            'name' => 'Casco de prueba',
            'slug' => 'casco-de-prueba',
            'sku' => 'CASCO-01',
            'barcode' => '7500000000001',
        ]);

        $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Casco de prueba',
            'brand' => 'LS2',
            'description' => 'Segundo casco con el mismo nombre.',
            'barcode' => '7500000000002',
            'barcode2' => 'CASCO-02',
            'price' => 1500,
            'tax_rate' => 16,
            'stock' => 4,
            'is_active' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'barcode2' => 'CASCO-02',
            'slug' => 'casco-de-prueba-2',
        ]);
    }

    public function test_admin_can_update_product_and_replace_image(): void
    {
        Storage::fake('public');

        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create([
            'name' => 'Producto original',
            'sku' => 'PROD-01',
            'barcode' => '7500000000003',
            'image_path' => 'products/old.jpg',
            'tax_rate' => 0.1600,
        ]);

        Storage::disk('public')->put('products/old.jpg', 'old-image');

        $response = $this->actingAs($this->admin())->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'name' => 'Producto actualizado',
            'brand' => 'Yamaha',
            'description' => 'Descripción actualizada del producto.',
            'barcode' => '7500000000004',
            'barcode2' => '00+(BARRA2)#',
            'price' => 1250,
            'tax_rate' => 8,
            'stock' => 7,
            'is_active' => '1',
            'image' => UploadedFile::fake()->createWithContent(
                'new.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
            ),
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertSame('7500000000004', $product->barcode);
        $this->assertSame('00+(BARRA2)#', $product->barcode2);
        $this->assertSame('PROD-01', $product->sku);
        $this->assertSame('0.0800', $product->tax_rate);
        $this->assertSame('producto-actualizado', $product->slug);
        $this->assertNotNull($product->image_path);
        $this->assertNotSame('products/old.jpg', $product->image_path);
        Storage::disk('public')->assertMissing('products/old.jpg');
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_barcode_must_be_unique(): void
    {
        $category = Category::factory()->create();

        Product::factory()->for($category)->create(['barcode' => '7500000000005']);

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'Producto duplicado',
                'brand' => 'Genérica',
                'description' => 'Producto con código repetido.',
                'barcode' => '7500000000005',
                'price' => 100,
                'tax_rate' => 16,
                'stock' => 1,
            ]);

        $response->assertRedirect(route('admin.products.create'));
        $response->assertSessionHasErrors('barcode');
    }

    public function test_second_barcode_must_be_unique_across_both_barcode_fields(): void
    {
        Product::factory()->create([
            'barcode' => 'PRIMARY-01',
            'barcode2' => 'SECONDARY-01',
        ]);

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'Producto duplicado',
                'brand' => 'Genérica',
                'description' => 'Producto con código secundario repetido.',
                'barcode' => 'PRIMARY-02',
                'barcode2' => 'SECONDARY-01',
                'price' => 100,
                'tax_rate' => 16,
                'stock' => 1,
            ]);

        $response->assertRedirect(route('admin.products.create'));
        $response->assertSessionHasErrors('barcode2');
    }

    public function test_primary_barcode_cannot_match_another_products_secondary_barcode(): void
    {
        Product::factory()->create(['barcode2' => 'SECONDARY-01']);

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'Producto duplicado',
                'brand' => 'Genérica',
                'description' => 'Producto con código principal repetido.',
                'barcode' => 'SECONDARY-01',
                'barcode2' => 'SECONDARY-02',
                'price' => 100,
                'tax_rate' => 16,
                'stock' => 1,
            ]);

        $response->assertRedirect(route('admin.products.create'));
        $response->assertSessionHasErrors('barcode');
    }

    public function test_product_barcode_fields_must_be_different(): void
    {
        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'Producto con códigos iguales',
                'brand' => 'Genérica',
                'description' => 'Los códigos no deben coincidir.',
                'barcode' => 'SAME-01',
                'barcode2' => 'SAME-01',
                'price' => 100,
                'tax_rate' => 16,
                'stock' => 1,
            ]);

        $response->assertRedirect(route('admin.products.create'));
        $response->assertSessionHasErrors('barcode2');
    }

    public function test_admin_product_search_finds_a_product_by_its_second_barcode(): void
    {
        $product = Product::factory()->create(['barcode2' => 'SECONDARY(03)+#']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.products.index', ['search' => 'SECONDARY(03)+#']));

        $response->assertOk();
        $this->assertSame([$product->id], $response->viewData('products')->getCollection()->modelKeys());
    }

    public function test_admin_can_render_product_create_and_edit_forms(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();

        $createResponse = $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('Nuevo producto')
            ->assertSee('Código de barras 2')
            ->assertSee('Tasa de IVA');

        $editResponse = $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Editar producto')
            ->assertSee($product->name);

        foreach ([$createResponse, $editResponse] as $response) {
            $response->assertSee('Marca (opcional)')->assertSee('Descripción (opcional)');
            $document = new DOMDocument;
            $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($document);

            foreach (['brand', 'description'] as $field) {
                $input = $xpath->query('//*[@name="'.$field.'"]')->item(0);

                $this->assertInstanceOf(DOMElement::class, $input);
                $this->assertFalse($input->hasAttribute('required'));
            }
        }
    }

    public function test_employee_cannot_manage_products(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');

        $this->actingAs($employee)->get(route('admin.products.create'))->assertForbidden();
    }

    /**
     * @param  list<string>  $expectedNames
     */
    #[TestWith(['bajo', ['B Minimo propio', 'C Fallback', 'H Bajo']])]
    #[TestWith(['agotado', ['A Agotado']])]
    public function test_stock_filters_use_active_tracked_products_and_their_minimum(string $stockFilter, array $expectedNames): void
    {
        Product::factory()->count(10)->sequence(
            ['name' => 'A Agotado', 'stock' => 0, 'min_stock' => 0],
            ['name' => 'B Minimo propio', 'stock' => 8, 'min_stock' => 8],
            ['name' => 'C Fallback', 'stock' => 5, 'min_stock' => 0],
            ['name' => 'D Minimo menor', 'stock' => 3, 'min_stock' => 2],
            ['name' => 'E Sano', 'stock' => 6, 'min_stock' => 0],
            ['name' => 'F Sin control agotado', 'stock' => 0, 'track_inventory' => false],
            ['name' => 'G Inactivo agotado', 'stock' => 0, 'is_active' => false],
            ['name' => 'H Bajo', 'stock' => 2, 'min_stock' => 3],
            ['name' => 'I Sin control bajo', 'stock' => 1, 'min_stock' => 5, 'track_inventory' => false],
            ['name' => 'J Inactivo bajo', 'stock' => 1, 'min_stock' => 5, 'is_active' => false],
        )->create();

        $response = $this->actingAs($this->admin())->get(route('admin.products.index', ['stock' => $stockFilter]));

        $response->assertOk();
        $response->assertViewIs('admin.products.index');
        $response->assertViewHas('stockFilter', $stockFilter);
        $response->assertViewHas('reviewFilter', '');
        $this->assertSame($expectedNames, $response->viewData('products')->pluck('name')->all());
    }

    /**
     * @param  list<string>  $expectedNames
     */
    #[TestWith(['sin-foto', ['A Nula', 'B Vacia', 'C Espacios']])]
    #[TestWith(['sin-precio', ['D Cero', 'E Negativo']])]
    public function test_review_filters_only_return_active_products_with_the_requested_issue(string $reviewFilter, array $expectedNames): void
    {
        Product::factory()->count(7)->sequence(
            ['name' => 'A Nula', 'image_path' => null, 'price' => 10],
            ['name' => 'B Vacia', 'image_path' => '', 'price' => 10],
            ['name' => 'C Espacios', 'image_path' => '   ', 'price' => 10],
            ['name' => 'D Cero', 'image_path' => 'products/zero.jpg', 'price' => 0],
            ['name' => 'E Negativo', 'image_path' => 'products/negative.jpg', 'price' => -1],
            ['name' => 'F Valido', 'image_path' => 'products/valid.jpg', 'price' => 0.01],
            ['name' => 'G Inactivo', 'image_path' => null, 'price' => 0, 'is_active' => false],
        )->create();

        $response = $this->actingAs($this->admin())->get(route('admin.products.index', ['revision' => $reviewFilter]));

        $response->assertOk();
        $response->assertViewHas('stockFilter', '');
        $response->assertViewHas('reviewFilter', $reviewFilter);
        $this->assertSame($expectedNames, $response->viewData('products')->pluck('name')->all());
    }

    #[TestWith(['bajo', '', 2])]
    #[TestWith(['agotado', '', 0])]
    #[TestWith(['', 'sin-foto', 2])]
    #[TestWith(['', 'sin-precio', 2])]
    #[TestWith(['bajo', 'sin-foto', 2])]
    #[TestWith(['agotado', 'sin-precio', 0])]
    public function test_catalog_filters_combine_with_search_and_preserve_pagination(string $stockFilter, string $reviewFilter, int $stock): void
    {
        $category = Category::factory()->create();
        $products = Product::factory()->for($category)->count(16)->sequence(
            fn (Sequence $sequence): array => ['name' => sprintf('Filtro pieza %02d', $sequence->index)],
        )->create([
            'stock' => $stock,
            'min_stock' => 5,
            'image_path' => null,
            'price' => 0,
        ]);
        Product::factory()->for($category)->create([
            'name' => 'Filtro inactivo', 'stock' => $stock, 'min_stock' => 5,
            'image_path' => null, 'price' => 0, 'is_active' => false,
        ]);
        Product::factory()->for($category)->create([
            'name' => 'Otro articulo', 'brand' => 'Otra marca', 'sku' => 'OTRO-001', 'barcode' => '7500000099991',
            'stock' => $stock, 'min_stock' => 5, 'image_path' => null, 'price' => 0,
        ]);
        $filters = [
            'stock' => $stockFilter,
            'revision' => $reviewFilter,
            'search' => 'Filtro',
        ];
        $this->actingAs($this->admin());

        $response = $this->get(route('admin.products.index', $filters));

        $response->assertOk();
        $response->assertViewHas('stockFilter', $stockFilter);
        $response->assertViewHas('reviewFilter', $reviewFilter);
        $response->assertViewHas('search', 'Filtro');
        $paginator = $response->viewData('products');
        $this->assertSame(16, $paginator->total());
        $this->assertSame($products->take(15)->modelKeys(), $paginator->getCollection()->modelKeys());
        parse_str(parse_url($paginator->nextPageUrl(), PHP_URL_QUERY), $nextPageQuery);
        foreach ($filters as $key => $value) {
            $this->assertSame($value, $nextPageQuery[$key] ?? '');
        }
        $this->assertSame('2', $nextPageQuery['page']);

        $nextPage = $this->get($paginator->nextPageUrl());

        $nextPage->assertOk();
        $this->assertSame([$products->last()->id], $nextPage->viewData('products')->getCollection()->modelKeys());
    }

    public function test_stock_and_review_filters_both_have_to_match(): void
    {
        $matching = Product::factory()->create(['stock' => 2, 'min_stock' => 5, 'image_path' => null]);
        Product::factory()->create(['stock' => 2, 'min_stock' => 5, 'image_path' => 'products/photo.jpg']);
        Product::factory()->create(['stock' => 20, 'min_stock' => 5, 'image_path' => null]);
        Product::factory()->create(['stock' => 2, 'min_stock' => 5, 'image_path' => null, 'track_inventory' => false]);

        $response = $this->actingAs($this->admin())->get(route('admin.products.index', [
            'stock' => 'bajo',
            'revision' => 'sin-foto',
        ]));

        $response->assertOk();
        $this->assertSame([$matching->id], $response->viewData('products')->getCollection()->modelKeys());
    }

    #[TestWith(['admin'])]
    #[TestWith(['empleado'])]
    public function test_clearing_inventory_deletes_all_products_and_preserves_other_records(string $role): void
    {
        $this->travelTo('2026-11-20 10:00:00');
        $operator = User::factory()->create()->assignRole($role);
        $operator->givePermissionTo(['admin.products.manage', 'pos.access', 'pos.tickets.print.own']);
        $product = Product::factory()->create(['name' => 'Producto con historial', 'stock' => 6, 'price' => 100]);
        Product::factory()->create(['stock' => 3, 'track_inventory' => false]);
        Product::factory()->create(['stock' => 2, 'is_active' => false]);
        Product::factory()->outOfStock()->create();
        app(SaleService::class)->create($operator, [
            'idempotency_key' => 'inventory-history',
            'items' => [['is_custom' => true, 'custom_name' => 'Servicio de prueba', 'custom_price' => 100, 'quantity' => 1]],
        ]);
        $sale = app(SaleService::class)->create($operator, [
            'idempotency_key' => 'inventory-paid-history',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);
        $saleItem = $sale->items->sole();
        $return = app(ReturnService::class)->request($operator, [
            'sale_id' => $sale->id,
            'reason' => 'Devolución antes de limpiar',
            'items' => [['sale_item_id' => $saleItem->id, 'quantity' => 1]],
        ]);
        app(ReturnService::class)->approve($return, $operator);
        AuditLog::create([
            'user_id' => $operator->id,
            'action' => AuditLog::ACTION_RETURN_SALE,
            'entity_type' => 'sale_return',
            'entity_id' => $return->id,
            'after' => ['status' => 'approved'],
        ]);
        app(DailyCutService::class)->generate(today(), $operator);
        $cutBefore = app(DailyCutService::class)->build(today());
        $ticketBefore = $this->actingAs($operator)->get(route('pos.sales.ticket', $sale))
            ->assertOk()->assertSee($product->name)->getContent();
        $history = [];
        foreach (['categories', 'sales', 'cash_movements', 'sale_returns', 'sale_return_items', 'daily_cuts', 'audit_logs'] as $table) {
            $history[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }
        $itemsBefore = DB::table('sale_items')->orderBy('id')->get()->map(function (object $item): object {
            $item->product_id = null;

            return $item;
        })->toJson();
        $movementsBefore = StockMovement::query()->orderBy('id')->get();
        $this->assertSame('paid', $sale->status);
        $this->assertDatabaseHas('sale_returns', ['id' => $return->id, 'status' => 'approved']);
        $this->assertCount(2, $movementsBefore);
        $this->assertSame(300.0, $cutBefore['sales']['total']);
        $this->assertSame(100.0, $cutBefore['returns']['total']);
        $this->travel(1)->minutes();

        $this->actingAs($operator)->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Se eliminaron 4 productos del inventario.');

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('stock_movements', 2);
        foreach ($history as $table => $rows) {
            $this->assertSame($rows, DB::table($table)->orderBy('id')->get()->toJson(), $table);
        }
        $this->assertSame($itemsBefore, DB::table('sale_items')->orderBy('id')->get()->toJson());
        foreach ($movementsBefore as $movement) {
            $after = $movement->fresh();
            $this->assertSame($product->only(['id', 'name', 'sku', 'barcode', 'barcode2']), $after->product_snapshot);
            $this->assertSame(
                [...$movement->getAttributes(), 'product_id' => null],
                [...$after->getAttributes(), 'product_snapshot' => null],
                'Only the product reference and snapshot may change.',
            );
        }
        $cutAfter = app(DailyCutService::class)->build(today());
        unset($cutBefore['generated_at'], $cutAfter['generated_at']);
        $this->assertSame($cutBefore, $cutAfter);
        $this->get(route('pos.sales.ticket', $sale))->assertOk()->assertContent($ticketBefore);
        $this->getJson(route('pos.products.search', ['q' => $product->barcode]))
            ->assertOk()->assertExactJson(['products' => []]);
        $this->getJson(route('pos.products.frequent'))->assertOk()->assertExactJson(['products' => []]);
        $index = $this->get(route('admin.products.index'))->assertOk();
        $this->assertSame(0, $index->viewData('products')->total());
    }

    public function test_clearing_already_empty_inventory_succeeds_without_creating_movements(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Se eliminaron 0 productos del inventario.');

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_clearing_inventory_preserves_adjustments_created_by_the_old_clear_button(): void
    {
        $this->freezeTime();
        $admin = $this->admin();
        $products = Product::factory()->count(2)->outOfStock()->create();
        $movements = [];
        foreach ($products as $product) {
            $movements[] = StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $admin->id,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => -5,
                'stock_before' => 5,
                'stock_after' => 0,
                'note' => 'Limpieza de inventario: existencias puestas en cero.',
            ])->fresh();
        }
        $this->travel(1)->hours();

        $this->actingAs($admin)->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'Se eliminaron 2 productos del inventario.');

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('stock_movements', 2);
        foreach ($movements as $index => $movement) {
            $after = $movement->fresh();
            $expectedSnapshot = $products[$index]->only(['id', 'name', 'sku', 'barcode', 'barcode2']);
            $this->assertSame($expectedSnapshot, $after->product_snapshot);
            $this->assertSame(
                [...$movement->getAttributes(), 'product_id' => null],
                [...$after->getAttributes(), 'product_snapshot' => null],
            );
        }
    }

    #[TestWith(['Movimiento que debe conservarse'])]
    #[TestWith(['Limpieza de inventario: existencias puestas en cero.'])]
    public function test_clearing_inventory_keeps_every_product_when_a_foreign_key_blocks_deletion(string $note): void
    {
        $admin = $this->admin();
        $this->freezeTime();
        $products = Product::factory()->for(Category::factory()->create())->count(201)
            ->sequence(['stock' => 0], ['stock' => 7])->create();
        StockMovement::create([
            'product_id' => $products->first()->id,
            'user_id' => $admin->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'quantity' => -4,
            'stock_before' => 4,
            'stock_after' => 0,
            'note' => 'Limpieza de inventario: existencias puestas en cero.',
        ]);
        StockMovement::create([
            'product_id' => $products->last()->id,
            'user_id' => $admin->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'quantity' => 7,
            'stock_before' => 0,
            'stock_after' => 7,
            'note' => $note,
        ]);
        $productsBefore = DB::table('products')->orderBy('id')->get()->toJson();
        $movementsBefore = DB::table('stock_movements')->orderBy('id')->get()->toJson();
        $firstMovementId = StockMovement::query()->min('id');
        $firstChunkDeleted = false;
        DB::listen(function (QueryExecuted $query) use (&$firstChunkDeleted, $products, $firstMovementId): void {
            if (str_starts_with($query->sql, 'delete from') && str_contains($query->sql, 'products')) {
                $firstChunkDeleted = ! DB::table('products')->where('id', $products->first()->id)->exists()
                    && DB::table('stock_movements')->where('id', $firstMovementId)->value('product_snapshot') !== null;
            }
        });
        Schema::create('inventory_clear_restrictions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
        });

        try {
            DB::table('inventory_clear_restrictions')->insert(['product_id' => $products->last()->id]);
            Exceptions::fake([QueryException::class]);
            $this->travel(1)->hours();

            $this->actingAs($admin)->post(route('admin.products.clearInventory'))
                ->assertRedirect(route('admin.products.index'))
                ->assertSessionMissing('status')
                ->assertSessionHas('error', 'No se pudo limpiar el inventario por una restricción de integridad. No se aplicó ningún cambio.');

            $this->assertTrue($firstChunkDeleted, 'The first chunk must have been deleted after saving its snapshots.');
            $this->assertSame($productsBefore, DB::table('products')->orderBy('id')->get()->toJson());
            $this->assertSame($movementsBefore, DB::table('stock_movements')->orderBy('id')->get()->toJson());
            Exceptions::assertReported(fn (QueryException $exception): bool => $exception->getCode() === '23000');
            $this->get(route('admin.products.index'))
                ->assertOk()
                ->assertSee('No se pudo limpiar el inventario por una restricción de integridad. No se aplicó ningún cambio.');
        } finally {
            Schema::dropIfExists('inventory_clear_restrictions');
        }

        $this->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'Se eliminaron 201 productos del inventario.');
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('stock_movements', 2);
        $movements = StockMovement::query()->orderBy('id')->get();
        foreach ([$products->first(), $products->last()] as $index => $product) {
            $this->assertSame($product->only(['id', 'name', 'sku', 'barcode', 'barcode2']), $movements[$index]->product_snapshot);
            $this->assertNull($movements[$index]->product_id);
        }
    }

    #[TestWith([null])]
    #[TestWith(['00(ALT)+#'])]
    public function test_clearing_inventory_preserves_exact_product_identity_and_does_not_attach_history_to_reused_codes(?string $secondBarcode): void
    {
        $this->freezeTime();
        $admin = $this->admin();
        $product = Product::factory()->create([
            'name' => 'Kit d\'arrastre "Premium" + #',
            'sku' => '000(REF)+#',
            'barcode' => '00012345',
            'barcode2' => $secondBarcode,
        ]);
        $movement = StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'quantity' => 3,
            'stock_before' => 2,
            'stock_after' => 5,
            'note' => 'Ajuste manual "original"',
        ])->fresh();
        $before = $movement->getAttributes();
        $this->travel(2)->hours();

        $this->actingAs($admin)->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'Se eliminaron 1 productos del inventario.');

        $after = $movement->fresh();
        $this->assertSame([
            'id' => $product->id,
            'name' => 'Kit d\'arrastre "Premium" + #',
            'sku' => '000(REF)+#',
            'barcode' => '00012345',
            'barcode2' => $secondBarcode,
        ], $after->product_snapshot);
        $this->assertSame([...$before, 'product_id' => null], [...$after->getAttributes(), 'product_snapshot' => null]);
        $this->assertNull($after->product);
        $history = DB::table('stock_movements')->orderBy('id')->get()->toJson();

        $this->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'Se eliminaron 0 productos del inventario.');
        $this->assertSame($history, DB::table('stock_movements')->orderBy('id')->get()->toJson());

        $this->post(route('admin.products.import'), [
            'import_file' => UploadedFile::fake()->createWithContent(
                'productos.csv',
                "sku,nombre,precio_venta,stock,codigo_barras,codigo_barras_2\n000(REF)+#,Nuevo producto,100,2,00012345,{$secondBarcode}",
            ),
        ])->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $replacement = Product::query()->sole();
        $this->assertGreaterThan($product->id, $replacement->id);
        $this->assertSame('000(REF)+#', $replacement->sku);
        $this->assertSame('00012345', $replacement->barcode);
        $this->assertSame($secondBarcode, $replacement->barcode2);
        $this->assertSame(0, $replacement->stockMovements()->count());
        $this->assertSame($history, DB::table('stock_movements')->orderBy('id')->get()->toJson());
    }

    /**
     * @param  array<string, int|string|null>  $snapshot
     */
    #[TestWith([[]])]
    #[TestWith([['id' => 99, 'name' => 'Nombre anterior', 'sku' => '000-OLD', 'barcode' => '00+#', 'barcode2' => null]])]
    public function test_clearing_inventory_does_not_overwrite_existing_or_detached_snapshots(array $snapshot): void
    {
        $this->freezeTime();
        $admin = $this->admin();
        $product = Product::factory()->create();
        $movement = StockMovement::create([
            'product_id' => $product->id,
            'product_snapshot' => $snapshot,
            'user_id' => $admin->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'quantity' => 1,
            'stock_before' => 0,
            'stock_after' => 1,
        ])->fresh();
        $detached = StockMovement::create([
            'product_id' => null,
            'product_snapshot' => ['id' => 100, 'name' => 'Producto ya eliminado'],
            'user_id' => $admin->id,
            'type' => StockMovement::TYPE_RETURN,
            'quantity' => 1,
            'stock_before' => 0,
            'stock_after' => 1,
        ])->fresh();
        $this->travel(1)->hours();

        $this->actingAs($admin)->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'Se eliminaron 1 productos del inventario.');

        $this->assertSame([...$movement->getAttributes(), 'product_id' => null], $movement->fresh()->getAttributes());
        $this->assertSame($snapshot, $movement->fresh()->product_snapshot);
        $this->assertSame($detached->getAttributes(), $detached->fresh()->getAttributes());
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_clearing_inventory_preserves_cards_families_publications_and_image_files_but_removes_product_links(): void
    {
        Storage::fake('public');
        $this->freezeTime();
        $product = Product::factory()->create(['image_path' => 'products/history.jpg']);
        Storage::disk('public')->put('products/history.jpg', 'product-image');
        Storage::disk('public')->put('home/cards/history.jpg', 'card-image');
        $card = HomeCard::factory()->create([
            'title' => 'Card conservada', 'product_id' => $product->id, 'image_path' => 'home/cards/history.jpg',
        ]);
        $family = HomeCard::factory()->category()->create(['title' => 'Familia conservada']);
        $family->linkedProducts()->attach($product, ['sort_order' => 0]);
        DB::table('home_publications')->insert([
            'title' => 'Publicación conservada', 'body' => 'Contenido original',
            'product_id' => $product->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $cardsBefore = DB::table('home_cards')->orderBy('id')->get()->map(function (object $card): object {
            $card->product_id = null;

            return $card;
        })->toJson();
        $publicationsBefore = DB::table('home_publications')->orderBy('id')->get()->map(function (object $publication): object {
            $publication->product_id = null;

            return $publication;
        })->toJson();
        $categoriesBefore = DB::table('categories')->orderBy('id')->get()->toJson();
        $this->travel(1)->hours();

        $this->actingAs($this->admin())->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'Se eliminaron 1 productos del inventario.');

        $this->assertDatabaseCount('products', 0);
        $this->assertSame($cardsBefore, DB::table('home_cards')->orderBy('id')->get()->toJson());
        $this->assertSame($publicationsBefore, DB::table('home_publications')->orderBy('id')->get()->toJson());
        $this->assertSame($categoriesBefore, DB::table('categories')->orderBy('id')->get()->toJson());
        $this->assertDatabaseCount('home_card_product', 0);
        $this->assertNull($card->fresh()->product);
        $this->assertSame(0, $family->linkedProducts()->count());
        Storage::disk('public')->assertExists(['products/history.jpg', 'home/cards/history.jpg']);
        $this->get(route('home'))->assertOk()->assertSee('Card conservada')->assertSee('Familia conservada');
    }

    public function test_clearing_inventory_rolls_back_and_reports_unexpected_errors_instead_of_showing_integrity_message(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create();
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'quantity' => 1,
            'stock_before' => 0,
            'stock_after' => 1,
        ]);
        $productsBefore = DB::table('products')->orderBy('id')->get()->toJson();
        $movementsBefore = DB::table('stock_movements')->orderBy('id')->get()->toJson();
        DB::listen(function (QueryExecuted $query): void {
            if (str_starts_with($query->sql, 'delete from') && str_contains($query->sql, 'products')) {
                DB::table('inventory_clear_nonexistent_table')->get();
            }
        });
        Exceptions::fake([QueryException::class]);

        $this->actingAs($admin)->post(route('admin.products.clearInventory'))
            ->assertInternalServerError()->assertSessionMissing('error')->assertSessionMissing('status');

        Exceptions::assertReported(fn (QueryException $exception): bool => $exception->getCode() !== '23000');
        $this->assertSame($productsBefore, DB::table('products')->orderBy('id')->get()->toJson());
        $this->assertSame($movementsBefore, DB::table('stock_movements')->orderBy('id')->get()->toJson());
    }

    public function test_guest_cannot_clear_inventory(): void
    {
        $product = Product::factory()->create();

        $this->post(route('admin.products.clearInventory'))->assertRedirect(route('login'));

        $this->assertModelExists($product);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_clear_inventory_form_preserves_post_csrf_and_cancelable_confirmation(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.products.index'))->assertOk();
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $form = $xpath->query('//form[@id="clear-inventory-form"]')->item(0);

        $this->assertInstanceOf(DOMElement::class, $form);
        $this->assertSame(route('admin.products.clearInventory'), $form->getAttribute('action'));
        $this->assertSame('POST', $form->getAttribute('method'));
        $token = $xpath->query('.//input[@name="_token"]', $form)->item(0);
        $this->assertInstanceOf(DOMElement::class, $token);
        $this->assertSame(csrf_token(), $token->getAttribute('value'));
        $button = $xpath->query('//button[contains(@onclick, "clear-inventory-form")]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $button);
        $this->assertSame('button', $button->getAttribute('type'));
        $confirmation = $button->getAttribute('onclick');
        $this->assertStringContainsString('if(confirm(', $confirmation);
        $this->assertStringContainsString("document.getElementById('clear-inventory-form').submit()", $confirmation);
        foreach (['TODOS los productos', 'movimientos históricos', 'desvincularán de la web', 'no se puede deshacer', 'respaldo recuperable'] as $warning) {
            $this->assertStringContainsString($warning, $confirmation);
        }
    }

    public function test_stock_history_migration_upgrades_an_existing_movement_without_changing_its_data(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $admin = $this->admin();
        $product = Product::factory()->create();
        $migration = require database_path('migrations/2026_11_20_114507_preserve_stock_movement_history_when_products_are_deleted.php');
        $migration->down();

        try {
            $movement = StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $admin->id,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => 1,
                'stock_before' => 0,
                'stock_after' => 1,
                'note' => 'Fila anterior a la migración',
            ])->fresh();
            $before = $movement->getAttributes();
        } finally {
            $migration->up();
        }

        $this->assertSame([...$before, 'product_snapshot' => null], $movement->fresh()->getAttributes());
        $this->actingAs($admin)->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'Se eliminaron 1 productos del inventario.');
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertSame($product->only(['id', 'name', 'sku', 'barcode', 'barcode2']), $movement->fresh()->product_snapshot);
        $this->assertSame([...$before, 'product_id' => null, 'product_snapshot' => null], [...$movement->fresh()->getAttributes(), 'product_snapshot' => null]);
    }

    #[TestWith([true, false])]
    #[TestWith([false, true])]
    public function test_stock_history_migration_refuses_rollback_when_it_would_lose_references_or_snapshots(bool $detached, bool $hasSnapshot): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create();
        $movement = StockMovement::create([
            'product_id' => $detached ? null : $product->id,
            'product_snapshot' => $hasSnapshot ? $product->only(['id', 'name', 'sku', 'barcode', 'barcode2']) : null,
            'user_id' => $admin->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'quantity' => 1,
            'stock_before' => 0,
            'stock_after' => 1,
        ])->fresh();
        $before = $movement->getAttributes();
        $migration = require database_path('migrations/2026_11_20_114507_preserve_stock_movement_history_when_products_are_deleted.php');

        $this->assertThrows(
            fn () => $migration->down(),
            fn (RuntimeException $exception): bool => $exception->getMessage() === 'No se puede revertir la migración porque se perderían referencias o identificación histórica de productos.',
        );

        $this->assertTrue(Schema::hasColumn('stock_movements', 'product_snapshot'));
        $this->assertSame($before, $movement->fresh()->getAttributes());
        $this->assertModelExists($product);
    }

    public function test_employee_without_product_permission_cannot_clear_inventory(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $product = Product::factory()->create(['stock' => 4]);

        $this->actingAs($employee)->post(route('admin.products.clearInventory'))->assertForbidden();

        $this->assertSame(4, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_product_delete_confirmation_preserves_names_with_quotes(): void
    {
        $product = Product::factory()->create(['name' => 'Kit d\'arrastre "Premium"']);

        $response = $this->actingAs($this->admin())->get(route('admin.products.index'));

        $response->assertOk();
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $form = (new DOMXPath($document))->query('//form[@action="'.route('admin.products.destroy', $product).'"]')->item(0);

        $this->assertInstanceOf(DOMElement::class, $form);
        $this->assertSame('return confirm(this.dataset.confirm)', $form->getAttribute('onsubmit'));
        $this->assertSame('¿Estás seguro de eliminar el producto "Kit d\'arrastre "Premium""? Esta acción no se puede deshacer.', $form->getAttribute('data-confirm'));
    }

    public function test_product_without_history_is_deleted_but_product_with_stock_movements_is_kept(): void
    {
        $admin = $this->admin();
        $removable = Product::factory()->create(['name' => 'Sin historial']);
        $withHistory = Product::factory()->create(['name' => 'Con historial']);
        StockMovement::create([
            'product_id' => $withHistory->id,
            'user_id' => $admin->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'quantity' => 1,
            'stock_before' => 0,
            'stock_after' => 1,
            'note' => 'Alta de existencias',
        ]);

        $this->actingAs($admin)->delete(route('admin.products.destroy', $removable))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status');

        $this->assertModelMissing($removable);

        $this->actingAs($admin)->delete(route('admin.products.destroy', $withHistory))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($withHistory);
        $this->assertDatabaseCount('products', 1);
    }

    #[TestWith(['sku'])]
    #[TestWith(['codigo'])]
    public function test_import_uses_original_codes_as_primary_barcodes(string $codeHeader): void
    {
        $csv = implode("\n", [
            $codeHeader.',nombre,marca,descripcion,precio_venta,stock,codigo_barras',
            '00012345,Producto uno,Marca,Descripción uno,10,2,',
            '00(ABC)+123,Producto dos,Marca,Descripción dos,20,3,',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.products.import'), [
                'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('products', ['sku' => '00012345', 'barcode' => '00012345', 'barcode2' => null, 'stock' => 2]);
        $this->assertDatabaseHas('products', ['sku' => '00(ABC)+123', 'barcode' => '00(ABC)+123', 'barcode2' => null, 'stock' => 3]);
    }

    #[TestWith([',', false])]
    #[TestWith([';', false])]
    #[TestWith([',', true])]
    #[TestWith([';', true])]
    public function test_import_accepts_excel_csv_delimiters_and_quoted_headers_with_bom(string $delimiter, bool $withBom): void
    {
        $csv = ($withBom ? "\xEF\xBB\xBF" : '').implode("\r\n", [
            implode($delimiter, ['"codigo"', '"nombre"', '"marca"', '"descripcion"', '"precio_venta"', '"stock"']),
            implode($delimiter, ['IMP-EXCEL', '"Bujía, estándar; especial"', 'NGK', '"Para motor, serie; A"', '85.50', '20']),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.products.import'), [
                'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Importación completada: 1 productos creados, 0 códigos principales completados, 0 omitidos (sin SKU o con código principal existente).');

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'sku' => 'IMP-EXCEL',
            'name' => 'Bujía, estándar; especial',
            'description' => 'Para motor, serie; A',
            'price' => 85.50,
            'stock' => 20,
        ]);
    }

    #[TestWith(["codigo,nombre,precio_venta,stock\nIMP-OPTIONAL,Producto,10,2"])]
    #[TestWith(["codigo,nombre,marca,descripcion,precio_venta,stock\nIMP-OPTIONAL,Producto,,,10,2"])]
    public function test_import_accepts_missing_or_empty_brand_and_description(string $csv): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.products.import'), [
                'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'sku' => 'IMP-OPTIONAL',
            'name' => 'Producto',
            'brand' => '',
            'description' => '',
            'price' => 10,
            'stock' => 2,
        ]);
    }

    public function test_import_displays_all_row_errors_without_saving_products(): void
    {
        $csv = "codigo,nombre,marca,descripcion,precio_venta,stock\nIMP-ERROR,,,,,-1";

        $this->actingAs($this->admin())
            ->from(route('admin.products.index'))
            ->post(route('admin.products.import'), [
                'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasErrors('import_file');

        $messages = session('errors')->get('import_file');
        $this->assertCount(3, $messages);
        $response = $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('No se importó ningún producto. Corrige el archivo y vuelve a seleccionarlo.');

        foreach ($messages as $message) {
            $this->assertStringContainsString('Fila 2', $message);
            $response->assertSee($message);
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_import_displays_missing_file_error_and_requires_file_selection(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.products.index'))
            ->post(route('admin.products.import'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasErrors(['import_file' => 'Selecciona un archivo CSV para importar.']);

        $response = $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Selecciona un archivo CSV para importar.');
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $input = (new DOMXPath($document))->query('//input[@name="import_file"]')->item(0);

        $this->assertInstanceOf(DOMElement::class, $input);
        $this->assertTrue($input->hasAttribute('required'));
        $this->assertSame('true', $input->getAttribute('aria-invalid'));
        $this->assertDatabaseCount('products', 0);
    }

    public function test_import_does_not_carry_values_from_the_previous_row(): void
    {
        $csv = implode("\n", [
            'codigo,nombre,marca,descripcion,precio_venta,stock,codigo_barras,iva',
            'IMP-FIRST,Primero,NGK,Descripción primero,85.50,20,BAR-FIRST,8',
            'IMP-NEXT,Segundo,,,10,2,,',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.products.import'), [
                'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('products', [
            'sku' => 'IMP-FIRST',
            'barcode' => 'BAR-FIRST',
            'tax_rate' => 0.08,
        ]);
        $this->assertDatabaseHas('products', [
            'sku' => 'IMP-NEXT',
            'name' => 'Segundo',
            'brand' => '',
            'description' => '',
            'barcode' => 'IMP-NEXT',
            'price' => 10,
            'stock' => 2,
            'tax_rate' => 0.16,
        ]);
    }

    public function test_import_saves_nothing_when_a_later_row_is_invalid(): void
    {
        $csv = implode("\n", [
            'sku,nombre,marca,descripcion,precio_venta,stock',
            'IMP-001,Producto uno,Marca,Descripción uno,10,2',
            'IMP-002,Producto dos,Marca,Descripción dos,,3',
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.products.index'))
            ->post(route('admin.products.import'), [
                'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasErrors('import_file');

        $this->assertDatabaseCount('products', 0);
        $this->assertStringContainsString('Fila 3', session('errors')->first('import_file'));
    }

    public function test_import_reports_repeated_codes_without_a_database_exception(): void
    {
        $csv = implode("\n", [
            'sku,nombre,marca,descripcion,precio_venta,stock,codigo_barras',
            'IMP-001,Producto uno,Marca,Descripción uno,10,2,DUP-001',
            'IMP-002,Producto dos,Marca,Descripción dos,20,3,DUP-001',
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.products.index'))
            ->post(route('admin.products.import'), [
                'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
            ])
            ->assertSessionHasErrors('import_file');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_import_rejects_excel_files(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.products.index'))
            ->post(route('admin.products.import'), [
                'import_file' => UploadedFile::fake()->create(
                    'productos.xlsx',
                    10,
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ),
            ])
            ->assertSessionHasErrors('import_file');
    }

    public function test_import_requires_sku_and_name_headers(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.products.index'))
            ->post(route('admin.products.import'), [
                'import_file' => UploadedFile::fake()->createWithContent(
                    'productos.csv',
                    "nombre,marca,descripcion,precio_venta,stock\nProducto,Marca,Descripción,10,1",
                ),
            ])
            ->assertSessionHasErrors('import_file');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_import_keeps_skipping_rows_with_an_existing_sku(): void
    {
        $existing = Product::factory()->create(['sku' => 'IMP-001', 'barcode' => 'EXISTING', 'barcode2' => 'SECOND']);
        $before = $existing->fresh()->getAttributes();
        $csv = implode("\n", [
            'sku,nombre,marca,descripcion,precio_venta,stock,codigo_barras',
            'IMP-001,Producto uno,Marca,Descripción uno,10,2,DO-NOT-REPLACE',
            'IMP-002,Producto dos,Marca,Descripción dos,20,3,',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.products.import'), [
                'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('products', ['sku' => 'IMP-002']);
        $this->assertSame(1, Product::query()->where('sku', 'IMP-001')->count());
        $this->assertSame($before, $existing->fresh()->getAttributes());
    }

    public function test_import_prioritizes_explicit_barcodes_over_ambiguous_or_generated_sku(): void
    {
        $sku = 'AUTO-550e8400-e29b-41d4-a716-446655440000';
        $csv = "sku,codigo,nombre,precio_venta,codigo_barras,codigo_barras_2\n{$sku},00012345,Producto,10,00098765,ALT(02)+#";

        $this->actingAs($this->admin())->post(route('admin.products.import'), [
            'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
        ])->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'sku' => $sku,
            'barcode' => '00098765',
            'barcode2' => 'ALT(02)+#',
        ]);
    }

    #[TestWith(['sku,nombre,precio_venta', 'AUTO-550e8400-e29b-41d4-a716-446655440000,Producto,10'])]
    #[TestWith(['sku,codigo,nombre,precio_venta', 'INTERNO,00012345,Producto,10'])]
    public function test_import_requires_explicit_barcode_for_ambiguous_or_generated_sku(string $header, string $row): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.products.index'))
            ->post(route('admin.products.import'), [
                'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $header."\n".$row),
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasErrors('import_file');

        $this->assertStringContainsString('Fila 2: indica codigo_barras explícitamente', session('errors')->first('import_file'));
        $this->assertDatabaseCount('products', 0);
    }

    #[TestWith([null])]
    #[TestWith([''])]
    public function test_reimport_only_completes_empty_primary_barcode_and_is_idempotent(?string $previousBarcode): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create([
            'sku' => '00012345',
            'barcode' => $previousBarcode,
            'barcode2' => 'MANUAL-SECOND',
        ]);
        $before = $product->fresh()->getAttributes();
        $csv = "codigo,nombre,precio_venta,stock,codigo_barras_2\n00012345,NO CAMBIAR,-1,-1,NO-REEMPLAZAR";

        $this->actingAs($admin)->post(route('admin.products.import'), [
            'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
        ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Importación completada: 0 productos creados, 1 códigos principales completados, 0 omitidos (sin SKU o con código principal existente).');

        $this->assertDatabaseCount('products', 1);
        $after = $product->fresh()->getAttributes();
        $this->assertSame('00012345', $after['barcode']);
        $unchanged = $after;
        unset($before['barcode'], $before['updated_at'], $unchanged['barcode'], $unchanged['updated_at']);
        $this->assertSame($before, $unchanged);
        $log = AuditLog::query()->where('action', 'completar_codigo_barras')->firstOrFail();
        $this->assertSame($product->id, $log->entity_id);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(['barcode' => $previousBarcode], $log->before);
        $this->assertSame(['barcode' => '00012345'], $log->after);

        $this->post(route('admin.products.import'), [
            'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
        ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Importación completada: 0 productos creados, 0 códigos principales completados, 1 omitidos (sin SKU o con código principal existente).');

        $this->assertSame($after, $product->fresh()->getAttributes());
        $this->assertSame(1, AuditLog::query()->where('action', 'completar_codigo_barras')->count());
    }

    #[TestWith(['barcode'])]
    #[TestWith(['barcode2'])]
    #[TestWith(['same_product_barcode2'])]
    public function test_reimport_rolls_back_creations_repairs_and_audit_on_barcode_conflict(string $conflictField): void
    {
        Product::factory()->create(['sku' => 'REPAIR-FIRST', 'barcode' => null, 'barcode2' => null]);
        Product::factory()->create([
            'sku' => 'REPAIR-CONFLICT',
            'barcode' => null,
            'barcode2' => $conflictField === 'same_product_barcode2' ? 'DUPLICATE' : null,
        ]);

        if ($conflictField !== 'same_product_barcode2') {
            Product::factory()->create([
                'barcode' => $conflictField === 'barcode' ? 'DUPLICATE' : null,
                'barcode2' => $conflictField === 'barcode2' ? 'DUPLICATE' : null,
            ]);
        }

        $before = DB::table('products')->orderBy('id')->get()->toJson();
        $csv = implode("\n", [
            'codigo,nombre,precio_venta,codigo_barras',
            'NEW-ONE,Nuevo,10,',
            'REPAIR-FIRST,No cambiar,10,',
            'REPAIR-CONFLICT,No cambiar,10,DUPLICATE',
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.products.index'))
            ->post(route('admin.products.import'), [
                'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasErrors('import_file');

        $this->assertStringContainsString('Fila 4', session('errors')->first('import_file'));
        $this->assertStringContainsString('El código principal ya está registrado', session('errors')->first('import_file'));
        $this->assertSame($before, DB::table('products')->orderBy('id')->get()->toJson());
        $this->assertSame(0, AuditLog::query()->where('action', 'completar_codigo_barras')->count());
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }
}
