<?php

namespace App\SocialAnalytics\LinkedIn\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Carbon\Carbon;

class LinkedInProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'linkedin_profiles';

    protected $fillable = [
        'user_id',
        'linkedin_id',
        'profile_url',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'profile_data',
        'company_data',
        'headline',
        'industry',
        'location',
        'summary',
        'profile_picture_url',
        'background_picture_url',
        'connection_count',
        'follower_count',
        'company_page_admin',
        'profile_language',
        'profile_status',
        'last_synced_at',
        'settings',
        'metadata'
    ];

    protected $casts = [
        'profile_data' => 'array',
        'company_data' => 'array',
        'settings' => 'array',
        'metadata' => 'array',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'company_page_admin' => 'boolean'
    ];

    protected $hidden = [
        'access_token',
        'refresh_token'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'token_expires_at',
        'last_synced_at'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function analytics()
    {
        return $this->hasOne(LinkedInAnalytics::class, 'profile_id', 'linkedin_id');
    }

    public function posts()
    {
        return $this->hasMany(LinkedInPost::class, 'profile_id', 'linkedin_id');
    }

    public function engagements()
    {
        return $this->hasMany(LinkedInEngagement::class, 'profile_id', 'linkedin_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('profile_status', 'active');
    }

    public function scopeNeedsTokenRefresh($query)
    {
        return $query->where('token_expires_at', '<=', now()->addDays(1));
    }

    public function scopeCompanyAdmins($query)
    {
        return $query->where('company_page_admin', true);
    }

    // Accessors & Mutators
    public function getFullNameAttribute(): string
    {
        return $this->profile_data['firstName'] . ' ' . $this->profile_data['lastName'];
    }

    public function getProfileStrengthAttribute(): float
    {
        $strength = 0;
        $fields = ['headline', 'summary', 'industry', 'location', 'profile_picture_url'];
        
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $strength += 20;
            }
        }
        
        return $strength;
    }

    // Token Management Methods
    public function isTokenValid(): bool
    {
        return $this->token_expires_at > now();
    }

    public function updateTokens(string $accessToken, string $refreshToken, int $expiresIn): void
    {
        $this->update([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_expires_at' => now()->addSeconds($expiresIn),
        ]);
    }

    // Profile Management Methods
    public function updateProfileData(array $data): void
    {
        $this->update([
            'profile_data' => array_merge($this->profile_data ?? [], $data),
            'headline' => $data['headline'] ?? $this->headline,
            'industry' => $data['industry'] ?? $this->industry,
            'location' => $data['location'] ?? $this->location,
            'summary' => $data['summary'] ?? $this->summary,
            'profile_picture_url' => $data['profilePicture'] ?? $this->profile_picture_url,
            'last_synced_at' => now(),
        ]);
    }

    public function updateCompanyData(array $data): void
    {
        $this->update([
            'company_data' => array_merge($this->company_data ?? [], $data),
            'company_page_admin' => $data['isAdmin'] ?? $this->company_page_admin,
        ]);
    }

    // Analytics Methods
    public function getEngagementMetrics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $query = $this->engagements();
        
        if ($startDate && $endDate) {
            $query->whereBetween('engagement_date', [$startDate, $endDate]);
        }

        return [
            'total_engagements' => $query->sum('engagement_count'),
            'engagement_types' => $query->groupBy('engagement_type')
                ->selectRaw('engagement_type, sum(engagement_count) as count')
                ->pluck('count', 'engagement_type'),
            'engagement_trend' => $query->groupBy('engagement_date')
                ->selectRaw('DATE(engagement_date) as date, sum(engagement_count) as count')
                ->pluck('count', 'date')
        ];
    }

    public function getPostPerformance(int $limit = 10): array
    {
        return $this->posts()
            ->orderBy('engagement_rate', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($post) {
                return [
                    'post_id' => $post->post_id,
                    'content_type' => $post->content_type,
                    'engagement_rate' => $post->engagement_rate,
                    'total_impressions' => $post->impressions_count,
                    'published_at' => $post->published_at,
                    'is_viral' => $post->is_viral
                ];
            })
            ->toArray();
    }

    // Profile Status Methods
    public function activate(): void
    {
        $this->update(['profile_status' => 'active']);
    }

    public function deactivate(): void
    {
        $this->update(['profile_status' => 'inactive']);
    }

    // Settings Management
    public function updateSettings(array $settings): void
    {
        $this->settings = array_merge($this->settings ?? [], $settings);
        $this->save();
    }

    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    // Utility Methods
    public function syncWithLinkedIn(): bool
    {
        try {
            // Implementation for syncing with LinkedIn API
            $this->update(['last_synced_at' => now()]);
            return true;
        } catch (\Exception $e) {
            \Log::error('LinkedIn profile sync failed', [
                'profile_id' => $this->linkedin_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getConnectionGrowth(int $days = 30): array
    {
        return LinkedInAnalytics::where('profile_id', $this->linkedin_id)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at')
            ->get()
            ->map(function ($record) {
                return [
                    'date' => $record->created_at->format('Y-m-d'),
                    'connections' => $record->connections_count,
                    'followers' => $record->followers_count
                ];
            })
            ->toArray();
    }
}