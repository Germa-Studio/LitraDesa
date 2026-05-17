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
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('softbook_id')->constrained()->onDelete('cascade');
            $table->integer('page_number')->nullable(); // For PDF
            $table->string('position')->nullable(); // For EPUB CFI
            $table->string('title')->nullable(); // Bookmark title/label
            $table->text('note')->nullable(); // Optional note
            $table->text('highlighted_text')->nullable(); // Text that was highlighted
            $table->string('color')->default('#ffeb3b'); // Highlight color
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('softbook_id');
            $table->index(['user_id', 'softbook_id']);
            $table->index('page_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
