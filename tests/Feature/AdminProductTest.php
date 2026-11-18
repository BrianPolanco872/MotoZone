<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\DailyCutService;
use App\Services\SaleService;
use Database\Seeders\RolePermissionSeeder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
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
        $this->freezeTime();
        $operator = User::factory()->create()->assignRole($role);
        $operator->givePermissionTo('admin.products.manage');
        Product::factory()->create(['stock' => 6, 'price' => 100]);
        Product::factory()->create(['stock' => 3, 'track_inventory' => false]);
        Product::factory()->create(['stock' => 2, 'is_active' => false]);
        Product::factory()->outOfStock()->create();
        app(SaleService::class)->create($operator, [
            'idempotency_key' => 'inventory-history',
            'items' => [['is_custom' => true, 'custom_name' => 'Servicio de prueba', 'custom_price' => 100, 'quantity' => 1]],
        ]);
        app(DailyCutService::class)->generate(today(), $operator);
        $history = [];
        foreach (['categories', 'sales', 'sale_items', 'cash_movements', 'sale_returns', 'sale_return_items', 'daily_cuts', 'audit_logs'] as $table) {
            $history[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }

        $this->actingAs($operator)->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Se eliminaron 4 productos del inventario.');

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        foreach ($history as $table => $rows) {
            $this->assertSame($rows, DB::table($table)->orderBy('id')->get()->toJson(), $table);
        }
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

    public function test_clearing_inventory_removes_adjustments_created_by_the_old_clear_button(): void
    {
        $admin = $this->admin();
        $products = Product::factory()->count(2)->outOfStock()->create();
        foreach ($products as $product) {
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $admin->id,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => -5,
                'stock_before' => 5,
                'stock_after' => 0,
                'note' => 'Limpieza de inventario: existencias puestas en cero.',
            ]);
        }

        $this->actingAs($admin)->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'Se eliminaron 2 productos del inventario.');

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    #[TestWith(['Movimiento que debe conservarse'])]
    #[TestWith(['Limpieza de inventario: existencias puestas en cero.'])]
    public function test_clearing_inventory_keeps_every_product_when_a_foreign_key_blocks_deletion(string $note): void
    {
        $admin = $this->admin();
        $products = Product::factory()->count(2)->sequence(['stock' => 0], ['stock' => 7])->create();
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

        $this->actingAs($admin)->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('error', 'No se eliminó ningún producto porque existen movimientos asociados.');

        $this->assertSame($productsBefore, DB::table('products')->orderBy('id')->get()->toJson());
        $this->assertSame($movementsBefore, DB::table('stock_movements')->orderBy('id')->get()->toJson());
        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('No se eliminó ningún producto porque existen movimientos asociados.');
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
