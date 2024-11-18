<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Instagram API Configuration
    |--------------------------------------------------------------------------
    */

    'client_id' => env('INSTAGRAM_CLIENT_ID'),
    'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),
    'redirect_uri' => env('INSTAGRAM_REDIRECT_URI'),

    // Base URLs for different Instagram APIs
    'api_base_url' => 'https://graph.instagram.com',
    'oauth_url' => 'https://api.instagram.com/oauth/authorize',
    'graph_url' => 'https://graph.instagram.com/v21.0',

    // Scopes needed for the application
    'scopes' => [
        'user_profile',
        'user_media',
    ],

    // Media configurations
    'media' => [
        'max_image_size' => 8192, // 8MB in kilobytes
        'max_video_size' => 102400, // 100MB in kilobytes
        'allowed_image_types' => ['jpeg', 'jpg', 'png'],
        'allowed_video_types' => ['mp4', 'mov'],
        'max_caption_length' => 2200,
        'max_carousel_items' => 10,
    ],

    // Cache configuration
    'cache' => [
        'token_refresh' => 3600, // Cache token refresh attempts for 1 hour
        'media_timeout' => 300,  // Cache media requests for 5 minutes
    ],

    'ffmpeg' => [
        'path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
        'ffprobe_path' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),
        'timeout' => 3600,
        'video' => [
            'max_duration' => [
                'VIDEO' => 60,
                'REELS' => 90,
                'STORY' => 15,
            ],
            'dimensions' => [
                'width' => 1080,
                'height' => 1920,
            ],
            'codecs' => [
                'video' => 'h264',
                'audio' => 'aac',
            ],
        ],
    ],
];