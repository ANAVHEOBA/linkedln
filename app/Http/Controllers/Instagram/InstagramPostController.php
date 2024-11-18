<?php

namespace App\Http\Controllers\Instagram;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instagram\CreatePostRequest;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;

class InstagramPostController extends Controller
{
    protected $instagramApiService;
    protected $ffmpeg;

    public function __construct(InstagramApiService $instagramApiService)
    {
        $this->instagramApiService = $instagramApiService;
        $this->ffmpeg = FFMpeg::create([
            'ffmpeg.binaries' => config('instagram.ffmpeg.path'),
            'ffprobe.binaries' => config('instagram.ffmpeg.ffprobe_path'),
            'timeout' => config('instagram.ffmpeg.timeout', 3600),
        ]);
    }

    /**
     * Create a regular post (image or video)
     */
    public function createPost(CreatePostRequest $request)
    {
        try {
            $mediaSource = $this->getMediaSource($request);
            $thumbnailSource = null;

            if (in_array($request->get('media_type'), ['VIDEO', 'REELS'])) {
                $thumbnailSource = $this->getThumbnailSource($request, $mediaSource);
                $mediaSource = $this->processVideo($mediaSource, $request->get('media_type'));
            }

            $result = $this->instagramApiService->createPost(
                $request->get('media_type'),
                $mediaSource,
                $request->get('caption'),
                $request->get('location_id'),
                $thumbnailSource
            );

            $this->cleanupTempFiles([$mediaSource, $thumbnailSource]);

            return response()->json([
                'success' => true,
                'media_id' => $result['id']
            ]);
        } catch (\Exception $e) {
            $this->cleanupTempFiles([$mediaSource ?? null, $thumbnailSource ?? null]);
            Log::error('Error creating Instagram post: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create post'], 500);
        }
    }

    /**
     * Create a story
     */
    public function createStory(CreatePostRequest $request)
    {
        try {
            $mediaSource = $this->getMediaSource($request);
            $thumbnailSource = null;

            if (str_contains(mime_content_type($mediaSource), 'video')) {
                $thumbnailSource = $this->getThumbnailSource($request, $mediaSource);
                $mediaSource = $this->processVideo($mediaSource, 'STORY');
            }

            $result = $this->instagramApiService->createStory(
                $mediaSource,
                $request->get('caption'),
                $thumbnailSource
            );

            $this->cleanupTempFiles([$mediaSource, $thumbnailSource]);

            return response()->json([
                'success' => true,
                'media_id' => $result['id']
            ]);
        } catch (\Exception $e) {
            $this->cleanupTempFiles([$mediaSource ?? null, $thumbnailSource ?? null]);
            Log::error('Error creating Instagram story: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create story'], 500);
        }
    }

    /**
     * Create a reel
     */
    public function createReel(CreatePostRequest $request)
    {
        try {
            $mediaSource = $this->getMediaSource($request);
            $thumbnailSource = $this->getThumbnailSource($request, $mediaSource);
            $mediaSource = $this->processVideo($mediaSource, 'REELS');

            $result = $this->instagramApiService->createReel(
                $mediaSource,
                $request->get('caption'),
                $request->boolean('share_to_feed'),
                $thumbnailSource,
                $request->get('audio_name')
            );

            $this->cleanupTempFiles([$mediaSource, $thumbnailSource]);

            return response()->json([
                'success' => true,
                'media_id' => $result['id']
            ]);
        } catch (\Exception $e) {
            $this->cleanupTempFiles([$mediaSource ?? null, $thumbnailSource ?? null]);
            Log::error('Error creating Instagram reel: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create reel'], 500);
        }
    }

    /**
     * Create a carousel post
     */
    public function createCarousel(CreatePostRequest $request)
    {
        $mediaSources = [];
        $thumbnailSources = [];
        
        try {
            $mediaSources = $this->getCarouselMediaSources($request);
            $thumbnailSources = $this->getCarouselThumbnailSources($request, $mediaSources);

            $result = $this->instagramApiService->createCarousel(
                $mediaSources,
                $request->get('caption'),
                $request->get('location_id'),
                $thumbnailSources
            );

            $this->cleanupTempFiles(array_merge($mediaSources, $thumbnailSources));

            return response()->json([
                'success' => true,
                'media_id' => $result['id']
            ]);
        } catch (\Exception $e) {
            $this->cleanupTempFiles(array_merge($mediaSources, $thumbnailSources));
            Log::error('Error creating Instagram carousel: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create carousel'], 500);
        }
    }

    /**
     * Get media source from either file upload or URL
     */
    protected function getMediaSource($request)
    {
        if ($request->hasFile('media')) {
            return $request->file('media')->getRealPath();
        }

        if ($request->has('media_url')) {
            return $this->downloadMediaFromUrl($request->get('media_url'));
        }

        throw new \Exception('No media source provided');
    }

    /**
     * Get thumbnail source
     */
    protected function getThumbnailSource($request, $videoPath)
    {
        if ($request->hasFile('thumbnail')) {
            return $request->file('thumbnail')->getRealPath();
        }

        if ($request->has('thumbnail_url')) {
            return $this->downloadMediaFromUrl($request->get('thumbnail_url'));
        }

        return $this->generateThumbnail($videoPath);
    }

    /**
     * Process video according to Instagram requirements
     */
    protected function processVideo($videoPath, $type = 'VIDEO')
    {
        try {
            $video = $this->ffmpeg->open($videoPath);
            
            // Check duration limits
            $duration = $video->getStreams()->first()->get('duration');
            $maxDuration = config("instagram.ffmpeg.video.max_duration.$type", 60);
            
            if ($duration > $maxDuration) {
                throw new \Exception("Video duration exceeds {$maxDuration} seconds");
            }

            // Process video
            $dimensions = config('instagram.ffmpeg.video.dimensions');
            $processedPath = Storage::disk('temp')->path('processed_' . uniqid() . '.mp4');
            
            $video->filters()
                ->resize($dimensions['width'], $dimensions['height'])
                ->synchronize();

            $format = new \FFMpeg\Format\Video\X264();
            $format->setAudioCodec(config('instagram.ffmpeg.video.codecs.audio'));
            
            $video->save($format, $processedPath);

            return $processedPath;
        } catch (\Exception $e) {
            Log::error('Video processing error: ' . $e->getMessage());
            throw new \Exception('Failed to process video');
        }
    }

    /**
     * Generate thumbnail from video
     */
    protected function generateThumbnail($videoPath)
    {
        try {
            $video = $this->ffmpeg->open($videoPath);
            $thumbnailPath = Storage::disk('temp')->path('thumb_' . uniqid() . '.jpg');

            $frame = $video->frame(TimeCode::fromSeconds(1));
            $frame->save($thumbnailPath);

            return $thumbnailPath;
        } catch (\Exception $e) {
            Log::error('Thumbnail generation error: ' . $e->getMessage());
            throw new \Exception('Failed to generate thumbnail');
        }
    }

    /**
     * Download media from URL
     */
    protected function downloadMediaFromUrl($url)
    {
        try {
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                throw new \Exception("Failed to download media from URL: {$url}");
            }

            $extension = $this->getExtensionFromMimeType($response->header('Content-Type'));
            $filename = 'instagram_' . uniqid() . '.' . $extension;
            
            Storage::disk('temp')->put($filename, $response->body());
            return Storage::disk('temp')->path($filename);
        } catch (\Exception $e) {
            Log::error('Error downloading media: ' . $e->getMessage());
            throw new \Exception('Failed to process media URL');
        }
    }

    /**
     * Get file extension from MIME type
     */
    protected function getExtensionFromMimeType($mimeType)
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            default => 'jpg',
        };
    }

    /**
     * Clean up temporary files
     */
    protected function cleanupTempFiles($files)
    {
        if (empty($files)) {
            return;
        }

        $files = is_array($files) ? $files : [$files];

        foreach ($files as $file) {
            if ($file && is_string($file) && file_exists($file)) {
                @unlink($file);
            }
        }
    }
}