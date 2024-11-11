<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLinkedInEventsJobsTables extends Migration
{
    public function up()
    {
        Schema::create('linkedin_events', function (Blueprint $table) {
            $table->id();
            $table->string('profile_id');
            $table->string('event_type');
            $table->json('event_data');
            $table->timestamp('occurred_at');
            $table->boolean('processed')->default(false);
            $table->json('processing_result')->nullable();
            $table->timestamps();

            $table->foreign('profile_id')->references('linkedin_id')->on('linkedin_profiles');
            $table->index(['profile_id', 'event_type', 'occurred_at']);
        });

        Schema::create('linkedin_jobs_history', function (Blueprint $table) {
            $table->id();
            $table->string('job_type');
            $table->string('profile_id');
            $table->json('parameters');
            $table->string('status');
            $table->text('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('profile_id')->references('linkedin_id')->on('linkedin_profiles');
            $table->index(['profile_id', 'job_type', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('linkedin_jobs_history');
        Schema::dropIfExists('linkedin_events');
    }
}