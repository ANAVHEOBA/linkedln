<?php

namespace App\SocialAnalytics\Common\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class BaseAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    protected function logJobStart(): void
    {
        \Log::info(class_basename($this) . ' started', [
            'job_id' => $this->job->getJobId(),
            'attempt' => $this->attempts()
        ]);
    }

    protected function logJobEnd(): void
    {
        \Log::info(class_basename($this) . ' completed', [
            'job_id' => $this->job->getJobId(),
            'execution_time' => microtime(true) - LARAVEL_START
        ]);
    }
}