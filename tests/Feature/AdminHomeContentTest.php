<?php

namespace Tests\Feature;

use App\Models\HomeCard;
use App\Models\HomePage;
use App\Models\Product;
use App\Models\User;
use App\Services\AuditLogService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class AdminHomeContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_read_the_editor_without_creating_a_home_page(): void
    {
        $card = HomeCard::factory()->create(['title' => 'Card guardada']);

        $response = $this->actingAs($this->admin())->get(route('admin.home.edit'));

        $response->assertOk()->assertViewHasAll(['page', 'cards']);

        $this->assertDatabaseCount('home_pages', 0);
        $this->assertSame('MotoZone', HomePage::current()->text('hero_eyebrow'));
        $this->assertTrue(HomePage::current()->enabled('hero'));
        $this->assertSame($card->id, $response->viewData('cards')['products']->first()->id);
    }

    public function test_editor_renders_cards_of_every_section(): void
    {
        HomeCard::factory()->category()->create(['title' => 'Categoría uno', 'sort_order' => 1]);
        HomeCard::factory()->category()->create(['title' => 'Categoría dos', 'sort_order' => 2]);
        HomeCard::factory()->create(['title' => 'Producto uno', 'price' => 100, 'product_id' => Product::factory()->create()->id]);
        HomeCard::factory()->novelty()->create(['title' => 'Novedad', 'is_active' => false]);

        $this->actingAs($this->admin())->get(route('admin.home.edit'))
            ->assertOk()
            ->assertSee('Categoría uno')
            ->assertSee('Producto uno')
            ->assertSee('Novedad')
            ->assertSee('Inactiva')
            ->assertSee('MotoZone CMS')
            ->assertSee('data-editor-tab="presentacion"', false)
            ->assertSee('data-editor-tab="categorias"', false)
            ->assertSee('data-editor-tab="destacados"', false)
            ->assertSee('data-editor-tab="nuevos"', false)
            ->assertSee('data-editor-tab="contacto"', false)
            ->assertSee('data-editor-tab="ubicacion"', false)
            ->assertSee('data-editor-tab="informacion"', false)
            ->assertSee('data-editor-save', false)
            ->assertSee('data-editor-discard', false)
            ->assertSee('data-editor-description', false)
            ->assertSee('Categoría asignada (nombre a mostrar)')
            ->assertSee(route('admin.home.cards.store'))
            ->assertSee(route('admin.home.preview'));
    }

    public function test_destacados_card_form_links_a_product_and_hides_other_fields(): void
    {
        $product = Product::factory()->create(['name' => 'Producto destacado', 'sku' => 'SKU-DEST']);
        $card = HomeCard::factory()->create([
            'title' => 'Card destacada', 'subtitle' => 'Subtítulo guardado', 'description' => 'Descripción guardada',
            'badge' => 'Oferta', 'link_url' => '/#nuevos', 'link_label' => 'Comprar ahora',
            'product_id' => $product->id, 'price' => 350, 'sale_price' => 300,
        ]);
        $this->actingAs($this->admin());

        $html = $this->get(route('admin.home.edit'))->assertOk()->getContent();

        $this->assertStringNotContainsString('name="subtitle" value="Subtítulo guardado"', $html);
        $this->assertStringNotContainsString('Descripción guardada', $html);
        $this->assertStringNotContainsString('name="badge" value="Oferta"', $html);
        $this->assertStringNotContainsString('name="link_url" value="/#nuevos"', $html);
        $this->assertStringNotContainsString('name="link_label" value="Comprar ahora"', $html);
        $this->assertStringContainsString('value="'.$product->id.'" data-product-id', $html);
        $this->assertStringContainsString('Vincular a un producto', $html);

        $this->get(route('home'))->assertOk()
            ->assertSee('Card destacada')
            ->assertSee('$300.00')
            ->assertSee(route('catalog.show', $product), false)
            ->assertSee('Ver más')
            ->assertDontSee('Descripción guardada')
            ->assertDontSee('Comprar ahora');

        $this->put(route('admin.home.cards.update', $card), ['section' => 'products', 'title' => 'Card editada', 'is_active' => '1'])
            ->assertSessionHasNoErrors();

        $card->refresh();
        $this->assertSame('Card editada', $card->title);
        $this->assertSame('Subtítulo guardado', $card->subtitle);
        $this->assertSame('Descripción guardada', $card->description);
        $this->assertSame('Oferta', $card->badge);
        $this->assertSame('/#nuevos', $card->link_url);
        $this->assertSame('Comprar ahora', $card->link_label);
        $this->assertSame($product->id, $card->product_id);
    }

    public function test_novelty_card_form_drops_subtitle_badge_and_manual_links(): void
    {
        $product = Product::factory()->create(['name' => 'Casco integral', 'price' => 1200]);
        $card = HomeCard::factory()->novelty()->create([
            'title' => 'Casco nuevo', 'subtitle' => 'Subtítulo guardado', 'badge' => 'Nuevo',
            'description' => 'Descripción de la novedad', 'link_url' => '/producto/casco',
            'link_label' => 'Conocer', 'price' => 1200, 'product_id' => $product->id,
        ]);
        $this->actingAs($this->admin());

        $html = $this->get(route('admin.home.edit'))->assertOk()->getContent();

        $this->assertStringNotContainsString('name="subtitle"', $html);
        $this->assertStringNotContainsString('name="badge"', $html);
        $this->assertStringNotContainsString('name="link_url"', $html);
        $this->assertStringNotContainsString('name="link_label"', $html);
        $this->assertStringContainsString('Descripción de la novedad', $html);

        $this->get(route('home'))->assertOk()
            ->assertSee('Casco nuevo')
            ->assertSee('Descripción de la novedad')
            ->assertSee(route('catalog.show', $product), false)
            ->assertSee('Ver más')
            ->assertDontSee('Subtítulo guardado')
            ->assertDontSee('Conocer');

        $this->put(route('admin.home.cards.update', $card), ['section' => 'new', 'title' => 'Casco editado', 'is_active' => '1'])
            ->assertSessionHasNoErrors();

        $card->refresh();
        $this->assertSame('Casco editado', $card->title);
        $this->assertSame('Subtítulo guardado', $card->subtitle);
        $this->assertSame('Nuevo', $card->badge);
        $this->assertSame('/producto/casco', $card->link_url);
        $this->assertSame('Conocer', $card->link_label);
    }

    public function test_presentation_editor_omits_button_fields_and_keeps_saved_configuration(): void
    {
        $page = HomePage::factory()->create(['content' => [
            'hero_button' => 'Ver novedades', 'hero_button_url' => '/#nuevos',
        ]]);

        $this->actingAs($this->admin())->get(route('admin.home.edit'))->assertOk()
            ->assertDontSee('name="content[hero_button]"', false)
            ->assertDontSee('name="content[hero_button_url]"', false)
            ->assertSee('name="content[hero_title]"', false);

        $this->put(route('admin.home.update'), ['block' => 'hero', 'content' => ['hero_title' => 'Mi presentación']])
            ->assertRedirect(route('admin.home.edit').'#presentacion')->assertSessionHasNoErrors();

        $this->assertSame('Mi presentación', $page->fresh()->text('hero_title'));
        $this->assertSame('Ver novedades', $page->fresh()->text('hero_button'));
        $this->assertSame('/#nuevos', $page->fresh()->text('hero_button_url'));
    }

    public function test_failed_card_input_does_not_replace_values_in_other_cards(): void
    {
        $editedCard = HomeCard::factory()->create(['title' => 'Título original']);
        HomeCard::factory()->create(['title' => 'Título conservado', 'price' => 123.45]);
        HomeCard::factory()->category()->create(['title' => 'Categoría conservada']);

        $response = $this->actingAs($this->admin())->withSession([
            '_old_input' => [
                'section' => 'products',
                'card_id' => (string) $editedCard->id,
                'title' => 'Borrador fallido',
                'price' => '999',
                'is_active' => '0',
            ],
        ])->get(route('admin.home.edit'));

        $response->assertOk()
            ->assertSee('value="Borrador fallido"', false)
            ->assertSee('value="Título conservado"', false)
            ->assertSee('value="123.45"', false)
            ->assertSee('value="Categoría conservada"', false);
    }

    #[TestWith(['admin.home.edit', 'admin.home.update'])]
    public function test_guest_cannot_read_or_write_home_content(string $readRoute, string $writeRoute): void
    {
        $this->get(route($readRoute))->assertRedirect(route('login'));
        $this->put(route($writeRoute), [])->assertRedirect(route('login'));

        $this->assertDatabaseCount('home_pages', 0);
    }

    #[TestWith(['admin.home.edit', 'admin.home.update'])]
    public function test_employee_cannot_read_or_write_home_content(string $readRoute, string $writeRoute): void
    {
        $employee = User::factory()->create()->assignRole('empleado');

        $this->actingAs($employee)->get(route($readRoute))->assertForbidden();
        $this->put(route($writeRoute), [])->assertForbidden();

        $this->assertDatabaseCount('home_pages', 0);
    }

    public function test_admin_can_save_texts_and_section_visibility_without_accepting_other_fields(): void
    {
        $content = array_replace(HomePage::DEFAULT_CONTENT, ['hero_title' => 'Piezas MotoZone', 'show_products' => false]);

        $this->actingAs($this->admin())->put(route('admin.home.update'), [
            'content' => $content,
            'hero_image_path' => 'untrusted.jpg',
            'id' => 2,
        ])->assertRedirect(route('admin.home.edit'))->assertSessionHasNoErrors();

        $page = HomePage::current();
        $this->assertSame(1, $page->id);
        $this->assertSame('Piezas MotoZone', $page->text('hero_title'));
        $this->assertFalse($page->enabled('products'));
        $this->assertNull($page->hero_image_path);
        $this->assertDatabaseCount('home_pages', 1);
    }

    public function test_editor_escapes_text_and_rejects_unknown_content_keys(): void
    {
        HomePage::factory()->create(['content' => array_replace(HomePage::DEFAULT_CONTENT, ['hero_title' => '<script>alert(1)</script>'])]);
        $this->actingAs($this->admin())->get(route('admin.home.edit'))->assertOk()
            ->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);

        $this->put(route('admin.home.update'), [
            'content' => array_replace(HomePage::DEFAULT_CONTENT, ['price' => 0]),
        ])->assertSessionHasErrors('content');

        $this->assertSame('<script>alert(1)</script>', HomePage::current()->text('hero_title'));
    }

    public function test_hero_button_url_accepts_internal_or_https_and_rejects_other_schemes(): void
    {
        $this->actingAs($this->admin());

        $this->put(route('admin.home.update'), ['block' => 'hero', 'content' => ['hero_button_url' => '/#nuevos']])
            ->assertSessionHasNoErrors();
        $this->assertSame('/#nuevos', HomePage::current()->text('hero_button_url'));

        $this->put(route('admin.home.update'), ['block' => 'hero', 'content' => ['hero_button_url' => 'https://example.com/promo']])
            ->assertSessionHasNoErrors();

        $this->put(route('admin.home.update'), ['block' => 'hero', 'content' => ['hero_button_url' => 'javascript:alert(1)']])
            ->assertSessionHasErrors('content.hero_button_url');
    }

    public function test_hero_upload_requires_alt_and_removes_uncommitted_new_image(): void
    {
        Storage::fake('public');
        $content = HomePage::DEFAULT_CONTENT;

        $this->actingAs($this->admin())->put(route('admin.home.update'), [
            'content' => $content, 'hero_image' => $this->image(),
        ])->assertSessionHasErrors('content.hero_image_alt');

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseCount('home_pages', 0);
    }

    public function test_hero_rejects_invalid_image_and_oversized_text_without_writing(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->put(route('admin.home.update'), [
            'content' => array_replace(HomePage::DEFAULT_CONTENT, ['hero_title' => str_repeat('x', 161)]),
            'hero_image' => UploadedFile::fake()->createWithContent('fake.png', '<script>alert(1)</script>'),
        ])->assertSessionHasErrors(['content.hero_title', 'hero_image']);

        $this->assertDatabaseCount('home_pages', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_hero_upload_is_kept_on_text_edit_and_replaced_only_after_a_successful_save(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('home/hero/old.png', 'old-image');
        HomePage::factory()->create(['hero_image_path' => 'home/hero/old.png']);
        $content = array_replace(HomePage::DEFAULT_CONTENT, ['hero_image_alt' => 'Piezas de MotoZone']);
        $this->actingAs($this->admin());

        $this->put(route('admin.home.update'), ['content' => $content])->assertSessionHasNoErrors();
        $this->assertSame('home/hero/old.png', HomePage::current()->hero_image_path);
        Storage::disk('public')->assertExists('home/hero/old.png');

        $this->put(route('admin.home.update'), ['content' => $content, 'hero_image' => $this->image()])->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists(HomePage::current()->hero_image_path);
        Storage::disk('public')->assertExists('home/hero/old.png');
    }

    public function test_failed_hero_database_save_keeps_old_image_and_removes_new_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('home/hero/old.png', 'old-image');
        HomePage::factory()->create(['hero_image_path' => 'home/hero/old.png']);
        Event::listen('eloquent.saving: '.HomePage::class, function (): void {
            throw new RuntimeException('Simulated database failure');
        });

        $this->actingAs($this->admin())->put(route('admin.home.update'), [
            'content' => array_replace(HomePage::DEFAULT_CONTENT, ['hero_image_alt' => 'Piezas']),
            'hero_image' => $this->image(),
        ])->assertServerError();

        $this->assertSame(['home/hero/old.png'], Storage::disk('public')->allFiles());
        $this->assertSame('home/hero/old.png', HomePage::current()->hero_image_path);
    }

    public function test_partial_blocks_preserve_other_saved_values(): void
    {
        $content = array_replace(HomePage::DEFAULT_CONTENT, [
            'hero_title' => 'Mi presentación', 'products_title' => 'Mis favoritos',
            'auto_decoration' => false,
        ]);
        HomePage::factory()->create(['content' => $content]);
        $this->actingAs($this->admin())->put(route('admin.home.update'), [
            'block' => 'categories', 'content' => ['categories_title' => 'Piezas por tipo'],
        ])->assertRedirect(route('admin.home.edit').'#categorias')->assertSessionHasNoErrors();

        $content['categories_title'] = 'Piezas por tipo';
        $this->assertSame($content, HomePage::current()->content);

        $this->put(route('admin.home.update'), ['content' => ['hero_title' => 'Otra presentación']])->assertSessionHasNoErrors();
        $content['hero_title'] = 'Otra presentación';
        $this->assertSame($content, HomePage::current()->content);
        $this->put(route('admin.home.update'), ['block' => 'new', 'content' => ['products_title' => 'No permitido']])
            ->assertRedirect(route('admin.home.edit').'#nuevos')->assertSessionHasErrors('content');
        $this->assertSame($content, HomePage::current()->content);
    }

    public function test_hero_rejects_ambiguous_foreign_file_fields(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->put(route('admin.home.update'), [
            'block' => 'categories',
            'content' => ['categories_title' => 'Categorías'],
            'hero_image' => $this->image(),
        ])->assertSessionHasErrors('hero_image');

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_hero_accepts_twenty_megabytes_but_rejects_larger_uploads_and_excess_dimensions(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin())->put(route('admin.home.update'), [
            'block' => 'hero', 'content' => ['hero_image_alt' => 'Piezas'], 'hero_image' => $this->image()->size(20480),
        ])->assertSessionHasNoErrors();
        $path = HomePage::current()->hero_image_path;

        $this->put(route('admin.home.update'), [
            'block' => 'hero', 'hero_image' => $this->image()->size(20481),
        ])->assertSessionHasErrors(['hero_image' => 'La imagen no debe superar los 20 MB.']);

        $image = $this->image();
        $bytes = file_get_contents($image->getPathname());
        $bytes = substr_replace($bytes, pack('N', 6001), 16, 4);
        $bytes = substr_replace($bytes, pack('N', crc32(substr($bytes, 12, 17))), 29, 4);
        $this->put(route('admin.home.update'), [
            'block' => 'hero', 'hero_image' => UploadedFile::fake()->createWithContent('wide.png', $bytes),
        ])->assertSessionHasErrors(['hero_image' => 'La imagen no debe superar 6000 × 6000 píxeles.']);

        $this->assertSame($path, HomePage::current()->hero_image_path);
        $this->assertSame([$path], Storage::disk('public')->allFiles());
    }

    public function test_reset_requires_confirmation_and_keeps_images_and_cards(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('home/hero/old.png', 'old');
        $card = HomeCard::factory()->create(['title' => 'Card intacta', 'image_path' => 'home/cards/card.jpg']);
        $page = HomePage::factory()->create(['hero_image_path' => 'home/hero/old.png', 'content' => [
            'hero_title' => 'Título previo', 'hero_image_alt' => 'Foto previa', 'hero_image_url' => '',
            'show_new' => false, 'auto_decoration' => false,
        ]]);
        $before = $page->only(['content', 'hero_image_path']);
        $cardBefore = $card->fresh()->getAttributes();
        $this->actingAs($this->admin())->post(route('admin.home.reset'), [])->assertSessionHasErrors('confirm');
        $this->assertSame($before, HomePage::current()->only(['content', 'hero_image_path']));

        $this->post(route('admin.home.reset'), ['confirm' => '1'])->assertRedirect(route('admin.home.edit'))->assertSessionHasNoErrors();

        $page = HomePage::current();
        $this->assertSame(HomePage::DEFAULT_CONTENT['hero_title'], $page->text('hero_title'));
        $this->assertSame('Foto previa', $page->text('hero_image_alt'));
        $this->assertSame('home/hero/old.png', $page->hero_image_path);
        $this->assertTrue($page->enabled('new'));
        $this->assertTrue($page->content['auto_decoration']);
        $this->assertSame($cardBefore, $card->fresh()->getAttributes());
        $snapshot = DB::table('audit_logs')->where('action', 'restablecer_inicio')->sole();
        $this->assertSame($before, json_decode($snapshot->before, true));
        $this->assertSame($page->only(['content', 'hero_image_path']), json_decode($snapshot->after, true));
        Storage::disk('public')->assertExists('home/hero/old.png');
    }

    public function test_reset_is_rolled_back_if_the_audit_snapshot_cannot_be_saved(): void
    {
        HomePage::factory()->create(['content' => ['hero_title' => 'No perder']]);
        $this->mock(AuditLogService::class)->shouldReceive('log')->once()->andThrow(new RuntimeException('Audit unavailable'));

        $this->actingAs($this->admin())->post(route('admin.home.reset'), ['confirm' => true])->assertServerError();

        $this->assertSame(['hero_title' => 'No perder'], HomePage::current()->content);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_reset_requires_an_active_admin(): void
    {
        $this->post(route('admin.home.reset'), ['confirm' => true])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create()->assignRole('empleado'));
        $this->post(route('admin.home.reset'), ['confirm' => true])->assertForbidden();
        $inactive = User::factory()->create(['is_active' => false])->assignRole('admin');
        $this->actingAs($inactive)->post(route('admin.home.reset'), ['confirm' => true])->assertRedirect(route('login'));

        $this->assertDatabaseCount('home_pages', 0);
    }

    public function test_product_search_returns_active_matches_by_name_sku_or_barcode(): void
    {
        Product::factory()->create(['name' => 'Balata delantera', 'sku' => 'BAL-001']);
        $bySku = Product::factory()->create(['name' => 'Otra pieza', 'sku' => 'FRE-777']);
        $byBarcode = Product::factory()->create(['name' => 'Bujía', 'barcode' => '7501234567890']);
        Product::factory()->create(['name' => 'Balata inactiva', 'sku' => 'BAL-002', 'is_active' => false]);

        $response = $this->actingAs($this->admin())->getJson(route('admin.home.products.search', ['q' => 'BAL']));

        $response->assertOk();
        $this->assertSame(['Balata delantera'], array_column($response->json('products'), 'name'));

        $response = $this->getJson(route('admin.home.products.search', ['q' => 'FRE-777']));
        $response->assertOk();
        $this->assertSame([$bySku->id], array_column($response->json('products'), 'id'));

        $response = $this->getJson(route('admin.home.products.search', ['q' => '7501234567890']));
        $response->assertOk();
        $this->assertSame([$byBarcode->id], array_column($response->json('products'), 'id'));
    }

    public function test_hero_gallery_photos_are_stored_removed_and_shown_as_carousel(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->put(route('admin.home.update'), [
            'block' => 'hero',
            'content' => ['hero_image_alt' => 'Piezas'],
            'gallery' => [$this->image(), $this->image()],
        ])->assertSessionHasNoErrors();

        $page = HomePage::current();
        $this->assertCount(2, $page->gallery);
        $this->get(route('home'))->assertOk()->assertSee('data-carousel', false)->assertSee('2 fotos');

        $removed = $page->gallery[0];
        $this->put(route('admin.home.update'), [
            'block' => 'hero',
            'content' => ['hero_image_alt' => 'Piezas'],
            'remove_gallery' => [$removed],
        ])->assertSessionHasNoErrors();

        $this->assertCount(1, HomePage::current()->gallery);
        Storage::disk('public')->assertMissing($removed);
    }

    public function test_hero_accepts_direct_image_urls_for_the_cover_and_gallery(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('home/hero/old.png', 'old-image');
        HomePage::factory()->create([
            'content' => array_replace(HomePage::DEFAULT_CONTENT, ['hero_image_alt' => 'Piezas']),
            'hero_image_path' => 'home/hero/old.png',
        ]);
        $heroUrl = 'https://images.example.test/hero.jpg';
        $galleryUrl = 'https://images.example.test/gallery.jpg';

        $this->actingAs($this->admin())->put(route('admin.home.update'), [
            'block' => 'hero',
            'content' => ['hero_image_alt' => 'Piezas'],
            'hero_image_url' => $heroUrl,
            'gallery_url' => $galleryUrl,
        ])->assertRedirect(route('admin.home.edit').'#presentacion')->assertSessionHasNoErrors();

        $page = HomePage::current();
        $this->assertSame($heroUrl, $page->heroImageUrl());
        $this->assertNull($page->hero_image_path);
        $this->assertSame([$galleryUrl], $page->gallery);
        $this->assertSame([$heroUrl, $galleryUrl], $page->galleryUrls());
        Storage::disk('public')->assertExists('home/hero/old.png');
        $this->get(route('home'))->assertOk()->assertSee($heroUrl, false)->assertSee($galleryUrl, false);
    }

    #[TestWith(['http://images.example.test/image.jpg'])]
    #[TestWith(['javascript:alert(1)'])]
    #[TestWith(['https:///image.jpg'])]
    #[TestWith(['https://user@example.test/image.jpg'])]
    #[TestWith(['https://localhost/image.jpg'])]
    #[TestWith(['https://127.0.0.1/image.jpg'])]
    public function test_hero_rejects_unsafe_image_urls_without_replacing_saved_file(string $url): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('home/hero/old.png', 'old-image');
        HomePage::factory()->create(['hero_image_path' => 'home/hero/old.png']);

        $this->actingAs($this->admin())->put(route('admin.home.update'), [
            'block' => 'hero',
            'hero_image_url' => $url,
        ])->assertSessionHasErrors([
            'hero_image_url' => 'Pega la dirección HTTPS directa de una imagen, sin credenciales ni hosts locales.',
        ]);

        $this->assertSame('home/hero/old.png', HomePage::current()->hero_image_path);
        Storage::disk('public')->assertExists('home/hero/old.png');
    }

    public function test_hero_rejects_file_and_url_for_the_same_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('home/hero/old.png', 'old-image');
        HomePage::factory()->create(['hero_image_path' => 'home/hero/old.png']);

        $this->actingAs($this->admin())->put(route('admin.home.update'), [
            'block' => 'hero',
            'hero_image' => $this->image(),
            'hero_image_url' => 'https://images.example.test/image.jpg',
        ])->assertSessionHasErrors('hero_image_url');

        $this->assertSame('home/hero/old.png', HomePage::current()->hero_image_path);
        $this->assertSame(['home/hero/old.png'], Storage::disk('public')->allFiles());
    }

    public function test_contact_save_preserves_other_content_images_and_cards(): void
    {
        $page = HomePage::factory()->create(['content' => ['hero_title' => 'Conservar presentación'], 'hero_image_path' => 'home/hero/keep.jpg']);
        $card = HomeCard::factory()->create();
        $cardBefore = $card->getAttributes();
        $contact = [
            'store_name' => 'Mi mostrador', 'store_description' => 'Atención de refacciones',
            'phone' => '+52 (55) 1234-5678', 'whatsapp' => '+52 55 1234 5678',
            'email' => 'cotizaciones@example.test', 'hours' => "Lunes a viernes\nSábado por la mañana",
            'whatsapp_message' => 'Hola, busco una pieza & accesorios',
            'show_whatsapp_bubble' => '1', 'show_email_bubble' => '1',
        ];

        $this->actingAs($this->admin())->put(route('admin.home.update'), ['block' => 'contact', 'content' => $contact])
            ->assertRedirect(route('admin.home.edit').'#contacto')->assertSessionHasNoErrors();

        $this->assertSame(['hero_title' => 'Conservar presentación', ...$contact], $page->fresh()->content);
        $this->assertSame('home/hero/keep.jpg', $page->fresh()->hero_image_path);
        $this->assertSame($cardBefore, $card->fresh()->getAttributes());
    }

    public function test_location_can_switch_from_google_to_a_pin_with_zero_coordinates(): void
    {
        $this->actingAs($this->admin())->put(route('admin.home.update'), ['block' => 'location', 'content' => [
            'map_mode' => 'google', 'map_embed_url' => 'https://www.google.com/maps/embed?pb=test-location',
            'maps_url' => 'https://maps.app.goo.gl/test-location', 'show_location' => '1',
        ]])->assertRedirect(route('admin.home.edit').'#ubicacion')->assertSessionHasNoErrors();
        $this->assertSame('https://www.google.com/maps/embed?pb=test-location', HomePage::current()->mapEmbedUrl());

        $this->put(route('admin.home.update'), ['block' => 'location', 'content' => [
            'map_mode' => 'pin', 'latitude' => '0', 'longitude' => '0', 'maps_url' => '',
        ]])->assertSessionHasNoErrors();

        $page = HomePage::current();
        $this->assertSame('pin', $page->text('map_mode'));
        $this->assertSame('0', $page->text('latitude'));
        $this->assertSame('0', $page->text('longitude'));
        $this->assertSame('https://www.openstreetmap.org/export/embed.html?bbox=-0.01%2C-0.006%2C0.01%2C0.006&layer=mapnik&marker=0%2C0', $page->mapEmbedUrl());
        $this->assertSame('https://www.google.com/maps/dir/?api=1&destination=0%2C0', $page->directionsUrl());
    }

    #[TestWith(['contact', ['email' => 'not-an-email'], 'content.email', 'Escribe un correo de contacto válido.'])]
    #[TestWith(['contact', ['whatsapp' => '+00000000'], 'content.whatsapp', 'Escribe de 7 a 15 dígitos, con código de país para WhatsApp; puedes usar +, espacios, guiones o paréntesis.'])]
    #[TestWith(['contact', ['phone' => '123abc4567'], 'content.phone', 'Escribe de 7 a 15 dígitos, con código de país para WhatsApp; puedes usar +, espacios, guiones o paréntesis.'])]
    #[TestWith(['contact', ['whatsapp' => '5212345678901234'], 'content.whatsapp', 'Escribe de 7 a 15 dígitos, con código de país para WhatsApp; puedes usar +, espacios, guiones o paréntesis.'])]
    #[TestWith(['location', ['latitude' => '90.1'], 'content.latitude', 'La latitud debe estar entre -90 y 90.'])]
    #[TestWith(['location', ['longitude' => '-180.1'], 'content.longitude', 'La longitud debe estar entre -180 y 180.'])]
    #[TestWith(['location', ['latitude' => 'north'], 'content.latitude', 'La latitud debe ser un número.'])]
    #[TestWith(['location', ['map_mode' => 'other'], 'content.map_mode', 'Selecciona Google Maps o pin por coordenadas.'])]
    #[TestWith(['location', ['map_mode' => 'pin', 'latitude' => 0], 'content.longitude', 'Indica la latitud y la longitud juntas para colocar el pin.'])]
    #[TestWith(['location', ['map_mode' => 'pin', 'longitude' => 0], 'content.latitude', 'Indica la latitud y la longitud juntas para colocar el pin.'])]
    #[TestWith(['location', ['map_mode' => 'google'], 'content.map_embed_url', 'Indica la URL de inserción de Google Maps para mostrar este mapa.'])]
    #[TestWith(['location', ['map_embed_url' => 'https://maps.app.goo.gl/short'], 'content.map_embed_url', 'Pega solo la URL HTTPS de Google Maps > Compartir > Insertar mapa (src), no el HTML ni un enlace corto.'])]
    #[TestWith(['location', ['map_embed_url' => '<iframe src="https://www.google.com/maps/embed?pb=test"></iframe>'], 'content.map_embed_url', 'Pega solo la URL HTTPS de Google Maps > Compartir > Insertar mapa (src), no el HTML ni un enlace corto.'])]
    #[TestWith(['location', ['map_embed_url' => 'https://www.google.com.evil.test/maps/embed?pb=test'], 'content.map_embed_url', 'Pega solo la URL HTTPS de Google Maps > Compartir > Insertar mapa (src), no el HTML ni un enlace corto.'])]
    #[TestWith(['location', ['maps_url' => 'javascript:alert(1)'], 'content.maps_url', 'Escribe un enlace HTTPS de Google Maps para llegar al mostrador.'])]
    #[TestWith(['location', ['maps_url' => 'http://maps.google.com/?q=test'], 'content.maps_url', 'Escribe un enlace HTTPS de Google Maps para llegar al mostrador.'])]
    #[TestWith(['location', ['maps_url' => 'https://www.google.com@evil.test/maps'], 'content.maps_url', 'Escribe un enlace HTTPS de Google Maps para llegar al mostrador.'])]
    public function test_invalid_contact_or_location_is_rejected_without_writing(string $block, array $content, string $field, string $message): void
    {
        $this->actingAs($this->admin())->put(route('admin.home.update'), compact('block', 'content'))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('home_pages', 0);
    }

    public function test_partial_location_update_uses_saved_coordinate_and_rejects_clearing_only_one(): void
    {
        $page = HomePage::factory()->create(['content' => ['map_mode' => 'pin', 'latitude' => '19', 'longitude' => '-99']]);
        $this->actingAs($this->admin())->put(route('admin.home.update'), ['block' => 'location', 'content' => ['latitude' => '20']])
            ->assertSessionHasNoErrors();
        $saved = ['map_mode' => 'pin', 'latitude' => '20', 'longitude' => '-99'];
        $this->assertSame($saved, $page->fresh()->content);

        $this->put(route('admin.home.update'), ['block' => 'location', 'content' => ['longitude' => '']])
            ->assertSessionHasErrors('content.longitude');

        $this->assertSame($saved, $page->fresh()->content);
    }

    public function test_information_is_optional_and_available_in_full_content_updates(): void
    {
        $content = [
            'show_benefits' => true, 'benefit_1_title' => 'Atención personal',
            'benefit_1_description' => 'Consulta tus dudas.', 'advice_title' => '',
            'show_metrics' => true, 'metric_1_value' => 'Desde 2020', 'metric_1_label' => 'Contigo',
            'email' => 'tienda@example.test', 'map_mode' => 'pin', 'latitude' => 0, 'longitude' => 0,
        ];

        $this->actingAs($this->admin())->put(route('admin.home.update'), ['content' => $content])
            ->assertSessionHasNoErrors();

        $this->assertSame('Atención personal', HomePage::current()->text('benefit_1_title'));
        $this->assertSame('', HomePage::current()->text('advice_title'));
        $this->assertSame('tienda@example.test', HomePage::current()->text('email'));
        $this->assertTrue(HomePage::current()->enabled('metrics'));
    }

    public function test_contact_block_rejects_location_keys_without_changing_saved_content(): void
    {
        $page = HomePage::factory()->create(['content' => ['email' => 'keep@example.test']]);

        $this->actingAs($this->admin())->put(route('admin.home.update'), ['block' => 'contact', 'content' => ['email' => 'new@example.test', 'show_location' => true]])
            ->assertSessionHasErrors('content');

        $this->assertSame(['email' => 'keep@example.test'], $page->fresh()->content);
    }

    public function test_reset_preserves_contact_map_and_complementary_content(): void
    {
        $settings = ['email' => 'keep@example.test', 'whatsapp' => '+525512345678', 'map_mode' => 'pin', 'latitude' => 0, 'longitude' => 0, 'show_location' => true, 'show_whatsapp_bubble' => true, 'benefit_1_title' => 'Beneficio guardado'];
        $page = HomePage::factory()->create(['content' => ['hero_title' => 'Cambiar este título', ...$settings]]);

        $this->actingAs($this->admin())->post(route('admin.home.reset'), ['confirm' => true])
            ->assertRedirect(route('admin.home.edit'))->assertSessionHasNoErrors();

        foreach ($settings as $key => $value) {
            $this->assertSame($value, $page->fresh()->content[$key]);
        }
        $this->assertSame(HomePage::DEFAULT_CONTENT['hero_title'], $page->fresh()->text('hero_title'));
    }

    public function test_location_error_recovers_its_tab_without_replacing_other_forms(): void
    {
        HomePage::factory()->create(['content' => ['email' => 'keep@example.test', 'address' => 'Dirección guardada']]);

        $this->actingAs($this->admin())->withSession(['_old_input' => ['block' => 'location', 'content' => ['address' => 'Borrador de dirección']]])
            ->get(route('admin.home.edit'))->assertOk()
            ->assertSee('data-initial-panel="ubicacion"', false)
            ->assertSee('Borrador de dirección')
            ->assertSee('value="keep@example.test"', false);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
    }
}
