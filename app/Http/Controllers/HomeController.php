<?php

namespace App\Http\Controllers;

use App\Models\HomeCard;
use App\Models\HomePage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Response;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return $this->renderHome();
    }

    public function preview(): Response
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        return response($this->renderHome(true))
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function renderHome(bool $isEditorPreview = false): View
    {
        $homePage = HomePage::current();
        $categoryCards = $this->cards($homePage, 'categories');
        $productCards = $this->cards($homePage, 'products');
        $newCards = $this->cards($homePage, 'new');

        if ($isEditorPreview) {
            $homePage->content = array_replace($homePage->content ?? [], [
                'show_hero' => true,
                'show_categories' => true,
                'show_products' => true,
                'show_new' => true,
            ]);

            $categoryCards = $this->cards($homePage, 'categories', true, HomeCard::CATEGORY_PRESETS);
            $productCards = $this->cards($homePage, 'products', true, [
                ['title' => 'Balata delantera', 'description' => 'Compuesto de alto rendimiento.', 'price' => '349.00'],
                ['title' => 'Cadena reforzada', 'description' => 'Arrastre 428 con retén.', 'price' => '689.00', 'sale_price' => '599.00', 'badge' => 'Más vendido'],
                ['title' => 'Bujía de iridio', 'description' => 'Encendido estable en todo clima.', 'price' => '219.00'],
                ['title' => 'Kit de arrastre', 'description' => 'Piñón, corona y cadena.', 'price' => '1249.00'],
            ]);
            $newCards = $this->cards($homePage, 'new', true, [
                ['title' => 'Casco urbano', 'description' => 'Certificado, con visor solar.', 'price' => '1899.00', 'badge' => 'Nuevo'],
                ['title' => 'Guantes de verano', 'description' => 'Ventilación y protección en nudillos.', 'price' => '549.00'],
            ]);
        }

        return view('home', compact('homePage', 'categoryCards', 'productCards', 'newCards', 'isEditorPreview'));
    }

    /**
     * @param  array<int, array<string, string>>  $examples
     * @return Collection<int, HomeCard>
     */
    private function cards(HomePage $homePage, string $section, bool $withExamples = false, array $examples = []): Collection
    {
        if (! $homePage->enabled($section)) {
            return new Collection;
        }

        $cards = HomeCard::query()->section($section)->active()->with('product')->ordered()->get();

        if ($section === 'categories') {
            $cards->load(['linkedProducts' => function (BelongsToMany $query): void {
                $query->active()->with('kitItems');
            }]);
        }

        if ($withExamples && $cards->isEmpty()) {
            foreach ($examples as $data) {
                $card = new HomeCard([...$data, 'section' => $section, 'is_active' => true]);
                $card->setRelation('product', null);
                if ($section === 'categories') {
                    $card->setRelation('linkedProducts', new Collection);
                }
                $cards->push($card);
            }
        }

        return $cards;
    }
}
