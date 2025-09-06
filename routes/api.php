<?php

use App\Http\Controllers\API\V1\AccessoryController;
use App\Http\Controllers\API\V1\AdditionalServiceController;
use App\Http\Controllers\API\V1\AuthenticationController;
use App\Http\Controllers\API\V1\AzotController;
use App\Http\Controllers\API\V1\TelegramMessageController;
use App\Http\Controllers\API\V1\UserController;
use App\Http\Controllers\API\V1\AzotPriceTypeController;
use App\Http\Controllers\API\V1\CartController;
use App\Http\Controllers\API\V1\OrderController;
use App\Http\Controllers\API\V1\PromocodeController;
use App\Http\Controllers\API\V1\SettingController;
use App\Models\AdditionalService;
use App\Models\Azot;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api/v1'], function () {

    /** Public routes **/
    Route::group(['prefix' => 'public'], function () {
        
        Route::get('/ping', function () {
            return response()->json(['message' => 'pong']);
        });
        Route::get('/updates', [SettingController::class, 'updates']);

        Route::post('/user-exists', [AuthenticationController::class, 'existUser']);
        Route::post('/register', [AuthenticationController::class, 'register']);

        Route::get('/azots', [AzotController::class, 'publicIndex']);
        Route::get('/azots/{azot}', [AzotController::class, 'publicShow']);
        Route::get('/azots/{azot}/price-types', [AzotPriceTypeController::class, 'publicIndex']);

        Route::get('/accessories', [AccessoryController::class, 'publicIndex']);
        Route::get('/accessories/{accessory}', [AccessoryController::class, 'publicShow']);

        Route::get('/services', [AdditionalServiceController::class, 'publicIndex']);
        Route::get('/services/{service}', [AdditionalServiceController::class, 'publicShow']);

        Route::post('/promocode/check', [PromocodeController::class, 'check']);

        Route::post('/orders', [OrderController::class, 'publicOrder']);
        Route::post('/orders/create', [OrderController::class, 'publicStore']);
        Route::post('/orders/{order}/finish', [OrderController::class, 'finish']);
        Route::post('/orders/{order}/delete', [OrderController::class, 'delete']);

        Route::prefix('cart')->group(function () {
            Route::post('/', [CartController::class, 'index']);
            Route::post('/add/azot', [CartController::class, 'addAzot']);
            Route::post('/add/accessuary', [CartController::class, 'addAccessuary']);
            Route::post('/add/service', [CartController::class, 'addService']);
            Route::post('/minus/azot', [CartController::class, 'minusAzot']);
            Route::post('/minus/accessuary', [CartController::class, 'minusAccessuary']);
            Route::post('/minus/service', [CartController::class, 'minusService']);
            Route::post('/clear', [CartController::class, 'clear']);
        });

    });
    


    /**
    ░█████╗░██████╗░███╗░░░███╗██╗███╗░░██╗  ██████╗░░█████╗░██╗░░░██╗████████╗███████╗░██████╗
    ██╔══██╗██╔══██╗████╗░████║██║████╗░██║  ██╔══██╗██╔══██╗██║░░░██║╚══██╔══╝██╔════╝██╔════╝
    ███████║██║░░██║██╔████╔██║██║██╔██╗██║  ██████╔╝██║░░██║██║░░░██║░░░██║░░░█████╗░░╚█████╗░
    ██╔══██║██║░░██║██║╚██╔╝██║██║██║╚████║  ██╔══██╗██║░░██║██║░░░██║░░░██║░░░██╔══╝░░░╚═══██╗
    ██║░░██║██████╔╝██║░╚═╝░██║██║██║░╚███║  ██║░░██║╚█████╔╝╚██████╔╝░░░██║░░░███████╗██████╔╝
    ╚═╝░░╚═╝╚═════╝░╚═╝░░░░░╚═╝╚═╝╚═╝░░╚══╝  ╚═╝░░╚═╝░╚════╝░░╚═════╝░░░░╚═╝░░░╚══════╝╚═════╝░
    **/
    Route::post('/auth/login', [AuthenticationController::class, 'login']);
    Route::middleware(['auth:sanctum', 'adminOnly'])->group(function () {
        Route::get('/auth/me', [AuthenticationController::class, 'userInfo']);
        Route::post('/auth/logout', [AuthenticationController::class, 'logOut']);

        Route::apiResource('users', UserController::class);
        Route::apiResource('accessories', AccessoryController::class);
        Route::apiResource('services', AdditionalServiceController::class);
        Route::apiResource('promocodes', PromocodeController::class);
        Route::apiResource('azots', AzotController::class);
        Route::prefix('azots/{azot}')->group(function () {
            Route::get('/price-types', [AzotPriceTypeController::class, 'index']);
            Route::post('/price-types/add', [AzotPriceTypeController::class, 'store']);
            Route::put('/price-types/{type}', [AzotPriceTypeController::class, 'update']);
            Route::delete('/price-types/{type}', [AzotPriceTypeController::class, 'destroy']);
        });
        Route::apiResource('orders', OrderController::class);
        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings/save', [SettingController::class, 'save']);

        Route::prefix('tg-messages')->name('tg-messages.')->group(function () {
            Route::get('/', [TelegramMessageController::class, 'index'])->name('index');
            Route::post('/', [TelegramMessageController::class, 'store'])->name('store');
            Route::get('/{batch}', [TelegramMessageController::class, 'show'])->name('show');
        });
    });

    Route::get('/stats', function () {
        $users = User::where('status', '!=', 'deleted')->count();
        $orders = Order::where('status', '!=', 'deleted')->count();
        $azots = Azot::where('status', '!=', 'deleted')->count();
        $services = AdditionalService::where('status', '!=', 'deleted')->count();
        return response()->json([
            'users' => $users,
            'orders' => $orders,
            'azots' => $azots,
            'services' => $services
        ]);
    });

});