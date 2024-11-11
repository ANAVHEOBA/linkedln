<?php

namespace App\SocialAnalytics\LinkedIn\Events;

use App\SocialAnalytics\Common\Events\BaseAnalyticsEvent;
use Throwable;

class AnalyticsProcessingFailed extends BaseAnalyticsEvent
{
    public string $processType;
    public string $errorMessage;
    public array $context;
    public ?Throwable $exception;

    public function __construct(
        string $processType,
        string $errorMessage,
        array $context = [],
        ?Throwable $exception = null
    ) {
        parent::__construct();
        
        $this->processType = $processType;
        $this->errorMessage = $errorMessage;
        $this->context = $context;
        $this->exception = $exception;
    }
}