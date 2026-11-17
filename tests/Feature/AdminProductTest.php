<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\DailyCutService;
use App\Services\ReturnService;
use App\Services\SaleService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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

        $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('Nuevo producto')
            ->assertSee('Código de barras 2')
            ->assertSee('Tasa de IVA');

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Editar producto')
            ->assertSee($product->name);
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
    public function test_clearing_inventory_zeros_stock_and_preserves_catalog_and_history(string $role): void
    {
        $this->freezeTime();
        $operator = User::factory()->create()->assignRole($role);
        $operator->givePermissionTo('admin.products.manage');
        $product = Product::factory()->create(['stock' => 6, 'price' => 100]);
        $untracked = Product::factory()->create(['stock' => 3, 'track_inventory' => false]);
        $inactive = Product::factory()->create(['stock' => 2, 'is_active' => false]);
        $empty = Product::factory()->outOfStock()->create();
        $sale = app(SaleService::class)->create($operator, [
            'idempotency_key' => 'inventory-history',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
        app(ReturnService::class)->request($operator, [
            'sale_id' => $sale->id,
            'reason' => 'Solicitud anterior a la limpieza',
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]],
        ]);
        app(DailyCutService::class)->generate(today(), $operator);
        $productBefore = $product->fresh()->only([
            'id', 'name', 'sku', 'barcode', 'barcode2', 'category_id', 'slug', 'price',
            'cost', 'wholesale_price', 'tax_rate', 'image_path', 'track_inventory', 'is_active',
        ]);
        $stockMovement = StockMovement::query()->firstOrFail();
        $stockMovementBefore = $stockMovement->getAttributes();
        $history = [];
        foreach (['sales', 'sale_items', 'cash_movements', 'sale_returns', 'sale_return_items', 'daily_cuts', 'audit_logs'] as $table) {
            $history[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }

        $this->actingAs($operator)->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Todas las existencias se pusieron en cero. El catálogo y el historial se conservaron.');

        $this->assertDatabaseCount('products', 4);
        foreach ([$product, $untracked, $inactive, $empty] as $item) {
            $this->assertSame(0, $item->fresh()->stock);
        }
        $this->assertSame($productBefore, $product->fresh()->only(array_keys($productBefore)));
        $this->assertSame($stockMovementBefore, $stockMovement->fresh()->getAttributes());
        foreach ($history as $table => $rows) {
            $this->assertSame($rows, DB::table($table)->orderBy('id')->get()->toJson(), $table);
        }
        $this->assertSame(3, StockMovement::query()->where('type', StockMovement::TYPE_ADJUSTMENT)->count());
        foreach ([[$product, 5], [$untracked, 3], [$inactive, 2]] as [$item, $stockBefore]) {
            $this->assertDatabaseHas('stock_movements', [
                'product_id' => $item->id,
                'user_id' => $operator->id,
                'sale_id' => null,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => -$stockBefore,
                'stock_before' => $stockBefore,
                'stock_after' => 0,
            ]);
        }
    }

    public function test_clearing_already_empty_inventory_does_not_add_adjustments(): void
    {
        $product = Product::factory()->outOfStock()->create();

        $this->actingAs($this->admin())->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertModelExists($product);
        $this->assertSame(0, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_clearing_inventory_rolls_back_every_product_when_an_adjustment_fails(): void
    {
        $products = Product::factory()->count(2)->sequence(['stock' => 4], ['stock' => 7])->create();
        $productsBefore = DB::table('products')->orderBy('id')->get()->toJson();
        Event::listen('eloquent.creating: '.StockMovement::class, function (StockMovement $movement) use ($products): void {
            if ($movement->product_id === $products->last()->id) {
                throw new RuntimeException('Simulated adjustment failure');
            }
        });

        $this->actingAs($this->admin())->post(route('admin.products.clearInventory'))->assertServerError();

        $this->assertSame($productsBefore, DB::table('products')->orderBy('id')->get()->toJson());
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_employee_without_product_permission_cannot_clear_inventory(): void
    {
        $employee = User::factory()->create()->assignRole('empleado');
        $product = Product::factory()->create(['stock' => 4]);

        $this->actingAs($employee)->post(route('admin.products.clearInventory'))->assertForbidden();

        $this->assertSame(4, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
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

    public function test_import_creates_rows_without_barcode_as_null(): void
    {
        $csv = implode("\n", [
            'sku,nombre,marca,descripcion,precio_venta,stock,codigo_barras',
            'IMP-001,Producto uno,Marca,Descripción uno,10,2,',
            'IMP-002,Producto dos,Marca,Descripción dos,20,3,',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.products.import'), [
                'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('products', ['sku' => 'IMP-001', 'barcode' => null, 'stock' => 2]);
        $this->assertDatabaseHas('products', ['sku' => 'IMP-002', 'barcode' => null, 'stock' => 3]);
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
        Product::factory()->create(['sku' => 'IMP-001']);
        $csv = implode("\n", [
            'sku,nombre,marca,descripcion,precio_venta,stock',
            'IMP-001,Producto uno,Marca,Descripción uno,10,2',
            'IMP-002,Producto dos,Marca,Descripción dos,20,3',
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
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }
}
