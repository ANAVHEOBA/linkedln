<?php

namespace App\SocialAnalytics\LinkedIn\Services;

use App\SocialAnalytics\LinkedIn\DTOs\LinkedInEngagementDTO;
use App\SocialAnalytics\LinkedIn\Events\NewLinkedInEngagement;
use Illuminate\Support\Facades\Event;

class LinkedInEngagementService
{
    private $analyticsService;

    public function __construct(LinkedInAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function trackEngagement(string $postId, array $engagementData): LinkedInEngagementDTO
    {
        $engagement = $this->processEngagement($postId, $engagementData);
        
        // Dispatch event for real-time tracking
        Event::dispatch(new NewLinkedInEngagement($engagement));

        return $engagement;
    }

    public function getEngagementMetrics(string $postId, string $timeframe = 'daily'): array
    {
        return [
            'likes' => $this->getLikeMetrics($postId, $timeframe),
            'comments' => $this->getCommentMetrics($postId, $timeframe),
            'shares' => $this->getShareMetrics($postId, $timeframe),
            'clicks' => $this->getClickMetrics($postId, $timeframe)
        ];
    }

    private function processEngagement(string $postId, array $data): LinkedInEngagementDTO
    {
        return new LinkedInEngagementDTO([
            'post_id' => $postId,
            'likes' => $data['likes'] ?? 0,
            'comments' => $data['comments'] ?? 0,
            'shares' => $data['shares'] ?? 0,
            'clicks' => $data['clicks'] ?? 0,
            'impressions' => $data['impressions'] ?? 0,
            'engagement_rate' => $this->calculateEngagementRate($data)
        ]);
    }
}