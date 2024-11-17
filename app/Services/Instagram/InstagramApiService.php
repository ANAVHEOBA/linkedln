<?php

namespace App\Services\Instagram;

use App\Models\InstagramAccount;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InstagramApiService
{
    protected $config;
    protected $account;
    protected $authService;

    public function __construct(InstagramAuthService $authService)
    {
        $this->config = config('instagram');
        $this->authService = $authService;
        $this->account = auth()->user()->instagramAccount;
    }

    /**
     * Make an API call to Instagram
     */
    protected function makeRequest(string $method, string $endpoint, array $params = [], array $headers = [])
    {
        if (!$this->account) {
            throw new \Exception('No Instagram account connected');
        }

        // Check if token needs refresh
        if ($this->account->needsTokenRefresh()) {
            $this->authService->refreshAccessToken($this->account);
            $this->account->refresh();
        }

        $params['access_token'] = $this->account->access_token;

        $response = Http::withHeaders($headers)
            ->$method($this->config['graph_url'] . $endpoint, $params);

        if (!$response->successful()) {
            Log::error('Instagram API error', [
                'endpoint' => $endpoint,
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            throw new \Exception('Instagram API request failed');
        }

        return $response->json();
    }

    /**
     * Create a media container
     */
    public function createMediaContainer(string $mediaType, $media, ?string $caption = null): array
    {
        $params = [
            'media_type' => $mediaType,
            'caption' => $caption,
        ];

        if ($media instanceof UploadedFile) {
            // Handle file upload
            $params['image_url'] = $this->uploadMedia($media);
        } else {
            $params['media_url'] = $media;
        }

        return $this->makeRequest('POST', '/media', $params);
    }

    /**
     * Publish a media container
     */
    public function publishMedia(string $creationId): array
    {
        return $this->makeRequest('POST', '/media_publish', [
            'creation_id' => $creationId
        ]);
    }

    /**
     * Create a post
     */
    public function createPost($mediaType, $media, ?string $caption = null, ?string $locationId = null): array
    {
        // First create the media container
        $container = $this->createMediaContainer($mediaType, $media, $caption);

        // Then publish it
        return $this->publishMedia($container['id']);
    }

    /**
     * Get user's media
     */
    public function getUserMedia(int $limit = 25): array
    {
        $cacheKey = "instagram_media_{$this->account->id}";

        return Cache::remember($cacheKey, $this->config['cache']['media_timeout'], function () use ($limit) {
            return $this->makeRequest('GET', '/me/media', [
                'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username',
                'limit' => $limit
            ]);
        });
    }

    /**
     * Get media details
     */
    public function getMediaDetails(string $mediaId): array
    {
        $cacheKey = "instagram_media_details_{$mediaId}";

        return Cache::remember($cacheKey, $this->config['cache']['media_timeout'], function () use ($mediaId) {
            return $this->makeRequest('GET', "/{$mediaId}", [
                'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username'
            ]);
        });
    }

    /**
     * Upload media file
     */
    protected function uploadMedia(UploadedFile $file): string
    {
        // Implementation depends on your file storage solution
        // This is just a placeholder
        $path = $file->store('instagram-media', 'public');
        return url('storage/' . $path);
    }
}