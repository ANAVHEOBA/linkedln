<?php

namespace App\SocialAnalytics\LinkedIn\Jobs;

use App\SocialAnalytics\Common\Jobs\BaseAnalyticsJob;
use App\SocialAnalytics\LinkedIn\Services\LinkedInAnalyticsService;
use App\SocialAnalytics\LinkedIn\Services\LinkedInProfileService;
use Illuminate\Support\Facades\Storage;

class GenerateAnalyticsReport extends BaseAnalyticsJob
{
    private string $profileId;
    private string $reportType;
    private array $dateRange;
    private string $format;

    public function __construct(
        string $profileId,
        string $reportType,
        array $dateRange,
        string $format = 'pdf'
    ) {
        $this->profileId = $profileId;
        $this->reportType = $reportType;
        $this->dateRange = $dateRange;
        $this->format = $format;
        $this->onQueue('report-generation');
    }

    public function handle(
        LinkedInAnalyticsService $analyticsService,
        LinkedInProfileService $profileService
    ) {
        $this->logJobStart();

        $data = $this->gatherReportData($analyticsService, $profileService);
        $report = $this->generateReport($data);
        $this->storeReport($report);
        $this->notifyReportCompletion();

        $this->logJobEnd();
    }

    private function gatherReportData($analyticsService, $profileService): array
    {
        return [
            'profile' => $profileService->getProfileAnalytics($this->profileId),
            'metrics' => $analyticsService->getAnalytics($this->profileId),
            'period' => $this->dateRange,
            'insights' => $this->generateInsights($analyticsService)
        ];
    }

    private function storeReport($report): void
    {
        $filename = "reports/linkedin/{$this->profileId}/{$this->reportType}-" . date('Y-m-d') . ".{$this->format}";
        Storage::put($filename, $report);
    }
}