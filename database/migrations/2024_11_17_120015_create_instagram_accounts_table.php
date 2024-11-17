<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('instagram_accounts', function (Blueprint $table) {
            $table->id();
            
            // User relationship
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Instagram account details
            $table->string('instagram_user_id')->nullable();
            $table->string('username')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->enum('account_type', ['personal', 'business', 'creator'])
                  ->default('personal');

            // Authentication tokens
            $table->text('access_token');
            $table->text('refresh_token');
            $table->string('token_type')->default('bearer');
            
            // Token expiration timestamps
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();

            // Account status
            $table->boolean('is_active')->default(true);

            // Metadata
            $table->json('metadata')->nullable(); // For storing additional Instagram data
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes for account recovery

            // Indexes for better query performance
            $table->index('instagram_user_id');
            $table->index('username');
            $table->index('token_expires_at');
            $table->index('refresh_token_expires_at');
            $table->index('is_active');
            $table->index('last_used_at');
            
            // Unique constraints
            $table->unique(['user_id', 'instagram_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_accounts');
    }
};