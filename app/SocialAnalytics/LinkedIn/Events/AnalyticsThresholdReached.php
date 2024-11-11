<?php

namespace App\SocialAnalytics\LinkedIn\Events;

use App\SocialAnalytics\Common\Events\BaseAnalyticsEvent;

class AnalyticsThresholdReached extends BaseAnalyticsEvent
{
    public string $metricType;
    public float $threshold;
    public float $currentValue;
    public string $profileId;
    public array $additionalData;

    public function __construct(
        string $metricType,
        float $threshold,
        float $currentValue,
        string $profileId,
        array $additionalData = []
    ) {
        parent::__construct();
        
        $this->metricType = $metricType;
        $this->threshold = $threshold;
        $this->currentValue = $currentValue;
        $this->profileId = $profileId;
        $this->additionalData = $additionalData;
    }
}