<?php

namespace App\Http\Controllers\API\LinkedIn;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;

class BaseLinkedInController extends Controller
{
    use ApiResponseTrait;

    protected function validateLinkedInProfile(string $profileId)
    {
        $profile = auth()->user()->linkedInProfiles()
            ->where('linkedin_id', $profileId)
            ->firstOrFail();

        if (!$profile->isTokenValid()) {
            return $this->errorResponse('LinkedIn token expired', 401);
        }

        return $profile;
    }
}