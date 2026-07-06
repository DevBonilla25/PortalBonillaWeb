<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Driver\DeliveryEvidenceController;
use App\Http\Controllers\Api\V1\Driver\DriverLocationController;
use App\Http\Controllers\Api\V1\Driver\DriverTicketController;
use App\Http\Controllers\Api\V1\Driver\NoveltyReasonController;
use App\Http\Controllers\Api\V1\Driver\TicketNoveltyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::get('driver/tickets', [DriverTicketController::class, 'index']);
        Route::get('driver/novelty-reasons', [NoveltyReasonController::class, 'index']);
        Route::get('driver/tickets/{ticket}', [DriverTicketController::class, 'show']);
        Route::post('driver/tickets/{ticket}/change-status', [DriverTicketController::class, 'changeStatus']);
        Route::post('driver/tickets/{ticket}/evidence', [DeliveryEvidenceController::class, 'store']);
        Route::post('driver/tickets/{ticket}/novelties', [TicketNoveltyController::class, 'store']);
        Route::post('driver/location', [DriverLocationController::class, 'store']);
    });
});
