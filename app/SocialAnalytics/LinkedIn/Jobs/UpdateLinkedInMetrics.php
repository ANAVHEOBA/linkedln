<?php

namespace App\SocialAnalytics\LinkedIn\Jobs;

use App\SocialAnalytics\Common\Jobs\BaseAnalyticsJob;
use App\SocialAnalytics\LinkedIn\Services\LinkedInAnalyticsService;
use App\SocialAnalytics\LinkedIn\DTOs\LinkedInMetricsDTO;
use Illuminate\Support\Facades\Cache;

class UpdateLinkedInMetrics extends BaseAnalyticsJob
{
    private string $profileId;
    private array $metricTypes;
    private string $timeframe;

    public function __construct(string $profileId, array $metricTypes, string $timeframe = 'daily')
    {
        $this->profileId = $profileId;
        $this->metricTypes = $metricTypes;
        $this->timeframe = $timeframe;
        $this->onQueue('metrics-processing');
    }

    public function handle(LinkedInAnalyticsService $analyticsService)
    {
        $this->logJobStart();

        foreach ($this->metricTypes as $metricType) {
            $this->processMetricType($metricType, $analyticsService);
        }

        $this->logJobEnd();
    }

    private function processMetricType(string $metricType, LinkedInAnalyticsService $service): void
    {
        $metrics = $service->getMetricsByType($this->profileId, $metricType, $this->timeframe);
        $this->updateCache($metricType, $metrics);
        $this->storeMetrics($metrics);
    }

    private function updateCache(string $metricType, LinkedInMetricsDTO $metrics): void
    {
        $cacheKey = "linkedin_metrics_{$this->profileId}_{$metricType}";
        Cache::put($cacheKey, $metrics, now()->addHours(1));
    }
}