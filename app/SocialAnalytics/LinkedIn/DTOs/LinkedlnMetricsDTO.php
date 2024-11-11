<?php

namespace App\SocialAnalytics\LinkedIn\DTOs;

use App\SocialAnalytics\Common\DTOs\BaseDTO;

class LinkedInMetricsDTO extends BaseDTO
{
    public string $metric_id;
    public string $metric_type;
    public string $time_period;
    public array $data_points;
    public array $benchmarks;
    public array $trends;
    public array $forecasts;

    public function __construct(array $data = [])
    {
        $this->metric_id = '';
        $this->metric_type = '';
        $this->time_period = '';
        $this->data_points = [];
        $this->benchmarks = [];
        $this->trends = [];
        $this->forecasts = [];

        parent::__construct($data);
    }

    public function getGrowthRate(): float
    {
        if (empty($this->data_points)) {
            return 0.0;
        }

        $first = reset($this->data_points);
        $last = end($this->data_points);

        if ($first == 0) return 0.0;

        return (($last - $first) / $first) * 100;
    }
}