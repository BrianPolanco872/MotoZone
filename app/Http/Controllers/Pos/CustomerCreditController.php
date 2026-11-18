<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreCreditPaymentRequest;
use App\Http\Requests\Pos\StoreCustomerCreditRequest;
use App\Models\CustomerCredit;
use App\Models\Product;
use App\Services\CustomerCreditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerCreditController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'settled' => ['nullable', 'boolean'],
        ]);

        $q = trim($filters['q'] ?? '');
        $showSettled = (bool) ($filters['settled'] ?? false);

        $credits = CustomerCredit::query()
            ->with(['payments.user'])
            ->when($q !== '', fn ($query) => $query->where('customer_name', 'like', "%{$q}%"))
            ->when(! $showSettled, fn ($query) => $query->pending())
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('pos.abonos.index', [
            'credits' => $credits,
            'filters' => ['q' => $q, 'settled' => $showSettled],
            'oldCredit' => old('credit_id') ? CustomerCredit::query()->find(old('credit_id')) : null,
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json(['products' => []]);
        }

        $products = Product::query()
            ->active()
            ->search($term)
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'price'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (string) $product->price,
            ]);

        return response()->json(['products' => $products]);
    }

    public function store(StoreCustomerCreditRequest $request, CustomerCreditService $service): RedirectResponse
    {
        $credit = $service->create($request->user(), $request->validated(), $request->validated('idempotency_key'));

        return redirect()
            ->route('pos.abonos.index')
            ->with('status', "Cuenta de {$credit->customer_name} registrada correctamente.");
    }

    public function storePayment(StoreCreditPaymentRequest $request, CustomerCredit $customerCredit, CustomerCreditService $service): RedirectResponse
    {
        $service->pay(
            $customerCredit,
            $request->user(),
            $request->validated('amount'),
            $request->validated('method'),
            $request->validated('idempotency_key'),
        );

        return redirect()
            ->route('pos.abonos.index')
            ->with('status', "Abono registrado para {$customerCredit->customer_name}.");
    }
}
