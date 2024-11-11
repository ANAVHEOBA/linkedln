<?php

namespace App\SocialAnalytics\LinkedIn\Events;

use App\SocialAnalytics\Common\Events\BaseAnalyticsEvent;

class ViralContentDetected extends BaseAnalyticsEvent
{
    public string $postId;
    public array $viralMetrics;
    public float $viralityScore;
    public array $engagementData;
    public string $contentType;

    public function __construct(
        string $postId,
        array $viralMetrics,
        float $viralityScore,
        array $engagementData,
        string $contentType
    ) {
        parent::__construct();
        
        $this->postId = $postId;
        $this->viralMetrics = $viralMetrics;
        $this->viralityScore = $viralityScore;
        $this->engagementData = $engagementData;
        $this->contentType = $contentType;
    }

    public function broadcastOn()
    {
        return ['viral-content'];
    }
}