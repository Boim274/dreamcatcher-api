<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : null;
        $hasDateFilter = $dateFrom && $dateTo;

        $pendingOrders = Order::where('status', 'pending')->orWhere('status', 'waiting_payment')->count();
        $pendingPayments = Payment::where('payment_status', 'pending')->count();

        $periodStart = $hasDateFilter ? $dateFrom : Carbon::now()->startOfMonth();
        $periodEnd = $hasDateFilter ? $dateTo : Carbon::now()->endOfMonth();

        $periodRevenue = Order::whereBetween('created_at', [$periodStart, $periodEnd])
            ->whereIn('status', ['paid', 'processed', 'completed'])
            ->sum('total_price');

        $periodOrders = Order::whereBetween('created_at', [$periodStart, $periodEnd])->count();

        if ($hasDateFilter) {
            $days = $dateFrom->diffInDays($dateTo) + 1;
            $maxDays = min($days, 90);
            $chartLabels = [];
            $orderCounts = [];
            for ($i = 0; $i < $maxDays; $i++) {
                $date = $dateFrom->copy()->addDays($i);
                $chartLabels[] = $date->format('d M');
                $orderCounts[] = Order::whereDate('created_at', $date)->count();
            }
        } else {
            $chartLabels = [];
            $orderCounts = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $chartLabels[] = $date->format('d M');
                $orderCounts[] = Order::whereDate('created_at', $date)->count();
            }
        }

        $recentOrders = Order::with(['items.service'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $statusQuery = Order::query();
        if ($hasDateFilter) {
            $statusQuery->whereBetween('created_at', [$periodStart, $periodEnd]);
        }

        $statusCounts = [
            'pending' => (clone $statusQuery)->where('status', 'pending')->count(),
            'waiting_payment' => (clone $statusQuery)->where('status', 'waiting_payment')->count(),
            'paid' => (clone $statusQuery)->where('status', 'paid')->count(),
            'processed' => (clone $statusQuery)->where('status', 'processed')->count(),
            'delivered' => (clone $statusQuery)->where('status', 'delivered')->count(),
            'completed' => (clone $statusQuery)->where('status', 'completed')->count(),
            'cancelled' => (clone $statusQuery)->where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'stats' => [
                'today_orders' => $periodOrders,
                'pending_orders' => $pendingOrders,
                'pending_payments' => $pendingPayments,
                'month_revenue' => $periodRevenue,
            ],
            'chart_data' => [
                'labels' => $chartLabels,
                'datasets' => [
                    [
                        'label' => 'Pesanan',
                        'data' => $orderCounts,
                    ],
                ],
            ],
            'recent_orders' => $recentOrders,
            'status_counts' => $statusCounts,
            'date_filter' => [
                'date_from' => $hasDateFilter ? $dateFrom->format('Y-m-d') : null,
                'date_to' => $hasDateFilter ? $dateTo->format('Y-m-d') : null,
            ],
        ]);
    }
}
