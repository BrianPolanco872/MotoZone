<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PosProductSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pos_search_matches_both_barcodes_but_not_internal_sku(): void
    {
        $product = Product::factory()->create([
            'sku' => 'INTERNAL-ONLY-01',
            'barcode' => '0001234567890',
            'barcode2' => 'A(02)+#',
        ]);
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        foreach (['0001234567890', 'A(02)+#'] as $code) {
            $this->actingAs($employee)
                ->getJson(route('pos.products.search', ['q' => $code]))
                ->assertOk()
                ->assertJsonPath('products.0.id', $product->id);
        }

        $this->actingAs($employee)
            ->getJson(route('pos.products.search', ['q' => 'INTERNAL-ONLY-01']))
            ->assertOk()
            ->assertJsonCount(0, 'products');
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_imported_or_repaired_code_is_visible_and_scannable_with_manual_second_code(bool $repairExisting): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        if ($repairExisting) {
            Product::factory()->create([
                'sku' => '00012345',
                'barcode' => null,
                'barcode2' => null,
                'is_active' => true,
            ]);
        }

        $this->actingAs($admin)->post(route('admin.products.import'), [
            'import_file' => UploadedFile::fake()->createWithContent(
                'productos.csv',
                "codigo,nombre,precio_venta\n00012345,Pieza importada,10",
            ),
        ])->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('products', 1);
        $product = Product::query()->where('sku', '00012345')->firstOrFail();
        $this->assertSame('00012345', $product->barcode);
        $this->assertNull($product->barcode2);
        $response = $this->get(route('admin.products.edit', $product))->assertOk();
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $input = (new DOMXPath($document))->query('//input[@name="barcode"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $input);
        $this->assertSame('00012345', $input->getAttribute('value'));

        $this->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'price' => $product->price,
            'tax_rate' => 16,
            'stock' => $product->stock,
            'barcode' => '00012345',
            'barcode2' => 'ALTERNATE(02)+#',
            'is_active' => '1',
        ])->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        foreach (['00012345', 'ALTERNATE(02)+#'] as $code) {
            $this->actingAs($employee)
                ->getJson(route('pos.products.search', ['q' => $code]))
                ->assertOk()
                ->assertJsonCount(1, 'products')
                ->assertJsonPath('products.0.id', $product->id)
                ->assertJsonPath('products.0.barcode', '00012345')
                ->assertJsonPath('products.0.barcode2', 'ALTERNATE(02)+#');
        }
    }

    public function test_pos_search_still_finds_products_by_name_and_brand(): void
    {
        $product = Product::factory()->create([
            'name' => 'Balata especial',
            'brand' => 'Marca única',
            'sku' => 'INTERNAL-SEARCH-02',
            'barcode' => null,
            'barcode2' => null,
        ]);
        $employee = User::factory()->create()->assignRole('empleado');
        $employee->givePermissionTo('pos.access');

        foreach (['Balata especial', 'Marca única'] as $term) {
            $this->actingAs($employee)
                ->getJson(route('pos.products.search', ['q' => $term]))
                ->assertOk()
                ->assertJsonPath('products.0.id', $product->id);
        }
    }
}
