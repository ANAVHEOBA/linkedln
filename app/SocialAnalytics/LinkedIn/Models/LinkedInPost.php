<?php

namespace App\SocialAnalytics\LinkedIn\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LinkedInPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'linkedin_posts';

    protected $fillable = [
        'profile_id',
        'post_id',
        'content_type',
        'content',
        'url',
        'likes_count',
        'comments_count',
        'shares_count',
        'impressions_count',
        'clicks_count',
        'engagement_rate',
        'is_viral',
        'virality_score',
        'published_at',
        'metrics',
        'status'
    ];

    protected $casts = [
        'metrics' => 'array',
        'is_viral' => 'boolean',
        'published_at' => 'datetime',
        'engagement_rate' => 'float',
        'virality_score' => 'float'
    ];

    // Relationships
    public function analytics()
    {
        return $this->belongsTo(LinkedInAnalytics::class, 'profile_id', 'profile_id');
    }

    public function engagements()
    {
        return $this->hasMany(LinkedInEngagement::class, 'post_id', 'post_id');
    }

    // Scopes
    public function scopeViral($query)
    {
        return $query->where('is_viral', true);
    }

    public function scopePublishedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('published_at', [$startDate, $endDate]);
    }

    public function scopeByContentType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    // Methods
    public function calculateEngagementRate(): float
    {
        $totalEngagements = $this->likes_count + 
                           ($this->comments_count * 2) + 
                           ($this->shares_count * 3);
        
        return $this->impressions_count > 0 
            ? ($totalEngagements / $this->impressions_count) * 100 
            : 0;
    }

    public function updateMetrics(array $newMetrics): void
    {
        $this->metrics = array_merge($this->metrics ?? [], $newMetrics);
        $this->engagement_rate = $this->calculateEngagementRate();
        $this->save();
    }

    public function markAsViral(float $score): void
    {
        $this->update([
            'is_viral' => true,
            'virality_score' => $score,
            'metrics' => array_merge($this->metrics ?? [], [
                'viral_detection_date' => now()->toDateTimeString(),
                'viral_score_history' => array_merge(
                    $this->metrics['viral_score_history'] ?? [],
                    [$score]
                )
            ])
        ]);
    }
}