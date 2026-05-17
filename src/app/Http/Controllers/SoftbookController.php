<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreSoftbookRequest;
use App\Http\Requests\UpdateSoftbookRequest;
use App\Models\Book;
use App\Models\Softbook;
use App\Models\SoftbookDownload;
use App\Services\FileEncryptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SoftbookController extends Controller
{
    public function __construct(
        private FileEncryptionService $encryptionService
    ) {}

    /**
     * Display a listing of softbooks.
     */
    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', Softbook::class);

        $query = Softbook::with(['book', 'uploadedBy'])
            ->latest();

        // Filter by format
        if ($request->filled('format')) {
            $query->where('format', $request->format);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by book title
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('book', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }

        $softbooks = $query->paginate(15)->withQueryString();

        // Get statistics
        $stats = [
            'total' => Softbook::count(),
            'active' => Softbook::where('is_active', true)->count(),
            'pdf' => Softbook::where('format', 'pdf')->count(),
            'epub' => Softbook::where('format', 'epub')->count(),
            'total_downloads' => Softbook::sum('total_downloads'),
        ];

        return Inertia::render('Softbooks/Index', [
            'softbooks' => $softbooks,
            'stats' => $stats,
            'filters' => $request->only(['format', 'is_active', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new softbook.
     */
    public function create(): InertiaResponse
    {
        $this->authorize('create', Softbook::class);

        // Get books that don't have softbooks yet or can have multiple formats
        $books = Book::select('id', 'title', 'author', 'isbn')
            ->orderBy('title')
            ->get();

        return Inertia::render('Softbooks/Create', [
            'books' => $books,
        ]);
    }

    /**
     * Store a newly created softbook in storage.
     */
    public function store(StoreSoftbookRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $file = $request->file('file');

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $validated['format'];
            $storagePath = 'softbooks/' . $filename;

            // Store file
            if ($validated['is_encrypted'] ?? false) {
                // Encrypt and store
                $encryptedContent = $this->encryptionService->encryptFile($file->getRealPath());
                Storage::disk('private')->put($storagePath, $encryptedContent);
                $encryptionKey = $this->encryptionService->generateKey();
            } else {
                // Store without encryption
                Storage::disk('private')->putFileAs('softbooks', $file, $filename);
                $encryptionKey = null;
            }

            // Create softbook record
            $softbook = Softbook::create([
                'book_id' => $validated['book_id'],
                'file_path' => $storagePath,
                'original_filename' => $file->getClientOriginalName(),
                'format' => $validated['format'],
                'file_size' => $file->getSize(),
                'pages' => $validated['pages'] ?? null,
                'encryption_key' => $encryptionKey,
                'is_encrypted' => $validated['is_encrypted'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
                'download_limit' => $validated['download_limit'] ?? 5,
                'description' => $validated['description'] ?? null,
                'uploaded_by' => auth()->id(),
            ]);

            DB::commit();

            Log::info('Softbook uploaded', [
                'softbook_id' => $softbook->id,
                'book_id' => $softbook->book_id,
                'format' => $softbook->format,
                'uploaded_by' => auth()->id(),
            ]);

            return redirect()->route('softbooks.show', $softbook)
                ->with('success', 'Softbook berhasil diunggah.');

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up uploaded file if exists
            if (isset($storagePath) && Storage::disk('private')->exists($storagePath)) {
                Storage::disk('private')->delete($storagePath);
            }

            Log::error('Failed to upload softbook', [
                'error' => $e->getMessage(),
                'book_id' => $request->book_id,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Gagal mengunggah softbook. Silakan coba lagi.');
        }
    }

    /**
     * Display the specified softbook.
     */
    public function show(Softbook $softbook): InertiaResponse
    {
        $this->authorize('view', $softbook);

        $softbook->load(['book.category', 'uploadedBy']);

        // Get user's download history for this softbook
        $userDownloads = null;
        $remainingDownloads = null;
        
        if (auth()->user()->role === 'member') {
            $userDownloads = SoftbookDownload::where('softbook_id', $softbook->id)
                ->where('user_id', auth()->id())
                ->where('is_completed', true)
                ->count();
            
            $remainingDownloads = $softbook->getRemainingDownloadsFor(auth()->user());
        }

        return Inertia::render('Softbooks/Show', [
            'softbook' => $softbook,
            'user_downloads' => $userDownloads,
            'remaining_downloads' => $remainingDownloads,
            'can_download' => $softbook->canBeDownloadedBy(auth()->user()),
        ]);
    }

    /**
     * Show the form for editing the specified softbook.
     */
    public function edit(Softbook $softbook): InertiaResponse
    {
        $this->authorize('update', $softbook);

        $softbook->load('book');

        return Inertia::render('Softbooks/Edit', [
            'softbook' => $softbook,
        ]);
    }

    /**
     * Update the specified softbook in storage.
     */
    public function update(UpdateSoftbookRequest $request, Softbook $softbook): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $softbook->update($validated);

            Log::info('Softbook updated', [
                'softbook_id' => $softbook->id,
                'updated_by' => auth()->id(),
            ]);

            return redirect()->route('softbooks.show', $softbook)
                ->with('success', 'Softbook berhasil diperbarui.');

        } catch (\Exception $e) {
            Log::error('Failed to update softbook', [
                'softbook_id' => $softbook->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Gagal memperbarui softbook. Silakan coba lagi.');
        }
    }

    /**
     * Remove the specified softbook from storage.
     */
    public function destroy(Softbook $softbook): RedirectResponse
    {
        $this->authorize('delete', $softbook);

        try {
            // Delete file from storage
            $softbook->deleteFile();

            // Soft delete softbook
            $softbook->delete();

            Log::info('Softbook deleted', [
                'softbook_id' => $softbook->id,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('softbooks.index')
                ->with('success', 'Softbook berhasil dihapus.');

        } catch (\Exception $e) {
            Log::error('Failed to delete softbook', [
                'softbook_id' => $softbook->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menghapus softbook. Silakan coba lagi.');
        }
    }

    /**
     * Generate download token for softbook.
     */
    public function generateDownloadToken(Softbook $softbook): RedirectResponse
    {
        $this->authorize('download', $softbook);

        try {
            // Check if user can download
            if (!$softbook->canBeDownloadedBy(auth()->user())) {
                return back()->with('error', 'Anda telah mencapai batas unduhan untuk softbook ini.');
            }

            // Create download record
            $download = SoftbookDownload::create([
                'softbook_id' => $softbook->id,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            Log::info('Download token generated', [
                'softbook_id' => $softbook->id,
                'user_id' => auth()->id(),
                'download_id' => $download->id,
            ]);

            return redirect()->route('softbooks.download', $download->download_token);

        } catch (\Exception $e) {
            Log::error('Failed to generate download token', [
                'softbook_id' => $softbook->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal membuat token unduhan. Silakan coba lagi.');
        }
    }

    /**
     * Download softbook using token.
     */
    public function download(string $token): Response|RedirectResponse
    {
        try {
            $download = SoftbookDownload::where('download_token', $token)
                ->with('softbook')
                ->firstOrFail();

            // Check if token is valid
            if (!$download->isTokenValid()) {
                return redirect()->route('softbooks.index')
                    ->with('error', 'Token unduhan telah kedaluwarsa. Silakan buat token baru.');
            }

            // Check if already downloaded
            if ($download->is_completed) {
                return redirect()->route('softbooks.index')
                    ->with('error', 'Token unduhan sudah digunakan.');
            }

            $softbook = $download->softbook;

            // Check if file exists
            if (!$softbook->fileExists()) {
                return redirect()->route('softbooks.index')
                    ->with('error', 'File tidak ditemukan.');
            }

            // Get file content
            if ($softbook->is_encrypted) {
                $encryptedContent = Storage::disk('private')->get($softbook->file_path);
                $content = $this->encryptionService->decryptFile($encryptedContent);
            } else {
                $content = Storage::disk('private')->get($softbook->file_path);
            }

            // Add watermark (optional)
            if ($softbook->format === 'pdf') {
                $watermarkText = auth()->user()->name . ' - ' . auth()->user()->member_id;
                $content = $this->encryptionService->addWatermark($content, $watermarkText);
            }

            // Mark download as completed
            $download->markAsCompleted();

            Log::info('Softbook downloaded', [
                'softbook_id' => $softbook->id,
                'user_id' => auth()->id(),
                'download_id' => $download->id,
            ]);

            // Return file download response
            return response($content)
                ->header('Content-Type', $softbook->format === 'pdf' ? 'application/pdf' : 'application/epub+zip')
                ->header('Content-Disposition', 'attachment; filename="' . $softbook->original_filename . '"')
                ->header('Content-Length', strlen($content));

        } catch (\Exception $e) {
            Log::error('Failed to download softbook', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('softbooks.index')
                ->with('error', 'Gagal mengunduh softbook. Silakan coba lagi.');
        }
    }

    /**
     * Get download history for a softbook.
     */
    public function downloadHistory(Softbook $softbook): InertiaResponse
    {
        $this->authorize('viewAny', Softbook::class);

        $downloads = SoftbookDownload::with('user')
            ->where('softbook_id', $softbook->id)
            ->where('is_completed', true)
            ->latest('downloaded_at')
            ->paginate(15);

        return Inertia::render('Softbooks/DownloadHistory', [
            'softbook' => $softbook->load('book'),
            'downloads' => $downloads,
        ]);
    }

    /**
     * Get user's download history.
     */
    public function myDownloads(): InertiaResponse
    {
        $downloads = SoftbookDownload::with(['softbook.book'])
            ->where('user_id', auth()->id())
            ->where('is_completed', true)
            ->latest('downloaded_at')
            ->paginate(15);

        return Inertia::render('Softbooks/MyDownloads', [
            'downloads' => $downloads,
        ]);
    }

    /**
     * Toggle softbook active status.
     */
    public function toggleActive(Softbook $softbook): RedirectResponse
    {
        $this->authorize('update', $softbook);

        try {
            $softbook->update([
                'is_active' => !$softbook->is_active,
            ]);

            $status = $softbook->is_active ? 'diaktifkan' : 'dinonaktifkan';

            Log::info('Softbook status toggled', [
                'softbook_id' => $softbook->id,
                'is_active' => $softbook->is_active,
                'updated_by' => auth()->id(),
            ]);

            return back()->with('success', "Softbook berhasil {$status}.");

        } catch (\Exception $e) {
            Log::error('Failed to toggle softbook status', [
                'softbook_id' => $softbook->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal mengubah status softbook. Silakan coba lagi.');
        }
    }
}
