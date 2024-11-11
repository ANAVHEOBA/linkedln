<?php

namespace App\SocialAnalytics\LinkedIn\Services;

use App\SocialAnalytics\LinkedIn\Models\LinkedInPost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LinkedInPostService
{
    private $engagementService;

    public function __construct(LinkedInEngagementService $engagementService)
    {
        $this->engagementService = $engagementService;
    }

    public function analyzePost(string $postId): array
    {
        $post = $this->getPost($postId);
        $engagement = $this->engagementService->getEngagementMetrics($postId);
        
        return $this->generatePostInsights($post, $engagement);
    }

    public function getBestPerformingPosts(string $profileId, int $limit = 10): Collection
    {
        $cacheKey = "linkedin_top_posts_{$profileId}";

        return Cache::remember($cacheKey, 3600, function () use ($profileId, $limit) {
            return LinkedInPost::where('profile_id', $profileId)
                ->orderBy('engagement_rate', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    private function generatePostInsights(LinkedInPost $post, array $engagement): array
    {
        return [
            'post_performance' => [
                'engagement_rate' => $engagement['engagement_rate'],
                'reach_score' => $this->calculateReachScore($post, $engagement),
                'viral_coefficient' => $this->calculateViralCoefficient($engagement),
                'best_performing_time' => $this->analyzeBestPostingTime($post)
            ],
            'content_analysis' => [
                'sentiment_score' => $this->analyzeSentiment($post),
                'content_category' => $this->categorizeContent($post),
                'keyword_performance' => $this->analyzeKeywords($post)
            ]
        ];
    }
}