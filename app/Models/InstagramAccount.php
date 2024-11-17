<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class InstagramAccount extends Model
{
    use HasFactory;

    /**
     * Token expiry constant in seconds (1 hour)
     */
    protected const TOKEN_EXPIRY_SECONDS = 3600;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'instagram_user_id',
        'access_token',
        'refresh_token',
        'token_type',
        'token_expires_at',
        'refresh_token_expires_at',
        'username',
        'profile_picture_url',
        'account_type',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'token_expires_at' => 'datetime',
        'refresh_token_expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the user that owns the Instagram account.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the access token needs refresh
     */
    public function needsTokenRefresh(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }
        // Refresh if token expires in less than 5 minutes
        return $this->token_expires_at->subMinutes(5)->isPast();
    }

    /**
     * Check if refresh token is expired
     */
    public function isRefreshTokenExpired(): bool
    {
        if (!$this->refresh_token_expires_at) {
            return true;
        }
        return $this->refresh_token_expires_at->isPast();
    }

    /**
     * Refresh the access token if needed
     */
    public function refreshTokenIfNeeded(): bool
    {
        if ($this->needsTokenRefresh() && !$this->isRefreshTokenExpired()) {
            return $this->refreshToken();
        }
        return false;
    }

    /**
     * Refresh the access token
     */
    public function refreshToken(): bool
    {
        try {
            $response = Http::post('https://graph.instagram.com/refresh_access_token', [
                'grant_type' => 'refresh_token',
                'client_id' => config('instagram.client_id'),
                'client_secret' => config('instagram.client_secret'),
                'refresh_token' => $this->refresh_token,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $now = Carbon::now();
                
                $this->update([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $this->refresh_token,
                    'token_expires_at' => $now->copy()->addSeconds(self::TOKEN_EXPIRY_SECONDS),
                    'refresh_token_expires_at' => $now->copy()->addSeconds(self::TOKEN_EXPIRY_SECONDS),
                    'is_active' => true,
                ]);

                return true;
            }

            $this->update(['is_active' => false]);
            return false;
        } catch (\Exception $e) {
            \Log::error('Failed to refresh Instagram token: ' . $e->getMessage(), [
                'instagram_account_id' => $this->id,
                'user_id' => $this->user_id
            ]);
            $this->update(['is_active' => false]);
            return false;
        }
    }

    /**
     * Get the active access token
     */
    public function getActiveToken(): ?string
    {
        if ($this->isRefreshTokenExpired()) {
            return null;
        }
        
        $this->refreshTokenIfNeeded();
        return $this->access_token;
    }

    /**
     * Scope a query to only include active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->whereNotNull('access_token')
                    ->whereNotNull('instagram_user_id')
                    ->where(function($q) {
                        $q->whereNull('refresh_token_expires_at')
                          ->orWhere('refresh_token_expires_at', '>', now());
                    });
    }

    /**
     * Check if the account is connected
     */
    public function isConnected(): bool
    {
        return $this->is_active 
            && !empty($this->access_token) 
            && !empty($this->instagram_user_id)
            && !$this->isRefreshTokenExpired();
    }

    /**
     * Check if the account is a business account
     */
    public function isBusinessAccount(): bool
    {
        return $this->account_type === 'business';
    }

    /**
     * Check if the account is a creator account
     */
    public function isCreatorAccount(): bool
    {
        return $this->account_type === 'creator';
    }

    /**
     * Check if the account is a personal account
     */
    public function isPersonalAccount(): bool
    {
        return $this->account_type === 'personal';
    }

    /**
     * Set the account as inactive
     */
    public function deactivate(): bool
    {
        return $this->update([
            'is_active' => false,
            'token_expires_at' => null,
            'refresh_token_expires_at' => null
        ]);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->token_type) {
                $model->token_type = 'bearer';
            }
        });
    }
}