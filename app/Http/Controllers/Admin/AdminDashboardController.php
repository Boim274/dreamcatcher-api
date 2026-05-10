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
    public function index()
    {
        $today = Carbon::today();

        $todayOrders = Order::whereDate('created_at', $today)->count();
        $pendingOrders = Order::where('status', 'pending')->orWhere('status', 'waiting_payment')->count();
        $pendingPayments = Payment::where('payment_status', 'pending')->count();

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $monthRevenue = Order::whereBetween('created_at', [$monthStart, $monthEnd])
            ->whereIn('status', ['paid', 'processed', 'completed'])
            ->sum('total_price');

        $last7Days = [];
        $orderCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $last7Days[] = $date->format('M d');
            $orderCounts[] = Order::whereDate('created_at', $date)->count();
        }

        $recentOrders = Order::with(['items.service'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $statusCounts = [
            'pending' => Order::where('status', 'pending')->count(),
            'waiting_payment' => Order::where('status', 'waiting_payment')->count(),
            'paid' => Order::where('status', 'paid')->count(),
            'processed' => Order::where('status', 'processed')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'stats' => [
                'today_orders' => $todayOrders,
                'pending_orders' => $pendingOrders,
                'pending_payments' => $pendingPayments,
                'month_revenue' => $monthRevenue,
            ],
            'chart_data' => [
                'labels' => $last7Days,
                'datasets' => [
                    [
                        'label' => 'Pesanan',
                        'data' => $orderCounts,
                    ],
                ],
            ],
            'recent_orders' => $recentOrders,
            'status_counts' => $statusCounts,
        ]);
    }
}