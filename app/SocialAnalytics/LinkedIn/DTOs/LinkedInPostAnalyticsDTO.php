<?php

namespace App\SocialAnalytics\LinkedIn\DTOs;

use App\SocialAnalytics\Common\DTOs\BaseDTO;

class LinkedInPostAnalyticsDTO extends BaseDTO
{
    public string $post_id;
    public string $post_type;
    public int $impressions;
    public int $unique_impressions;
    public int $clicks;
    public int $likes;
    public int $comments;
    public int $shares;
    public float $engagement_rate;
    public array $click_distribution;
    public array $audience_demographics;
    public array $time_metrics;

    public function __construct(array $data = [])
    {
        $this->post_id = '';
        $this->post_type = '';
        $this->impressions = 0;
        $this->unique_impressions = 0;
        $this->clicks = 0;
        $this->likes = 0;
        $this->comments = 0;
        $this->shares = 0;
        $this->engagement_rate = 0.0;
        $this->click_distribution = [];
        $this->audience_demographics = [];
        $this->time_metrics = [];

        parent::__construct($data);
    }

    public function getViralityScore(): float
    {
        return ($this->shares * 100) / max(1, $this->impressions);
    }
}