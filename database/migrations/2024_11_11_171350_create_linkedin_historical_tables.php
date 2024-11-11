<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLinkedInHistoricalTables extends Migration
{
    public function up()
    {
        Schema::create('linkedin_historical_data', function (Blueprint $table) {
            $table->id();
            $table->string('profile_id');
            $table->string('metric_type');
            $table->json('data');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->foreign('profile_id')->references('linkedin_id')->on('linkedin_profiles');
            $table->index(['profile_id', 'metric_type', 'recorded_at']);
        });

        Schema::create('linkedin_metrics_daily', function (Blueprint $table) {
            $table->id();
            $table->string('profile_id');
            $table->date('date');
            $table->string('metric_type');
            $table->float('value');
            $table->json('breakdown')->nullable();
            $table->timestamps();

            $table->foreign('profile_id')->references('linkedin_id')->on('linkedin_profiles');
            $table->unique(['profile_id', 'date', 'metric_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('linkedin_metrics_daily');
        Schema::dropIfExists('linkedin_historical_data');
    }
}