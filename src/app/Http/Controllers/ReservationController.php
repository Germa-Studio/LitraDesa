<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReservationRequest;
use App\Models\Book;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    /**
     * Display a listing of reservations.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        
        if ($user->isAdmin()) {
            // Admin sees all reservations
            $query = Reservation::with(['user', 'book'])
                ->latest();

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Search by member name or book title
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereHas('book', function ($bookQuery) use ($search) {
                        $bookQuery->where('title', 'ILIKE', "%{$search}%");
                    });
                });
            }

            $reservations = $query->paginate(20)->withQueryString();

            // Get statistics
            $stats = [
                'pending' => Reservation::pending()->count(),
                'ready' => Reservation::ready()->count(),
                'expired_today' => Reservation::where('status', 'expired')
                    ->whereDate('updated_at', today())
                    ->count(),
            ];
        } else {
            // Member sees only their reservations
            $reservations = Reservation::with(['book.category'])
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(20);

            $stats = [
                'active' => Reservation::forUser($user->id)->active()->count(),
                'completed' => Reservation::forUser($user->id)->where('status', 'completed')->count(),
            ];
        }

        return Inertia::render('Reservations/Index', [
            'reservations' => $reservations,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new reservation.
     */
    public function create(Request $request): Response
    {
        // Get available books or books with waitlist capacity
        $books = Book::with('category')
            ->where(function ($query) {
                $query->where('is_available', true)
                    ->orWhereHas('reservations', function ($q) {
                        $q->where('status', 'pending');
                    }, '<', 10); // Max 10 in waitlist
            })
            ->orderBy('title')
            ->get()
            ->map(function ($book) {
                $waitlistCount = $book->waitlist()->count();
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'category' => $book->category->name ?? null,
                    'available_copies' => $book->available_copies,
                    'is_available' => $book->is_available,
                    'waitlist_count' => $waitlistCount,
                    'can_reserve' => $book->canBeReserved(),
                ];
            });

        // If book_id is provided, pre-select it
        $selectedBook = null;
        if ($request->filled('book_id')) {
            $selectedBook = Book::with('category')->find($request->book_id);
        }

        return Inertia::render('Reservations/Create', [
            'books' => $books,
            'selectedBook' => $selectedBook,
        ]);
    }

    /**
     * Store a newly created reservation.
     */
    public function store(ReservationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        $book = Book::findOrFail($validated['book_id']);
        
        // Determine initial status based on availability
        $status = $book->is_available && $book->available_copies > 0 ? 'ready' : 'pending';
        
        $reservation = Reservation::create([
            'user_id' => auth()->id(),
            'book_id' => $validated['book_id'],
            'status' => $status,
            'reserved_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        if ($status === 'ready') {
            // TODO: Send notification to user (email/WhatsApp)
            // This will be implemented in Phase 2
            $message = "Reservasi berhasil! Buku '{$book->title}' siap diambil. Harap ambil dalam 24 jam.";
        } else {
            $waitlistPosition = $book->waitlist()->count();
            $message = "Reservasi berhasil! Anda berada di posisi {$waitlistPosition} dalam daftar tunggu untuk buku '{$book->title}'.";
        }

        return redirect()
            ->route('reservations.index')
            ->with('success', $message);
    }

    /**
     * Display the specified reservation.
     */
    public function show(Reservation $reservation): Response
    {
        // Check authorization
        if (!auth()->user()->isAdmin() && $reservation->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $reservation->load(['user', 'book.category']);

        return Inertia::render('Reservations/Show', [
            'reservation' => $reservation,
            'hours_remaining' => $reservation->hours_remaining,
        ]);
    }

    /**
     * Cancel a reservation.
     */
    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->isAdmin() && $reservation->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if (!$reservation->isActive()) {
            return back()->with('error', 'Reservasi tidak dapat dibatalkan.');
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $reservation->cancel($request->reason ?? 'Dibatalkan oleh pengguna');

        // Check if there's a waitlist and notify next person
        $this->notifyNextInWaitlist($reservation->book);

        return redirect()
            ->route('reservations.index')
            ->with('success', 'Reservasi berhasil dibatalkan.');
    }

    /**
     * Mark reservation as ready (admin only).
     */
    public function markAsReady(Reservation $reservation): RedirectResponse
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        if ($reservation->status !== 'pending') {
            return back()->with('error', 'Reservasi tidak dalam status pending.');
        }

        $reservation->markAsReady();

        // TODO: Send notification to user (email/WhatsApp)
        // This will be implemented in Phase 2

        return redirect()
            ->route('reservations.index')
            ->with('success', "Reservasi untuk {$reservation->user->name} ditandai sebagai siap.");
    }

    /**
     * Process expired reservations (cron job endpoint).
     */
    public function processExpired(): RedirectResponse
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        $expiredReservations = Reservation::expired()->get();
        
        foreach ($expiredReservations as $reservation) {
            $reservation->markAsExpired();
            
            // Check if there's a waitlist and notify next person
            $this->notifyNextInWaitlist($reservation->book);
        }

        $count = $expiredReservations->count();

        return redirect()
            ->route('reservations.index')
            ->with('success', "{$count} reservasi kadaluarsa telah diproses.");
    }

    /**
     * Get reservation statistics for dashboard.
     */
    public function statistics(): Response
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        $stats = [
            'total_active' => Reservation::active()->count(),
            'pending' => Reservation::pending()->count(),
            'ready' => Reservation::ready()->count(),
            'completed_today' => Reservation::where('status', 'completed')
                ->whereDate('picked_up_at', today())
                ->count(),
            'expired_today' => Reservation::where('status', 'expired')
                ->whereDate('updated_at', today())
                ->count(),
            'cancelled_today' => Reservation::where('status', 'cancelled')
                ->whereDate('updated_at', today())
                ->count(),
        ];

        // Get books with most reservations
        $popularBooks = Book::withCount(['reservations' => function ($query) {
            $query->where('created_at', '>=', now()->subMonth());
        }])
            ->having('reservations_count', '>', 0)
            ->orderBy('reservations_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($book) {
                return [
                    'title' => $book->title,
                    'author' => $book->author,
                    'reservations_count' => $book->reservations_count,
                ];
            });

        return Inertia::render('Reservations/Statistics', [
            'stats' => $stats,
            'popularBooks' => $popularBooks,
        ]);
    }

    /**
     * Get user's reservation history.
     */
    public function history(): Response
    {
        $user = auth()->user();

        $reservations = Reservation::with(['book.category'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return Inertia::render('Reservations/History', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * Notify next person in waitlist when book becomes available.
     */
    private function notifyNextInWaitlist(Book $book): void
    {
        if ($book->is_available && $book->available_copies > 0) {
            $nextReservation = $book->getNextInWaitlist();
            
            if ($nextReservation) {
                $nextReservation->markAsReady();
                
                // TODO: Send notification to user (email/WhatsApp)
                // This will be implemented in Phase 2 with WhatsApp integration
            }
        }
    }
}
