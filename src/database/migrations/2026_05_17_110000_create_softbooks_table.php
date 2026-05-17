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
        Schema::create('softbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('original_filename');
            $table->enum('format', ['pdf', 'epub']);
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->integer('pages')->nullable();
            $table->string('encryption_key')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('download_limit')->default(5); // per member
            $table->integer('total_downloads')->default(0);
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('book_id');
            $table->index('format');
            $table->index('is_active');
            $table->index(['book_id', 'format']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('softbooks');
    }
};
