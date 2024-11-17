<?php

namespace App\Http\Controllers\Instagram;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instagram\CreatePostRequest;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Support\Facades\Log;

class InstagramPostController extends Controller
{
    protected $instagramApiService;

    public function __construct(InstagramApiService $instagramApiService)
    {
        $this->instagramApiService = $instagramApiService;
    }

    public function createPost(CreatePostRequest $request)
    {
        try {
            $result = $this->instagramApiService->createPost(
                $request->get('media_type'),
                $request->file('media'),
                $request->get('caption'),
                $request->get('location_id')
            );

            return response()->json([
                'success' => true,
                'media_id' => $result['id']
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating Instagram post: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create post'], 500);
        }
    }

    public function createStory(CreatePostRequest $request)
    {
        try {
            $result = $this->instagramApiService->createStory(
                $request->file('media'),
                $request->get('caption')
            );

            return response()->json([
                'success' => true,
                'media_id' => $result['id']
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating Instagram story: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create story'], 500);
        }
    }

    public function createReel(CreatePostRequest $request)
    {
        try {
            $result = $this->instagramApiService->createReel(
                $request->file('video'),
                $request->get('caption'),
                $request->get('share_to_feed')
            );

            return response()->json([
                'success' => true,
                'media_id' => $result['id']
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating Instagram reel: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create reel'], 500);
        }
    }
}