<?php

namespace App\SocialAnalytics\LinkedIn\Services;

use App\SocialAnalytics\LinkedIn\DTOs\LinkedInAnalyticsDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\SocialAnalytics\Common\Traits\MetricsCalculator;

class LinkedInAnalyticsService
{
    use MetricsCalculator;

    private $apiUrl;
    private $accessToken;

    public function __construct()
    {
        $this->apiUrl = config('services.linkedin.api_url');
        $this->accessToken = config('services.linkedin.access_token');
    }

    public function getAnalytics(string $profileId): LinkedInAnalyticsDTO
    {
        $cacheKey = "linkedin_analytics_{$profileId}";

        return Cache::remember($cacheKey, 3600, function () use ($profileId) {
            $response = $this->fetchAnalyticsData($profileId);
            return $this->processAnalyticsData($response);
        });
    }

    private function fetchAnalyticsData(string $profileId): array
    {
        $response = Http::withToken($this->accessToken)
            ->get("{$this->apiUrl}/analytics/profiles/{$profileId}");

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch LinkedIn analytics');
        }

        return $response->json();
    }

    private function processAnalyticsData(array $data): LinkedInAnalyticsDTO
    {
        // Process and transform the raw data into DTO
        return new LinkedInAnalyticsDTO([
            'engagement_rate' => $this->calculateEngagementRate($data),
            'profile_views' => $data['profile_views'] ?? 0,
            'post_impressions' => $data['post_impressions'] ?? 0,
            'connection_growth' => $this->calculateGrowthRate($data),
            'metrics' => $this->aggregateMetrics($data)
        ]);
    }
}