<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('author', 255);
            $table->string('isbn', 20)->unique()->nullable();
            $table->foreignId('book_category_id')->constrained()->onDelete('restrict');
            $table->text('description')->nullable();
            $table->string('publisher', 255)->nullable();
            $table->year('publication_year')->nullable();
            $table->string('language', 50)->default('id'); // Indonesian by default
            $table->integer('total_copies')->default(1);
            $table->integer('available_copies')->default(1);
            $table->string('location', 100)->nullable(); // Shelf/section in village hall
            $table->string('cover_image')->nullable();
            $table->string('qr_code', 100)->unique(); // Unique QR code for each book
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('title');
            $table->index('author');
            $table->index('isbn');
            $table->index('qr_code');
            $table->index('is_available');
            $table->index('book_category_id');
        });
        
        // Full-text search index for PostgreSQL using raw SQL
        DB::statement('CREATE INDEX books_search_idx ON books USING gin(to_tsvector(\'indonesian\', coalesce(title, \'\') || \' \' || coalesce(author, \'\') || \' \' || coalesce(description, \'\')))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
