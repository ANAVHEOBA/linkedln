<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountDepositController;
use App\Http\Controllers\AccountWithdrawalController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\API\LinkedIn\Controllers\ProfileController;
use App\Http\Controllers\API\LinkedIn\Controllers\PostsController;
use App\Http\Controllers\API\LinkedIn\Controllers\EngagementController;
use App\Http\Controllers\API\LinkedIn\Controllers\AnalyticsController;
use App\Http\Controllers\API\Linkedln\BaseLinkedInController;
use App\Http\Controllers\Auth\InstagramAuthController;
use App\Http\Controllers\Instagram\InstagramMediaController;
use App\Http\Controllers\Instagram\InstagramPostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;




Route::prefix('auth')->group(function () {
//    dd(\request()->isProduction());
    Route::post('register', [AuthenticationController::class, 'register']);
    Route::post('login', [AuthenticationController::class, 'login']);
    Route::middleware("auth:sanctum")->group(function () {
        Route::get("user", [AuthenticationController::class, 'user']);
        Route::get('logout', [AuthenticationController::class, 'logout']);
    });
});

Route::middleware("auth:sanctum")->group(function () {
    Route::prefix('onboarding')->group(function () {
        Route::post('setup/pin', [PinController::class, 'setupPin']);
        Route::middleware('has.set.pin')->group(function () {
            Route::post('validate/pin', [PinController::class, 'validatePin']);
            Route::post('generate/account-number', [AccountController::class, 'store']);
        });
    });

    Route::middleware('has.set.pin')->group(function () {
        Route::prefix('account')->group(function () {
            Route::post('deposit', [AccountDepositController::class, 'store']);
            Route::post('withdraw', [AccountWithdrawalController::class, 'store']);
            Route::post('transfer', [TransferController::class, 'store']);
        });
        Route::prefix('transactions')->group(function () {
            Route::get('history', [TransactionController::class, 'index']);
        });
    });


});


Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('linkedin')->group(function () {
        // Profile Routes
        Route::get('profiles/{profileId}', 'ProfileController@show');
        Route::put('profiles/{profileId}', 'ProfileController@update');
        Route::get('profiles/{profileId}/analytics', 'ProfileController@analytics');
        Route::get('profiles/{profileId}/insights', 'ProfileController@insights');

        // Posts Routes
        Route::get('profiles/{profileId}/posts', 'PostsController@index');
        Route::get('profiles/{profileId}/posts/{postId}', 'PostsController@show');
        Route::get('profiles/{profileId}/posts/{postId}/analytics', 'PostsController@analytics');
        Route::get('profiles/{profileId}/posts/viral', 'PostsController@viral');

        // Engagement Routes
        Route::get('profiles/{profileId}/engagements', 'EngagementController@index');
        Route::get('profiles/{profileId}/posts/{postId}/metrics', 'EngagementController@metrics');
        Route::post('profiles/{profileId}/posts/{postId}/track', 'EngagementController@track');

        // Analytics Routes
        Route::get('profiles/{profileId}/dashboard', 'AnalyticsController@dashboard');
        Route::get('profiles/{profileId}/report', 'AnalyticsController@report');
        Route::get('profiles/{profileId}/trends', 'AnalyticsController@trends');
    });
});



// Instagram Authentication Routes
Route::prefix('auth/instagram')->group(function () {
    Route::get('connect', [InstagramAuthController::class, 'redirectToInstagram'])->name('instagram.connect');
    Route::get('callback', [InstagramAuthController::class, 'handleCallback'])->name('instagram.callback');
    Route::post('disconnect', [InstagramAuthController::class, 'disconnect'])->name('instagram.disconnect');
});

// Protected Instagram Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('instagram')->group(function () {
        // Account Status
        Route::get('status', [InstagramAuthController::class, 'getStatus'])
            ->name('instagram.status');

        // Media Management
        Route::prefix('media')->group(function () {
            Route::get('/', [InstagramMediaController::class, 'getUserMedia'])
                ->name('instagram.media.list');
            Route::get('{mediaId}', [InstagramMediaController::class, 'getMediaDetails'])
                ->name('instagram.media.details');
        });

        // Posting Routes
        Route::prefix('post')->group(function () {
            // Regular Posts
            Route::post('/', [InstagramPostController::class, 'createPost'])
                ->name('instagram.post.create');
            
            // Stories
            Route::post('story', [InstagramPostController::class, 'createStory'])
                ->name('instagram.story.create');
            
            // Reels
            Route::post('reel', [InstagramPostController::class, 'createReel'])
                ->name('instagram.reel.create');
            
            // Carousel Posts
            Route::post('carousel', [InstagramPostController::class, 'createCarousel'])
                ->name('instagram.carousel.create');
        });
    });
});

/*
|--------------------------------------------------------------------------
| API Health Check
|--------------------------------------------------------------------------
*/
Route::get('health', function () {
    return response()->json(['status' => 'ok']);
})->name('api.health');