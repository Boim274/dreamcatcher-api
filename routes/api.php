<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\AIChatController;
use App\Http\Controllers\Api\AIDesignRecommendationController;
use App\Http\Controllers\Api\ColorController;
use App\Http\Controllers\Api\ConversationController;
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
use App\Http\Controllers\Admin\AdminUserController;
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
        Route::post('google', [AuthController::class, 'googleAuth']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
        Route::put('profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
        Route::post('profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
        Route::post('avatar', [AuthController::class, 'updateAvatar'])->middleware('auth:sanctum');
        Route::delete('avatar', [AuthController::class, 'deleteAvatar'])->middleware('auth:sanctum');
        Route::put('password', [AuthController::class, 'updatePassword'])->middleware('auth:sanctum');
    });

    Route::prefix('services')->group(function () {
        Route::get('/', [ServiceController::class, 'index']);
        Route::get('{id}', [ServiceController::class, 'show']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('ai')->group(function () {
            Route::post('chat', [AIChatController::class, 'chat']);
            Route::get('chat/history', [AIChatController::class, 'history']);
            Route::get('recommendations', [AIDesignRecommendationController::class, 'index']);
            Route::get('recommendations/suggestions', [AIDesignRecommendationController::class, 'suggestions']);
        });

        Route::prefix('designs')->group(function () {
            Route::get('/', [DesignController::class, 'index']);
            Route::get('quota', [DesignController::class, 'quota']);
            Route::post('generate', [DesignController::class, 'generate']);
            Route::post('upload', [DesignController::class, 'upload']);
            Route::get('{id}', [DesignController::class, 'show']);
            Route::get('{id}/versions', [DesignController::class, 'versions']);
            Route::post('{id}/enhance', [DesignController::class, 'enhance']);
            Route::post('{id}/edit', [DesignController::class, 'edit']);
            Route::post('{id}/validate', [DesignController::class, 'validateDesign']);
            Route::delete('{id}', [DesignController::class, 'destroy']);
        });

        Route::prefix('conversations')->group(function () {
            Route::get('/', [ConversationController::class, 'index']);
            Route::post('/', [ConversationController::class, 'store']);
            Route::get('{id}', [ConversationController::class, 'show']);
            Route::put('{id}', [ConversationController::class, 'update']);
            Route::post('{id}/duplicate', [ConversationController::class, 'duplicate']);
            Route::delete('{id}', [ConversationController::class, 'destroy']);
            Route::post('{id}/restore', [ConversationController::class, 'restore']);
            Route::delete('{id}/force', [ConversationController::class, 'forceDestroy']);
        });
    });

    Route::prefix('orders')->group(function () {
        Route::post('/', [OrderController::class, 'store'])->middleware('auth:sanctum');
        Route::get('track', [OrderController::class, 'track']);
        Route::get('by-code/{orderCode}', [OrderController::class, 'showByCode']);
        Route::get('my-stats', [OrderController::class, 'myStats'])->middleware('auth:sanctum');
        Route::get('my-orders', [OrderController::class, 'index'])->middleware('auth:sanctum');
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
    Route::get('testimonials/my', [TestimonialController::class, 'myTestimonial'])->middleware('auth:sanctum');
    Route::get('testimonials', [TestimonialController::class, 'index']);
    Route::post('testimonials', [TestimonialController::class, 'store']);
    Route::put('testimonials/{id}', [TestimonialController::class, 'update']);
    Route::get('sizes', [SizeController::class, 'index']);
    Route::get('settings', [SettingController::class, 'index']);
    Route::get('settings/{group}', [SettingController::class, 'group']);

    Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index']);

        Route::prefix('users')->group(function () {
            Route::get('/', [AdminUserController::class, 'index']);
            Route::post('/', [AdminUserController::class, 'store']);
            Route::put('{id}', [AdminUserController::class, 'update']);
            Route::patch('{id}/role', [AdminUserController::class, 'updateRole']);
            Route::delete('{id}', [AdminUserController::class, 'destroy']);
            Route::put('{id}/quota', [AdminUserController::class, 'updateQuota']);
        });

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
            Route::patch('{id}/verify-dp', [AdminPaymentController::class, 'verifyDp']);
            Route::patch('{id}/verify-pelunasan', [AdminPaymentController::class, 'verifyPelunasan']);
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
            Route::match(['post', 'put'], '{id}', [AdminProductController::class, 'update']);
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
