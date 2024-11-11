<?php

namespace App\SocialAnalytics\LinkedIn\Jobs;

use App\SocialAnalytics\Common\Jobs\BaseAnalyticsJob;
use App\SocialAnalytics\LinkedIn\Services\LinkedInAnalyticsService;
use App\SocialAnalytics\LinkedIn\Events\AnalyticsProcessingFailed;
use App\SocialAnalytics\LinkedIn\Models\LinkedInAnalytics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class FetchLinkedInAnalytics extends BaseAnalyticsJob
{
    private string $profileId;
    private array $metrics;
    private bool $forceRefresh;
    
    private const CACHE_TTL = 3600; // 1 hour
    private const BATCH_SIZE = 100;

    public function __construct(string $profileId, array $metrics = [], bool $forceRefresh = false)
    {
        $this->profileId = $profileId;
        $this->metrics = $metrics;
        $this->forceRefresh = $forceRefresh;
        $this->onQueue('linkedin-analytics');
    }

    public function handle(LinkedInAnalyticsService $analyticsService)
    {
        try {
            $this->logJobStart();

            DB::beginTransaction();

            $analytics = $analyticsService->getAnalytics(
                $this->profileId,
                $this->metrics,
                $this->forceRefresh
            );

            $this->processAnalytics($analytics);
            
            DB::commit();

            // Update cache
            $this->updateAnalyticsCache($analytics);

            $this->logJobEnd();
        } catch (\Exception $e) {
            DB::rollBack();
            
            event(new AnalyticsProcessingFailed(
                'fetch_analytics',
                $e->getMessage(),
                [
                    'profile_id' => $this->profileId,
                    'metrics' => $this->metrics
                ],
                $e
            ));

            throw $e;
        }
    }

    private function processAnalytics($analytics): void
    {
        // Process different types of analytics
        $this->processProfileMetrics($analytics['profile'] ?? []);
        $this->processEngagementMetrics($analytics['engagement'] ?? []);
        $this->processContentMetrics($analytics['content'] ?? []);
        $this->processAudienceMetrics($analytics['audience'] ?? []);
        
        // Store historical data
        $this->storeHistoricalData($analytics);
    }

    private function processProfileMetrics(array $profileMetrics): void
    {
        LinkedInAnalytics::updateOrCreate(
            ['profile_id' => $this->profileId],
            [
                'followers_count' => $profileMetrics['followers'] ?? 0,
                'connections_count' => $profileMetrics['connections'] ?? 0,
                'profile_views' => $profileMetrics['views'] ?? 0,
                'search_appearances' => $profileMetrics['search_appearances'] ?? 0,
                'updated_at' => Carbon::now()
            ]
        );
    }

    private function processEngagementMetrics(array $engagementMetrics): void
    {
        $batch = [];
        foreach ($engagementMetrics as $metric) {
            $batch[] = [
                'profile_id' => $this->profileId,
                'metric_type' => 'engagement',
                'metric_value' => $metric['value'],
                'metric_date' => $metric['date'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];

            if (count($batch) >= self::BATCH_SIZE) {
                DB::table('linkedin_metrics')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('linkedin_metrics')->insert($batch);
        }
    }

    private function processContentMetrics(array $contentMetrics): void
    {
        foreach ($contentMetrics as $postId => $metrics) {
            DB::table('linkedin_post_metrics')->updateOrInsert(
                ['post_id' => $postId],
                [
                    'impressions' => $metrics['impressions'] ?? 0,
                    'clicks' => $metrics['clicks'] ?? 0,
                    'reactions' => $metrics['reactions'] ?? 0,
                    'comments' => $metrics['comments'] ?? 0,
                    'shares' => $metrics['shares'] ?? 0,
                    'updated_at' => Carbon::now()
                ]
            );
        }
    }

    private function processAudienceMetrics(array $audienceMetrics): void
    {
        DB::table('linkedin_audience_metrics')->updateOrInsert(
            ['profile_id' => $this->profileId],
            [
                'demographics' => json_encode($audienceMetrics['demographics'] ?? []),
                'industries' => json_encode($audienceMetrics['industries'] ?? []),
                'locations' => json_encode($audienceMetrics['locations'] ?? []),
                'updated_at' => Carbon::now()
            ]
        );
    }

    private function storeHistoricalData(array $analytics): void
    {
        $historicalData = [
            'profile_id' => $this->profileId,
            'data' => json_encode($analytics),
            'captured_at' => Carbon::now()
        ];

        DB::table('linkedin_historical_data')->insert($historicalData);
    }

    private function updateAnalyticsCache(array $analytics): void
    {
        $cacheKey = "linkedin_analytics_{$this->profileId}";
        Cache::put($cacheKey, $analytics, self::CACHE_TTL);
    }
}