<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBaseAnalyticsTables extends Migration
{
    public function up()
    {
        Schema::create('analytics_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->string('type');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('analytics_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_type');
            $table->string('status');
            $table->json('payload');
            $table->json('result')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('analytics_settings');
        Schema::dropIfExists('analytics_jobs');
    }
}