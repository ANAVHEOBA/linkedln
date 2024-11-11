<?php

namespace App\SocialAnalytics\LinkedIn\Events;

use App\SocialAnalytics\Common\Events\BaseAnalyticsEvent;
use App\SocialAnalytics\LinkedIn\DTOs\LinkedInPostAnalyticsDTO;

class PostPerformanceAlert extends BaseAnalyticsEvent
{
    public LinkedInPostAnalyticsDTO $postAnalytics;
    public string $alertType;
    public array $metrics;
    public string $severity;

    public function __construct(
        LinkedInPostAnalyticsDTO $postAnalytics,
        string $alertType,
        array $metrics,
        string $severity = 'info'
    ) {
        parent::__construct();
        
        $this->postAnalytics = $postAnalytics;
        $this->alertType = $alertType;
        $this->metrics = $metrics;
        $this->severity = $severity;
    }
}