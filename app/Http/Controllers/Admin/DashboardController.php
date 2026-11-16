<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeCard;
use App\Models\HomePage;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\DailyCutService;
use App\Services\PresenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DashboardController extends Controller
{
    public function __invoke(DailyCutService $dailyCutService, PresenceService $presenceService): View
    {
        $generatedAt = now();
        $startOfDay = $generatedAt->copy()->startOfDay();
        $rankingStart = $startOfDay->copy()->subDays(29);
        $employeeBreakdown = $dailyCutService->employeeBreakdown($generatedAt->toDateString());
        $onlineUsers = $presenceService->onlineUsers();

        $sales = Sale::query()->paid()->whereBetween('paid_at', [$startOfDay, $generatedAt]);
        $returns = SaleReturn::query()
            ->where('status', SaleReturn::STATUS_APPROVED)
            ->whereBetween('approved_at', [$startOfDay, $generatedAt]);

        $salesSummary = [
            'paid_total' => (clone $sales)->sum('total'),
            'tickets' => (clone $sales)->count(),
            'refunds_total' => (clone $returns)->sum('total'),
            'refunds_count' => (clone $returns)->count(),
        ];

        $inventoryCounts = [
            'low_stock' => Product::query()->active()->lowStock()->count(),
            'out_of_stock' => Product::query()->active()->outOfStock()->count(),
        ];

        $catalogCounts = [
            'active' => Product::query()->active()->count(),
            'missing_photo' => Product::query()->active()->missingPhoto()->count(),
            'missing_price' => Product::query()->active()->missingPrice()->count(),
        ];

        $lowStockProducts = Product::query()
            ->active()
            ->where(fn (Builder $query) => $query->outOfStock()->orWhere(fn (Builder $query) => $query->lowStock()))
            ->orderBy('stock')
            ->orderBy('name')
            ->limit(6)
            ->get();

        $homePage = HomePage::current();
        $homeCards = $homePage->enabled('products')
            ? HomeCard::query()->section('products')->active()->ordered()->limit(5)->get()
            : new Collection;

        $paidItems = fn (Builder $query) => $query->where('is_custom', false)
            ->whereHas('sale', fn (Builder $query) => $query
                ->paid()->whereBetween('paid_at', [$rankingStart, $generatedAt]));

        $topProducts = Product::query()
            ->active()
            ->whereHas('saleItems', $paidItems)
            ->withSum(['saleItems as units_sold' => $paidItems], 'quantity')
            ->orderByDesc('units_sold')
            ->orderBy('name')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'generatedAt',
            'rankingStart',
            'salesSummary',
            'inventoryCounts',
            'catalogCounts',
            'lowStockProducts',
            'homeCards',
            'topProducts',
            'employeeBreakdown',
            'onlineUsers',
        ));
    }
}
