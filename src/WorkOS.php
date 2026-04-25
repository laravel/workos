<?php

namespace Laravel\WorkOS;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;
use WorkOS\WorkOS as SDK;

class WorkOS
{
    /**
     * Get the configured WorkOS client ID.
     */
    public static function clientId(): string
    {
        return config('services.workos.client_id')
            ?: throw new RuntimeException("The 'services.workos.client_id' configuration value is undefined.");
    }

    /**
     * Get the configured WorkOS API secret.
     */
    public static function secret(): string
    {
        return config('services.workos.secret')
            ?: throw new RuntimeException("The 'services.workos.secret' configuration value is undefined.");
    }

    /**
     * Get the configured WorkOS redirect URL.
     */
    public static function redirectUrl(): string
    {
        return config('services.workos.redirect_url')
            ?: throw new RuntimeException("The 'services.workos.redirect_url' configuration value is undefined.");
    }

    /**
     * Get the configured WorkOS API base URL.
     */
    public static function baseUrl(): string
    {
        return config('services.workos.base_url', 'https://api.workos.com');
    }

    /**
     * Build a configured WorkOS SDK client.
     */
    public static function client(): SDK
    {
        return new SDK(
            apiKey: static::secret(),
            clientId: static::clientId(),
            baseUrl: static::baseUrl(),
        );
    }

    /**
     * Ensure the given access token is valid, refreshing it if necessary.
     */
    public static function ensureAccessTokenIsValid(string $accessToken, string $refreshToken): array
    {
        $workOsSession = static::decodeAccessToken($accessToken);

        if (! $workOsSession) {
            $result = static::client()->userManagement()->authenticateWithRefreshToken(
                refreshToken: $refreshToken,
            );

            return [
                $result->accessToken,
                $result->refreshToken,
            ];
        }

        return [
            $accessToken,
            $refreshToken,
        ];
    }

    /**
     * Decode the given WorkOS access token.
     */
    public static function decodeAccessToken(string $accessToken): array|bool
    {
        try {
            return (array) JWT::decode($accessToken, JWK::parseKeySet(static::getJwk()));
        } catch (Throwable $e) {
            //
        }

        return false;
    }

    /**
     * Get the WorkOS JWK.
     */
    protected static function getJwk(): array
    {
        return Cache::remember('workos:jwk', now()->addHours(12), function () {
            return static::client()->userManagement()->getJwks(
                static::clientId(),
            )->toArray();
        });
    }
}
