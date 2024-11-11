<?php

namespace App\SocialAnalytics\LinkedIn\DTOs;

use App\SocialAnalytics\Common\DTOs\BaseDTO;

class LinkedInProfileAnalyticsDTO extends BaseDTO
{
    public string $profile_id;
    public int $followers_count;
    public int $connections_count;
    public float $profile_strength;
    public float $network_growth_rate;
    public array $engagement_metrics;
    public array $top_performing_content;
    public float $recommendation_score;
    public array $industry_metrics;
    public array $growth_trends;

    public function __construct(array $data = [])
    {
        $this->profile_id = '';
        $this->followers_count = 0;
        $this->connections_count = 0;
        $this->profile_strength = 0.0;
        $this->network_growth_rate = 0.0;
        $this->engagement_metrics = [];
        $this->top_performing_content = [];
        $this->recommendation_score = 0.0;
        $this->industry_metrics = [];
        $this->growth_trends = [];

        parent::__construct($data);
    }

    public function getNetworkStrength(): float
    {
        return ($this->followers_count + $this->connections_count) * $this->profile_strength;
    }
}