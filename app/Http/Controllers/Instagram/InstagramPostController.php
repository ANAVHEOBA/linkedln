<?php

namespace App\Http\Controllers\Instagram;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instagram\CreatePostRequest;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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
            $mediaData = $this->prepareMediaData($request);
            
            $result = $this->instagramApiService->createPost(
                $request->get('media_type'),
                $mediaData,
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

    protected function prepareMediaData(CreatePostRequest $request)
    {
        $mediaType = $request->get('media_type');
        $mediaSource = $request->get('media_source');

        if ($mediaType === 'CAROUSEL_ALBUM') {
            return $this->prepareCarouselData($request);
        }

        $media = $mediaSource === 'file' ? $request->file('media') : $request->get('media');
        
        if ($this->isVideoContent($mediaType)) {
            return [
                'media' => $media,
                'thumbnail' => $request->get('thumbnail'),
                'is_video' => true
            ];
        }

        return $media;
    }

    protected function prepareCarouselData(CreatePostRequest $request)
    {
        $mediaSource = $request->get('media_source');
        $mediaTypes = $request->get('media_types', []);
        $thumbnails = $request->get('thumbnails', []);

        if ($mediaSource === 'file') {
            return collect($request->file('media'))
                ->map(function ($media, $index) use ($mediaTypes, $thumbnails) {
                    if ($this->isVideoFile($media)) {
                        return [
                            'media' => $media,
                            'thumbnail' => $thumbnails[$index] ?? null,
                            'is_video' => true
                        ];
                    }
                    return $media;
                })->all();
        }

        return collect($request->get('media'))
            ->map(function ($url, $index) use ($mediaTypes, $thumbnails) {
                if ($mediaTypes[$index] === 'VIDEO') {
                    return [
                        'media' => $url,
                        'thumbnail' => $thumbnails[$index] ?? null,
                        'is_video' => true
                    ];
                }
                return $url;
            })->all();
    }

    protected function isVideoContent($mediaType)
    {
        return in_array($mediaType, ['VIDEO', 'REELS']);
    }

    protected function isVideoFile($file)
    {
        return in_array($file->getMimeType(), ['video/mp4', 'video/quicktime']);
    }

    protected function validateAndReturnUrl($url)
    {
        try {
            $response = Http::head($url);
            
            if (!$response->successful()) {
                throw new \Exception("Unable to access media URL: {$url}");
            }

            return $url;
        } catch (\Exception $e) {
            Log::error('Media URL validation failed: ' . $e->getMessage());
            throw new \Exception('Invalid or inaccessible media URL');
        }
    }
}