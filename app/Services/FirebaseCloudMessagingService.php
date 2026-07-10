<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FirebaseCloudMessagingService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $projectId = config('services.firebase.project_id');

        if (blank($projectId) || blank($token)) {
            Log::warning('Firebase push omitido: falta project_id o token.');

            return false;
        }

        try {
            $response = Http::withToken($this->accessToken())
                ->acceptJson()
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => $this->stringData([
                            ...$data,
                            'title' => $title,
                            'body' => $body,
                        ]),
                        'android' => [
                            'priority' => 'HIGH',
                        ],
                        'apns' => [
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('Firebase push fallido.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $exception) {
            Log::warning('Firebase push no enviado.', [
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function accessToken(): string
    {
        $serviceAccount = $this->serviceAccount();
        $cacheKey = 'firebase_access_token_'.sha1((string) ($serviceAccount['client_email'] ?? 'default'));

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($serviceAccount): string {
            $response = Http::asForm()
                ->acceptJson()
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $this->jwtAssertion($serviceAccount),
                ]);

            if ($response->failed() || blank($response->json('access_token'))) {
                throw new RuntimeException('No se pudo obtener access token de Firebase.');
            }

            return (string) $response->json('access_token');
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceAccount(): array
    {
        $json = config('services.firebase.credentials_json');

        if (filled($json)) {
            return $this->decodeServiceAccount((string) $json);
        }

        $path = config('services.firebase.credentials_path');

        if (blank($path)) {
            throw new RuntimeException('No hay credenciales Firebase configuradas.');
        }

        $resolvedPath = $this->isAbsolutePath((string) $path)
            ? (string) $path
            : base_path((string) $path);

        if (! is_file($resolvedPath)) {
            throw new RuntimeException("No existe el archivo de credenciales Firebase: {$resolvedPath}");
        }

        return $this->decodeServiceAccount((string) file_get_contents($resolvedPath));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeServiceAccount(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded) || blank($decoded['client_email'] ?? null) || blank($decoded['private_key'] ?? null)) {
            throw new RuntimeException('Credenciales Firebase invalidas.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $serviceAccount
     */
    private function jwtAssertion(array $serviceAccount): string
    {
        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $serviceAccount['client_email'],
            'scope' => self::SCOPE,
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $unsigned = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR))
            .'.'.$this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR));

        $privateKey = str_replace('\\n', "\n", (string) $serviceAccount['private_key']);

        if (! openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar JWT para Firebase.');
        }

        return $unsigned.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function stringData(array $data): array
    {
        return collect($data)
            ->filter(fn ($value): bool => $value !== null)
            ->map(fn ($value): string => (string) $value)
            ->all();
    }
}
