<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $stockFilter = (string) $request->query('stock', '');

        $products = Product::query()
            ->with('category')
            ->search($search)
            ->when($request->query('categoria'), fn (Builder $query, string $slug) => $query->whereHas('category', fn (Builder $query) => $query->where('slug', $slug)))
            ->when($stockFilter === 'bajo', fn (Builder $query) => $query->whereBetween('stock', [1, Product::LOW_STOCK_THRESHOLD]))
            ->when($stockFilter === 'agotado', fn (Builder $query) => $query->where('stock', 0))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'search' => $search,
            'stockFilter' => $stockFilter,
        ]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Verificar si el producto tiene movimientos antes de permitir el borrado
        if ($product->saleItems()->exists() || $product->stockMovements()->exists()) {
            return redirect()
                ->route('admin.products.index')
                ->with('error', "No se puede eliminar «{$product->name}» porque tiene ventas o movimientos registrados.");
        }

        $name = $product->name;
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('status', "Producto «{$name}» eliminado correctamente.");
    }

    public function massUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'margin_percentage' => ['required', 'numeric', 'min:0'],
        ]);

        $taxRate = round(((float) $request->input('tax_rate')) / 100, 4);
        $margin = (float) $request->input('margin_percentage');

        Product::query()->each(function ($product) use ($taxRate, $margin) {
            if ($product->cost > 0) {
                $price = round($product->cost * (1 + $margin / 100) * (1 + $taxRate), 2);
                $product->update([
                    'price' => $price,
                    'tax_rate' => $taxRate,
                ]);
            } else {
                $product->update(['tax_rate' => $taxRate]);
            }
        });

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Se actualizaron los precios e IVA de todos los productos con costo capturado.');
    }

    public function clearInventory(): RedirectResponse
    {
        // Primero eliminamos movimientos y otros registros dependientes para evitar errores de integridad
        StockMovement::query()->delete();
        SaleItem::query()->delete();
        Sale::query()->delete();

        // Ahora borramos todos los productos
        Product::query()->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Todo el catálogo de productos y el historial asociado han sido eliminados.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $path = $request->file('import_file')->getRealPath();
        $header = null;
        $created = 0;
        $skipped = 0;

        if (($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (! $header) {
                    $header = $row;

                    continue;
                }

                $mappedData = [];
                foreach ($header as $index => $colName) {
                    $mappedData[strtolower(trim($colName))] = $row[$index] ?? null;
                }

                $sku = trim($mappedData['sku'] ?? $mappedData['codigo'] ?? '');

                if ($sku === '') {
                    $skipped++;

                    continue;
                }

                $exists = Product::query()->where('sku', $sku)->exists();
                if ($exists) {
                    $skipped++;

                    continue;
                }

                $category = null;
                $categoryName = trim($mappedData['categoria'] ?? $mappedData['departamento'] ?? '');
                if ($categoryName !== '') {
                    $category = Category::query()->firstOrCreate(
                        ['name' => $categoryName],
                        ['slug' => Str::slug($categoryName)]
                    );
                } else {
                    // Fallback a una categoría por defecto si no existe
                    $category = Category::query()->firstOrCreate(
                        ['name' => 'Sin categoría'],
                        ['slug' => 'sin-categoria']
                    );
                }

                Product::create([
                    'category_id' => $category->id,
                    'name' => trim($mappedData['nombre'] ?? $mappedData['producto'] ?? $mappedData['descripcion'] ?? ''),
                    'sku' => $sku,
                    'barcode' => trim($mappedData['codigo_barras'] ?? $mappedData['barcode'] ?? null),
                    'brand' => trim($mappedData['marca'] ?? $mappedData['brand'] ?? ''),
                    'description' => trim($mappedData['descripcion'] ?? $mappedData['descripción'] ?? null),
                    'cost' => (float) ($mappedData['costo'] ?? $mappedData['cost'] ?? $mappedData['precio costo'] ?? 0),
                    'price' => (float) ($mappedData['precio_venta'] ?? $mappedData['price'] ?? $mappedData['precio venta'] ?? 0),
                    'wholesale_price' => (float) ($mappedData['precio_mayoreo'] ?? $mappedData['wholesale_price'] ?? $mappedData['precio mayoreo'] ?? 0),
                    'stock' => (int) ($mappedData['stock'] ?? $mappedData['inventario'] ?? 0),
                    'min_stock' => (int) ($mappedData['min_stock'] ?? $mappedData['inventario minio'] ?? $mappedData['inventario mínimo'] ?? 0),
                    'tax_rate' => round(((float) ($mappedData['iva'] ?? $mappedData['tax_rate'] ?? 16)) / 100, 4),
                    'track_inventory' => true,
                    'slug' => Str::slug(($sku ?: uniqid()).'-'.uniqid()),
                    'is_active' => true,
                ]);

                $created++;
            }
            fclose($handle);
        }

        return redirect()
            ->route('admin.products.index')
            ->with('status', "Importación completada: {$created} productos creados, {$skipped} omitidos (duplicados o sin SKU).");
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product,
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $this->productData($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        return redirect()
            ->route('admin.products.index')
            ->with('status', "Producto «{$product->name}» creado correctamente.");
    }

    public function edit(Product $product): View
    {
        $product->load('category');

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $this->productData($request, $product);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');

            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
        }

        $product->update($data);

        return redirect()
            ->route('admin.products.index')
            ->with('status', "Producto «{$product->name}» actualizado correctamente.");
    }

    /**
     * @return array<string, mixed>
     */
    private function productData(ProductRequest $request, ?Product $product = null): array
    {
        $data = $request->safe()->only([
            'category_id',
            'name',
            'sku',
            'barcode',
            'brand',
            'description',
            'price',
            'cost',
            'wholesale_price',
            'stock',
            'min_stock',
            'track_inventory',
            'featured',
            'is_active',
        ]);

        $data['slug'] = $this->uniqueSlug($data['name'], $product);
        $data['tax_rate'] = round(((float) $request->validated('tax_rate')) / 100, 4);

        return $data;
    }

    private function uniqueSlug(string $name, ?Product $product = null): string
    {
        $base = Str::slug($name) ?: 'producto';
        $slug = $base;
        $suffix = 2;

        while (Product::query()
            ->where('slug', $slug)
            ->when($product, fn (Builder $query) => $query->whereKeyNot($product->getKey()))
            ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
