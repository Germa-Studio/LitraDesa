<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BookStoreRequest;
use App\Http\Requests\BookUpdateRequest;
use App\Models\Book;
use App\Models\BookCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BookController extends Controller
{
    /**
     * Display a listing of books with search and filter.
     */
    public function index(Request $request): Response
    {
        $query = Book::with('category')
            ->orderBy('created_at', 'desc');

        // Search functionality
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Filter by category
        if ($categoryId = $request->input('category')) {
            $query->byCategory((int) $categoryId);
        }

        // Filter by availability
        if ($request->input('available') === 'true') {
            $query->available();
        }

        $books = $query->paginate(12)->withQueryString();
        $categories = BookCategory::active()->orderBy('name')->get();

        return Inertia::render('Books/Index', [
            'books' => $books,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'available']),
        ]);
    }

    /**
     * Show the form for creating a new book.
     */
    public function create(): Response
    {
        $categories = BookCategory::active()->orderBy('name')->get();

        return Inertia::render('Books/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created book in storage.
     */
    public function store(BookStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('book-covers', 'public');
        }

        $book = Book::create($validated);

        // Create individual book copies based on total_copies
        $totalCopies = $validated['total_copies'] ?? 1;
        $book->createCopies($totalCopies, [
            'location_code' => $validated['location'] ?? null,
        ]);

        // Generate QR code image for the book (legacy support)
        $this->generateQrCodeImage($book);

        return redirect()->route('books.show', $book)
            ->with('success', 'Buku berhasil ditambahkan ke katalog dengan ' . $totalCopies . ' eksemplar.');
    }

    /**
     * Display the specified book.
     */
    public function show(Book $book): Response
    {
        $book->load(['category', 'copies']);

        return Inertia::render('Books/Show', [
            'book' => $book,
            'copies' => $book->copies,
            'copiesStats' => [
                'available' => $book->getCopiesCountByStatus('available'),
                'borrowed' => $book->getCopiesCountByStatus('borrowed'),
                'damaged' => $book->getCopiesCountByStatus('damaged'),
                'lost' => $book->getCopiesCountByStatus('lost'),
                'maintenance' => $book->getCopiesCountByStatus('maintenance'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified book.
     */
    public function edit(Book $book): Response
    {
        $book->load('category');
        $categories = BookCategory::active()->orderBy('name')->get();

        return Inertia::render('Books/Edit', [
            'book' => $book,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified book in storage.
     */
    public function update(BookUpdateRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            // Delete old cover image
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('book-covers', 'public');
        }

        $book->update($validated);

        return redirect()->route('books.show', $book)
            ->with('success', 'Data buku berhasil diperbarui.');
    }

    /**
     * Remove the specified book from storage.
     */
    public function destroy(Book $book): RedirectResponse
    {
        // Delete cover image
        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }

        // Delete QR code image
        $qrCodePath = 'qr-codes/' . $book->qr_code . '.png';
        if (Storage::disk('public')->exists($qrCodePath)) {
            Storage::disk('public')->delete($qrCodePath);
        }

        $book->delete();

        return redirect()->route('books.index')
            ->with('success', 'Buku berhasil dihapus dari katalog.');
    }

    /**
     * Download QR code for the book.
     */
    public function downloadQrCode(Book $book)
    {
        $qrCodePath = 'qr-codes/' . $book->qr_code . '.png';

        if (!Storage::disk('public')->exists($qrCodePath)) {
            $this->generateQrCodeImage($book);
        }

        return Storage::disk('public')->download($qrCodePath, $book->qr_code . '.png');
    }

    /**
     * Bulk import books from CSV.
     */
    public function bulkImport(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('csv_file');
        $csvData = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_shift($csvData);

        $imported = 0;
        $errors = [];

        foreach ($csvData as $index => $row) {
            try {
                $data = array_combine($header, $row);

                // Find or create category
                $category = BookCategory::firstOrCreate(
                    ['name' => $data['category'] ?? 'Umum'],
                    ['slug' => \Illuminate\Support\Str::slug($data['category'] ?? 'Umum')]
                );

                Book::create([
                    'title' => $data['title'],
                    'author' => $data['author'],
                    'isbn' => $data['isbn'] ?? null,
                    'book_category_id' => $category->id,
                    'description' => $data['description'] ?? null,
                    'publisher' => $data['publisher'] ?? null,
                    'publication_year' => $data['publication_year'] ?? null,
                    'language' => $data['language'] ?? 'id',
                    'total_copies' => (int) ($data['total_copies'] ?? 1),
                    'location' => $data['location'] ?? null,
                ]);

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Baris " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        if (count($errors) > 0) {
            return redirect()->route('books.index')
                ->with('warning', "Berhasil mengimpor {$imported} buku. " . count($errors) . " buku gagal diimpor.");
        }

        return redirect()->route('books.index')
            ->with('success', "Berhasil mengimpor {$imported} buku ke katalog.");
    }

    /**
     * Generate QR code image for the book.
     */
    protected function generateQrCodeImage(Book $book): void
    {
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->generate($book->qr_code);

        $qrCodePath = 'qr-codes/' . $book->qr_code . '.png';
        Storage::disk('public')->put($qrCodePath, $qrCode);
    }
}
