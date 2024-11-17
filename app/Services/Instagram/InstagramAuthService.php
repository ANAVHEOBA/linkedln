<?php

namespace App\Services\Instagram;

use App\Models\InstagramAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InstagramAuthService
{
    protected $config;
    protected const TOKEN_EXPIRY_SECONDS = 3600; // 1 hour

    public function __construct()
    {
        $this->config = config('instagram');
    }

    /**
     * Get the Instagram authorization URL
     */
    public function getAuthorizationUrl(): string
    {
        $params = http_build_query([
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => implode(',', $this->config['scopes']),
            'response_type' => 'code',
            'state' => csrf_token(),
        ]);

        return $this->config['oauth_url'] . "?{$params}";
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken(string $code): array
    {
        try {
            $response = Http::post($this->config['api_base_url'] . '/oauth/access_token', [
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->config['redirect_uri'],
                'code' => $code,
            ]);

            if (!$response->successful()) {
                Log::error('Instagram token exchange failed', [
                    'response' => $response->json(),
                    'status' => $response->status()
                ]);
                throw new \Exception('Failed to exchange code for token');
            }

            $tokenData = $response->json();
            
            // Set expiration times
            $now = Carbon::now();
            $tokenData['token_expires_at'] = $now->copy()->addSeconds(self::TOKEN_EXPIRY_SECONDS);
            $tokenData['refresh_token_expires_at'] = $now->copy()->addSeconds(self::TOKEN_EXPIRY_SECONDS);

            return $tokenData;
        } catch (\Exception $e) {
            Log::error('Instagram auth error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Refresh an access token
     */
    public function refreshAccessToken(InstagramAccount $account): bool
    {
        $cacheKey = "instagram_token_refresh_{$account->id}";

        // Prevent multiple refresh attempts
        if (Cache::has($cacheKey)) {
            return false;
        }

        try {
            Cache::put($cacheKey, true, now()->addMinutes(5));

            $response = Http::post($this->config['api_base_url'] . '/oauth/refresh_token', [
                'grant_type' => 'refresh_token',
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'refresh_token' => $account->refresh_token,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Token refresh failed');
            }

            $data = $response->json();
            $now = Carbon::now();

            $account->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? $account->refresh_token,
                'token_expires_at' => $now->copy()->addSeconds(self::TOKEN_EXPIRY_SECONDS),
                'refresh_token_expires_at' => $now->copy()->addSeconds(self::TOKEN_EXPIRY_SECONDS),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Token refresh error: ' . $e->getMessage());
            return false;
        } finally {
            Cache::forget($cacheKey);
        }
    }

    /**
     * Check if token needs refresh
     */
    public function needsTokenRefresh(InstagramAccount $account): bool
    {
        // Refresh if token expires in less than 5 minutes
        return $account->token_expires_at->subMinutes(5)->isPast();
    }

    /**
     * Check if refresh token is expired
     */
    public function isRefreshTokenExpired(InstagramAccount $account): bool
    {
        return $account->refresh_token_expires_at->isPast();
    }
}