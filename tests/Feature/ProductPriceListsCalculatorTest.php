<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPriceListsCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_create_and_edit_forms_expose_the_reference_price_list_calculator(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $product = Product::factory()->for(Category::factory()->create())->create();

        foreach ([
            route('admin.products.create'),
            route('admin.products.edit', $product),
        ] as $url) {
            $response = $this->actingAs($admin)->get($url)
                ->assertOk()
                ->assertSee('Publicación')
                ->assertSee('Listas de precios (referencia)')
                ->assertSee('Calcular listas')
                ->assertSee('IVA / Desc. (%)')
                ->assertSee('Precio Público suma IVA al costo; las listas L1 a L5 aplican descuento en cascada sobre la lista anterior.')
                ->assertSee('Lista 1 / CESVE, TALLER')
                ->assertSee('Lista 2 / MINORISTA')
                ->assertSee('Lista 3 / SENIOR')
                ->assertSee('Lista 4 / MASTER')
                ->assertSee('Lista 5 / DISTRIBUIDOR, POWERSPORT');

            $html = $response->getContent();
            $this->assertLessThan(strpos($html, 'Listas de precios (referencia)'), strpos($html, '>Publicación<'));
            $this->assertStringNotContainsString('Base de cálculo', $html);
            $this->assertStringNotContainsString('data-price-lists-base', $html);
            $this->assertStringNotContainsString('min-w-[36rem]', $html);
            $this->assertStringNotContainsString('data-price-list-tax', $html);

            $document = new DOMDocument;
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($document);

            $this->assertSame(6, $xpath->query('//tr[@data-price-list-row]')->length);
            $this->assertSame(3, $xpath->query('//table[.//tr[@data-price-list-row]]/thead/tr/th')->length);
            $this->assertSame(
                ['16', '10', '21', '18', '15', '10'],
                array_map(
                    fn ($input) => $input->getAttribute('value'),
                    iterator_to_array($xpath->query('//tr[@data-price-list-row]//input[@data-price-list-percentage]'))
                )
            );
            $this->assertSame(0, $xpath->query('//tr[@data-price-list-row]//input[@name]')->length);
            $this->assertSame(0, $xpath->query('//select[@data-price-lists-base]')->length);
            $this->assertSame(1, $xpath->query('//button[@data-calculate-price-lists][@type="button"]')->length);
        }
    }
}
