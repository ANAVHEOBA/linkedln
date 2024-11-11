<?php

namespace App\Http\Controllers\API\LinkedIn\Controllers;

use App\Http\Controllers\API\LinkedIn\BaseLinkedInController;
use App\SocialAnalytics\LinkedIn\Services\LinkedInPostService;
use Illuminate\Http\Request;

class PostsController extends BaseLinkedInController
{
    private LinkedInPostService $postService;

    public function __construct(LinkedInPostService $postService)
    {
        $this->postService = $postService;
    }

    public function index(Request $request, string $profileId)
    {
        $profile = $this->validateLinkedInProfile($profileId);
        
        $posts = $this->postService->getPosts(
            $profileId,
            $request->get('page', 1),
            $request->get('per_page', 15),
            $request->get('sort_by', 'published_at'),
            $request->get('sort_direction', 'desc')
        );

        return $this->successResponse($posts);
    }

    public function show(string $profileId, string $postId)
    {
        $this->validateLinkedInProfile($profileId);
        $post = $this->postService->getPost($postId);
        return $this->successResponse($post->toArray());
    }

    public function analytics(string $profileId, string $postId)
    {
        $this->validateLinkedInProfile($profileId);
        $analytics = $this->postService->analyzePost($postId);
        return $this->successResponse($analytics);
    }

    public function viral(string $profileId)
    {
        $profile = $this->validateLinkedInProfile($profileId);
        $viralPosts = $this->postService->getViralPosts($profileId);
        return $this->successResponse($viralPosts);
    }
}