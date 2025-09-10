<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::prefix('payments')->group(function () {
    Route::post('/initiate', [PaymentController::class, 'initiate']);
    Route::get('/return/{referenceId}', [PaymentController::class, 'return']);
    Route::put('/callback/{referenceId}', [PaymentController::class, 'callback']);
    Route::get('/status/{referenceId}', [PaymentController::class, 'status']);
});

