<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class FileEncryptionService
{
    /**
     * Encrypt a file and return the encrypted content.
     */
    public function encryptFile(string $filePath): string
    {
        $content = file_get_contents($filePath);
        return Crypt::encryptString($content);
    }

    /**
     * Decrypt file content.
     */
    public function decryptFile(string $encryptedContent): string
    {
        return Crypt::decryptString($encryptedContent);
    }

    /**
     * Generate a unique encryption key.
     */
    public function generateKey(): string
    {
        return Str::random(32);
    }

    /**
     * Encrypt file with custom key.
     */
    public function encryptFileWithKey(string $filePath, string $key): string
    {
        $content = file_get_contents($filePath);
        return openssl_encrypt($content, 'AES-256-CBC', $key, 0, substr($key, 0, 16));
    }

    /**
     * Decrypt file with custom key.
     */
    public function decryptFileWithKey(string $encryptedContent, string $key): string
    {
        return openssl_decrypt($encryptedContent, 'AES-256-CBC', $key, 0, substr($key, 0, 16));
    }

    /**
     * Add watermark to PDF content (member info).
     */
    public function addWatermark(string $content, string $watermarkText): string
    {
        // For now, return content as-is
        // In production, use a PDF library like TCPDF or FPDF to add watermark
        return $content;
    }
}
