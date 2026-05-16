<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BookCopyController extends Controller
{
    /**
     * Store a new book copy.
     */
    public function store(Request $request, Book $book): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'location_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $quantity = $validated['quantity'];
        $book->createCopies($quantity, [
            'location_code' => $validated['location_code'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('books.show', $book)
            ->with('success', "Berhasil menambahkan {$quantity} eksemplar buku.");
    }

    /**
     * Update the specified book copy.
     */
    public function update(Request $request, BookCopy $bookCopy): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:available,borrowed,damaged,lost,maintenance'],
            'location_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $bookCopy->update($validated);
        $bookCopy->book->syncAvailableCopies();

        return redirect()->route('books.show', $bookCopy->book)
            ->with('success', 'Status eksemplar buku berhasil diperbarui.');
    }

    /**
     * Mark a book copy as damaged.
     */
    public function markAsDamaged(Request $request, BookCopy $bookCopy): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $bookCopy->markAsDamaged($validated['notes']);
        $bookCopy->book->syncAvailableCopies();

        return redirect()->route('books.show', $bookCopy->book)
            ->with('success', 'Eksemplar buku ditandai sebagai rusak.');
    }

    /**
     * Mark a book copy as lost.
     */
    public function markAsLost(Request $request, BookCopy $bookCopy): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $bookCopy->markAsLost($validated['notes']);
        $bookCopy->book->syncAvailableCopies();

        return redirect()->route('books.show', $bookCopy->book)
            ->with('success', 'Eksemplar buku ditandai sebagai hilang.');
    }

    /**
     * Mark a book copy as available (returned).
     */
    public function markAsAvailable(BookCopy $bookCopy): RedirectResponse
    {
        $bookCopy->markAsReturned();
        $bookCopy->book->syncAvailableCopies();

        return redirect()->route('books.show', $bookCopy->book)
            ->with('success', 'Eksemplar buku ditandai sebagai tersedia.');
    }

    /**
     * Download QR code for a specific book copy.
     */
    public function downloadQrCode(BookCopy $bookCopy)
    {
        $qrCodePath = 'qr-codes/copies/' . $bookCopy->qr_code . '.png';

        if (!Storage::disk('public')->exists($qrCodePath)) {
            $this->generateQrCodeImage($bookCopy);
        }

        return Storage::disk('public')->download(
            $qrCodePath,
            $bookCopy->book->title . ' - Copy ' . $bookCopy->copy_number . '.png'
        );
    }

    /**
     * Download all QR codes for a book's copies as a ZIP file.
     */
    public function downloadAllQrCodes(Book $book)
    {
        $copies = $book->copies;
        
        if ($copies->isEmpty()) {
            return redirect()->route('books.show', $book)
                ->with('error', 'Tidak ada eksemplar untuk buku ini.');
        }

        $zip = new \ZipArchive();
        $zipFileName = storage_path('app/temp/' . $book->title . '-qr-codes.zip');
        
        // Create temp directory if it doesn't exist
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if ($zip->open($zipFileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($copies as $copy) {
                $qrCodePath = 'qr-codes/copies/' . $copy->qr_code . '.png';
                
                if (!Storage::disk('public')->exists($qrCodePath)) {
                    $this->generateQrCodeImage($copy);
                }
                
                $fullPath = Storage::disk('public')->path($qrCodePath);
                $zip->addFile($fullPath, 'Copy-' . $copy->copy_number . '.png');
            }
            $zip->close();
        }

        return response()->download($zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Delete a book copy.
     */
    public function destroy(BookCopy $bookCopy): RedirectResponse
    {
        $book = $bookCopy->book;
        
        // Delete QR code image
        $qrCodePath = 'qr-codes/copies/' . $bookCopy->qr_code . '.png';
        if (Storage::disk('public')->exists($qrCodePath)) {
            Storage::disk('public')->delete($qrCodePath);
        }

        $bookCopy->delete();
        $book->syncAvailableCopies();

        return redirect()->route('books.show', $book)
            ->with('success', 'Eksemplar buku berhasil dihapus.');
    }

    /**
     * Generate QR code image for a book copy.
     */
    protected function generateQrCodeImage(BookCopy $bookCopy): void
    {
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->generate($bookCopy->qr_code);

        $qrCodePath = 'qr-codes/copies/' . $bookCopy->qr_code . '.png';
        Storage::disk('public')->put($qrCodePath, $qrCode);
    }
}

// Made with Bob
