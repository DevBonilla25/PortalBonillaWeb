<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::query()
            ->with('driverProfile')
            ->where('email', $data['email'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son validas.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'El usuario no esta activo.',
            ]);
        }

        if (! $user->hasAnyRole(['driver', 'external_driver'])) {
            throw ValidationException::withMessages([
                'email' => 'El usuario no tiene un rol de chofer autorizado.',
            ]);
        }

        if (! $user->driverProfile || ! $user->driverProfile->is_active) {
            throw ValidationException::withMessages([
                'email' => 'El usuario no tiene un perfil de chofer activo.',
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken($data['device_name'] ?? 'driver-api', ['driver'])->plainTextToken;

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => UserResource::make($user->refresh()->load('driverProfile')),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user()->load('driverProfile'));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesion cerrada.',
        ]);
    }
}
