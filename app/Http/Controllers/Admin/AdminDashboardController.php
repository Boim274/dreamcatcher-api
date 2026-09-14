<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : null;
        $hasDateFilter = $dateFrom && $dateTo;

        $periodStart = $hasDateFilter ? $dateFrom : Carbon::now()->startOfMonth();
        $periodEnd = $hasDateFilter ? $dateTo : Carbon::now()->endOfDay();

        // Pending queues for quick notification counters
        $pendingOrders = Order::whereIn('status', ['pending', 'waiting_payment', 'waiting_verification'])->count();
        $pendingPayments = Payment::where('payment_status', 'pending')->count();
        $cancelRequests = Order::where('status', 'cancel_requested')->count();

        // Query scoped to period
        $baseOrderQuery = Order::whereBetween('created_at', [$periodStart, $periodEnd]);

        // Revenue (from valid active/completed orders)
        $validStatuses = ['ready_to_process', 'processing', 'ready_for_pickup', 'completed', 'paid', 'delivered'];
        $periodRevenue = (clone $baseOrderQuery)
            ->whereIn('status', $validStatuses)
            ->sum('total_price');

        $periodOrders = (clone $baseOrderQuery)->count();
        $completedOrders = (clone $baseOrderQuery)->where('status', 'completed')->count();
        $cancelledOrders = (clone $baseOrderQuery)->whereIn('status', ['cancelled', 'cancel_requested'])->count();
        $activeOrders = (clone $baseOrderQuery)->whereIn('status', ['ready_to_process', 'processing', 'ready_for_pickup'])->count();

        // Calculate Average Order Value (AOV)
        $avgOrderValue = $periodOrders > 0 && $periodRevenue > 0 ? round($periodRevenue / max(1, (clone $baseOrderQuery)->whereIn('status', $validStatuses)->count())) : 0;

        // Daily chart data
        $chartLabels = [];
        $orderCounts = [];
        $dailyRevenues = [];
        $completedCounts = [];

        if ($hasDateFilter) {
            $days = $dateFrom->diffInDays($dateTo) + 1;
            $maxDays = min($days, 90);
            for ($i = 0; $i < $maxDays; $i++) {
                $date = $dateFrom->copy()->addDays($i);
                $chartLabels[] = $date->format('d M');
                $orderCounts[] = Order::whereDate('created_at', $date)->count();
                $completedCounts[] = Order::whereDate('created_at', $date)->where('status', 'completed')->count();
                $dailyRevenues[] = (float) Order::whereDate('created_at', $date)
                    ->whereIn('status', $validStatuses)
                    ->sum('total_price');
            }
        } else {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $chartLabels[] = $date->format('d M');
                $orderCounts[] = Order::whereDate('created_at', $date)->count();
                $completedCounts[] = Order::whereDate('created_at', $date)->where('status', 'completed')->count();
                $dailyRevenues[] = (float) Order::whereDate('created_at', $date)
                    ->whereIn('status', $validStatuses)
                    ->sum('total_price');
            }
        }

        // Recent orders
        $recentOrders = Order::with(['items.service', 'items.product.images', 'user'])
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        // All orders in period (for summary list & report export)
        $periodOrdersList = (clone $baseOrderQuery)
            ->with(['items.service', 'user'])
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get()
            ->map(function ($o) {
                return [
                    'id' => $o->id,
                    'order_code' => $o->order_code,
                    'customer_name' => $o->customer_name,
                    'customer_email' => $o->customer_email,
                    'phone' => $o->phone,
                    'delivery_method' => $o->delivery_method,
                    'total_price' => $o->total_price,
                    'status' => $o->status,
                    'payment_status' => $o->payment_status,
                    'created_at' => $o->created_at->format('Y-m-d H:i:s'),
                    'items_count' => $o->items->sum('quantity') ?: 1,
                ];
            });

        // Accurate status distribution counts
        $statusCounts = [
            'pending' => (clone $baseOrderQuery)->where('status', 'pending')->count(),
            'waiting_payment' => (clone $baseOrderQuery)->where('status', 'waiting_payment')->count(),
            'waiting_verification' => (clone $baseOrderQuery)->where('status', 'waiting_verification')->count(),
            'ready_to_process' => (clone $baseOrderQuery)->where('status', 'ready_to_process')->count(),
            'processing' => (clone $baseOrderQuery)->where('status', 'processing')->count(),
            'ready_for_pickup' => (clone $baseOrderQuery)->whereIn('status', ['ready_for_pickup', 'delivered'])->count(),
            'completed' => $completedOrders,
            'cancel_requested' => (clone $baseOrderQuery)->where('status', 'cancel_requested')->count(),
            'cancelled' => (clone $baseOrderQuery)->where('status', 'cancelled')->count(),
        ];

        // Best-selling Sablon Services / Products in period
        $topServices = [];
        try {
            $topServices = OrderItem::select('service_id', DB::raw('COUNT(*) as total_orders'), DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_amount'))
                ->whereNotNull('service_id')
                ->whereHas('order', function ($q) use ($periodStart, $periodEnd) {
                    $q->whereBetween('created_at', [$periodStart, $periodEnd]);
                })
                ->with('service:id,name')
                ->groupBy('service_id')
                ->orderByDesc('total_amount')
                ->take(5)
                ->get();
        } catch (\Throwable $e) {
            // Silently fallback if aggregation fails
        }

        return response()->json([
            'stats' => [
                'today_orders' => $periodOrders,
                'period_orders' => $periodOrders,
                'completed_orders' => $completedOrders,
                'active_orders' => $activeOrders,
                'cancelled_orders' => $cancelledOrders,
                'pending_orders' => $pendingOrders,
                'pending_payments' => $pendingPayments,
                'cancel_requests' => $cancelRequests,
                'month_revenue' => $periodRevenue,
                'total_revenue' => $periodRevenue,
                'avg_order_value' => $avgOrderValue,
            ],
            'chart_data' => [
                'labels' => $chartLabels,
                'datasets' => [
                    [
                        'label' => 'Pesanan',
                        'data' => $orderCounts,
                    ],
                    [
                        'label' => 'Selesai',
                        'data' => $completedCounts,
                    ],
                    [
                        'label' => 'Pendapatan',
                        'data' => $dailyRevenues,
                    ],
                ],
                'daily_revenues' => $dailyRevenues,
            ],
            'recent_orders' => $recentOrders,
            'orders_list' => $periodOrdersList,
            'top_services' => $topServices,
            'status_counts' => $statusCounts,
            'date_filter' => [
                'date_from' => $hasDateFilter ? $dateFrom->format('Y-m-d') : null,
                'date_to' => $hasDateFilter ? $dateTo->format('Y-m-d') : null,
            ],
        ]);
    }
}
