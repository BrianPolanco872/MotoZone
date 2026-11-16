<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HomeCard;
use App\Models\HomePage;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeTime();
    }

    public function test_home_shows_active_cards_of_each_section_in_sort_order(): void
    {
        $third = HomeCard::factory()->create(['section' => 'products', 'title' => 'Tercera', 'sort_order' => 3]);
        $first = HomeCard::factory()->create(['section' => 'products', 'title' => 'Primera', 'sort_order' => 1]);
        $second = HomeCard::factory()->create(['section' => 'products', 'title' => 'Segunda', 'sort_order' => 1]);
        $hidden = HomeCard::factory()->create(['section' => 'products', 'title' => 'Oculta', 'sort_order' => 0, 'is_active' => false]);
        $category = HomeCard::factory()->category()->create(['title' => 'Categoría promocional', 'sort_order' => 2]);
        $novelty = HomeCard::factory()->novelty()->create(['title' => 'Novedad de temporada', 'sort_order' => 4]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertViewIs('home');
        $this->assertSame([$first->id, $second->id, $third->id], $response->viewData('productCards')->modelKeys());
        $this->assertSame([$category->id], $response->viewData('categoryCards')->modelKeys());
        $this->assertSame([$novelty->id], $response->viewData('newCards')->modelKeys());
        $response->assertSeeInOrder([$first->title, $second->title, $third->title]);
        $response->assertDontSee($hidden->title);
    }

    public function test_home_hides_disabled_sections_even_when_they_have_cards(): void
    {
        HomePage::query()->create(['content' => [
            'hero_title' => 'Presentación desactivada',
            'show_hero' => false,
            'show_categories' => false,
            'show_products' => false,
            'show_new' => false,
        ]]);
        HomeCard::factory()->category()->create(['title' => 'Categoría oculta']);
        $productCard = HomeCard::factory()->create(['title' => 'Producto oculto']);
        HomeCard::factory()->novelty()->create(['title' => 'Novedad oculta']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('categoryCards'));
        $this->assertCount(0, $response->viewData('productCards'));
        $this->assertCount(0, $response->viewData('newCards'));
        $response->assertDontSee('Presentación desactivada');
        $response->assertDontSee($productCard->title);
        $response->assertDontSee('id="home-categories-title"', false);
        $response->assertDontSee('id="home-products-title"', false);
        $response->assertDontSee('id="home-new-title"', false);
    }

    public function test_home_does_not_create_a_singleton_or_show_examples_on_get(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $this->assertFalse($response->viewData('homePage')->exists);
        $this->assertDatabaseCount('home_pages', 0);
        $this->assertCount(0, $response->viewData('categoryCards'));
        $response->assertDontSee('Vista de ejemplo.');
        $response->assertDontSee('Ejemplo');
    }

    public function test_admin_preview_renders_example_prices_without_persisting_cards(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.home.preview'));

        $response->assertOk()
            ->assertSee('Kit de arrastre')
            ->assertSee('$1,249.00')
            ->assertSee('Casco urbano')
            ->assertSee('$1,899.00');
        $this->assertDatabaseCount('home_cards', 0);
        $this->assertDatabaseCount('home_pages', 0);
    }

    public function test_public_home_hides_hero_description_but_editor_preview_keeps_it(): void
    {
        $description = 'Descubre las piezas y accesorios de MotoZone.';
        HomePage::factory()->create(['content' => ['hero_description' => $description]]);

        $this->get(route('home'))->assertOk()
            ->assertDontSee('<p class="home-hero-description', false)
            ->assertSee('<meta name="description" content="'.$description.'">', false);

        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.home.preview'))->assertOk()
            ->assertSee('<p class="home-hero-description', false)
            ->assertSee('>'.$description.'</p>', false);
    }

    public function test_home_escapes_edited_text_and_card_content(): void
    {
        $content = [
            'hero_eyebrow' => '<script>alert("eyebrow")</script>',
            'hero_title' => '<script>alert("title")</script>',
            'hero_description' => '"><script>alert("description")</script>',
            'hero_image_alt' => '" onload="alert(1)',
            'categories_title' => '<script>alert("categories-title")</script>',
            'products_title' => '<script>alert("products-title")</script>',
            'new_title' => '<script>alert("new-title")</script>',
        ];
        HomePage::query()->create(['content' => $content, 'hero_image_path' => 'home/hero.jpg']);
        HomeCard::factory()->category()->create(['title' => 'Categoría']);
        HomeCard::factory()->create(['title' => 'Producto']);
        HomeCard::factory()->novelty()->create(['title' => 'Novedad']);
        $card = HomeCard::factory()->novelty()->create([
            'title' => '<script>alert("card")</script>',
            'description' => '<script>alert("card-description")</script>',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        foreach ([...array_values($content), $card->title, $card->description] as $text) {
            $response->assertSee($text);
            $response->assertDontSee($text, false);
        }
        $response->assertSee('<meta name="description" content="'.e($content['hero_description']).'">', false);
        $response->assertSee('alt="'.e($content['hero_image_alt']).'"', false);
    }

    public function test_home_omits_catalogue_cta_and_renders_configured_contact_buttons_without_deleting_hero_configuration(): void
    {
        $page = HomePage::factory()->create(['content' => [
            'hero_button' => 'Ver novedades', 'hero_button_url' => '/#nuevos',
            'whatsapp' => '+525576543210',
            'email' => 'admin@example.test',
        ]]);
        HomeCard::factory()->category()->create();

        $this->get(route('home'))->assertOk()
            ->assertDontSee('Ver novedades')
            ->assertDontSee('href="/#nuevos"', false)
            ->assertDontSee('Explorar catálogo')
            ->assertDontSee('type="search"', false)
            ->assertDontSee('home-hazard-strip', false)
            ->assertSee('https://wa.me/525576543210', false)
            ->assertSee('Cotizar por WhatsApp')
            ->assertSee('Gmail')
            ->assertSee('href="https://mail.google.com/mail/?view=cm&amp;fs=1&amp;to=admin%40example.test&amp;su=Cotizaci%C3%B3n%20de%20refacciones%20MotoZone"', false)
            ->assertSee('Acceso al panel');

        $this->assertSame('Ver novedades', $page->fresh()->text('hero_button'));
        $this->assertSame('/#nuevos', $page->fresh()->text('hero_button_url'));
    }

    public function test_home_renders_uploaded_images_and_cards_without_photos(): void
    {
        HomePage::query()->create(['content' => ['hero_image_alt' => 'Taller MotoZone'], 'hero_image_path' => 'home/taller.jpg']);
        $withImage = HomeCard::factory()->create(['title' => 'Balata fotografiada', 'image_path' => 'home/cards/balata.jpg']);
        $withoutImage = HomeCard::factory()->create(['title' => 'Pieza sin fotografía', 'image_path' => null]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<img src="'.Storage::disk('public')->url('home/taller.jpg').'" alt="Taller MotoZone"', false);
        $response->assertSee(Storage::disk('public')->url($withImage->image_path), false);
        $response->assertSee($withoutImage->title);
    }

    public function test_home_uses_manual_prices_and_links_the_linked_product(): void
    {
        $product = Product::factory()->create(['name' => 'Producto real', 'price' => 999]);
        $card = HomeCard::factory()->create([
            'title' => 'Cadena reforzada',
            'price' => 689.00,
            'sale_price' => 599.00,
            'badge' => 'Más vendido',
            'description' => 'Descripción de la card',
            'product_id' => $product->id,
        ]);
        $plainCard = HomeCard::factory()->create(['title' => 'Bujía de iridio', 'price' => 219.00, 'sale_price' => null]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('$599.00');
        $response->assertSee('$689.00');
        $response->assertSee('$219.00');
        $response->assertSee(route('catalog.show', $product), false);
        $response->assertDontSee('Más vendido');
        $response->assertDontSee('Descripción de la card');
        $this->assertSame($product->id, $card->fresh()->product_id);
        $this->assertNull($plainCard->fresh()->product_id);
    }

    public function test_promotional_categories_do_not_depend_on_pos_categories(): void
    {
        Category::factory()->create(['name' => 'Categoría del POS', 'home_visible' => true]);
        $card = HomeCard::factory()->category()->create(['title' => 'Frenos de temporada', 'link_url' => '/#destacados']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Frenos de temporada');
        $this->assertDatabaseCount('home_cards', 1);
        $this->assertSame([$card->id], $response->viewData('categoryCards')->modelKeys());
    }

    #[TestWith(['/catalogo'])]
    #[TestWith(['/catalogo?categoria=frenos'])]
    #[TestWith(['/catalogo/'])]
    #[TestWith(['https://motozone.example/catalogo?categoria=frenos'])]
    public function test_home_keeps_manual_cards_without_rendering_legacy_catalog_links(string $url): void
    {
        HomePage::query()->create(['content' => ['hero_button' => 'Catálogo anterior', 'hero_button_url' => $url]]);
        $category = HomeCard::factory()->category()->create(['title' => 'Categoría manual', 'link_url' => $url]);
        HomeCard::factory()->create(['title' => 'Selección manual', 'link_url' => $url]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Categoría manual')
            ->assertSee('Selección manual')
            ->assertDontSee('Catálogo anterior')
            ->assertDontSee('href="'.$url.'"', false);
        $this->assertNull($category->destination());
        $this->assertSame($url, $category->fresh()->link_url);
    }

    public function test_guests_see_login_access_from_the_home(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Acceso al panel');
        $response->assertSee('Iniciar sesión');
        $response->assertSee(route('login'), false);
    }

    public function test_authenticated_admin_sees_panel_access_instead_of_login(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('admin.dashboard'), false);
        $response->assertDontSee('Iniciar sesión');
    }

    public function test_home_uses_saved_contact_in_buttons_and_both_bubbles(): void
    {
        HomePage::factory()->create(['content' => [
            'store_name' => 'Mostrador de prueba', 'store_description' => 'Atención local',
            'phone' => '+52 (55) 7654-3210', 'whatsapp' => '+52 55 7654 3210',
            'email' => 'cotizaciones+home@example.test', 'whatsapp_message' => 'Hola & cotización',
            'hours' => "Lunes a viernes\nSábado por la mañana", 'show_location' => true,
            'address' => 'Dirección de prueba', 'show_whatsapp_bubble' => true, 'show_email_bubble' => true,
        ]]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('home-storefront', false)
            ->assertSee('Mostrador de prueba')
            ->assertSee('Dirección de prueba')
            ->assertSee('tel:+525576543210', false)
            ->assertSee('https://wa.me/525576543210?text=Hola%20%26%20cotizaci%C3%B3n', false)
            ->assertSee('mailto:cotizaciones%2Bhome@example.test?subject=Cotizaci%C3%B3n%20de%20refacciones%20MotoZone', false)
            ->assertSee('aria-label="Chatear en WhatsApp"', false)
            ->assertSee('aria-label="Escríbenos por correo"', false)
            ->assertDontSee('wa.me/525512345678', false)
            ->assertDontSee('Av. de los Talleres 245');
    }

    public function test_home_hides_empty_or_disabled_contact_bubbles_and_new_blocks(): void
    {
        HomePage::factory()->create(['content' => [
            'show_whatsapp_bubble' => true, 'show_email_bubble' => true,
            'show_location' => true, 'show_benefits' => true, 'show_metrics' => true, 'show_advice' => true, 'show_notice' => true,
        ]]);

        $response = $this->get(route('home'));

        $response->assertOk()->assertDontSee('https://wa.me/', false)->assertDontSee('mailto:', false)
            ->assertDontSee('mail.google.com', false)
            ->assertDontSee('<iframe', false)->assertDontSee('id="ubicacion"', false);

        HomePage::current()->update(['content' => [
            'whatsapp' => '+525576543210', 'email' => 'contact@example.test',
            'show_whatsapp_bubble' => false, 'show_email_bubble' => false,
            'show_location' => false, 'address' => 'Dirección oculta',
            'benefit_1_title' => 'Beneficio oculto', 'metric_1_value' => 'Indicador oculto',
            'advice_title' => 'Asesoría oculta', 'store_notice' => 'Aviso oculto',
        ]]);

        $this->get(route('home'))->assertOk()
            ->assertDontSee('aria-label="Chatear en WhatsApp"', false)
            ->assertDontSee('aria-label="Escríbenos por correo"', false)
            ->assertDontSee('id="ubicacion"', false)
            ->assertDontSee('Beneficio oculto')->assertDontSee('Indicador oculto')
            ->assertDontSee('Asesoría oculta')->assertDontSee('Aviso oculto');
    }

    #[TestWith(['google', ['map_embed_url' => 'https://www.google.com/maps/embed?pb=test-location'], 'https://www.google.com/maps/embed?pb=test-location'])]
    #[TestWith(['pin', ['latitude' => 0, 'longitude' => 0], 'marker=0%2C0'])]
    public function test_home_renders_the_configured_map_without_fake_marker_overlay(string $mode, array $coordinates, string $expected): void
    {
        HomePage::factory()->create(['content' => ['show_location' => true, 'map_mode' => $mode, 'address' => 'Mi dirección', ...$coordinates]]);

        $response = $this->get(route('home'));

        $response->assertOk()->assertSee('id="ubicacion"', false)->assertSee('<iframe', false)
            ->assertSee('loading="lazy"', false)->assertSee($expected, false)->assertSee('Mi dirección');
        if ($mode === 'pin') {
            $response->assertSee('OpenStreetMap')->assertSee('destination=0%2C0', false);
        }
    }

    #[TestWith(['https://www.google.com.evil.test/maps/embed?pb=test'])]
    #[TestWith(['https://maps.app.goo.gl/short'])]
    #[TestWith(['https://user@www.google.com/maps/embed?pb=test'])]
    #[TestWith(['https://www.google.com/maps/embed?pb[]=test'])]
    public function test_home_never_embeds_untrusted_or_non_embed_saved_urls(string $url): void
    {
        HomePage::factory()->create(['content' => ['show_location' => true, 'address' => 'Dirección disponible', 'map_mode' => 'google', 'map_embed_url' => $url]]);

        $this->get(route('home'))->assertOk()->assertSee('Dirección disponible')->assertDontSee('<iframe', false);
    }

    public function test_complementary_home_content_is_escaped_and_opt_in(): void
    {
        $script = '<script>alert("contenido")</script>';
        HomePage::factory()->create(['content' => [
            'store_name' => $script, 'store_description' => $script,
            'show_location' => true, 'address' => $script, 'location_reference' => $script, 'hours' => $script,
            'show_notice' => true, 'store_notice' => $script,
            'show_benefits' => true, 'benefit_1_title' => $script, 'benefit_1_description' => $script,
            'show_metrics' => true, 'metric_1_value' => $script, 'metric_1_label' => $script,
            'show_advice' => true, 'advice_title' => $script, 'advice_description' => $script,
        ]]);

        $this->get(route('home'))->assertOk()->assertSee($script)->assertDontSee($script, false);
    }

    public function test_contact_helpers_encode_reserved_characters_and_reject_invalid_saved_numbers(): void
    {
        $page = new HomePage(['content' => [
            'email' => 'a?b@example.test', 'phone' => '+52 (55) 7654-3210',
            'whatsapp' => '+52 55 7654 3210', 'whatsapp_message' => 'Pieza A&B #1',
        ]]);

        $this->assertSame('mailto:a%3Fb@example.test?subject=Cotizaci%C3%B3n%20de%20refacciones%20MotoZone', $page->emailUrl());
        $gmailPage = new HomePage(['content' => ['email' => 'cotizaciones+home@example.test']]);
        $this->assertSame('https://mail.google.com/mail/?view=cm&fs=1&to=cotizaciones%2Bhome%40example.test&su=Cotizaci%C3%B3n%20de%20refacciones%20MotoZone', $gmailPage->gmailComposeUrl());
        $this->assertSame('tel:+525576543210', $page->phoneUrl());
        $this->assertSame('https://wa.me/525576543210?text=Pieza%20A%26B%20%231', $page->whatsappUrl());
        $this->assertSame('https://wa.me/525576543210', $page->whatsappUrl(''));

        $page->content = ['phone' => 'call me', 'whatsapp' => '0bad-number', 'email' => "mail@example.test\r\nBcc: other@example.test"];

        $this->assertNull($page->phoneUrl());
        $this->assertNull($page->whatsappUrl());
        $this->assertNull($page->emailUrl());
        $this->assertNull($page->gmailComposeUrl());
    }

    public function test_preview_uses_home_variant_without_writing_contact_or_examples(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.home.preview'))->assertOk()
            ->assertSee('home-storefront', false)->assertSee('Vista de ejemplo.')
            ->assertDontSee('https://wa.me/', false)->assertDontSee('mailto:', false);

        $this->assertDatabaseCount('home_pages', 0);
        $this->assertDatabaseCount('home_cards', 0);
    }
}
