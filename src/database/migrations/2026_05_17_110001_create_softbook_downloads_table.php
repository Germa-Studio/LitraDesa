<?php

declare(strict_types=1);

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
        Schema::create('softbook_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('softbook_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->string('download_token')->unique();
            $table->timestamp('token_expires_at');
            $table->timestamp('downloaded_at')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->unsignedBigInteger('file_size_downloaded')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('softbook_id');
            $table->index('user_id');
            $table->index('download_token');
            $table->index(['user_id', 'softbook_id']);
            $table->index('downloaded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('softbook_downloads');
    }
};
