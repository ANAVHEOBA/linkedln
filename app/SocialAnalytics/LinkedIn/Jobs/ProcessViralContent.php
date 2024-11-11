<?php

namespace App\SocialAnalytics\LinkedIn\Jobs;

use App\SocialAnalytics\Common\Jobs\BaseAnalyticsJob;
use App\SocialAnalytics\LinkedIn\Services\LinkedInPostService;
use App\SocialAnalytics\LinkedIn\Events\ViralContentDetected;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessViralContent extends BaseAnalyticsJob
{
    private string $postId;
    private array $metrics;
    
    // Virality thresholds
    private const ENGAGEMENT_WEIGHT = 0.4;
    private const SHARE_WEIGHT = 0.3;
    private const VELOCITY_WEIGHT = 0.3;
    private const TIME_WINDOW_HOURS = 24;

    public function __construct(string $postId, array $metrics)
    {
        $this->postId = $postId;
        $this->metrics = $metrics;
        $this->onQueue('content-analysis');
    }

    public function handle(LinkedInPostService $postService)
    {
        $this->logJobStart();

        try {
            $viralityScore = $this->calculateViralityScore();
            
            if ($this->isViralContent($viralityScore)) {
                $engagementData = $this->getEngagementData();
                $contentType = $this->determineContentType();
                
                // Cache viral content data
                $this->cacheViralContent($viralityScore, $engagementData);
                
                event(new ViralContentDetected(
                    $this->postId,
                    $this->metrics,
                    $viralityScore,
                    $engagementData,
                    $contentType
                ));
                
                // Update post status in database
                $postService->markAsViral($this->postId, $viralityScore);
            }

            $this->logJobEnd();
        } catch (\Exception $e) {
            Log::error('Viral content processing failed', [
                'post_id' => $this->postId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function calculateViralityScore(): float
    {
        $engagementScore = $this->calculateEngagementScore();
        $shareScore = $this->calculateShareScore();
        $velocityScore = $this->calculateVelocityScore();

        return (
            $engagementScore * self::ENGAGEMENT_WEIGHT +
            $shareScore * self::SHARE_WEIGHT +
            $velocityScore * self::VELOCITY_WEIGHT
        ) * 100;
    }

    private function calculateEngagementScore(): float
    {
        $totalEngagements = $this->metrics['likes'] + 
                           $this->metrics['comments'] * 2 + 
                           $this->metrics['reactions'] * 1.5;
        
        $impressions = max($this->metrics['impressions'], 1);
        return $totalEngagements / $impressions;
    }

    private function calculateShareScore(): float
    {
        $shares = $this->metrics['shares'] ?? 0;
        $connections = $this->metrics['author_connections'] ?? 1;
        return $shares / sqrt($connections);
    }

    private function calculateVelocityScore(): float
    {
        $timeElapsed = max(1, $this->metrics['hours_since_posted']);
        $engagements = $this->metrics['total_engagements'] ?? 0;
        return ($engagements / $timeElapsed) / self::TIME_WINDOW_HOURS;
    }

    private function isViralContent(float $score): bool
    {
        $threshold = config('linkedin.viral_threshold', 75.0);
        return $score > $threshold;
    }

    private function getEngagementData(): array
    {
        return [
            'total_engagements' => $this->metrics['total_engagements'] ?? 0,
            'engagement_rate' => $this->calculateEngagementScore(),
            'share_rate' => $this->calculateShareScore(),
            'velocity' => $this->calculateVelocityScore(),
            'engagement_breakdown' => [
                'likes' => $this->metrics['likes'] ?? 0,
                'comments' => $this->metrics['comments'] ?? 0,
                'shares' => $this->metrics['shares'] ?? 0,
                'reactions' => $this->metrics['reactions'] ?? 0
            ],
            'time_metrics' => [
                'posted_at' => $this->metrics['posted_at'],
                'viral_detection_at' => now()
            ]
        ];
    }

    private function determineContentType(): string
    {
        return match ($this->metrics['content_type'] ?? 'text') {
            'image' => 'image_post',
            'video' => 'video_post',
            'article' => 'article_post',
            'document' => 'document_post',
            default => 'text_post'
        };
    }

    private function cacheViralContent(float $viralityScore, array $engagementData): void
    {
        $cacheKey = "viral_content_{$this->postId}";
        $cacheData = [
            'virality_score' => $viralityScore,
            'engagement_data' => $engagementData,
            'detected_at' => now(),
        ];
        
        Cache::put($cacheKey, $cacheData, now()->addDays(7));
    }
}