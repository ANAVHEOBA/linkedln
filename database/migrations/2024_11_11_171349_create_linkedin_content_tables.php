<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLinkedInContentTables extends Migration
{
    public function up()
    {
        Schema::create('linkedin_posts', function (Blueprint $table) {
            $table->id();
            $table->string('profile_id');
            $table->string('post_id')->unique();
            $table->string('content_type');
            $table->text('content');
            $table->string('url')->nullable();
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->integer('impressions_count')->default(0);
            $table->integer('clicks_count')->default(0);
            $table->float('engagement_rate')->default(0);
            $table->boolean('is_viral')->default(false);
            $table->float('virality_score')->default(0);
            $table->timestamp('published_at');
            $table->json('metrics')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('profile_id')->references('linkedin_id')->on('linkedin_profiles');
            $table->index(['profile_id', 'published_at']);
            $table->index('is_viral');
        });

        Schema::create('linkedin_engagements', function (Blueprint $table) {
            $table->id();
            $table->string('profile_id');
            $table->string('post_id');
            $table->string('engagement_type');
            $table->integer('engagement_count')->default(0);
            $table->timestamp('engagement_date');
            $table->json('user_demographics')->nullable();
            $table->json('interaction_data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('profile_id')->references('linkedin_id')->on('linkedin_profiles');
            $table->foreign('post_id')->references('post_id')->on('linkedin_posts');
            $table->index(['profile_id', 'engagement_date']);
            $table->index(['post_id', 'engagement_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('linkedin_engagements');
        Schema::dropIfExists('linkedin_posts');
    }
}