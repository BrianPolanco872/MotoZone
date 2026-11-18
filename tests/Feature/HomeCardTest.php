<?php

namespace Tests\Feature;

use App\Models\HomeCard;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class HomeCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_admin_can_create_cards_per_section_and_they_are_appended_in_order(): void
    {
        $existing = HomeCard::factory()->create(['section' => 'products', 'sort_order' => 4]);
        $this->actingAs($this->admin());

        $response = $this->post(route('admin.home.cards.store'), $this->payload([
            'section' => 'products', 'title' => 'Frenos', 'description' => 'Balatas y discos',
            'link_url' => '/#destacados',
        ]))->assertSessionHasNoErrors();

        $card = HomeCard::query()->where('title', 'Frenos')->sole();
        $response->assertRedirect(route('admin.home.edit').'#destacados');
        $this->assertSame('products', $card->section);
        $this->assertSame(5, $card->sort_order);
        $this->assertSame('/#destacados', $card->destination());
        $this->assertSame($existing->id, HomeCard::query()->ordered()->first()->id);
    }

    public function test_store_validates_section_title_prices_and_product_link(): void
    {
        $inactive = Product::factory()->create(['is_active' => false]);
        $this->actingAs($this->admin());

        $this->post(route('admin.home.cards.store'), $this->payload(['section' => 'otra', 'title' => 'X']))
            ->assertSessionHasErrors('section');
        $this->post(route('admin.home.cards.store'), $this->payload(['title' => '']))
            ->assertSessionHasErrors('title');
        $this->post(route('admin.home.cards.store'), $this->payload(['price' => 100, 'sale_price' => 150]))
            ->assertSessionHasErrors('sale_price');
        $this->post(route('admin.home.cards.store'), $this->payload(['product_id' => $inactive->id]))
            ->assertSessionHasErrors('product_id');
        $this->post(route('admin.home.cards.store'), $this->payload(['link_url' => 'javascript:alert(1)']))
            ->assertSessionHasErrors('link_url');
        $this->post(route('admin.home.cards.store'), $this->payload(['image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('home_cards', 0);
    }

    public function test_card_accepts_six_photos_and_rejects_a_seventh(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.home.cards.store'), $this->payload([
            'section' => 'products',
            'gallery' => array_map(fn (int $i) => $this->image("foto-$i.png"), range(1, 7)),
        ]))->assertSessionHasErrors('gallery');

        $this->assertDatabaseCount('home_cards', 0);

        $this->post(route('admin.home.cards.store'), $this->payload([
            'section' => 'products',
            'gallery' => array_map(fn (int $i) => $this->image("foto-$i.png"), range(1, 6)),
        ]))->assertSessionHasNoErrors();

        $this->assertCount(6, HomeCard::query()->sole()->gallery);
    }

    public function test_card_photo_is_copied_to_the_linked_product_only_when_it_has_no_photo(): void
    {
        Storage::disk('public')->put('products/existing.png', 'own');
        $withoutPhoto = Product::factory()->create();
        $withPhoto = Product::factory()->create(['image_path' => 'products/existing.png']);
        $this->actingAs($this->admin());

        $this->post(route('admin.home.cards.store'), $this->payload([
            'title' => 'Con foto', 'product_id' => $withoutPhoto->id, 'image' => $this->image('card.png'),
        ]))->assertSessionHasNoErrors();

        $card = HomeCard::query()->where('title', 'Con foto')->sole();
        $copied = $withoutPhoto->fresh()->image_path;
        $this->assertNotNull($card->image_path);
        $this->assertSame('products/'.basename($card->image_path), $copied);
        Storage::disk('public')->assertExists($copied);

        $this->post(route('admin.home.cards.store'), $this->payload([
            'title' => 'Foto propia', 'product_id' => $withPhoto->id, 'image' => $this->image('card2.png'),
        ]))->assertSessionHasNoErrors();

        $this->assertSame('products/existing.png', $withPhoto->fresh()->image_path);
        $this->assertCount(2, Storage::disk('public')->allFiles('products'));
    }

    public function test_card_accepts_direct_image_urls_without_changing_linked_product_photo(): void
    {
        $product = Product::factory()->create();
        $imageUrl = 'https://images.example.test/card.jpg';
        $galleryUrl = 'https://images.example.test/gallery.jpg';

        $this->actingAs($this->admin())->post(route('admin.home.cards.store'), $this->payload([
            'product_id' => $product->id,
            'image_url' => $imageUrl,
            'gallery_url' => $galleryUrl,
        ]))->assertSessionHasNoErrors();

        $card = HomeCard::query()->sole();
        $this->assertSame($imageUrl, $card->image_url);
        $this->assertNull($card->image_path);
        $this->assertSame([$imageUrl, $galleryUrl], $card->galleryUrls());
        $this->assertNull($product->fresh()->image_path);
        $this->get(route('home'))->assertOk()->assertSee($imageUrl, false)->assertSee($galleryUrl, false);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_removing_main_photo_keeps_gallery_and_linked_product_photo(bool $useRemoteUrl): void
    {
        Storage::disk('public')->put('home/cards/main.png', 'main');
        Storage::disk('public')->put('products/own.png', 'product');
        $product = Product::factory()->create(['image_path' => 'products/own.png']);
        $photos = ['https://images.example.test/gallery.jpg'];
        $card = HomeCard::factory()->create([
            'image_path' => $useRemoteUrl ? null : 'home/cards/main.png',
            'image_url' => $useRemoteUrl ? 'https://images.example.test/main.jpg' : null,
            'gallery' => $photos,
            'product_id' => $product->id,
        ]);

        $this->actingAs($this->admin())->put(route('admin.home.cards.update', $card), $this->payload([
            'image_url' => '', 'remove_image' => '1', 'product_id' => $product->id,
        ]))->assertRedirect(route('admin.home.edit').'#destacados')->assertSessionHasNoErrors();

        $card->refresh();
        $this->assertNull($card->image_path);
        $this->assertNull($card->image_url);
        $this->assertNull($card->imageUrl());
        $this->assertSame($photos, $card->gallery);
        $this->assertSame($photos, $card->galleryUrls());
        $this->assertSame('products/own.png', $product->fresh()->image_path);
        Storage::disk('public')->assertExists('products/own.png');
    }

    #[TestWith(['products'])]
    #[TestWith(['new'])]
    #[TestWith(['categories'])]
    public function test_gallery_removal_keeps_unselected_photos_and_main_in_each_card_section(string $section): void
    {
        Storage::disk('public')->put('products/own.png', 'product');
        $product = Product::factory()->create(['image_path' => 'products/own.png']);
        $remove = 'https://images.example.test/remove.jpg';
        $keep = 'https://images.example.test/keep.jpg';
        $main = 'https://images.example.test/main.jpg';
        $card = HomeCard::factory()->create([
            'section' => $section, 'gallery' => [$remove, $keep],
            'image_path' => null, 'image_url' => $main, 'product_id' => $product->id,
        ]);

        $this->actingAs($this->admin())->put(route('admin.home.cards.update', $card), $this->payload([
            'section' => $section, 'title' => $card->title, 'remove_gallery' => [$remove],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $card->refresh();
        $this->assertSame([$keep], $card->gallery);
        $this->assertSame($main, $card->imageUrl());
        $this->assertSame($product->id, $card->product_id);
        $this->assertSame('products/own.png', $product->fresh()->image_path);
        Storage::disk('public')->assertExists('products/own.png');
    }

    public function test_card_rejects_non_https_image_url_without_replacing_saved_file(): void
    {
        Storage::disk('public')->put('home/cards/old.png', 'old-image');
        $card = HomeCard::factory()->create(['image_path' => 'home/cards/old.png']);

        $this->actingAs($this->admin())->put(route('admin.home.cards.update', $card), $this->payload([
            'image_url' => 'http://images.example.test/image.jpg',
        ]))->assertSessionHasErrors([
            'image_url' => 'Pega la dirección HTTPS directa de una imagen, sin credenciales ni hosts locales.',
        ]);

        $this->assertSame('home/cards/old.png', $card->fresh()->image_path);
        Storage::disk('public')->assertExists('home/cards/old.png');
    }

    public function test_card_rejects_upload_and_url_for_the_same_photo(): void
    {
        Storage::disk('public')->put('home/cards/old.png', 'old-image');
        $card = HomeCard::factory()->create(['image_path' => 'home/cards/old.png']);

        $this->actingAs($this->admin())->put(route('admin.home.cards.update', $card), $this->payload([
            'image' => $this->image('new.png'),
            'image_url' => 'https://images.example.test/image.jpg',
        ]))->assertSessionHasErrors('image_url');

        $this->assertSame('home/cards/old.png', $card->fresh()->image_path);
        $this->assertSame(['home/cards/old.png'], Storage::disk('public')->allFiles());
    }

    public function test_update_keeps_section_replaces_photos_and_removes_old_files_after_commit(): void
    {
        Storage::disk('public')->put('home/cards/old.png', 'old');
        $card = HomeCard::factory()->create([
            'section' => 'products', 'title' => 'Cadena', 'image_path' => 'home/cards/old.png',
            'gallery' => ['home/cards/keep.png', 'home/cards/drop.png'],
        ]);
        Storage::disk('public')->put('home/cards/keep.png', 'keep');
        Storage::disk('public')->put('home/cards/drop.png', 'drop');
        $this->actingAs($this->admin());

        $this->put(route('admin.home.cards.update', $card), $this->payload([
            'section' => 'new', 'title' => 'Cadena reforzada', 'price' => 689, 'sale_price' => 599,
            'badge' => 'Más vendido', 'image' => $this->image('nueva.png'),
            'remove_gallery' => ['home/cards/drop.png'], 'gallery' => [$this->image('extra.png')],
        ]))->assertRedirect(route('admin.home.edit').'#destacados')->assertSessionHasNoErrors();

        $card->refresh();
        $this->assertSame('products', $card->section);
        $this->assertSame('Cadena reforzada', $card->title);
        $this->assertTrue($card->hasDiscount());
        $this->assertNotSame('home/cards/old.png', $card->image_path);
        Storage::disk('public')->assertMissing('home/cards/old.png');
        Storage::disk('public')->assertMissing('home/cards/drop.png');
        Storage::disk('public')->assertExists('home/cards/keep.png');
        $this->assertContains('home/cards/keep.png', $card->gallery);
        $this->assertCount(2, $card->gallery);
    }

    public function test_update_rejects_more_than_six_photos_when_keeping_existing_ones(): void
    {
        $card = HomeCard::factory()->create([
            'gallery' => array_map(fn (int $i) => "home/cards/$i.png", range(1, 5)),
        ]);
        $this->actingAs($this->admin());

        $this->put(route('admin.home.cards.update', $card), $this->payload([
            'title' => $card->title,
            'gallery' => [$this->image('a.png')],
            'gallery_url' => 'https://images.example.test/extra.jpg',
        ]))->assertSessionHasErrors('gallery');

        $this->assertCount(5, $card->fresh()->gallery);
    }

    public function test_destroy_removes_card_and_its_photos(): void
    {
        Storage::disk('public')->put('home/cards/main.png', 'main');
        Storage::disk('public')->put('home/cards/extra.png', 'extra');
        $card = HomeCard::factory()->create([
            'image_path' => 'home/cards/main.png', 'gallery' => ['home/cards/extra.png'],
        ]);
        $this->actingAs($this->admin());

        $this->delete(route('admin.home.cards.destroy', $card))
            ->assertRedirect(route('admin.home.edit').'#destacados')->assertSessionHasNoErrors();

        $this->assertModelMissing($card);
        Storage::disk('public')->assertMissing('home/cards/main.png');
        Storage::disk('public')->assertMissing('home/cards/extra.png');
    }

    public function test_move_swaps_order_and_normalizes_positions(): void
    {
        $first = HomeCard::factory()->create(['title' => 'A', 'sort_order' => 5]);
        $second = HomeCard::factory()->create(['title' => 'B', 'sort_order' => 9]);
        $third = HomeCard::factory()->create(['title' => 'C', 'sort_order' => 9]);
        $this->actingAs($this->admin());

        $this->post(route('admin.home.cards.move', $third), ['direction' => 'up'])
            ->assertRedirect(route('admin.home.edit').'#destacados')->assertSessionHasNoErrors();

        $this->assertSame(
            [$first->id, $third->id, $second->id],
            HomeCard::query()->ordered()->pluck('id')->all(),
        );
        $this->assertSame([0, 1, 2], HomeCard::query()->ordered()->pluck('sort_order')->all());

        $this->post(route('admin.home.cards.move', $first), ['direction' => 'up'])->assertSessionHasNoErrors();
        $this->assertSame(
            [$first->id, $third->id, $second->id],
            HomeCard::query()->ordered()->pluck('id')->all(),
        );

        $this->post(route('admin.home.cards.move', $first), ['direction' => 'lateral'])->assertSessionHasErrors('direction');
    }

    public function test_inactive_cards_are_stored_but_not_published_and_product_link_is_optional(): void
    {
        $product = Product::factory()->create(['name' => 'Producto real']);
        $this->actingAs($this->admin());

        $this->post(route('admin.home.cards.store'), $this->payload([
            'title' => 'Card oculta', 'is_active' => '0', 'product_id' => $product->id,
        ]))->assertSessionHasNoErrors();

        $card = HomeCard::query()->sole();
        $this->assertFalse($card->is_active);
        $this->assertSame($product->id, $card->product_id);
        $this->get(route('home'))->assertOk()->assertDontSee('Card oculta');

        $card->update(['is_active' => true]);
        $this->get(route('home'))->assertOk()->assertSee('Card oculta')
            ->assertSee(route('catalog.show', $product), false);
    }

    public function test_card_linked_to_a_deactivated_product_stops_linking_to_its_page(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        $card = HomeCard::factory()->create(['title' => 'Refacción descontinuada', 'product_id' => $product->id]);

        $this->assertSame(route('catalog.show', $product), $card->destination());

        $product->update(['is_active' => false]);

        $this->assertNull($card->fresh()->destination());
        $this->get(route('home'))->assertOk()
            ->assertDontSee(route('catalog.show', $product), false);
    }

    public function test_only_admins_can_manage_cards(): void
    {
        $card = HomeCard::factory()->create();

        $this->post(route('admin.home.cards.store'), $this->payload(['title' => 'X']))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create()->assignRole('empleado'));
        $this->post(route('admin.home.cards.store'), $this->payload(['title' => 'X']))->assertForbidden();
        $this->put(route('admin.home.cards.update', $card), $this->payload(['title' => 'X']))->assertForbidden();
        $this->delete(route('admin.home.cards.destroy', $card))->assertForbidden();
        $this->post(route('admin.home.cards.move', $card), ['direction' => 'up'])->assertForbidden();

        $this->assertModelExists($card);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'section' => 'products',
            'title' => 'Card de prueba',
            'is_active' => '1',
        ], $overrides);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function image(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
    }
}
