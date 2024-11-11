<?php

namespace App\Http\Controllers\API\LinkedIn\Controllers;

use App\Http\Controllers\API\LinkedIn\BaseLinkedInController;
use App\SocialAnalytics\LinkedIn\Services\LinkedInEngagementService;
use Illuminate\Http\Request;

class EngagementController extends BaseLinkedInController
{
    private LinkedInEngagementService $engagementService;

    public function __construct(LinkedInEngagementService $engagementService)
    {
        $this->engagementService = $engagementService;
    }

    public function index(Request $request, string $profileId)
    {
        $profile = $this->validateLinkedInProfile($profileId);
        
        $engagements = $this->engagementService->getEngagements(
            $profileId,
            $request->get('start_date'),
            $request->get('end_date'),
            $request->get('type')
        );

        return $this->successResponse($engagements);
    }

    public function metrics(string $profileId, string $postId)
    {
        $this->validateLinkedInProfile($profileId);
        
        $metrics = $this->engagementService->getEngagementMetrics(
            $postId,
            request('timeframe', 'daily')
        );

        return $this->successResponse($metrics);
    }

    public function track(Request $request, string $profileId, string $postId)
    {
        $this->validateLinkedInProfile($profileId);
        
        $validated = $request->validate([
            'type' => 'required|string|in:like,comment,share,click',
            'metadata' => 'sometimes|array'
        ]);

        $engagement = $this->engagementService->trackEngagement(
            $postId,
            $validated['type'],
            $validated['metadata'] ?? []
        );

        return $this->successResponse($engagement->toArray());
    }
}