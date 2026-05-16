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
        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->string('qr_code', 100)->unique(); // Unique QR code for each physical copy
            $table->string('copy_number', 20); // e.g., "001", "002" for tracking
            $table->enum('status', ['available', 'borrowed', 'damaged', 'lost', 'maintenance'])->default('available');
            $table->string('location_code', 100)->nullable(); // Specific shelf location
            $table->text('notes')->nullable(); // Condition notes, damage description, etc.
            $table->timestamp('last_borrowed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('book_id');
            $table->index('qr_code');
            $table->index('status');
            $table->unique(['book_id', 'copy_number']); // Ensure unique copy numbers per book
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};
