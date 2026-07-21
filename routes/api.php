<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Driver\DeliveryEvidenceController;
use App\Http\Controllers\Api\V1\Driver\DriverFcmTokenController;
use App\Http\Controllers\Api\V1\Driver\DriverLocationController;
use App\Http\Controllers\Api\V1\Driver\DriverNotificationController;
use App\Http\Controllers\Api\V1\Driver\DriverTicketController;
use App\Http\Controllers\Api\V1\Driver\NoveltyReasonController;
use App\Http\Controllers\Api\V1\Driver\TicketNoveltyController;
use App\Http\Controllers\Api\V1\LogisticOperationController;
use App\Http\Controllers\Api\V1\OperationAttachmentController;
use App\Http\Controllers\Api\V1\OperationIncidentController;
use App\Http\Controllers\Api\V1\OperationStopController;
use App\Http\Controllers\Api\V1\SubzoneController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::get('driver/tickets', [DriverTicketController::class, 'index']);
        Route::get('driver/notifications', [DriverNotificationController::class, 'index']);
        Route::post('driver/notifications/{notification}/read', [DriverNotificationController::class, 'markAsRead']);
        Route::get('driver/novelty-reasons', [NoveltyReasonController::class, 'index']);
        Route::get('driver/tickets/{ticket}', [DriverTicketController::class, 'show']);
        Route::post('driver/fcm-token', [DriverFcmTokenController::class, 'store']);
        Route::post('driver/tickets/{ticket}/change-status', [DriverTicketController::class, 'changeStatus']);
        Route::post('driver/tickets/{ticket}/evidence', [DeliveryEvidenceController::class, 'store']);
        Route::post('driver/tickets/{ticket}/novelties', [TicketNoveltyController::class, 'store']);
        Route::post('driver/location', [DriverLocationController::class, 'store']);

        Route::apiResource('subzones', SubzoneController::class)->except('show');
        Route::apiResource('logistic-operations', LogisticOperationController::class)->except('destroy');
        Route::post('logistic-operations/{logisticOperation}/transition', [LogisticOperationController::class, 'transition']);
        Route::post('logistic-operations/{logisticOperation}/incidents', [OperationIncidentController::class, 'store']);
        Route::post('logistic-operations/{logisticOperation}/attachments', [OperationAttachmentController::class, 'store']);
        Route::post('logistic-operations/{logisticOperation}/stops', [OperationStopController::class, 'store']);
        Route::post('logistic-operations/{logisticOperation}/stops/{stop}/finish', [OperationStopController::class, 'finish']);
    });
});
