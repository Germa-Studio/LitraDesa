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
        Schema::table('users', function (Blueprint $table) {
            // Role and Status
            $table->enum('role', ['admin', 'member'])->default('member')->after('id');
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending')->after('role');
            
            // Member Profile Information
            $table->string('phone', 20)->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->string('ktp_number', 16)->unique()->nullable()->after('address');
            $table->string('ktp_photo_path')->nullable()->after('ktp_number');
            
            // QR Code for Member Card
            $table->string('qr_code', 100)->unique()->nullable()->after('ktp_photo_path');
            
            // Admin Approval Tracking
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('qr_code');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            
            // Soft Deletes for data retention
            $table->softDeletes()->after('updated_at');
            
            // Indexes for performance
            $table->index('role');
            $table->index('status');
            $table->index('ktp_number');
            $table->index('qr_code');
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropIndex(['ktp_number']);
            $table->dropIndex(['qr_code']);
            $table->dropIndex(['phone']);
            $table->dropColumn([
                'role',
                'status',
                'phone',
                'address',
                'ktp_number',
                'ktp_photo_path',
                'qr_code',
                'approved_by',
                'approved_at',
                'rejection_reason',
            ]);
        });
    }
};

// Made with Bob
