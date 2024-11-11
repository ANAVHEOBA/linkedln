<?php

namespace App\SocialAnalytics\LinkedIn\DTOs;

use App\SocialAnalytics\Common\DTOs\BaseDTO;

class LinkedInEngagementDTO extends BaseDTO
{
    public string $post_id;
    public int $likes;
    public int $comments;
    public int $shares;
    public int $clicks;
    public int $impressions;
    public float $engagement_rate;
    public array $engagement_breakdown;
    public array $interaction_timeline;
    public array $user_segments;

    public function __construct(array $data = [])
    {
        $this->post_id = '';
        $this->likes = 0;
        $this->comments = 0;
        $this->shares = 0;
        $this->clicks = 0;
        $this->impressions = 0;
        $this->engagement_rate = 0.0;
        $this->engagement_breakdown = [];
        $this->interaction_timeline = [];
        $this->user_segments = [];

        parent::__construct($data);
    }

    public function getTotalEngagements(): int
    {
        return $this->likes + $this->comments + $this->shares + $this->clicks;
    }

    public function getEngagementQualityScore(): float
    {
        $weightedScore = ($this->comments * 3) + ($this->shares * 2) + $this->likes;
        return $weightedScore / max(1, $this->impressions) * 100;
    }
}