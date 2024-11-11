<?php

namespace App\Http\Controllers\API\LinkedIn\Controllers;

use App\Http\Controllers\API\LinkedIn\BaseLinkedInController;
use App\SocialAnalytics\LinkedIn\Services\LinkedInProfileService;
use App\SocialAnalytics\LinkedIn\Services\LinkedInAnalyticsService;
use Illuminate\Http\Request;

class ProfileController extends BaseLinkedInController
{
    private LinkedInProfileService $profileService;
    private LinkedInAnalyticsService $analyticsService;

    public function __construct(
        LinkedInProfileService $profileService,
        LinkedInAnalyticsService $analyticsService
    ) {
        $this->profileService = $profileService;
        $this->analyticsService = $analyticsService;
    }

    public function show(string $profileId)
    {
        $profile = $this->validateLinkedInProfile($profileId);
        return $this->successResponse($profile->toArray());
    }

    public function analytics(string $profileId)
    {
        $profile = $this->validateLinkedInProfile($profileId);
        $analytics = $this->analyticsService->getAnalytics($profileId);
        return $this->successResponse($analytics->toArray());
    }

    public function update(Request $request, string $profileId)
    {
        $profile = $this->validateLinkedInProfile($profileId);
        
        $validated = $request->validate([
            'headline' => 'sometimes|string|max:255',
            'industry' => 'sometimes|string|max:100',
            'location' => 'sometimes|string|max:100',
            'summary' => 'sometimes|string',
            'settings' => 'sometimes|array'
        ]);

        $profile = $this->profileService->updateProfile($profile, $validated);
        return $this->successResponse($profile->toArray());
    }

    public function insights(string $profileId)
    {
        $profile = $this->validateLinkedInProfile($profileId);
        $insights = $this->profileService->getAudienceInsights($profileId);
        return $this->successResponse($insights);
    }
}