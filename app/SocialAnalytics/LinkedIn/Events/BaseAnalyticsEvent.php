<?php

namespace App\SocialAnalytics\Common\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseAnalyticsEvent
{
    use Dispatchable, SerializesModels;

    public $timestamp;

    public function __construct()
    {
        $this->timestamp = now();
    }
}