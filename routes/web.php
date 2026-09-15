<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
})->name('login');
