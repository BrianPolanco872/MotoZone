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
use ZipArchive;

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
        $this->assertSame(Product::MODE_UNIT, $product->saleMode());
        $this->assertFalse($product->kit_deduct_components);
        $this->assertSame(0, $product->kitItems()->count());
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

    /**
     * @param  array{cost?: string|null, min_stock?: int|string|null}  $optionalFields
     */
    #[TestWith([[], '0.00', 0])]
    #[TestWith([['cost' => '', 'min_stock' => ''], '0.00', 0])]
    #[TestWith([['cost' => null, 'min_stock' => null], '0.00', 0])]
    #[TestWith([['cost' => '7.50', 'min_stock' => 3], '7.50', 3])]
    #[TestWith([['cost' => '0', 'min_stock' => '0'], '0.00', 0])]
    public function test_admin_can_create_product_with_optional_cost_and_minimum_stock_without_changing_sale_price(array $optionalFields, string $expectedCost, int $expectedMinimumStock): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'name' => 'Producto con precio directo',
            'barcode' => '00012345',
            'barcode2' => 'ALT(02)+#',
            'price' => '10.00',
            'wholesale_price' => '',
            'tax_rate' => 16,
            'stock' => 2,
            ...$optionalFields,
        ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $product = Product::query()->sole();

        $this->assertSame($expectedCost, $product->cost);
        $this->assertSame($expectedMinimumStock, $product->min_stock);
        $this->assertSame('10.00', $product->price);
        $this->assertSame('0.1600', $product->tax_rate);
        $this->assertSame('00012345', $product->barcode);
        $this->assertSame('ALT(02)+#', $product->barcode2);
        $this->assertNull($product->wholesale_price);
    }

    /**
     * @param  array{cost?: string|null, min_stock?: int|string|null}  $optionalFields
     */
    #[TestWith([[], '7.50', 3])]
    #[TestWith([['cost' => '', 'min_stock' => ''], '0.00', 0])]
    #[TestWith([['cost' => null, 'min_stock' => null], '0.00', 0])]
    #[TestWith([['cost' => '12.25', 'min_stock' => 5], '12.25', 5])]
    #[TestWith([['cost' => '0', 'min_stock' => '0'], '0.00', 0])]
    #[TestWith([['cost' => ''], '0.00', 3])]
    #[TestWith([['min_stock' => ''], '7.50', 0])]
    public function test_admin_can_preserve_clear_or_fill_cost_and_minimum_stock_without_changing_sale_price(array $optionalFields, string $expectedCost, int $expectedMinimumStock): void
    {
        $product = Product::factory()->create([
            'sku' => 'PROD-01',
            'barcode' => '00012345',
            'barcode2' => 'ALT(02)+#',
            'cost' => '7.50',
            'min_stock' => 3,
            'wholesale_price' => '9.00',
        ]);

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), [
            'name' => 'Producto con precio directo',
            'price' => '10.00',
            'wholesale_price' => '',
            'tax_rate' => 16,
            'stock' => 2,
            ...$optionalFields,
        ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $product->refresh();

        $this->assertSame($expectedCost, $product->cost);
        $this->assertSame($expectedMinimumStock, $product->min_stock);
        $this->assertSame('10.00', $product->price);
        $this->assertSame('0.1600', $product->tax_rate);
        $this->assertSame('PROD-01', $product->sku);
        $this->assertSame('00012345', $product->barcode);
        $this->assertSame('ALT(02)+#', $product->barcode2);
        $this->assertNull($product->wholesale_price);
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
        Product::factory()->create(['name' => 'Pieza SECONDARY(03)+# larga', 'barcode' => 'SECONDARY(03)+#A']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.products.index', ['search' => 'SECONDARY(03)+#']));

        $response->assertOk();
        $this->assertSame([$product->id], $response->viewData('products')->getCollection()->modelKeys());
    }

    #[TestWith(['barcode'])]
    #[TestWith(['barcode2'])]
    #[TestWith([null])]
    public function test_admin_numeric_search_never_suggests_products_with_partial_matches(?string $exactField): void
    {
        Product::factory()->count(3)->sequence(
            ['barcode' => '39', 'sku' => $exactField === null ? 'SKU-039' : '039'],
            ['barcode' => '1039', 'name' => 'Balata 039'],
            ['barcode2' => '039A', 'brand' => 'Serie 039'],
        )->create();
        $exactProduct = $exactField === null ? null : Product::factory()->create([$exactField => '039']);

        $response = $this->actingAs($this->admin())->get(route('admin.products.index', ['search' => '039']));

        $response->assertOk();
        $this->assertSame($exactProduct === null ? [] : [$exactProduct->id], $response->viewData('products')->getCollection()->modelKeys());
    }

    #[TestWith(['Balata'])]
    #[TestWith(['única'])]
    public function test_admin_product_search_still_matches_partial_name_and_brand(string $term): void
    {
        $product = Product::factory()->create(['name' => 'Balata especial', 'brand' => 'Marca única']);

        $response = $this->actingAs($this->admin())->get(route('admin.products.index', ['search' => $term]));

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

            $this->assertSame(3, $xpath->query('//input[@name="sale_mode"]')->length);
            $this->assertSame('unit', $xpath->query('//input[@name="sale_mode"][@checked]')->item(0)->getAttribute('value'));
            $this->assertSame('text', $xpath->query('//input[@name="stock"]')->item(0)->getAttribute('type'));
            $this->assertSame('decimal', $xpath->query('//input[@name="stock"]')->item(0)->getAttribute('inputmode'));

            foreach (['brand', 'description'] as $field) {
                $input = $xpath->query('//*[@name="'.$field.'"]')->item(0);

                $this->assertInstanceOf(DOMElement::class, $input);
                $this->assertFalse($input->hasAttribute('required'));
            }
        }
    }

    public function test_inventory_fields_are_plain_text_and_category_is_written_manually(): void
    {
        $admin = $this->admin();
        Category::factory()->create(['name' => 'Frenos']);

        $createResponse = $this->actingAs($admin)->get(route('admin.products.create'))->assertOk();
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$createResponse->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);

        foreach (['stock', 'min_stock', 'warehouse_stock', 'store_2_stock'] as $field) {
            $input = $xpath->query('//input[@name="'.$field.'"]')->item(0);
            $this->assertInstanceOf(DOMElement::class, $input);
            $this->assertSame('text', $input->getAttribute('type'));
            $this->assertSame('decimal', $input->getAttribute('inputmode'));
            $this->assertFalse($input->hasAttribute('step'));
        }

        $categoryInput = $xpath->query('//input[@id="category"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $categoryInput);
        $this->assertSame('text', $categoryInput->getAttribute('type'));
        $this->assertSame('product-categories', $categoryInput->getAttribute('list'));
        $this->assertStringContainsString('Frenos', $createResponse->getContent());

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Producto con categoría manual',
            'category' => 'Llantas',
            'price' => '100',
            'tax_rate' => '16',
            'sale_mode' => 'unit',
            'stock' => '12',
            'min_stock' => '2',
            'warehouse_stock' => '3',
            'store_2_stock' => '4',
            'track_inventory' => '1',
        ])->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $product = Product::query()->where('name', 'Producto con categoría manual')->firstOrFail();
        $this->assertSame('Llantas', $product->category?->name);
        $this->assertDatabaseHas('categories', ['name' => 'Llantas']);
        $this->assertSame(12, $product->stock);
        $this->assertSame(3.0, (float) $product->warehouse_stock);

        $editResponse = $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk();
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$editResponse->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $this->assertSame('Llantas', $xpath->query('//input[@id="category"]')->item(0)->getAttribute('value'));

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'category' => 'Frenos',
            'price' => '100',
            'tax_rate' => '16',
            'sale_mode' => 'unit',
            'stock' => '12',
            'min_stock' => '2',
            'warehouse_stock' => '3',
            'store_2_stock' => '4',
            'track_inventory' => '1',
        ])->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $this->assertSame('Frenos', $product->fresh()->category?->name);
        $this->assertDatabaseCount('categories', 2);
    }

    public function test_manual_selling_price_does_not_change_the_entered_margin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->productInput([
            'name' => 'Margen manual',
            'cost' => '80',
            'margin' => '30',
            'price' => '121',
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $product = Product::query()->where('name', 'Margen manual')->firstOrFail();
        $this->assertSame(121.0, (float) $product->price);
        $this->assertSame(30.0, $product->marginPercentage());

        $response = $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk();
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $input = $xpath->query('//input[@id="margin"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $input);
        $this->assertSame('margin', $input->getAttribute('name'));
        $this->assertSame('30', $input->getAttribute('value'));
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

    public function test_product_list_offers_csv_import_without_a_bulk_price_or_inventory_cleanup_form(): void
    {
        $this->actingAs($this->admin())->get(route('admin.products.index'))
            ->assertOk()
            ->assertSeeText('Importar productos CSV')
            ->assertDontSeeText('Herramientas de inventario y precios')
            ->assertDontSee(route('admin.products.massUpdate'))
            ->assertDontSeeText('Limpiar inventario')
            ->assertDontSee(route('admin.products.clearInventory'));
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
            ->assertRedirect(route('admin.products.index'));

        // Sin aserciones de sesión antes del GET: iniciar la sesión consume el flash de errores.
        $messages = session('errors')['default']['messages']['import_file'];
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
            ->assertRedirect(route('admin.products.index'));

        // Sin aserciones de sesión antes del GET: iniciar la sesión consume el flash de errores.
        $response = $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Selecciona un archivo CSV para importar.');
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $input = (new DOMXPath($document))->query('//details[@open]//input[@name="import_file"]')->item(0);

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

    public function test_admin_can_export_full_inventory_as_excel_workbook(): void
    {
        $category = Category::factory()->create(['name' => 'Frenos']);
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Balata delantera',
            'sku' => 'EXP-001',
            'barcode' => '7501234567890',
            'barcode2' => null,
            'price' => 199.90,
            'cost' => 100,
            'stock' => 7,
            'min_stock' => 2,
            'tax_rate' => 0.16,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.products.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertDownload();

        $path = $response->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertIsString($sheet);
        $this->assertStringContainsString('Código de barras', $sheet);
        $this->assertStringContainsString('EXP-001', $sheet);
        $this->assertStringContainsString('Balata delantera', $sheet);
        $this->assertStringContainsString('Frenos', $sheet);
        $this->assertStringContainsString('<v>199.9</v>', $sheet);
        $this->assertStringContainsString('<v>16</v>', $sheet);
        $this->assertStringContainsString('<v>7</v>', $sheet);
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

    #[TestWith(['unit'])]
    #[TestWith(['bulk'])]
    #[TestWith(['kit'])]
    public function test_legacy_edits_keep_the_existing_sale_mode_and_recipe(string $mode): void
    {
        $component = Product::factory()->create();
        $product = Product::factory()->create(['sale_mode' => $mode, 'stock' => 0, 'min_stock' => 0, 'kit_deduct_components' => $mode === 'kit']);
        if ($mode === 'kit') {
            $product->kitItems()->attach($component, ['quantity' => 2]);
        }

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->productInput())
            ->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $this->assertSame($mode, $product->fresh()->saleMode());
        $this->assertSame($mode === 'kit', $product->fresh()->kit_deduct_components);
        $this->assertSame($mode === 'kit' ? [$component->id] : [], $product->kitItems()->pluck('products.id')->all());
    }

    public function test_bulk_product_keeps_three_decimal_stock_and_minimum_without_changing_prices(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->productInput([
            'sale_mode' => 'bulk', 'stock' => '2.750', 'min_stock' => '0.500', 'cost' => '7.50', 'price' => '14.10',
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $product = Product::query()->sole();
        $this->assertSame(2.75, $product->stock);
        $this->assertSame(0.5, $product->min_stock);
        $this->assertSame('7.50', $product->cost);
        $this->assertSame('14.10', $product->price);
    }

    public function test_stale_product_edit_cannot_restore_stock_deducted_by_a_bulk_sale(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->bulk()->create(['name' => 'Aceite original', 'stock' => 10, 'price' => 14.10]);
        $response = $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk();
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $this->assertSame('10', $xpath->query('//input[@name="original_stock"]')->item(0)->getAttribute('value'));
        $sale = app(SaleService::class)->create($admin, [
            'idempotency_key' => 'sale-during-product-edit',
            'items' => [['product_id' => $product->id, 'quantity' => 0.5]],
        ]);

        $this->put(route('admin.products.update', $product), $this->productInput([
            'name' => 'Nombre desde ficha antigua', 'sale_mode' => 'bulk', 'stock' => 10, 'original_stock' => '10',
        ]))->assertSessionHasErrors('stock');

        $this->assertSame(9.5, $product->fresh()->stock);
        $this->assertSame('Aceite original', $product->fresh()->name);
        $this->assertSame('14.10', $product->fresh()->price);
        $this->assertSame('7.05', $sale->total);
        $this->assertDatabaseHas('stock_movements', ['sale_id' => $sale->id, 'quantity' => -0.5, 'stock_after' => 9.5]);
        $this->assertDatabaseCount('cash_movements', 1);
    }

    public function test_current_stock_reference_allows_an_explicit_inventory_edit(): void
    {
        $product = Product::factory()->bulk()->create(['stock' => 9.5]);

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->productInput([
            'sale_mode' => 'bulk', 'stock' => '11.125', 'original_stock' => '9.500',
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $this->assertSame(11.125, $product->fresh()->stock);
    }

    public function test_bulk_validation_redirect_restores_mode_and_fractional_inventory_inputs(): void
    {
        $product = Product::factory()->bulk()->create(['stock' => 2.75, 'min_stock' => 0.5]);
        $this->actingAs($this->admin())->from(route('admin.products.edit', $product))
            ->put(route('admin.products.update', $product), $this->productInput([
                'name' => '', 'sale_mode' => 'bulk', 'stock' => '1.125', 'min_stock' => '0.250',
            ]))->assertRedirect(route('admin.products.edit', $product))->assertSessionHasErrors('name');

        $response = $this->get(route('admin.products.edit', $product))->assertOk();
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $this->assertSame('bulk', $xpath->query('//input[@name="sale_mode"][@checked]')->item(0)->getAttribute('value'));
        $this->assertSame('1.125', $xpath->query('//input[@id="stock"]')->item(0)->getAttribute('value'));
        $this->assertSame('text', $xpath->query('//input[@id="stock"]')->item(0)->getAttribute('type'));
        $this->assertSame('0.250', $xpath->query('//input[@id="min_stock"]')->item(0)->getAttribute('value'));
        $this->assertSame(2.75, $product->fresh()->stock);
    }

    #[TestWith(['unit', 'stock', '0.5'])]
    #[TestWith(['unit', 'min_stock', '0.125'])]
    #[TestWith(['bulk', 'stock', '0.0001'])]
    #[TestWith(['bulk', 'min_stock', '0.0001'])]
    #[TestWith(['bulk', 'stock', '-1'])]
    #[TestWith(['bulk', 'stock', '4294967295.001'])]
    #[TestWith(['bulk', 'stock', '1e2'])]
    public function test_invalid_inventory_quantities_are_rejected_without_truncating_or_saving(string $mode, string $field, string $value): void
    {
        $product = Product::factory()->create(['sale_mode' => $mode, 'stock' => 2, 'min_stock' => 1]);
        $before = $product->fresh()->getAttributes();

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->productInput([
            'sale_mode' => $mode, $field => $value,
        ]))->assertSessionHasErrors($field);

        $this->assertSame($before, $product->fresh()->getAttributes());
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_admin_can_create_and_edit_kit_recipe_and_inventory_toggle(bool $deduct): void
    {
        $piece = Product::factory()->create(['track_inventory' => false]);
        $bulk = Product::factory()->bulk()->create();
        $items = [['product_id' => $piece->id, 'quantity' => 2], ['product_id' => $bulk->id, 'quantity' => '0.125']];

        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->productInput([
            'sale_mode' => 'kit', 'stock' => 99, 'min_stock' => 20, 'track_inventory' => true,
            'kit_items' => $items, ...($deduct ? ['kit_deduct_components' => true] : []),
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $kit = Product::query()->where('sale_mode', 'kit')->sole();
        $this->assertSame(0, $kit->stock);
        $this->assertSame(0, $kit->min_stock);
        $this->assertFalse($kit->track_inventory);
        $this->assertSame($deduct, $kit->kit_deduct_components);
        $this->assertDatabaseHas('product_kit_items', ['kit_product_id' => $kit->id, 'component_product_id' => $bulk->id, 'quantity' => 0.125]);
        $this->get(route('admin.products.edit', $kit))->assertOk()
            ->assertSee($piece->name)->assertSee('Sin control de inventario')->assertSee('value="0.125"', false);

        $this->put(route('admin.products.update', $kit), $this->productInput([
            'sale_mode' => 'kit', 'kit_deduct_components' => ! $deduct,
            'kit_items' => [['product_id' => $bulk->id, 'quantity' => '0.250']],
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $this->assertSame(! $deduct, $kit->fresh()->kit_deduct_components);
        $this->assertSame([$bulk->id], $kit->kitItems()->pluck('products.id')->all());
        $this->assertDatabaseHas('product_kit_items', ['kit_product_id' => $kit->id, 'component_product_id' => $bulk->id, 'quantity' => 0.25]);
    }

    #[TestWith(['missing', 'kit_items.0.product_id'])]
    #[TestWith(['inactive', 'kit_items.0.product_id'])]
    #[TestWith(['nested', 'kit_items.0.product_id'])]
    #[TestWith(['self', 'kit_items.0.product_id'])]
    #[TestWith(['duplicate', 'kit_items.1.product_id'])]
    #[TestWith(['fractional_piece', 'kit_items.0.quantity'])]
    #[TestWith(['zero', 'kit_items.0.quantity'])]
    #[TestWith(['negative', 'kit_items.0.quantity'])]
    #[TestWith(['too_precise', 'kit_items.0.quantity'])]
    #[TestWith(['empty', 'kit_items'])]
    #[TestWith(['client_metadata', 'kit_items.0'])]
    #[TestWith(['bulk_too_precise', 'kit_items.0.quantity'])]
    #[TestWith(['bulk_overflow', 'kit_items.0.quantity'])]
    public function test_invalid_kit_recipe_saves_neither_product_changes_nor_recipe(string $case, string $error): void
    {
        $kit = Product::factory()->kit()->create();
        $component = Product::factory()->create([
            'is_active' => $case !== 'inactive',
            'sale_mode' => match ($case) {
                'nested' => 'kit', 'bulk_too_precise', 'bulk_overflow' => 'bulk', default => 'unit'
            },
        ]);
        $id = match ($case) {
            'self' => $kit->id, 'missing' => 999999, default => $component->id
        };
        $quantity = match ($case) {
            'fractional_piece' => '0.5', 'zero' => 0, 'negative' => -1, 'too_precise', 'bulk_too_precise' => '0.0001', 'bulk_overflow' => '4294967295.001', default => 1
        };
        $items = [['product_id' => $id, 'quantity' => $quantity]];
        if ($case === 'client_metadata') {
            $items[0]['sale_mode'] = 'bulk';
            $items[0]['name'] = 'Nombre manipulado';
        }
        if ($case === 'duplicate') {
            $items[] = $items[0];
        }
        $before = $kit->fresh()->getAttributes();
        $recipe = DB::table('product_kit_items')->orderBy('kit_product_id')->orderBy('component_product_id')->get()->toJson();

        $this->actingAs($this->admin())->put(route('admin.products.update', $kit), $this->productInput([
            'sale_mode' => 'kit', 'kit_deduct_components' => true, 'kit_items' => $case === 'empty' ? [] : $items,
        ]))->assertSessionHasErrors($error);

        $this->assertSame($before, $kit->fresh()->getAttributes());
        $this->assertSame($recipe, DB::table('product_kit_items')->orderBy('kit_product_id')->orderBy('component_product_id')->get()->toJson());
    }

    public function test_invalid_kit_form_restores_mode_toggle_quantities_and_authoritative_component_names(): void
    {
        $component = Product::factory()->bulk()->create(['name' => 'Aceite <original>', 'track_inventory' => false]);
        $this->actingAs($this->admin())->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->productInput([
                'name' => '', 'sale_mode' => 'kit', 'kit_deduct_components' => true,
                'kit_items' => [['product_id' => $component->id, 'quantity' => '0.125']],
            ]))->assertRedirect(route('admin.products.create'))->assertSessionHasErrors('name');

        $response = $this->get(route('admin.products.create'))->assertOk()->assertSee('Aceite <original>')->assertSee('Sin control de inventario');
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $this->assertSame('kit', $xpath->query('//input[@name="sale_mode"][@checked]')->item(0)->getAttribute('value'));
        $this->assertTrue($xpath->query('//input[@id="kit_deduct_components"]')->item(0)->hasAttribute('checked'));
        $this->assertSame('0.125', $xpath->query('//input[@name="kit_items[0][quantity]"]')->item(0)->getAttribute('value'));
        $this->assertFalse($xpath->query('//fieldset[@data-kit-fields]')->item(0)->hasAttribute('disabled'));
        $this->assertTrue($xpath->query('//fieldset[@data-product-inventory]')->item(0)->hasAttribute('disabled'));
        $this->assertDatabaseCount('product_kit_items', 0);
    }

    public function test_kit_sync_failure_rolls_back_product_and_previous_recipe(): void
    {
        $kit = Product::factory()->kit()->create();
        $replacement = Product::factory()->create();
        $before = $kit->fresh()->getAttributes();
        $recipe = DB::table('product_kit_items')->orderBy('kit_product_id')->orderBy('component_product_id')->get()->toJson();
        $failed = false;
        DB::listen(function (QueryExecuted $query) use (&$failed): void {
            if (! $failed && str_starts_with($query->sql, 'insert into') && str_contains($query->sql, 'product_kit_items')) {
                $failed = true;
                throw new RuntimeException('Recipe persistence failed.');
            }
        });
        Exceptions::fake([RuntimeException::class]);

        $this->actingAs($this->admin())->put(route('admin.products.update', $kit), $this->productInput([
            'sale_mode' => 'kit', 'kit_items' => [['product_id' => $replacement->id, 'quantity' => 2]],
        ]))->assertInternalServerError();

        $this->assertTrue($failed);
        $this->assertSame($before, $kit->fresh()->getAttributes());
        $this->assertSame($recipe, DB::table('product_kit_items')->orderBy('kit_product_id')->orderBy('component_product_id')->get()->toJson());
        Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Recipe persistence failed.');
    }

    public function test_component_search_requires_product_permission_but_not_pos_permission(): void
    {
        $this->get(route('admin.products.components', ['q' => 'Aceite']))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create()->assignRole('empleado'))
            ->getJson(route('admin.products.components', ['q' => 'Aceite']))->assertForbidden();

        $manager = User::factory()->create()->givePermissionTo('admin.products.manage');
        $this->assertFalse($manager->can('pos.access'));
        $this->actingAs($manager)->getJson(route('admin.products.components', ['q' => 'Aceite']))
            ->assertOk()->assertExactJson(['products' => []]);
    }

    #[TestWith(['name', 'Aceite especial'])]
    #[TestWith(['barcode', '000(ABC)+#'])]
    #[TestWith(['barcode2', '00-SECOND'])]
    public function test_component_search_returns_only_active_piece_and_bulk_public_fields(string $field, string $term): void
    {
        $match = Product::factory()->bulk()->create([$field => $term, 'track_inventory' => false]);
        $excluded = Product::factory()->create(['name' => $term]);
        Product::factory()->create(['name' => $term, 'is_active' => false]);
        Product::factory()->create(['name' => $term, 'sale_mode' => 'kit']);

        $response = $this->actingAs($this->admin())->getJson(route('admin.products.components', ['q' => $term, 'exclude' => $excluded->id]))
            ->assertOk()->assertJsonCount(1, 'products')->assertJsonPath('products.0.id', $match->id);

        $this->assertSame(['id', 'name', 'barcode', 'barcode2', 'sale_mode', 'track_inventory'], array_keys($response->json('products.0')));
        $response->assertJsonPath('products.0.sale_mode', 'bulk')->assertJsonPath('products.0.track_inventory', false);
    }

    public function test_component_search_is_limited_and_empty_queries_do_not_list_catalog(): void
    {
        Product::factory()->count(13)->create(['name' => 'Pieza buscada']);
        $this->actingAs($this->admin())->getJson(route('admin.products.components', ['q' => 'Pieza buscada']))
            ->assertOk()->assertJsonCount(12, 'products');
        $this->getJson(route('admin.products.components'))->assertOk()->assertExactJson(['products' => []]);
    }

    public function test_converting_to_kit_never_erases_existing_physical_stock(): void
    {
        $product = Product::factory()->create(['stock' => 2]);
        $component = Product::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->productInput([
            'sale_mode' => 'kit', 'stock' => 0, 'kit_items' => [['product_id' => $component->id, 'quantity' => 1]],
        ]))->assertSessionHasErrors('sale_mode');

        $this->assertSame(2, $product->fresh()->stock);
        $this->assertSame('unit', $product->fresh()->saleMode());
        $this->assertSame(0, $product->kitItems()->count());
    }

    public function test_current_component_cannot_be_deleted_or_converted_to_kit(): void
    {
        $kit = Product::factory()->kit()->create();
        $component = $kit->kitItems->sole();
        $component->update(['stock' => 0]);

        $this->actingAs($this->admin())->delete(route('admin.products.destroy', $component))
            ->assertRedirect(route('admin.products.index'))->assertSessionHas('error');
        $this->put(route('admin.products.update', $component), $this->productInput([
            'sale_mode' => 'kit', 'kit_items' => [['product_id' => $component->id, 'quantity' => 1]],
        ]))->assertSessionHasErrors('sale_mode');

        $this->assertModelExists($component);
        $this->assertSame('unit', $component->fresh()->saleMode());
        $this->assertSame([$component->id], $kit->kitItems()->pluck('products.id')->all());
    }

    #[TestWith(['stock'])]
    #[TestWith(['min_stock'])]
    #[TestWith(['recipe'])]
    public function test_bulk_to_piece_conversion_does_not_truncate_existing_fractional_values(string $source): void
    {
        $product = Product::factory()->bulk()->create(['stock' => $source === 'stock' ? 0.125 : 0, 'min_stock' => $source === 'min_stock' ? 0.5 : 0]);
        if ($source === 'recipe') {
            $kit = Product::factory()->kit()->create();
            $kit->kitItems()->sync([$product->id => ['quantity' => 0.125]]);
        }
        $before = $product->fresh()->getAttributes();

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->productInput(['sale_mode' => 'unit', 'stock' => 0, 'min_stock' => 0]))
            ->assertSessionHasErrors('sale_mode');

        $this->assertSame($before, $product->fresh()->getAttributes());
    }

    #[TestWith(['pending'])]
    #[TestWith(['paid'])]
    public function test_pending_or_returnable_direct_sales_block_mode_changes_even_without_inventory_tracking(string $status): void
    {
        $admin = $this->admin();
        $product = Product::factory()->bulk()->create(['stock' => 0, 'min_stock' => 0, 'track_inventory' => false]);
        $sale = app(SaleService::class)->create($admin, [
            'status' => $status, 'idempotency_key' => 'mode-direct-'.$status,
            'items' => [['product_id' => $product->id, 'quantity' => '0.5']],
        ]);
        $before = $sale->items->sole()->getAttributes();

        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->productInput(['sale_mode' => 'unit']))
            ->assertSessionHasErrors('sale_mode');

        $this->assertSame('bulk', $product->fresh()->saleMode());
        $this->assertSame($before, $sale->items()->sole()->getAttributes());
        $this->assertDatabaseCount('stock_movements', 0);
    }

    #[TestWith(['pending'])]
    #[TestWith(['paid'])]
    public function test_removed_recipe_component_keeps_mode_while_its_sale_snapshot_is_pending_or_has_physical_history(string $status): void
    {
        $admin = $this->admin();
        $component = Product::factory()->bulk()->create(['stock' => 1, 'min_stock' => 0]);
        $kit = Product::factory()->kit()->create(['kit_deduct_components' => true]);
        $kit->kitItems()->sync([$component->id => ['quantity' => 0.125]]);
        $sale = app(SaleService::class)->create($admin, [
            'status' => $status, 'idempotency_key' => 'mode-component-'.$status,
            'items' => [['product_id' => $kit->id, 'quantity' => 1]],
        ]);
        $kit->kitItems()->detach($component);
        $component->update(['stock' => 0]);
        $snapshot = $sale->items->sole()->inventory_snapshot;

        $this->actingAs($admin)->put(route('admin.products.update', $component), $this->productInput(['sale_mode' => 'unit']))
            ->assertSessionHasErrors('sale_mode');

        $this->assertSame('bulk', $component->fresh()->saleMode());
        $this->assertSame($snapshot, $sale->items()->sole()->inventory_snapshot);
    }

    #[TestWith(['unit', 'bulk'])]
    #[TestWith(['bulk', 'unit'])]
    #[TestWith(['unit', 'kit'])]
    #[TestWith(['kit', 'unit'])]
    #[TestWith(['bulk', 'kit'])]
    #[TestWith(['kit', 'bulk'])]
    public function test_mode_can_change_when_inventory_and_operations_are_compatible(string $from, string $to): void
    {
        $product = Product::factory()->create(['sale_mode' => $from, 'stock' => 0, 'min_stock' => 0]);
        $component = Product::factory()->create();
        if ($from === 'kit') {
            $product->kitItems()->attach($component, ['quantity' => 1]);
        }

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->productInput([
            'sale_mode' => $to, 'kit_items' => [['product_id' => $component->id, 'quantity' => 1]],
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $this->assertSame($to, $product->fresh()->saleMode());
        $this->assertSame($to === 'kit' ? [$component->id] : [], $product->kitItems()->pluck('products.id')->all());
    }

    public function test_clear_inventory_removes_current_recipes_but_keeps_paid_inventory_snapshots(): void
    {
        $admin = $this->admin();
        $kit = Product::factory()->kit()->create(['kit_deduct_components' => true]);
        $kit->kitItems->sole()->update(['stock' => 3]);
        $sale = app(SaleService::class)->create($admin, [
            'idempotency_key' => 'clear-kit-history', 'items' => [['product_id' => $kit->id, 'quantity' => 1]],
        ]);
        $snapshot = $sale->items->sole()->inventory_snapshot;
        $this->assertNotEmpty($snapshot['effects']);

        $this->actingAs($admin)->post(route('admin.products.clearInventory'))
            ->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('product_kit_items', 0);
        $this->assertSame($snapshot, $sale->items()->sole()->inventory_snapshot);
        $this->assertNull($sale->items()->sole()->product_id);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_csv_creates_units_and_only_completes_barcode_of_existing_kit_without_touching_recipe(): void
    {
        $kit = Product::factory()->kit()->create(['sku' => 'KIT-CSV', 'barcode' => null, 'kit_deduct_components' => true]);
        $recipe = DB::table('product_kit_items')->orderBy('kit_product_id')->orderBy('component_product_id')->get()->toJson();
        $before = $kit->fresh()->getAttributes();
        $csv = "sku,nombre,precio_venta,stock,codigo_barras,sale_mode\nKIT-CSV,No cambiar,-1,-1,000-KIT,unit\nNEW-CSV,Nuevo,10,2,000-NEW,kit";

        $this->actingAs($this->admin())->post(route('admin.products.import'), [
            'import_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
        ])->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $after = $kit->fresh()->getAttributes();
        unset($before['barcode'], $before['updated_at'], $after['barcode'], $after['updated_at']);
        $this->assertSame($before, $after);
        $this->assertSame($recipe, DB::table('product_kit_items')->orderBy('kit_product_id')->orderBy('component_product_id')->get()->toJson());
        $this->assertSame('000-KIT', $kit->fresh()->barcode);
        $this->assertSame('unit', Product::query()->where('sku', 'NEW-CSV')->sole()->saleMode());
    }

    /** @param array<string, string|null> $fields */
    #[TestWith([[], null, null])]
    #[TestWith([['warehouse_stock' => '', 'store_2_stock' => ''], null, null])]
    #[TestWith([['warehouse_stock' => null, 'store_2_stock' => null], null, null])]
    #[TestWith([['warehouse_stock' => '0', 'store_2_stock' => '0'], '0.000', '0.000'])]
    #[TestWith([['warehouse_stock' => '2.750', 'store_2_stock' => '0.125'], '2.750', '0.125'])]
    public function test_optional_location_stocks_can_be_created_without_affecting_sellable_stock(array $fields, ?string $warehouse, ?string $store2): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->productInput([
            'sale_mode' => 'bulk', 'stock' => '1.500', ...$fields,
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $product = Product::query()->sole();
        $this->assertSame($warehouse, $product->warehouse_stock);
        $this->assertSame($store2, $product->store_2_stock);
        $this->assertSame(1.5, $product->availableStock());
        $this->assertDatabaseCount('stock_movements', 0);
    }

    /** @param array<string, string|null> $fields */
    #[TestWith([[], '2.750', '4.125'])]
    #[TestWith([['warehouse_stock' => '', 'store_2_stock' => ''], null, null])]
    #[TestWith([['warehouse_stock' => null, 'store_2_stock' => null], null, null])]
    #[TestWith([['warehouse_stock' => '0', 'store_2_stock' => '0'], '0.000', '0.000'])]
    #[TestWith([['warehouse_stock' => '3.125', 'store_2_stock' => '9.500'], '3.125', '9.500'])]
    #[TestWith([['warehouse_stock' => ''], null, '4.125'])]
    #[TestWith([['store_2_stock' => ''], '2.750', null])]
    public function test_optional_location_stocks_distinguish_omitted_empty_and_zero_on_update(array $fields, ?string $warehouse, ?string $store2): void
    {
        $product = Product::factory()->bulk()->create(['stock' => 2, 'warehouse_stock' => '2.750', 'store_2_stock' => '4.125']);

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->productInput([
            'stock' => 2, ...$fields,
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame($warehouse, $product->warehouse_stock);
        $this->assertSame($store2, $product->store_2_stock);
        $this->assertSame(2, $product->availableStock());
        $this->assertDatabaseCount('stock_movements', 0);
    }

    #[TestWith(['unit', 'warehouse_stock', '0.125'])]
    #[TestWith(['unit', 'store_2_stock', '0.125'])]
    #[TestWith(['kit', 'warehouse_stock', '0.125'])]
    #[TestWith(['kit', 'store_2_stock', '0.125'])]
    #[TestWith(['bulk', 'warehouse_stock', '-1'])]
    #[TestWith(['bulk', 'store_2_stock', '-1'])]
    #[TestWith(['bulk', 'warehouse_stock', '0.0001'])]
    #[TestWith(['bulk', 'store_2_stock', '0.0001'])]
    #[TestWith(['bulk', 'warehouse_stock', '4294967295.001'])]
    #[TestWith(['bulk', 'store_2_stock', '4294967295.001'])]
    #[TestWith(['bulk', 'warehouse_stock', '1e2'])]
    #[TestWith(['bulk', 'store_2_stock', '1e2'])]
    public function test_invalid_optional_location_quantity_preserves_product_and_recipe(string $mode, string $field, string $value): void
    {
        $product = Product::factory()->kit()->create(['sale_mode' => $mode, 'warehouse_stock' => 2, 'store_2_stock' => 3]);
        $before = $product->fresh()->getAttributes();
        $recipe = $product->kitItems()->pluck('product_kit_items.quantity', 'products.id')->all();
        $message = 'La cantidad debe ser no negativa, dentro del rango permitido y '.($mode === 'bulk' ? 'con hasta 3 decimales.' : 'entera.');

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->productInput([
            $field => $value,
        ]))->assertSessionHasErrors([$field => $message]);

        $this->assertSame($before, $product->fresh()->getAttributes());
        $this->assertSame($recipe, $product->kitItems()->pluck('product_kit_items.quantity', 'products.id')->all());
        $this->assertDatabaseCount('stock_movements', 0);
    }

    #[TestWith(['unit'])]
    #[TestWith(['kit'])]
    public function test_whole_optional_locations_can_be_created_for_units_and_kits(string $mode): void
    {
        $component = Product::factory()->create(['stock' => 4]);

        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->productInput([
            'sale_mode' => $mode, 'warehouse_stock' => '4294967295', 'store_2_stock' => '0',
            'kit_items' => [['product_id' => $component->id, 'quantity' => 2]],
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $product = Product::query()->whereKeyNot($component->id)->sole();
        $this->assertSame('4294967295.000', $product->warehouse_stock);
        $this->assertSame('0.000', $product->store_2_stock);
        $this->assertSame(0, $product->stock);
        $this->assertSame(4, $component->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_kit_optional_locations_are_saved_without_changing_recipe_or_derived_availability(): void
    {
        $product = Product::factory()->kit()->create(['kit_deduct_components' => true]);
        $component = $product->kitItems->sole();
        $component->update(['stock' => 4]);

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->productInput([
            'warehouse_stock' => '20', 'store_2_stock' => '0',
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('20.000', $product->warehouse_stock);
        $this->assertSame('0.000', $product->store_2_stock);
        $this->assertSame(0, $product->stock);
        $this->assertSame(4, $product->availableStock());
        $this->assertSame([$component->id], $product->kitItems()->pluck('products.id')->all());
        $this->assertFalse($product->track_inventory);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    #[TestWith(['unit'])]
    #[TestWith(['bulk'])]
    #[TestWith(['kit'])]
    public function test_optional_location_form_restores_values_outside_disabled_kit_inventory(string $mode): void
    {
        $product = Product::factory()->kit()->create(['sale_mode' => $mode, 'warehouse_stock' => null, 'store_2_stock' => 0]);
        $this->actingAs($this->admin());
        $response = $this->get(route('admin.products.edit', $product))->assertOk()->assertSee('Existencia en tienda');
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);

        foreach (['warehouse_stock' => '', 'store_2_stock' => '0.000'] as $field => $value) {
            $input = $xpath->query('//input[@name="'.$field.'"]')->item(0);
            $this->assertSame($value, $input->getAttribute('value'));
            $this->assertSame('text', $input->getAttribute('type'));
            $this->assertSame('decimal', $input->getAttribute('inputmode'));
            $this->assertFalse($input->hasAttribute('required'));
            $this->assertFalse($input->hasAttribute('disabled'));
            $this->assertSame(0, $xpath->query('ancestor::fieldset[@disabled]', $input)->length);
        }

        $this->from(route('admin.products.edit', $product))->put(route('admin.products.update', $product), $this->productInput([
            'name' => '', 'warehouse_stock' => '7', 'store_2_stock' => '0',
        ]))->assertSessionHasErrors('name');
        $response = $this->get(route('admin.products.edit', $product))->assertOk();
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $this->assertSame('7', $xpath->query('//input[@name="warehouse_stock"]')->item(0)->getAttribute('value'));
        $this->assertSame('0', $xpath->query('//input[@name="store_2_stock"]')->item(0)->getAttribute('value'));
        $this->assertNull($product->fresh()->warehouse_stock);
    }

    public function test_employee_cannot_write_optional_location_stocks(): void
    {
        $product = Product::factory()->create(['warehouse_stock' => null, 'store_2_stock' => 0]);
        $before = $product->fresh()->getAttributes();
        $employee = User::factory()->create()->assignRole('empleado');

        $this->actingAs($employee)->put(route('admin.products.update', $product), $this->productInput([
            'warehouse_stock' => '10', 'store_2_stock' => '20',
        ]))->assertForbidden();

        $this->assertSame($before, $product->fresh()->getAttributes());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productInput(array $overrides = []): array
    {
        return ['name' => 'Producto modalidad', 'price' => '14.10', 'tax_rate' => 16, 'stock' => 0, 'is_active' => true, ...$overrides];
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }
}
