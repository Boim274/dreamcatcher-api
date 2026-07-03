<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ColorController;
use App\Http\Controllers\Api\DesignController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductOrderController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SizeController;
use App\Http\Controllers\Api\TestimonialController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminColorController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminPortfolioController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminServiceController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminSizeController;
use App\Http\Controllers\Admin\AdminTestimonialController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
        Route::put('profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
        Route::put('password', [AuthController::class, 'updatePassword'])->middleware('auth:sanctum');
    });

    Route::prefix('services')->group(function () {
        Route::get('/', [ServiceController::class, 'index']);
        Route::get('{id}', [ServiceController::class, 'show']);
    });

    Route::prefix('designs')->group(function () {
        Route::post('generate', [DesignController::class, 'generate']);
        Route::post('upload', [DesignController::class, 'upload']);
        Route::get('{id}', [DesignController::class, 'show']);
        Route::post('{id}/validate', [DesignController::class, 'validateDesign']);
        Route::delete('{id}', [DesignController::class, 'destroy']);
    });

    Route::prefix('orders')->group(function () {
        Route::post('/', [OrderController::class, 'store']);
        Route::get('track', [OrderController::class, 'track']);
        Route::get('by-code/{orderCode}', [OrderController::class, 'showByCode']);
        Route::get('/', [OrderController::class, 'index'])->middleware('auth:sanctum');
        Route::get('{id}', [OrderController::class, 'show'])->middleware('auth:sanctum');
        Route::post('{id}/request-cancel', [OrderController::class, 'requestCancel'])->middleware('auth:sanctum');
    });

    Route::prefix('payments')->group(function () {
        Route::post('upload', [PaymentController::class, 'upload']);
        Route::get('{orderId}', [PaymentController::class, 'show']);
        Route::delete('{id}', [PaymentController::class, 'destroy']);
    });

    Route::get('portfolios', [PortfolioController::class, 'index']);
    Route::get('portfolios/featured', [PortfolioController::class, 'featured']);

    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);

    Route::post('product-orders', [ProductOrderController::class, 'store'])->middleware('auth:sanctum');

    Route::get('colors', [ColorController::class, 'index']);
    Route::get('testimonials', [TestimonialController::class, 'index']);
    Route::post('testimonials', [TestimonialController::class, 'store']);
    Route::put('testimonials/{id}', [TestimonialController::class, 'update']);
    Route::get('sizes', [SizeController::class, 'index']);
    Route::get('settings', [SettingController::class, 'index']);
    Route::get('settings/{group}', [SettingController::class, 'group']);

    Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index']);

        Route::prefix('orders')->group(function () {
            Route::get('/', [AdminOrderController::class, 'index']);
            Route::get('stats', [AdminOrderController::class, 'getOrderStats']);
            Route::get('{id}', [AdminOrderController::class, 'show']);
            Route::patch('{id}/status', [AdminOrderController::class, 'updateStatus']);
        });

        Route::prefix('payments')->group(function () {
            Route::get('/', [AdminPaymentController::class, 'index']);
            Route::get('pending', [AdminPaymentController::class, 'pending']);
            Route::get('{id}', [AdminPaymentController::class, 'show']);
            Route::patch('{id}/verify', [AdminPaymentController::class, 'verify']);
            Route::patch('{id}/reject', [AdminPaymentController::class, 'reject']);
        });

        Route::prefix('services')->group(function () {
            Route::get('/', [AdminServiceController::class, 'index']);
            Route::post('/', [AdminServiceController::class, 'store']);
            Route::get('{id}', [AdminServiceController::class, 'show']);
            Route::put('{id}', [AdminServiceController::class, 'update']);
            Route::delete('{id}', [AdminServiceController::class, 'destroy']);
        });

        Route::prefix('portfolios')->group(function () {
            Route::get('/', [AdminPortfolioController::class, 'index']);
            Route::post('/', [AdminPortfolioController::class, 'store']);
            Route::get('{id}', [AdminPortfolioController::class, 'show']);
            Route::put('{id}', [AdminPortfolioController::class, 'update']);
            Route::delete('{id}', [AdminPortfolioController::class, 'destroy']);
        });

        Route::prefix('colors')->group(function () {
            Route::get('/', [AdminColorController::class, 'index']);
            Route::post('/', [AdminColorController::class, 'store']);
            Route::put('{id}', [AdminColorController::class, 'update']);
            Route::delete('{id}', [AdminColorController::class, 'destroy']);
        });

        Route::prefix('settings')->group(function () {
            Route::get('/', [AdminSettingController::class, 'index']);
            Route::put('/', [AdminSettingController::class, 'update']);
            Route::delete('{id}', [AdminSettingController::class, 'destroy']);
        });

        Route::prefix('testimonials')->group(function () {
            Route::get('/', [AdminTestimonialController::class, 'index']);
            Route::post('/', [AdminTestimonialController::class, 'store']);
            Route::put('{id}', [AdminTestimonialController::class, 'update']);
            Route::delete('{id}', [AdminTestimonialController::class, 'destroy']);
        });

        Route::prefix('sizes')->group(function () {
            Route::get('/', [AdminSizeController::class, 'index']);
            Route::post('/', [AdminSizeController::class, 'store']);
            Route::put('{id}', [AdminSizeController::class, 'update']);
            Route::delete('{id}', [AdminSizeController::class, 'destroy']);
        });

        Route::prefix('products')->group(function () {
            Route::get('/', [AdminProductController::class, 'index']);
            Route::post('/', [AdminProductController::class, 'store']);
            Route::get('{id}', [AdminProductController::class, 'show']);
            Route::post('{id}', [AdminProductController::class, 'update']);
            Route::delete('{id}', [AdminProductController::class, 'destroy']);
        });

        Route::prefix('categories')->group(function () {
            Route::get('/', [AdminCategoryController::class, 'index']);
            Route::post('/', [AdminCategoryController::class, 'store']);
            Route::get('{id}', [AdminCategoryController::class, 'show']);
            Route::put('{id}', [AdminCategoryController::class, 'update']);
            Route::delete('{id}', [AdminCategoryController::class, 'destroy']);
        });
    });
});
