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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_copy_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('reservation_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('processed_by')->constrained('users')->onDelete('cascade'); // Admin who processed
            $table->enum('status', ['active', 'returned', 'overdue', 'lost'])->default('active');
            $table->date('loan_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->foreignId('returned_by')->nullable()->constrained('users')->onDelete('set null'); // Admin who processed return
            $table->integer('days_overdue')->default(0);
            $table->decimal('fine_amount', 10, 2)->default(0);
            $table->boolean('fine_paid')->default(false);
            $table->text('notes')->nullable();
            $table->text('return_notes')->nullable();
            $table->enum('book_condition_at_loan', ['excellent', 'good', 'fair', 'poor'])->default('good');
            $table->enum('book_condition_at_return', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('book_id');
            $table->index('book_copy_id');
            $table->index('status');
            $table->index('due_date');
            $table->index('loan_date');
            $table->index(['user_id', 'status']);
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
