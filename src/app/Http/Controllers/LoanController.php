<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoanStoreRequest;
use App\Http\Requests\ReturnRequest;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    /**
     * Display a listing of loans.
     */
    public function index(Request $request): Response
    {
        $query = Loan::with(['user', 'book', 'bookCopy', 'processedBy'])
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'overdue') {
                $query->overdue();
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by book
        if ($request->filled('book_id')) {
            $query->where('book_id', $request->book_id);
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

        $loans = $query->paginate(20)->withQueryString();

        // Get statistics
        $stats = [
            'active' => Loan::active()->count(),
            'overdue' => Loan::overdue()->count(),
            'returned_today' => Loan::returned()
                ->whereDate('return_date', today())
                ->count(),
            'due_today' => Loan::active()
                ->whereDate('due_date', today())
                ->count(),
        ];

        return Inertia::render('Loans/Index', [
            'loans' => $loans,
            'stats' => $stats,
            'filters' => $request->only(['status', 'user_id', 'book_id', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new loan.
     */
    public function create(Request $request): Response
    {
        // Get active members
        $members = User::members()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'active_loans_count' => $member->active_loans_count,
                    'can_borrow' => $member->canBorrowMoreBooks(),
                ];
            });

        // Get available books
        $books = Book::with('category')
            ->available()
            ->orderBy('title')
            ->get()
            ->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'category' => $book->category->name ?? null,
                    'available_copies' => $book->available_copies,
                ];
            });

        // If reservation_id is provided, get reservation details
        $reservation = null;
        if ($request->filled('reservation_id')) {
            $reservation = Reservation::with(['user', 'book'])
                ->find($request->reservation_id);
        }

        return Inertia::render('Loans/Create', [
            'members' => $members,
            'books' => $books,
            'reservation' => $reservation,
        ]);
    }

    /**
     * Store a newly created loan.
     */
    public function store(LoanStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Get or create book copy
        $bookCopy = null;
        if ($validated['book_copy_id'] ?? null) {
            $bookCopy = BookCopy::find($validated['book_copy_id']);
        } else {
            // Auto-select first available copy
            $bookCopy = BookCopy::where('book_id', $validated['book_id'])
                ->where('status', 'available')
                ->first();
        }

        if (!$bookCopy) {
            return back()->with('error', 'Tidak ada salinan buku yang tersedia.');
        }

        // Create loan
        $loan = Loan::create([
            'user_id' => $validated['user_id'],
            'book_id' => $validated['book_id'],
            'book_copy_id' => $bookCopy->id,
            'reservation_id' => $validated['reservation_id'] ?? null,
            'processed_by' => auth()->id(),
            'loan_date' => $validated['loan_date'],
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'] ?? null,
            'book_condition_at_loan' => $validated['book_condition_at_loan'],
            'status' => 'active',
        ]);

        // Update book copy status
        $bookCopy->update(['status' => 'borrowed']);

        // Update book availability
        $loan->book->syncAvailableCopies();

        // If this was from a reservation, mark it as completed
        if ($loan->reservation_id) {
            $reservation = Reservation::find($loan->reservation_id);
            $reservation?->markAsCompleted();
        }

        // Check if there's a waitlist and notify next person
        $this->notifyNextInWaitlist($loan->book);

        return redirect()
            ->route('loans.index')
            ->with('success', "Peminjaman berhasil dicatat untuk {$loan->user->name}.");
    }

    /**
     * Display the specified loan.
     */
    public function show(Loan $loan): Response
    {
        $loan->load([
            'user',
            'book.category',
            'bookCopy',
            'reservation',
            'processedBy',
            'returnedBy',
        ]);

        return Inertia::render('Loans/Show', [
            'loan' => $loan,
        ]);
    }

    /**
     * Show the form for processing a return.
     */
    public function returnForm(Loan $loan): Response
    {
        $loan->load(['user', 'book', 'bookCopy']);

        return Inertia::render('Loans/Return', [
            'loan' => $loan,
            'overdue_days' => $loan->calculateOverdueDays(),
            'fine_amount' => $loan->calculateFine(),
        ]);
    }

    /**
     * Process the return of a book.
     */
    public function processReturn(ReturnRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $loan = Loan::findOrFail($validated['loan_id']);

        $loan->processReturn(
            auth()->user(),
            $validated['book_condition_at_return'],
            $validated['return_notes'] ?? null
        );

        // Check if there's a waitlist and notify next person
        $this->notifyNextInWaitlist($loan->book);

        $message = "Pengembalian berhasil dicatat untuk {$loan->user->name}.";
        
        if ($loan->days_overdue > 0) {
            $message .= " Terlambat {$loan->days_overdue} hari. Denda: Rp " . number_format($loan->fine_amount, 0, ',', '.');
        }

        return redirect()
            ->route('loans.index')
            ->with('success', $message);
    }

    /**
     * Mark a book as lost.
     */
    public function markAsLost(Request $request, Loan $loan): RedirectResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $loan->markAsLost(auth()->user(), $request->notes);

        return redirect()
            ->route('loans.index')
            ->with('success', "Buku ditandai sebagai hilang untuk peminjaman {$loan->user->name}.");
    }

    /**
     * Get loan history for a user.
     */
    public function history(Request $request, User $user): Response
    {
        $loans = Loan::with(['book', 'processedBy', 'returnedBy'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return Inertia::render('Loans/History', [
            'user' => $user,
            'loans' => $loans,
        ]);
    }

    /**
     * Get overdue loans report.
     */
    public function overdueReport(): Response
    {
        $overdueLoans = Loan::with(['user', 'book'])
            ->overdue()
            ->orderBy('due_date')
            ->get()
            ->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'user' => $loan->user->name,
                    'book' => $loan->book->title,
                    'due_date' => $loan->due_date->format('d/m/Y'),
                    'days_overdue' => $loan->calculateOverdueDays(),
                    'fine_amount' => $loan->calculateFine(),
                ];
            });

        $totalFines = $overdueLoans->sum('fine_amount');

        return Inertia::render('Loans/OverdueReport', [
            'overdueLoans' => $overdueLoans,
            'totalFines' => $totalFines,
        ]);
    }

    /**
     * Get popular books report.
     */
    public function popularBooksReport(): Response
    {
        $popularBooks = Book::withCount(['loans' => function ($query) {
            $query->where('created_at', '>=', now()->subMonths(3));
        }])
            ->having('loans_count', '>', 0)
            ->orderBy('loans_count', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'loans_count' => $book->loans_count,
                    'category' => $book->category->name ?? null,
                ];
            });

        return Inertia::render('Loans/PopularBooksReport', [
            'popularBooks' => $popularBooks,
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
