<?php

namespace App\SocialAnalytics\LinkedIn\Services;

use App\SocialAnalytics\LinkedIn\DTOs\LinkedInProfileAnalyticsDTO;
use Illuminate\Support\Facades\Cache;

class LinkedInProfileService
{
    private $analyticsService;
    private $postService;

    public function __construct(
        LinkedInAnalyticsService $analyticsService,
        LinkedInPostService $postService
    ) {
        $this->analyticsService = $analyticsService;
        $this->postService = $postService;
    }

    public function getProfileAnalytics(string $profileId): LinkedInProfileAnalyticsDTO
    {
        $cacheKey = "linkedin_profile_analytics_{$profileId}";

        return Cache::remember($cacheKey, 3600, function () use ($profileId) {
            $analytics = $this->analyticsService->getAnalytics($profileId);
            $topPosts = $this->postService->getBestPerformingPosts($profileId);
            
            return $this->compileProfileAnalytics($analytics, $topPosts);
        });
    }

    public function getAudienceInsights(string $profileId): array
    {
        return [
            'demographics' => $this->getAudienceDemographics($profileId),
            'interests' => $this->getAudienceInterests($profileId),
            'industry_breakdown' => $this->getIndustryBreakdown($profileId),
            'growth_trends' => $this->getGrowthTrends($profileId)
        ];
    }

    private function compileProfileAnalytics($analytics, $topPosts): LinkedInProfileAnalyticsDTO
    {
        return new LinkedInProfileAnalyticsDTO([
            'profile_strength' => $this->calculateProfileStrength($analytics),
            'network_growth_rate' => $this->calculateNetworkGrowth($analytics),
            'engagement_metrics' => $this->compileEngagementMetrics($analytics),
            'top_performing_content' => $topPosts,
            'recommendation_score' => $this->calculateRecommendationScore($analytics)
        ]);
    }
}