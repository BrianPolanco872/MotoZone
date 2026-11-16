<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_cards', function (Blueprint $table) {
            $table->id();
            $table->string('section', 20)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true);
            $table->string('title', 160);
            $table->string('subtitle', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->json('gallery')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->string('badge', 60)->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->string('link_label', 80)->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        $this->copyExistingHome();
    }

    public function down(): void
    {
        Schema::dropIfExists('home_cards');
    }

    /** One-time copy of the editorial content that used to live in the POS tables. */
    private function copyExistingHome(): void
    {
        $now = now();

        $categories = DB::table('categories')
            ->where('home_visible', true)
            ->where('slug', '!=', 'sin-categoria')
            ->orderBy('home_position')->orderBy('id')->limit(4)->get();

        foreach ($categories as $index => $category) {
            DB::table('home_cards')->insert([
                'section' => 'categories',
                'sort_order' => $index,
                'is_active' => true,
                'title' => $category->name,
                'description' => $category->description,
                'gallery' => $category->gallery,
                'link_url' => '/catalogo?categoria='.rawurlencode($category->slug),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $products = DB::table('products')
            ->where('home_visible', true)->where('is_active', true)
            ->orderBy('home_position')->orderBy('id')->limit(8)->get();

        foreach ($products as $index => $product) {
            DB::table('home_cards')->insert($this->productCard($product, 'products', $index, $now));
        }

        $newIndex = 0;

        foreach (DB::table('home_publications')->orderBy('position')->orderBy('id')->get() as $publication) {
            DB::table('home_cards')->insert([
                'section' => 'new',
                'sort_order' => $newIndex++,
                'is_active' => (bool) $publication->is_visible,
                'title' => $publication->title,
                'description' => $publication->body,
                'image_path' => $publication->image_path,
                'image_url' => $publication->image_url,
                'gallery' => $publication->gallery,
                'product_id' => $publication->product_id,
                'link_url' => $publication->link_url,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $content = DB::table('home_pages')->where('id', 1)->value('content');
        $newIds = $content ? (array) (json_decode($content, true)['new_product_ids'] ?? []) : [];
        $newProducts = DB::table('products')->whereIn('id', $newIds)->where('is_active', true)->get()->keyBy('id');

        foreach ($newIds as $id) {
            if ($newProducts->has($id)) {
                DB::table('home_cards')->insert($this->productCard($newProducts->get($id), 'new', $newIndex++, $now));
            }
        }
    }

    /** @return array<string, mixed> */
    private function productCard(object $product, string $section, int $position, object $now): array
    {
        return [
            'section' => $section,
            'sort_order' => $position,
            'is_active' => true,
            'title' => $product->name,
            'subtitle' => $product->brand,
            'description' => $product->description,
            'image_path' => $product->image_path,
            'price' => $product->price,
            'link_url' => '/producto/'.$product->slug,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
};
