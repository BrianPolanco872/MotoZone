<?php

namespace Tests\Feature;

use App\Models\HomeCard;
use App\Models\HomePage;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeFamilyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_family_first_with_generated_slug_and_icon(): void
    {
        $existing = HomeCard::factory()->category()->create(['sort_order' => 0]);
        $this->actingAs($this->admin());

        $response = $this->post(route('admin.home.cards.store'), $this->payload([
            'title' => 'Motor y Transmisión', 'icon' => 'engine',
        ]));

        $card = HomeCard::query()->where('title', 'Motor y Transmisión')->sole();
        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.home.edit', ['family' => $card->id]).'#categorias');
        $this->assertSame('motor-y-transmision', $card->slug);
        $this->assertSame('engine', $card->icon);
        $this->assertSame(0, $card->sort_order);
        $this->assertSame(1, $existing->fresh()->sort_order);
        $this->assertSame([$card->id, $existing->id], HomeCard::query()->ordered()->pluck('id')->all());
        $this->assertDatabaseCount('home_card_product', 0);
    }

    public function test_hidden_family_is_appended_and_goes_first_only_when_published(): void
    {
        $existing = HomeCard::factory()->category()->create(['sort_order' => 0]);
        $this->actingAs($this->admin());

        $this->post(route('admin.home.cards.store'), $this->payload(['title' => 'Familia oculta', 'is_active' => '0']))
            ->assertSessionHasNoErrors();
        $card = HomeCard::query()->where('title', 'Familia oculta')->sole();
        $this->assertSame(1, $card->sort_order);

        $this->put(route('admin.home.cards.update', $card), $this->payload(['title' => 'Familia oculta']))
            ->assertSessionHasNoErrors();
        $this->assertSame(0, $card->fresh()->sort_order);
        $this->assertSame(1, $existing->fresh()->sort_order);
        $this->assertSame([$card->id, $existing->id], HomeCard::query()->ordered()->pluck('id')->all());
    }

    public function test_admin_edits_family_using_real_section_and_ignores_removed_fields(): void
    {
        $card = HomeCard::factory()->category()->create(['slug' => 'motor', 'icon' => 'engine', 'sort_order' => 3]);
        $this->actingAs($this->admin());

        $this->put(route('admin.home.cards.update', $card), $this->payload([
            'section' => 'products', 'title' => 'Frenos', 'icon' => 'brake', 'slug' => 'frenos', 'sort_order' => 12,
        ]))->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.home.edit', ['family' => $card->id]).'#categorias');

        $card->refresh();
        $this->assertSame('categories', $card->section);
        $this->assertSame('motor', $card->slug);
        $this->assertSame('brake', $card->icon);
        $this->assertSame(3, $card->sort_order);
    }

    public function test_duplicate_family_titles_receive_unique_anchors(): void
    {
        HomeCard::factory()->category()->create(['title' => 'Frenos', 'slug' => 'frenos']);
        $this->actingAs($this->admin());

        $this->post(route('admin.home.cards.store'), $this->payload(['title' => 'Frenos']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('home_cards', 2);
        $this->assertDatabaseHas('home_cards', ['title' => 'Frenos', 'slug' => 'frenos-2']);
    }

    public function test_invalid_family_icon_is_rejected(): void
    {
        HomeCard::factory()->category()->create();
        $this->actingAs($this->admin());

        $this->post(route('admin.home.cards.store'), $this->payload(['icon' => '<svg onload=alert(1)>']))
            ->assertSessionHasErrors('icon');

        $this->assertDatabaseCount('home_cards', 1);
    }

    public function test_family_editor_omits_removed_fields_and_keeps_saved_values(): void
    {
        $card = HomeCard::factory()->category()->create([
            'subtitle' => 'Subtítulo histórico', 'badge' => '+80 productos',
            'link_url' => '/#destacados', 'link_label' => 'Ver más',
        ]);
        $this->actingAs($this->admin());

        $this->get(route('admin.home.edit', ['family' => $card->id]))->assertOk()
            ->assertSee('name="title"', false)
            ->assertSee('name="icon"', false)
            ->assertDontSee('name="slug"', false)
            ->assertDontSee('name="sort_order"', false)
            ->assertDontSee('name="subtitle" value="Subtítulo histórico"', false)
            ->assertDontSee('name="badge" value="+80 productos"', false)
            ->assertDontSee('name="link_url" value="/#destacados"', false)
            ->assertDontSee('name="link_label" value="Ver más"', false);

        $this->put(route('admin.home.cards.update', $card), $this->payload(['title' => 'Frenos']))
            ->assertSessionHasNoErrors();

        $card->refresh();
        $this->assertSame('Subtítulo histórico', $card->subtitle);
        $this->assertSame('+80 productos', $card->badge);
        $this->assertSame('/#destacados', $card->link_url);
        $this->assertSame('Ver más', $card->link_label);
    }

    public function test_family_fields_are_ignored_for_other_sections_even_when_section_is_spoofed(): void
    {
        $card = HomeCard::factory()->create(['sort_order' => 7]);
        $this->actingAs($this->admin());

        $this->put(route('admin.home.cards.update', $card), $this->payload([
            'slug' => 'no-usar', 'icon' => 'invalid', 'sort_order' => 42,
        ]))->assertSessionHasNoErrors();

        $card->refresh();
        $this->assertSame('products', $card->section);
        $this->assertNull($card->slug);
        $this->assertNull($card->icon);
        $this->assertSame(7, $card->sort_order);
    }

    public function test_linking_is_idempotent_and_does_not_modify_inventory(): void
    {
        $card = HomeCard::factory()->category()->create();
        $product = Product::factory()->create(['stock' => 8, 'price' => 125]);
        $before = $product->getAttributes();
        $this->actingAs($this->admin());

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->post(route('admin.home.categories.products.store', $card), ['product_id' => $product->id])
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('admin.home.edit', ['family' => $card->id]).'#categorias');
        }

        $this->assertDatabaseCount('home_card_product', 1);
        $this->assertSame([$product->id], $card->linkedProducts->modelKeys());
        $this->assertSame($before, $product->fresh()->getAttributes());
    }

    public function test_a_product_can_belong_to_multiple_families_and_detaching_does_not_delete_it(): void
    {
        $first = HomeCard::factory()->category()->create();
        $second = HomeCard::factory()->category()->create();
        $product = Product::factory()->create();
        $this->actingAs($this->admin());
        $this->post(route('admin.home.categories.products.store', $first), ['product_id' => $product->id])
            ->assertSessionHasNoErrors();
        $this->post(route('admin.home.categories.products.store', $second), ['product_id' => $product->id])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('home_card_product', 2);

        $this->delete(route('admin.home.categories.products.destroy', [$first, $product]))
            ->assertRedirect(route('admin.home.edit', ['family' => $first->id]).'#categorias');

        $this->assertModelExists($product);
        $this->assertDatabaseMissing('home_card_product', ['home_card_id' => $first->id, 'product_id' => $product->id]);
        $this->assertDatabaseHas('home_card_product', ['home_card_id' => $second->id, 'product_id' => $product->id]);
    }

    public function test_only_active_existing_products_can_be_linked(): void
    {
        $card = HomeCard::factory()->category()->create();
        $inactive = Product::factory()->create(['is_active' => false]);
        $this->actingAs($this->admin());

        $this->post(route('admin.home.categories.products.store', $card), ['product_id' => $inactive->id])
            ->assertSessionHasErrors('product_id');
        $this->post(route('admin.home.categories.products.store', $card), ['product_id' => 999999])
            ->assertSessionHasErrors('product_id');
        $this->assertDatabaseCount('home_card_product', 0);
    }

    public function test_products_move_stably_in_both_directions_and_append_after_existing_links(): void
    {
        $card = HomeCard::factory()->category()->create();
        [$first, $second, $third] = Product::factory()->count(3)->create()->all();
        $card->linkedProducts()->attach([$first->id => ['sort_order' => 9], $second->id => ['sort_order' => 9]]);
        $this->actingAs($this->admin());
        $this->post(route('admin.home.categories.products.store', $card), ['product_id' => $third->id])
            ->assertSessionHasNoErrors();
        $this->assertSame([$first->id, $second->id, $third->id], $card->linkedProducts()->get()->modelKeys());

        $this->post(route('admin.home.categories.products.move', [$card, $third]), ['direction' => 'up'])
            ->assertRedirect(route('admin.home.edit', ['family' => $card->id]).'#categorias');
        $this->assertSame([$first->id, $third->id, $second->id], $card->linkedProducts()->get()->modelKeys());
        $this->assertSame([0, 1, 2], $card->linkedProducts()->get()->pluck('pivot.sort_order')->all());

        $this->post(route('admin.home.categories.products.move', [$card, $third]), ['direction' => 'down'])
            ->assertSessionHasNoErrors();
        $this->assertSame([$first->id, $second->id, $third->id], $card->linkedProducts()->get()->modelKeys());
        $this->post(route('admin.home.categories.products.move', [$card, $first]), ['direction' => 'up'])
            ->assertSessionHasNoErrors();
        $this->assertSame([$first->id, $second->id, $third->id], $card->linkedProducts()->get()->modelKeys());
        $this->post(route('admin.home.categories.products.move', [$card, $first]), ['direction' => 'side'])
            ->assertSessionHasErrors('direction');
    }

    public function test_detach_and_move_require_membership(): void
    {
        $card = HomeCard::factory()->category()->create();
        $product = Product::factory()->create();
        $this->actingAs($this->admin());

        $this->delete(route('admin.home.categories.products.destroy', [$card, $product]))->assertNotFound();
        $this->post(route('admin.home.categories.products.move', [$card, $product]), ['direction' => 'up'])->assertNotFound();

        $this->assertModelExists($product);
        $this->assertDatabaseCount('home_card_product', 0);
    }

    public function test_membership_endpoints_reject_other_card_sections(): void
    {
        $card = HomeCard::factory()->create();
        $product = Product::factory()->create();
        $this->actingAs($this->admin());

        $this->post(route('admin.home.categories.products.store', $card), ['product_id' => $product->id])->assertNotFound();
        $this->delete(route('admin.home.categories.products.destroy', [$card, $product]))->assertNotFound();
        $this->post(route('admin.home.categories.products.move', [$card, $product]), ['direction' => 'up'])->assertNotFound();

        $this->assertDatabaseCount('home_card_product', 0);
    }

    public function test_guests_cannot_manage_memberships(): void
    {
        $card = HomeCard::factory()->category()->create();
        $product = Product::factory()->create();

        $this->post(route('admin.home.categories.products.store', $card), ['product_id' => $product->id])->assertRedirect(route('login'));
        $this->delete(route('admin.home.categories.products.destroy', [$card, $product]))->assertRedirect(route('login'));
        $this->post(route('admin.home.categories.products.move', [$card, $product]), ['direction' => 'up'])->assertRedirect(route('login'));

        $this->assertDatabaseCount('home_card_product', 0);
    }

    public function test_employees_cannot_manage_memberships(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $card = HomeCard::factory()->category()->create();
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create()->assignRole('empleado'));

        $this->post(route('admin.home.categories.products.store', $card), ['product_id' => $product->id])->assertForbidden();
        $this->delete(route('admin.home.categories.products.destroy', [$card, $product]))->assertForbidden();
        $this->post(route('admin.home.categories.products.move', [$card, $product]), ['direction' => 'up'])->assertForbidden();

        $this->assertDatabaseCount('home_card_product', 0);
    }

    public function test_home_loads_only_active_members_and_uses_family_anchor_without_catalog(): void
    {
        $card = HomeCard::factory()->category()->create(['slug' => 'motor', 'link_url' => '/catalogo']);
        $active = Product::factory()->create();
        $inactive = Product::factory()->create(['is_active' => false]);
        $card->linkedProducts()->attach([$active->id, $inactive->id]);
        HomeCard::factory()->category()->create(['is_active' => false]);
        $other = HomeCard::factory()->create();

        $response = $this->get(route('home'))->assertOk();

        $categories = $response->viewData('categoryCards');
        $this->assertSame([$card->id], $categories->modelKeys());
        $this->assertSame([$active->id], $categories->sole()->linkedProducts->modelKeys());
        $this->assertSame(route('home').'#familia-'.$card->id.'-motor', $categories->sole()->destination());
        $this->assertFalse($response->viewData('productCards')->find($other->id)->relationLoaded('linkedProducts'));
        $response->assertDontSee('href="/catalogo"', false);
        $this->get('/catalogo')->assertNotFound();
    }

    public function test_disabled_families_are_not_loaded_or_shown(): void
    {
        HomePage::query()->create(['content' => ['show_categories' => false]]);
        $card = HomeCard::factory()->category()->create(['title' => 'Familia deshabilitada']);
        $card->linkedProducts()->attach(Product::factory()->create());

        $response = $this->get(route('home'))->assertOk();

        $this->assertCount(0, $response->viewData('categoryCards'));
        $response->assertDontSee('Familia deshabilitada');
    }

    public function test_family_without_slug_uses_id_and_empty_family_keeps_existing_fallback(): void
    {
        $card = HomeCard::factory()->category()->create(['link_url' => '/#nuevos']);
        $card->load(['product', 'linkedProducts']);
        $this->assertSame('familia-'.$card->id, $card->familyAnchor());
        $this->assertSame('/#nuevos', $card->destination());
        $card->linkedProducts()->attach(Product::factory()->create());
        $card->load('linkedProducts');
        $this->assertSame(route('home').'#familia-'.$card->id, $card->destination());
    }

    public function test_numeric_slugs_do_not_collide_with_legacy_family_anchors(): void
    {
        $legacy = HomeCard::factory()->category()->create();
        $numeric = HomeCard::factory()->category()->create(['slug' => (string) $legacy->id]);

        $this->assertSame('familia-'.$legacy->id, $legacy->familyAnchor());
        $this->assertSame('familia-'.$numeric->id.'-'.$legacy->id, $numeric->familyAnchor());
        $this->assertNotSame($legacy->familyAnchor(), $numeric->familyAnchor());
    }

    public function test_editor_selects_only_category_ids_and_loads_inactive_members(): void
    {
        $first = HomeCard::factory()->category()->create(['sort_order' => 0]);
        $second = HomeCard::factory()->category()->create(['sort_order' => 1]);
        $other = HomeCard::factory()->create();
        $inactive = Product::factory()->create(['is_active' => false]);
        $second->linkedProducts()->attach($inactive);
        $this->actingAs($this->admin());

        $response = $this->get(route('admin.home.edit', ['family' => $second->id]))->assertOk();

        $this->assertSame($second->id, $response->viewData('selectedCategory')->id);
        $this->assertSame([$inactive->id], $response->viewData('selectedCategory')->linkedProducts->modelKeys());
        $response = $this->get(route('admin.home.edit', ['family' => $other->id]))->assertOk();
        $this->assertSame($first->id, $response->viewData('selectedCategory')->id);
        $response = $this->withSession(['_old_input' => ['section' => 'categories', 'card_id' => $second->id]])
            ->get(route('admin.home.edit', ['family' => $first->id]))->assertOk();
        $this->assertSame($second->id, $response->viewData('selectedCategory')->id);
        $response = $this->withSession(['_old_input' => ['section' => 'products', 'card_id' => $first->id]])
            ->get(route('admin.home.edit', ['family' => $second->id]))->assertOk();
        $this->assertSame($second->id, $response->viewData('selectedCategory')->id);
    }

    public function test_category_move_retains_selection(): void
    {
        $card = HomeCard::factory()->category()->create();
        $this->actingAs($this->admin());

        $this->post(route('admin.home.cards.move', $card), ['direction' => 'up'])
            ->assertRedirect(route('admin.home.edit', ['family' => $card->id]).'#categorias');
    }

    public function test_editor_without_families_has_no_selection_and_preview_does_not_create_them(): void
    {
        $this->actingAs($this->admin());

        $response = $this->get(route('admin.home.edit'))->assertOk();
        $this->assertNull($response->viewData('selectedCategory'));
        $this->get(route('admin.home.preview'))->assertOk()
            ->assertSee('Motor y Transmisión')
            ->assertSee('Frenos y Suspensión')
            ->assertSee('Filtros y Aceites')
            ->assertSee('Eléctrico y Baterías')
            ->assertSee('Accesorios y Cascos');
        $this->assertDatabaseCount('home_cards', 0);
        $this->assertDatabaseCount('home_card_product', 0);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_replace(['section' => 'categories', 'title' => 'Familia editorial', 'is_active' => '1'], $overrides);
    }

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        return User::factory()->create()->assignRole('admin');
    }
}
