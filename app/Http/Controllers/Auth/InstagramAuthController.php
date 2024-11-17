<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Instagram\InstagramAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstagramAuthController extends Controller
{
    protected $instagramAuthService;

    public function __construct(InstagramAuthService $instagramAuthService)
    {
        $this->instagramAuthService = $instagramAuthService;
    }

    public function redirectToInstagram()
    {
        $url = $this->instagramAuthService->getAuthorizationUrl();
        return redirect($url);
    }

    public function handleCallback(Request $request)
    {
        try {
            if ($request->has('error')) {
                return redirect()->route('home')->with('error', 'Instagram authorization failed');
            }

            $code = $request->get('code');
            $tokens = $this->instagramAuthService->getAccessToken($code);

            // Store tokens in database
            auth()->user()->instagramAccount()->updateOrCreate(
                ['user_id' => auth()->id()],
                [
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'expires_in' => now()->addSeconds($tokens['expires_in']),
                    'instagram_user_id' => $tokens['user_id']
                ]
            );

            return redirect()->route('home')->with('success', 'Instagram connected successfully');
        } catch (\Exception $e) {
            Log::error('Instagram callback error: ' . $e->getMessage());
            return redirect()->route('home')->with('error', 'Failed to connect Instagram');
        }
    }

    public function disconnect()
    {
        try {
            auth()->user()->instagramAccount()->delete();
            return redirect()->route('home')->with('success', 'Instagram disconnected successfully');
        } catch (\Exception $e) {
            Log::error('Instagram disconnect error: ' . $e->getMessage());
            return redirect()->route('home')->with('error', 'Failed to disconnect Instagram');
        }
    }
}