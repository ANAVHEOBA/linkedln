<?php

namespace App\Http\Controllers\API\LinkedIn\Controllers;

use App\Http\Controllers\API\LinkedIn\BaseLinkedInController;
use App\SocialAnalytics\LinkedIn\Services\LinkedInAnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends BaseLinkedInController
{
    private LinkedInAnalyticsService $analyticsService;

    public function __construct(LinkedInAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function dashboard(string $profileId)
    {
        $profile = $this->validateLinkedInProfile($profileId);
        $dashboard = $this->analyticsService->getDashboardMetrics($profileId);
        return $this->successResponse($dashboard);
    }

    public function report(Request $request, string $profileId)
    {
        $profile = $this->validateLinkedInProfile($profileId);
        
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'metrics' => 'sometimes|array',
            'format' => 'sometimes|string|in:pdf,csv,json'
        ]);

        $report = $this->analyticsService->generateReport(
            $profileId,
            $validated['start_date'],
            $validated['end_date'],
            $validated['metrics'] ?? [],
            $validated['format'] ?? 'json'
        );

        return $this->successResponse($report);
    }

    public function trends(string $profileId)
    {
        $profile = $this->validateLinkedInProfile($profileId);
        $trends = $this->analyticsService->getAnalyticsTrends($profileId);
        return $this->successResponse($trends);
    }
}