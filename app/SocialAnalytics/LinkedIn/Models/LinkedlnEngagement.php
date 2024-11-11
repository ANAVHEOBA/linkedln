<?php

namespace App\SocialAnalytics\LinkedIn\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LinkedInEngagement extends Model
{
    use HasFactory;

    protected $table = 'linkedin_engagements';

    protected $fillable = [
        'profile_id',
        'post_id',
        'engagement_type',
        'engagement_count',
        'engagement_date',
        'user_demographics',
        'interaction_data',
        'metadata'
    ];

    protected $casts = [
        'user_demographics' => 'array',
        'interaction_data' => 'array',
        'metadata' => 'array',
        'engagement_date' => 'datetime'
    ];

    // Relationships
    public function post()
    {
        return $this->belongsTo(LinkedInPost::class, 'post_id', 'post_id');
    }

    public function analytics()
    {
        return $this->belongsTo(LinkedInAnalytics::class, 'profile_id', 'profile_id');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('engagement_type', $type);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('engagement_date', '>=', now()->subDays($days));
    }

    // Methods
    public function incrementCount(int $amount = 1): void
    {
        $this->increment('engagement_count', $amount);
        $this->touch();
    }

    public function updateDemographics(array $newData): void
    {
        $this->user_demographics = array_merge(
            $this->user_demographics ?? [],
            $newData
        );
        $this->save();
    }
}