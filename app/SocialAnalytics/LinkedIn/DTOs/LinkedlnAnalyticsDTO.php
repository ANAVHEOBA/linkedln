<?php

namespace App\SocialAnalytics\LinkedIn\DTOs;

use App\SocialAnalytics\Common\DTOs\BaseDTO;

class LinkedInAnalyticsDTO extends BaseDTO
{
    public int $profile_views;
    public int $post_impressions;
    public float $engagement_rate;
    public float $connection_growth;
    public array $metrics;
    public array $timeline_data;
    public array $demographic_data;

    public function __construct(array $data = [])
    {
        $this->profile_views = 0;
        $this->post_impressions = 0;
        $this->engagement_rate = 0.0;
        $this->connection_growth = 0.0;
        $this->metrics = [];
        $this->timeline_data = [];
        $this->demographic_data = [];

        parent::__construct($data);
    }

    public function getEngagementScore(): float
    {
        return ($this->engagement_rate * $this->post_impressions) / 100;
    }
}