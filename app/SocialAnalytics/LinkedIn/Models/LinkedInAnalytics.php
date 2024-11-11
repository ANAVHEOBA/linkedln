<?php

namespace App\SocialAnalytics\LinkedIn\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class LinkedInAnalytics extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'linkedin_analytics';

    protected $fillable = [
        'profile_id',
        'user_id',
        'followers_count',
        'connections_count',
        'profile_views',
        'post_impressions',
        'engagement_rate',
        'search_appearances',
        'demographics',
        'industry_metrics',
        'location_metrics',
        'last_sync_at',
        'metadata'
    ];

    protected $casts = [
        'demographics' => 'array',
        'industry_metrics' => 'array',
        'location_metrics' => 'array',
        'metadata' => 'array',
        'last_sync_at' => 'datetime',
        'engagement_rate' => 'float'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'last_sync_at'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function posts()
    {
        return $this->hasMany(LinkedInPost::class, 'profile_id', 'profile_id');
    }

    public function engagements()
    {
        return $this->hasMany(LinkedInEngagement::class, 'profile_id', 'profile_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHighEngagement($query, float $threshold = 2.0)
    {
        return $query->where('engagement_rate', '>=', $threshold);
    }

    // Accessors & Mutators
    public function getEngagementScoreAttribute(): float
    {
        return ($this->engagement_rate * $this->followers_count) / 100;
    }

    public function getNetworkStrengthAttribute(): float
    {
        return ($this->followers_count + $this->connections_count) * 0.5;
    }

    // Helper Methods
    public function calculateGrowthRate(string $metric, int $days = 30): float
    {
        $previousValue = $this->getHistoricalValue($metric, $days);
        $currentValue = $this->attributes[$metric] ?? 0;

        if ($previousValue == 0) return 0.0;

        return (($currentValue - $previousValue) / $previousValue) * 100;
    }

    private function getHistoricalValue(string $metric, int $days): int
    {
        return LinkedInHistoricalData::where('profile_id', $this->profile_id)
            ->where('metric', $metric)
            ->where('created_at', '<=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->value('value') ?? 0;
    }
}