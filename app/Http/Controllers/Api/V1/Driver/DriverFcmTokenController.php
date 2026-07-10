<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\StoreFcmTokenRequest;
use App\Models\DriverFcmToken;
use App\Models\DriverProfile;
use Illuminate\Http\JsonResponse;

class DriverFcmTokenController extends Controller
{
    public function store(StoreFcmTokenRequest $request): JsonResponse
    {
        $driver = $this->driver($request);
        $data = $request->validated();
        $token = $data['token'];

        $fcmToken = DriverFcmToken::query()->updateOrCreate(
            ['token_hash' => hash('sha256', $token)],
            [
                'driver_profile_id' => $driver->id,
                'user_id' => $request->user()->id,
                'token' => $token,
                'platform' => $data['platform'] ?? null,
                'last_seen_at' => now(),
            ],
        );

        return response()->json([
            'message' => 'Token FCM registrado.',
            'data' => [
                'id' => $fcmToken->id,
                'platform' => $fcmToken->platform,
                'last_seen_at' => $fcmToken->last_seen_at?->toISOString(),
            ],
        ]);
    }

    private function driver(StoreFcmTokenRequest $request): DriverProfile
    {
        $driver = $request->user()->driverProfile;

        abort_unless($driver && $driver->is_active, 403, 'No tienes un perfil de chofer activo.');

        return $driver;
    }
}
