<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\ReadingProgress;
use App\Models\Softbook;
use App\Services\FileEncryptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReaderController extends Controller
{
    public function __construct(
        private FileEncryptionService $encryptionService
    ) {}

    /**
     * Open reader for a softbook.
     */
    public function read(Softbook $softbook): InertiaResponse
    {
        // Check if user can access this softbook
        if (!$softbook->is_active) {
            abort(403, 'Softbook tidak aktif.');
        }

        if (auth()->user()->status !== 'active') {
            abort(403, 'Akun Anda tidak aktif.');
        }

        $softbook->load('book');

        // Get or create reading progress
        $progress = ReadingProgress::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'softbook_id' => $softbook->id,
            ],
            [
                'current_page' => 1,
                'total_pages' => $softbook->pages,
                'last_read_at' => now(),
            ]
        );

        // Get user's bookmarks for this softbook
        $bookmarks = Bookmark::where('user_id', auth()->id())
            ->where('softbook_id', $softbook->id)
            ->orderBy('page_number')
            ->get();

        return Inertia::render('Reader/Index', [
            'softbook' => $softbook,
            'progress' => $progress,
            'bookmarks' => $bookmarks,
            'user' => [
                'id' => auth()->id(),
                'name' => auth()->user()->name,
                'member_id' => auth()->user()->member_id,
            ],
        ]);
    }

    /**
     * Serve softbook content securely.
     */
    public function getContent(Softbook $softbook): Response
    {
        // Verify access
        if (!$softbook->is_active || auth()->user()->status !== 'active') {
            abort(403);
        }

        try {
            // Check if file exists
            if (!$softbook->fileExists()) {
                abort(404, 'File tidak ditemukan.');
            }

            // Get file content
            if ($softbook->is_encrypted) {
                $encryptedContent = Storage::disk('private')->get($softbook->file_path);
                $content = $this->encryptionService->decryptFile($encryptedContent);
            } else {
                $content = Storage::disk('private')->get($softbook->file_path);
            }

            // Add watermark for PDF (member info)
            if ($softbook->format === 'pdf') {
                $watermarkText = auth()->user()->name . ' - ' . auth()->user()->member_id;
                $content = $this->encryptionService->addWatermark($content, $watermarkText);
            }

            Log::info('Softbook content accessed', [
                'softbook_id' => $softbook->id,
                'user_id' => auth()->id(),
            ]);

            // Return content with appropriate headers
            return response($content)
                ->header('Content-Type', $softbook->format === 'pdf' ? 'application/pdf' : 'application/epub+zip')
                ->header('Content-Disposition', 'inline; filename="' . $softbook->original_filename . '"')
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('Cache-Control', 'private, max-age=3600');

        } catch (\Exception $e) {
            Log::error('Failed to serve softbook content', [
                'softbook_id' => $softbook->id,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Gagal memuat konten.');
        }
    }

    /**
     * Update reading progress.
     */
    public function updateProgress(Request $request, Softbook $softbook): JsonResponse
    {
        $validated = $request->validate([
            'current_page' => 'required|integer|min:1',
            'total_pages' => 'nullable|integer|min:1',
            'position' => 'nullable|string',
            'reading_time' => 'nullable|integer|min:0', // seconds
        ]);

        try {
            $progress = ReadingProgress::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'softbook_id' => $softbook->id,
                ],
                [
                    'current_page' => $validated['current_page'],
                    'total_pages' => $validated['total_pages'] ?? $softbook->pages,
                    'last_position' => $validated['position'] ?? null,
                    'last_read_at' => now(),
                ]
            );

            // Calculate progress percentage
            if ($progress->total_pages > 0) {
                $progress->progress_percentage = round(
                    ($progress->current_page / $progress->total_pages) * 100,
                    2
                );
                $progress->save();
            }

            // Add reading time if provided
            if (isset($validated['reading_time']) && $validated['reading_time'] > 0) {
                $progress->addReadingTime($validated['reading_time']);
            }

            Log::info('Reading progress updated', [
                'softbook_id' => $softbook->id,
                'user_id' => auth()->id(),
                'page' => $validated['current_page'],
            ]);

            return response()->json([
                'success' => true,
                'progress' => $progress,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update reading progress', [
                'softbook_id' => $softbook->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan progres membaca.',
            ], 500);
        }
    }

    /**
     * Get reading progress.
     */
    public function getProgress(Softbook $softbook): JsonResponse
    {
        $progress = ReadingProgress::where('user_id', auth()->id())
            ->where('softbook_id', $softbook->id)
            ->first();

        return response()->json([
            'progress' => $progress,
        ]);
    }

    /**
     * Create a bookmark.
     */
    public function createBookmark(Request $request, Softbook $softbook): JsonResponse
    {
        $validated = $request->validate([
            'page_number' => 'nullable|integer|min:1',
            'position' => 'nullable|string',
            'title' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'highlighted_text' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
        ]);

        try {
            $bookmark = Bookmark::create([
                'user_id' => auth()->id(),
                'softbook_id' => $softbook->id,
                'page_number' => $validated['page_number'] ?? null,
                'position' => $validated['position'] ?? null,
                'title' => $validated['title'] ?? null,
                'note' => $validated['note'] ?? null,
                'highlighted_text' => $validated['highlighted_text'] ?? null,
                'color' => $validated['color'] ?? '#ffeb3b',
            ]);

            Log::info('Bookmark created', [
                'bookmark_id' => $bookmark->id,
                'softbook_id' => $softbook->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'bookmark' => $bookmark,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create bookmark', [
                'softbook_id' => $softbook->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat bookmark.',
            ], 500);
        }
    }

    /**
     * Update a bookmark.
     */
    public function updateBookmark(Request $request, Bookmark $bookmark): JsonResponse
    {
        // Verify ownership
        if ($bookmark->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7',
        ]);

        try {
            $bookmark->update($validated);

            Log::info('Bookmark updated', [
                'bookmark_id' => $bookmark->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'bookmark' => $bookmark,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update bookmark', [
                'bookmark_id' => $bookmark->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui bookmark.',
            ], 500);
        }
    }

    /**
     * Delete a bookmark.
     */
    public function deleteBookmark(Bookmark $bookmark): JsonResponse
    {
        // Verify ownership
        if ($bookmark->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $bookmark->delete();

            Log::info('Bookmark deleted', [
                'bookmark_id' => $bookmark->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete bookmark', [
                'bookmark_id' => $bookmark->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus bookmark.',
            ], 500);
        }
    }

    /**
     * Get all bookmarks for a softbook.
     */
    public function getBookmarks(Softbook $softbook): JsonResponse
    {
        $bookmarks = Bookmark::where('user_id', auth()->id())
            ->where('softbook_id', $softbook->id)
            ->orderBy('page_number')
            ->get();

        return response()->json([
            'bookmarks' => $bookmarks,
        ]);
    }

    /**
     * Get reading statistics for user.
     */
    public function getStatistics(): JsonResponse
    {
        $stats = [
            'total_books_read' => ReadingProgress::where('user_id', auth()->id())->count(),
            'completed_books' => ReadingProgress::where('user_id', auth()->id())
                ->where('progress_percentage', '>=', 100)
                ->count(),
            'in_progress_books' => ReadingProgress::where('user_id', auth()->id())
                ->where('progress_percentage', '>', 0)
                ->where('progress_percentage', '<', 100)
                ->count(),
            'total_bookmarks' => Bookmark::where('user_id', auth()->id())->count(),
            'total_reading_time' => ReadingProgress::where('user_id', auth()->id())
                ->sum('total_reading_time'),
        ];

        return response()->json($stats);
    }

    /**
     * Get recently read books.
     */
    public function getRecentlyRead(): JsonResponse
    {
        $recentBooks = ReadingProgress::with('softbook.book')
            ->where('user_id', auth()->id())
            ->orderBy('last_read_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'recent_books' => $recentBooks,
        ]);
    }
}
