<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\AttributeController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\UserPreferenceController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\Api\CartController;

Route::group(['middleware' => 'api','prefix' => 'auth'], function ($router) {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login-2fa', [AuthController::class, 'loginWith2FA']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('/user-profile', [AuthController::class, 'userProfile'])->middleware('auth:api');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->middleware('auth:api');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('auth:api');
    
    // 2FA Routes
    Route::get('/two-factor-qr', [AuthController::class, 'get2FAQrCode'])->middleware('auth:api');
    Route::post('/two-factor-confirm', [AuthController::class, 'confirm2FA'])->middleware('auth:api');
    Route::post('/two-factor-disable', [AuthController::class, 'disable2FA'])->middleware('auth:api');
    
    // Password Reset
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
    Route::post('/reset-password', [PasswordResetController::class, 'reset']);
});

// Payment Routes
Route::post('/payments/initiate', [PaymentController::class, 'initiate'])->middleware('auth:api');
Route::match(['get', 'post'], '/payments/callback/{gateway}/{status}', [PaymentController::class, 'handleCallback'])->name('payment.callback');

// Product & Category API (Public read, Admin write)
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{id}', [CategoryController::class, 'show']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{slug}', [ProductController::class, 'showBySlug']);
Route::get('reviews/{productId}', [ReviewController::class, 'index']);


Route::group(['middleware' => ['auth:api', 'admin']], function() {
    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{id}', [ProductController::class, 'update']);
    Route::delete('products/{id}', [ProductController::class, 'destroy']);

});

Route::group(['middleware' => ['auth:api']], function() {
    Route::post('reviews', [ReviewController::class, 'store']);
    Route::post('reviews/{reviewId}/replies', [ReviewController::class, 'storeReply']);
    Route::delete('reviews/{id}', [ReviewController::class, 'destroy']);
    
    // Cart Routes
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart', [CartController::class, 'store']);
    Route::post('cart/sync', [CartController::class, 'sync']);
    Route::put('cart/{id}', [CartController::class, 'update']);
    Route::delete('cart/{id}', [CartController::class, 'destroy']);
    Route::delete('cart/clear', [CartController::class, 'clear']);
});

// Order API
Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);

// Blog & Comment API
Route::get('blogs/slug/{slug}', [BlogController::class, 'showBySlug']);
Route::apiResource('blogs', BlogController::class);
Route::post('blogs/{id}/like', [BlogController::class, 'toggleLike'])->middleware('auth:api');
Route::apiResource('comments', CommentController::class)->only(['index', 'store', 'update', 'destroy']);

// Notification API
Route::group(['prefix' => 'notifications', 'middleware' => 'auth:api'], function() {
    Route::get('/',               [NotificationController::class, 'index']);
    Route::get('/unread-count',   [NotificationController::class, 'unreadCount']);
    Route::post('/{id}/read',     [NotificationController::class, 'markAsRead']);
    Route::post('/read-all',      [NotificationController::class, 'markAllAsRead']);
    Route::delete('/{id}',        [NotificationController::class, 'destroy']);
});

// User Notification Preferences
Route::middleware('auth:api')->group(function () {
    Route::get('/auth/notification-preferences',  [UserPreferenceController::class, 'show']);
    Route::put('/auth/notification-preferences',  [UserPreferenceController::class, 'update']);
});

// Newsletter API
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
Route::post('/newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe']);

// Contact API (Public)
Route::post('/contact', [ContactController::class, 'store']);

// Admin Only Routes
Route::group(['prefix' => 'admin', 'middleware' => ['auth:api', 'admin']], function() {
    Route::get('/dashboard-stats', [AdminController::class, 'getDashboardStats']);

    // Order Management
    Route::get('/orders', [AdminController::class, 'getAllOrders']);
    Route::get('/orders/{id}', [AdminController::class, 'getOrderDetails']);
    Route::put('/orders/{id}/status', [AdminController::class, 'updateOrderStatus']);

    // User Management
    Route::get('/users', [AdminController::class, 'getAllUsers']);
    Route::post('/users', [AdminController::class, 'storeUser']);
    Route::put('/users/{id}', [AdminController::class, 'updateUser']);
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

    // Newsletter Management
    Route::get('/newsletter', [NewsletterController::class, 'index']);
    Route::delete('/newsletter/{id}', [NewsletterController::class, 'destroy']);

    // Contact Message Management
    Route::get('/contact-messages', [ContactController::class, 'index']);
    Route::get('/contact-messages/{id}', [ContactController::class, 'show']);
    Route::post('/contact-messages/{id}/reply', [ContactController::class, 'reply']);
    Route::delete('/contact-messages/{id}', [ContactController::class, 'destroy']);

    // Blog Management
    Route::get('/blogs', [AdminController::class, 'getAllBlogs']);

    // Settings Management
    Route::put('/settings', [\App\Http\Controllers\SettingController::class, 'update']);

    // Attributes for Product Form
    Route::get('/get-attributes', [AdminController::class, 'getAttributes']);

    // Attribute Management (CRUD)
    Route::get('/attributes/{type}',          [AttributeController::class, 'index']);
    Route::post('/attributes/{type}',         [AttributeController::class, 'store']);
    Route::put('/attributes/{type}/{id}',     [AttributeController::class, 'update']);
    Route::delete('/attributes/{type}/{id}',  [AttributeController::class, 'destroy']);

    // Notification Management (Admin)
    Route::post('/notifications/promote-product', [AdminController::class, 'promoteProduct']);
    Route::post('/notifications/broadcast',       [AdminController::class, 'broadcastNotification']);
});

// Settings API (Public)
Route::get('settings', [\App\Http\Controllers\SettingController::class, 'index']);
Route::get('settings/group/{group}', [\App\Http\Controllers\SettingController::class, 'getByGroup']);


// routes/web.php
Route::get('/auth/{provider}/redirect',  [SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback',  [SocialAuthController::class, 'callback'])->name('social.callback');