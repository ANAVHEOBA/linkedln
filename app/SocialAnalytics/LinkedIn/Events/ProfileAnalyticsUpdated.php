<?php

namespace App\SocialAnalytics\LinkedIn\Events;

use App\SocialAnalytics\Common\Events\BaseAnalyticsEvent;
use App\SocialAnalytics\LinkedIn\DTOs\LinkedInProfileAnalyticsDTO;

class ProfileAnalyticsUpdated extends BaseAnalyticsEvent
{
    public LinkedInProfileAnalyticsDTO $profileAnalytics;
    public array $changes;
    public bool $significantChange;

    public function __construct(
        LinkedInProfileAnalyticsDTO $profileAnalytics,
        array $changes,
        bool $significantChange = false
    ) {
        parent::__construct();
        
        $this->profileAnalytics = $profileAnalytics;
        $this->changes = $changes;
        $this->significantChange = $significantChange;
    }

    public function broadcastOn()
    {
        return ['profile-analytics'];
    }
}