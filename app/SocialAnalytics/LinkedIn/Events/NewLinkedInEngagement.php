<?php

namespace App\SocialAnalytics\LinkedIn\Events;

use App\SocialAnalytics\Common\Events\BaseAnalyticsEvent;
use App\SocialAnalytics\LinkedIn\DTOs\LinkedInEngagementDTO;

class NewLinkedInEngagement extends BaseAnalyticsEvent
{
    public LinkedInEngagementDTO $engagement;
    public string $userId;
    public string $postId;

    public function __construct(LinkedInEngagementDTO $engagement, string $userId, string $postId)
    {
        parent::__construct();
        
        $this->engagement = $engagement;
        $this->userId = $userId;
        $this->postId = $postId;
    }

    public function broadcastOn()
    {
        return ['linkedin-analytics'];
    }
}