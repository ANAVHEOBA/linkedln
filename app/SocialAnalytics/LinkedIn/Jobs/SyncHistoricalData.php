<?php

namespace App\SocialAnalytics\LinkedIn\Jobs;

use App\SocialAnalytics\Common\Jobs\BaseAnalyticsJob;
use App\SocialAnalytics\LinkedIn\Services\LinkedInAnalyticsService;
use Carbon\Carbon;

class SyncHistoricalData extends BaseAnalyticsJob
{
    private string $profileId;
    private Carbon $startDate;
    private Carbon $endDate;

    public function __construct(string $profileId, Carbon $startDate, Carbon $endDate)
    {
        $this->profileId = $profileId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->onQueue('data-sync');
    }

    public function handle(LinkedInAnalyticsService $analyticsService)
    {
        $this->logJobStart();

        $currentDate = clone $this->startDate;
        
        while ($currentDate <= $this->endDate) {
            $this->syncDayData($currentDate, $analyticsService);
            $currentDate->addDay();
        }

        $this->logJobEnd();
    }

    private function syncDayData(Carbon $date, LinkedInAnalyticsService $service): void
    {
        try {
            $data = $service->getHistoricalData($this->profileId, $date);
            $this->processAndStoreHistoricalData($data, $date);
        } catch (\Exception $e) {
            \Log::error("Historical data sync failed for {$date->format('Y-m-d')}", [
                'profile_id' => $this->profileId,
                'error' => $e->getMessage()
            ]);
        }
    }
}