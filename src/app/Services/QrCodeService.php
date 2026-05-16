<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /**
     * Generate QR code for a member.
     */
    public function generateMemberQrCode(User $member): string
    {
        // Generate unique QR code data
        $qrData = $this->generateQrData($member);

        // Generate QR code image
        $qrCode = QrCode::format('svg')
            ->size(300)
            ->errorCorrection('H')
            ->generate($qrData);

        return (string) $qrCode;
    }

    /**
     * Generate QR code data for a member.
     */
    public function generateQrData(User $member): string
    {
        return sprintf(
            'LITRADESA-MEMBER:%s:%s',
            $member->id,
            $member->qr_code
        );
    }

    /**
     * Save QR code as PNG file.
     */
    public function saveQrCodeAsPng(User $member): string
    {
        $qrData = $this->generateQrData($member);

        $qrCode = QrCode::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->generate($qrData);

        $filename = "qr-codes/member-{$member->id}.png";
        Storage::disk('public')->put($filename, $qrCode);

        return Storage::url($filename);
    }

    /**
     * Verify QR code data.
     */
    public function verifyQrCode(string $qrData): ?User
    {
        // Parse QR code data
        if (!preg_match('/^LITRADESA-MEMBER:(\d+):([A-Z0-9]+)$/', $qrData, $matches)) {
            return null;
        }

        $memberId = (int) $matches[1];
        $qrCode = $matches[2];

        // Find member
        $member = User::members()
            ->where('id', $memberId)
            ->where('qr_code', $qrCode)
            ->first();

        return $member;
    }

    /**
     * Generate downloadable QR code as base64.
     */
    public function generateBase64QrCode(User $member): string
    {
        $qrData = $this->generateQrData($member);

        $qrCode = QrCode::format('png')
            ->size(400)
            ->errorCorrection('H')
            ->generate($qrData);

        // Cast HtmlString to string before base64 encoding
        return 'data:image/png;base64,' . base64_encode((string) $qrCode);
    }
}

// Made with Bob
