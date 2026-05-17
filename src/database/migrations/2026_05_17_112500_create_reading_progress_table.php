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
        Schema::create('reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('softbook_id')->constrained()->onDelete('cascade');
            $table->integer('current_page')->default(1);
            $table->integer('total_pages')->nullable();
            $table->decimal('progress_percentage', 5, 2)->default(0.00); // 0.00 to 100.00
            $table->string('last_position')->nullable(); // For EPUB CFI (Canonical Fragment Identifier)
            $table->timestamp('last_read_at')->nullable();
            $table->integer('total_reading_time')->default(0); // in seconds
            $table->timestamps();

            // Indexes
            $table->unique(['user_id', 'softbook_id']);
            $table->index('user_id');
            $table->index('softbook_id');
            $table->index('last_read_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_progress');
    }
};
