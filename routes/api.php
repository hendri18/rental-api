<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CarController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
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


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::get('/cars', [CarController::class, 'index']);
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/order', [OrderController::class, 'order']);
    Route::get('/order-history', [OrderController::class, 'index']);
    Route::post('/return-car', [OrderController::class, 'returnCar']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/users/{id}', [AuthController::class, 'updateUser']);

    Route::prefix('admin')->group(function () {
        Route::get('/cars', [CarController::class, 'getCarServerSide']);
        Route::post('/cars', [CarController::class, 'store']);
        Route::post('/cars/{id}', [CarController::class, 'update']);
        Route::delete('/cars/{id}', [CarController::class, 'destroy']);
    
        Route::get('/users', [AuthController::class, 'getAdmin']);
        Route::post('/users', [AuthController::class, 'registerAdmin']);
        Route::post('/users/{id}', [AuthController::class, 'updateAdmin']);
        Route::delete('/users/{id}', [AuthController::class, 'destroyAdmin']);

        Route::get('/customers', [CustomerController::class, 'index']);
        Route::post('/customers/{id}', [CustomerController::class, 'update']);
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
    });
    
    


});
