<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\Quantity;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.products.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('sale_mode') && ! $this->has('kit_items') && $this->route('product')?->isKit()) {
            $this->merge(['kit_items' => $this->route('product')->kitItems->map(fn (Product $component): array => [
                'product_id' => $component->id,
                'quantity' => $component->pivot->quantity,
            ])->all()]);
        }

        $this->merge([
            'featured' => $this->boolean('featured'),
            'is_active' => $this->boolean('is_active'),
            'track_inventory' => $this->has('track_inventory') ? $this->boolean('track_inventory') : true,
            'sale_mode' => $this->input('sale_mode', $this->route('product')?->saleMode() ?? Product::MODE_UNIT),
            'kit_deduct_components' => $this->has('kit_deduct_components')
                ? $this->boolean('kit_deduct_components')
                : ($this->route('product')?->kit_deduct_components ?? false),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $quantity = function (string $attribute, mixed $value, Closure $fail): void {
            if (! Quantity::valid($value, $this->input('sale_mode') === Product::MODE_BULK)) {
                $fail('La cantidad debe ser no negativa, dentro del rango permitido y '.($this->input('sale_mode') === Product::MODE_BULK ? 'con hasta 3 decimales.' : 'entera.'));
            }
        };

        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')->ignore($product?->id),
                Rule::unique('products', 'barcode2')->ignore($product?->id),
            ],
            'barcode2' => [
                'nullable',
                'string',
                'max:255',
                'different:barcode',
                Rule::unique('products', 'barcode2')->ignore($product?->id),
                Rule::unique('products', 'barcode')->ignore($product?->id),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'margin' => ['nullable', 'numeric', 'between:0,100'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'between:0,100'],
            'sale_mode' => ['required', Rule::in([Product::MODE_UNIT, Product::MODE_BULK, Product::MODE_KIT])],
            'kit_deduct_components' => ['boolean'],
            'kit_items' => ['exclude_unless:sale_mode,kit', 'required', 'array', 'min:1'],
            'kit_items.*' => ['array:product_id,quantity'],
            'kit_items.*.product_id' => ['required', 'integer', 'min:1', 'distinct'],
            'kit_items.*.quantity' => ['required'],
            'stock' => ['exclude_if:sale_mode,kit', 'required', $quantity],
            'warehouse_stock' => ['sometimes', 'nullable', $quantity],
            'store_2_stock' => ['sometimes', 'nullable', $quantity],
            'original_stock' => ['sometimes', function (string $attribute, mixed $value, Closure $fail): void {
                if (! Quantity::valid($value)) {
                    $fail('La referencia de existencias no es válida. Recarga el producto.');
                }
            }],
            'min_stock' => ['exclude_if:sale_mode,kit', 'nullable', $quantity],
            'track_inventory' => ['boolean'],
            'featured' => ['boolean'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /** @return list<Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isEmpty()) {
                $this->validateSaleConfiguration($this->route('product'));
            }
        }];
    }

    /**
     * Also called after locking the product and components, since catalog state can change after validation.
     *
     * @param  Collection<int, Product>|null  $components
     */
    public function validateSaleConfiguration(?Product $product, ?Collection $components = null): void
    {
        $mode = $this->input('sale_mode');
        $errors = [];

        if ($product !== null && $mode === Product::MODE_KIT && Quantity::milli($product->stock) !== 0) {
            $errors['sale_mode'] = 'No se puede guardar un paquete con existencia propia. Concilia el inventario antes de cambiar su modalidad.';
        }

        if ($product !== null && $mode !== $product->saleMode()) {
            if ($mode === Product::MODE_KIT && $product->usedInKits()->exists()) {
                $errors['sale_mode'] = 'No se puede convertir a paquete un producto con existencia propia o usado en otro paquete.';
            }

            $returnableSale = $product->saleItems()->whereColumn('quantity', '>', 'returned_quantity')
                ->whereHas('sale', fn (Builder $query) => $query->where('status', Sale::STATUS_PAID))->exists();

            if ($product->stockMovements()->exists() || $returnableSale) {
                $errors['sale_mode'] = 'No se puede cambiar la modalidad de un producto con movimientos de inventario o ventas aún devolvibles.';
            }

            if ($product->saleItems()->whereHas('sale', fn (Builder $query) => $query->where('status', Sale::STATUS_PENDING))->exists()
                || $this->hasPendingComponentReference($product)) {
                $errors['sale_mode'] = 'Resuelve las ventas pendientes de este producto antes de cambiar su modalidad.';
            }

            if ($product->isBulk() && $mode === Product::MODE_UNIT) {
                $fractionalRecipe = $product->usedInKits()->get()->contains(
                    fn (Product $kit): bool => Quantity::milli($kit->pivot->quantity) % 1000 !== 0,
                );
                if (Quantity::milli($product->stock) % 1000 !== 0 || Quantity::milli($product->min_stock) % 1000 !== 0 || $fractionalRecipe) {
                    $errors['sale_mode'] = 'No se puede pasar a piezas con existencias, mínimos, recetas fraccionarios o ventas a granel aún devolvibles.';
                }
            }
        }

        if ($mode === Product::MODE_KIT) {
            $items = $this->input('kit_items', []);
            $components ??= Product::query()->whereKey(array_column($items, 'product_id'))->get()->keyBy('id');

            foreach ($items as $index => $item) {
                $component = $components->get($item['product_id']);

                if ($component === null || ! $component->is_active || $component->isKit() || $component->id === $product?->id) {
                    $errors["kit_items.{$index}.product_id"] = 'Selecciona un componente activo por pieza o a granel, distinto del paquete.';

                    continue;
                }

                if (! Quantity::valid($item['quantity'], $component->isBulk()) || Quantity::milli($item['quantity']) <= 0) {
                    $errors["kit_items.{$index}.quantity"] = $component->isBulk()
                        ? 'La cantidad debe ser positiva y tener hasta 3 decimales.'
                        : 'La cantidad de piezas debe ser un entero positivo.';
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function hasPendingComponentReference(Product $product): bool
    {
        $found = false;
        SaleItem::query()->whereHas('sale', fn (Builder $query) => $query->where('status', Sale::STATUS_PENDING))
            ->whereNotNull('inventory_snapshot')->select(['id', 'inventory_snapshot'])
            ->chunkById(200, function (Collection $items) use ($product, &$found): bool {
                foreach ($items as $item) {
                    foreach ($item->inventory_snapshot['components'] ?? [] as $component) {
                        if ((int) $component['product_id'] === $product->id) {
                            $found = true;

                            return false;
                        }
                    }
                }

                return true;
            });

        return $found;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del producto es obligatorio.',
            'sale_mode.in' => 'Selecciona una modalidad de venta válida.',
            'kit_items.required' => 'Agrega al menos un producto al contenido del paquete.',
            'kit_items.*.product_id.distinct' => 'No repitas un producto en el paquete.',
            'barcode.unique' => 'Ya existe un producto con ese código de barras.',
            'barcode2.unique' => 'Ese código de barras ya está registrado en otro producto.',
            'barcode2.different' => 'El segundo código debe ser diferente del primero.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.min' => 'El precio no puede ser negativo.',
            'cost.min' => 'El costo no puede ser negativo.',
            'wholesale_price.min' => 'El precio de mayoreo no puede ser negativo.',
            'min_stock.min' => 'El mínimo de existencia no puede ser negativo.',
            'min_stock.integer' => 'Las existencias mínimas deben ser un número entero.',
            'tax_rate.required' => 'La tasa de IVA es obligatoria.',
            'tax_rate.between' => 'La tasa de IVA debe estar entre 0 y 100.',
            'stock.required' => 'La existencia es obligatoria.',
            'stock.min' => 'La existencia no puede ser negativa.',
            'stock.integer' => 'Las existencias deben ser un número entero.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.max' => 'La imagen no debe superar los 2 MB.',
        ];
    }
}
