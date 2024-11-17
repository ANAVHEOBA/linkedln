<?php

namespace App\Http\Controllers\Instagram;

use App\Http\Controllers\Controller;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstagramMediaController extends Controller
{
    protected $instagramApiService;

    public function __construct(InstagramApiService $instagramApiService)
    {
        $this->instagramApiService = $instagramApiService;
    }

    public function getUserMedia()
    {
        try {
            $media = $this->instagramApiService->getUserMedia();
            return response()->json($media);
        } catch (\Exception $e) {
            Log::error('Error fetching Instagram media: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch media'], 500);
        }
    }

    public function getMediaDetails($mediaId)
    {
        try {
            $mediaDetails = $this->instagramApiService->getMediaDetails($mediaId);
            return response()->json($mediaDetails);
        } catch (\Exception $e) {
            Log::error('Error fetching media details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch media details'], 500);
        }
    }
}