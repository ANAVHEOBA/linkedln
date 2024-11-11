<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLinkedInProfileTables extends Migration
{
    public function up()
    {
        Schema::create('linkedin_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('linkedin_id')->unique();
            $table->string('profile_url')->nullable();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('token_expires_at');
            $table->json('profile_data')->nullable();
            $table->json('company_data')->nullable();
            $table->string('headline')->nullable();
            $table->string('industry')->nullable();
            $table->string('location')->nullable();
            $table->text('summary')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->string('background_picture_url')->nullable();
            $table->integer('connection_count')->default(0);
            $table->integer('follower_count')->default(0);
            $table->boolean('company_page_admin')->default(false);
            $table->string('profile_language')->default('en');
            $table->string('profile_status')->default('active');
            $table->timestamp('last_synced_at')->nullable();
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['linkedin_id', 'profile_status']);
            $table->index('token_expires_at');
        });

        Schema::create('linkedin_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('profile_id');
            $table->foreignId('user_id')->constrained();
            $table->integer('followers_count')->default(0);
            $table->integer('connections_count')->default(0);
            $table->integer('profile_views')->default(0);
            $table->integer('post_impressions')->default(0);
            $table->float('engagement_rate')->default(0);
            $table->integer('search_appearances')->default(0);
            $table->json('demographics')->nullable();
            $table->json('industry_metrics')->nullable();
            $table->json('location_metrics')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('profile_id')->references('linkedin_id')->on('linkedin_profiles');
        });
    }

    public function down()
    {
        Schema::dropIfExists('linkedin_analytics');
        Schema::dropIfExists('linkedin_profiles');
    }
}