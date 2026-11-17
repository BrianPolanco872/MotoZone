<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $stockFilter = (string) $request->query('stock', '');
        $reviewFilter = (string) $request->query('revision', '');

        $products = Product::query()
            ->search($search)
            ->when($stockFilter === 'bajo', fn (Builder $query) => $query->active()->lowStock())
            ->when($stockFilter === 'agotado', fn (Builder $query) => $query->active()->outOfStock())
            ->when($reviewFilter === 'sin-foto', fn (Builder $query) => $query->active()->missingPhoto())
            ->when($reviewFilter === 'sin-precio', fn (Builder $query) => $query->active()->missingPrice())
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'search' => $search,
            'stockFilter' => $stockFilter,
            'reviewFilter' => $reviewFilter,
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

    public function clearInventory(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $products = Product::query()
                ->where('stock', '!=', 0)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($products as $product) {
                $stockBefore = $product->stock;
                $product->update(['stock' => 0]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => StockMovement::TYPE_ADJUSTMENT,
                    'quantity' => -$stockBefore,
                    'stock_before' => $stockBefore,
                    'stock_after' => 0,
                    'note' => 'Limpieza de inventario: existencias puestas en cero.',
                ]);
            }
        });

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Todas las existencias se pusieron en cero. El catálogo y el historial se conservaron.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $handle = fopen($request->file('import_file')->getRealPath(), 'r');
        $header = null;
        $rows = [];
        $line = 1;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if ($header === null) {
                $header = array_map(
                    fn ($name): string => ltrim(strtolower(trim((string) $name)), "\u{FEFF}"),
                    $row,
                );

                continue;
            }

            $line++;
            $rows[] = ['line' => $line, 'data' => $this->importRow($header, $row)];
        }

        fclose($handle);

        $this->assertImportHeader($header ?? []);

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, &$created, &$skipped): void {
            foreach ($rows as $row) {
                $sku = (string) ($row['data']['sku'] ?? $row['data']['codigo'] ?? '');

                // Política vigente: se omiten las filas sin SKU o con un SKU ya registrado.
                if ($sku === '' || Product::query()->where('sku', $sku)->exists()) {
                    $skipped++;

                    continue;
                }

                $attributes = $this->importAttributes($row['data']);
                $validator = Validator::make($attributes, $this->importRules());

                if ($validator->fails()) {
                    throw ValidationException::withMessages([
                        'import_file' => array_map(
                            fn (string $message): string => "Fila {$row['line']}: {$message}",
                            $validator->errors()->all(),
                        ),
                    ]);
                }

                $validated = $validator->validated();

                Product::create([
                    ...$validated,
                    'sku' => $sku,
                    'tax_rate' => round(((float) $validated['tax_rate']) / 100, 4),
                    'track_inventory' => true,
                    'slug' => Str::slug($sku).'-'.uniqid(),
                    'is_active' => true,
                ]);

                $created++;
            }
        });

        return redirect()
            ->route('admin.products.index')
            ->with('status', "Importación completada: {$created} productos creados, {$skipped} omitidos (duplicados o sin SKU).");
    }

    /**
     * @param  list<string>  $header
     * @param  array<int, string|null>  $values
     * @return array<string, string|null>
     */
    private function importRow(array $header, array $values): array
    {
        $data = [];

        foreach ($header as $index => $column) {
            $value = $values[$index] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $data[$column] = $value === '' ? null : $value;
        }

        return $data;
    }

    /**
     * @param  list<string>  $header
     */
    private function assertImportHeader(array $header): void
    {
        $hasSku = in_array('sku', $header, true) || in_array('codigo', $header, true);
        $hasName = array_intersect(['nombre', 'producto', 'descripcion'], $header) !== [];

        if (! $hasSku || ! $hasName) {
            throw ValidationException::withMessages([
                'import_file' => 'El encabezado del archivo debe incluir las columnas «sku» (o «codigo») y «nombre» (o «producto»).',
            ]);
        }
    }

    /**
     * @param  array<string, string|null>  $data
     * @return array<string, mixed>
     */
    private function importAttributes(array $data): array
    {
        return [
            'name' => $data['nombre'] ?? $data['producto'] ?? $data['descripcion'] ?? null,
            'barcode' => $data['codigo_barras'] ?? $data['barcode'] ?? null,
            'barcode2' => $data['codigo_barras_2'] ?? $data['barcode2'] ?? null,
            'brand' => $data['marca'] ?? $data['brand'] ?? null,
            'description' => $data['descripcion'] ?? null,
            'price' => $data['precio_venta'] ?? $data['price'] ?? $data['precio venta'] ?? null,
            'cost' => $data['costo'] ?? $data['cost'] ?? $data['precio costo'] ?? 0,
            'wholesale_price' => $data['precio_mayoreo'] ?? $data['wholesale_price'] ?? $data['precio mayoreo'] ?? null,
            'stock' => $data['stock'] ?? $data['inventario'] ?? 0,
            'min_stock' => $data['min_stock'] ?? $data['inventario minio'] ?? $data['inventario mínimo'] ?? 0,
            'tax_rate' => $data['iva'] ?? $data['tax_rate'] ?? 16,
        ];
    }

    /**
     * Mismas reglas que valida el alta manual de productos.
     *
     * @return array<string, array<int, mixed>>
     */
    private function importRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode'),
                Rule::unique('products', 'barcode2'),
            ],
            'barcode2' => [
                'nullable',
                'string',
                'max:255',
                'different:barcode',
                Rule::unique('products', 'barcode2'),
                Rule::unique('products', 'barcode'),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'between:0,100'],
        ];
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product,
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
        return view('admin.products.edit', [
            'product' => $product,
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
            'name',
            'barcode',
            'barcode2',
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

        if ($product === null) {
            $data['sku'] = 'AUTO-'.Str::uuid()->toString();
        }

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
