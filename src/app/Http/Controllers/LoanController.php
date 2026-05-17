<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanRequest;
use App\Http\Requests\UpdateLoanRequest;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    /**
     * Display a listing of loans.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Loan::class);

        $query = Loan::with(['user', 'book', 'bookCopy', 'processedBy'])
            ->latest('loan_date');

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'overdue') {
                $query->overdue();
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by member
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
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('book', function ($bookQuery) use ($search) {
                    $bookQuery->where('title', 'like', "%{$search}%");
                });
            });
        }

        $loans = $query->paginate(15)->withQueryString();

        // Get statistics
        $stats = [
            'active' => Loan::where('status', 'active')->count(),
            'overdue' => Loan::overdue()->count(),
            'returned_today' => Loan::where('status', 'returned')
                ->whereDate('return_date', today())
                ->count(),
            'due_soon' => Loan::dueWithin(3)->count(),
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
    public function create(): Response
    {
        $this->authorize('create', Loan::class);

        // Get active members
        $members = User::where('role', 'member')
            ->where('status', 'active')
            ->select('id', 'name', 'email', 'member_id')
            ->orderBy('name')
            ->get();

        // Get available books with available copies
        $books = Book::whereHas('bookCopies', function ($query) {
            $query->where('status', 'available');
        })
        ->with(['bookCopies' => function ($query) {
            $query->where('status', 'available');
        }])
        ->select('id', 'title', 'author', 'isbn')
        ->orderBy('title')
        ->get();

        return Inertia::render('Loans/Create', [
            'members' => $members,
            'books' => $books,
        ]);
    }

    /**
     * Store a newly created loan in storage.
     */
    public function store(StoreLoanRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // Get book copy and book
            $bookCopy = BookCopy::findOrFail($validated['book_copy_id']);
            $book = $bookCopy->book;

            // Create loan
            $loan = Loan::create([
                'user_id' => $validated['user_id'],
                'book_id' => $book->id,
                'book_copy_id' => $bookCopy->id,
                'reservation_id' => $validated['reservation_id'] ?? null,
                'processed_by' => auth()->id(),
                'status' => 'active',
                'loan_date' => $validated['loan_date'] ?? now()->toDateString(),
                'due_date' => $validated['due_date'] ?? now()->addDays(14)->toDateString(),
                'notes' => $validated['notes'] ?? null,
                'book_condition_at_loan' => $validated['book_condition_at_loan'] ?? 'good',
            ]);

            // Update book copy status
            $bookCopy->update(['status' => 'borrowed']);

            // Update book available copies count
            $book->syncAvailableCopies();

            // If loan is from reservation, mark reservation as fulfilled
            if ($loan->reservation_id) {
                $loan->reservation->update(['status' => 'fulfilled']);
            }

            DB::commit();

            Log::info('Loan created', [
                'loan_id' => $loan->id,
                'user_id' => $loan->user_id,
                'book_id' => $loan->book_id,
                'processed_by' => auth()->id(),
            ]);

            return redirect()->route('loans.show', $loan)
                ->with('success', 'Peminjaman berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create loan', [
                'error' => $e->getMessage(),
                'user_id' => $request->user_id,
                'book_copy_id' => $request->book_copy_id,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Gagal membuat peminjaman. Silakan coba lagi.');
        }
    }

    /**
     * Display the specified loan.
     */
    public function show(Loan $loan): Response
    {
        $this->authorize('view', $loan);

        $loan->load([
            'user',
            'book.category',
            'bookCopy',
            'processedBy',
            'returnedBy',
            'reservation',
        ]);

        return Inertia::render('Loans/Show', [
            'loan' => $loan,
        ]);
    }

    /**
     * Show the form for editing the specified loan.
     */
    public function edit(Loan $loan): Response
    {
        $this->authorize('update', $loan);

        $loan->load(['user', 'book', 'bookCopy']);

        return Inertia::render('Loans/Edit', [
            'loan' => $loan,
        ]);
    }

    /**
     * Update the specified loan in storage.
     */
    public function update(UpdateLoanRequest $request, Loan $loan): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $action = $validated['action'];

            switch ($action) {
                case 'return':
                    $this->processReturn($loan, $validated);
                    $message = 'Buku berhasil dikembalikan.';
                    break;

                case 'mark_lost':
                    $this->processLost($loan, $validated);
                    $message = 'Buku berhasil ditandai sebagai hilang.';
                    break;

                case 'extend':
                    $this->processExtension($loan, $validated);
                    $message = 'Peminjaman berhasil diperpanjang.';
                    break;

                default:
                    throw new \InvalidArgumentException('Invalid action');
            }

            DB::commit();

            Log::info('Loan updated', [
                'loan_id' => $loan->id,
                'action' => $action,
                'processed_by' => auth()->id(),
            ]);

            return redirect()->route('loans.show', $loan)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update loan', [
                'loan_id' => $loan->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Gagal memperbarui peminjaman. Silakan coba lagi.');
        }
    }

    /**
     * Process book return.
     */
    private function processReturn(Loan $loan, array $data): void
    {
        $loan->processReturn(
            auth()->user(),
            $data['book_condition_at_return'] ?? 'good',
            $data['return_notes'] ?? null
        );

        // Update fine paid status if provided
        if (isset($data['fine_paid'])) {
            $loan->update(['fine_paid' => $data['fine_paid']]);
        }
    }

    /**
     * Process book lost.
     */
    private function processLost(Loan $loan, array $data): void
    {
        $loan->markAsLost(
            auth()->user(),
            $data['return_notes'] ?? null
        );
    }

    /**
     * Process loan extension.
     */
    private function processExtension(Loan $loan, array $data): void
    {
        if ($loan->status !== 'active') {
            throw new \InvalidArgumentException('Only active loans can be extended');
        }

        $extendDays = $data['extend_days'] ?? 7;
        $newDueDate = now()->parse($loan->due_date)->addDays($extendDays);

        $loan->update([
            'due_date' => $newDueDate->toDateString(),
            'notes' => ($loan->notes ?? '') . "\nDiperpanjang {$extendDays} hari pada " . now()->format('d/m/Y'),
        ]);
    }

    /**
     * Remove the specified loan from storage.
     */
    public function destroy(Loan $loan): RedirectResponse
    {
        $this->authorize('delete', $loan);

        try {
            // Only allow deletion of returned loans
            if ($loan->status !== 'returned') {
                return back()->with('error', 'Hanya peminjaman yang sudah dikembalikan yang dapat dihapus.');
            }

            $loan->delete();

            Log::info('Loan deleted', [
                'loan_id' => $loan->id,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('loans.index')
                ->with('success', 'Peminjaman berhasil dihapus.');

        } catch (\Exception $e) {
            Log::error('Failed to delete loan', [
                'loan_id' => $loan->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menghapus peminjaman. Silakan coba lagi.');
        }
    }

    /**
     * Get member's loan history.
     */
    public function memberHistory(User $user): Response
    {
        $this->authorize('viewAny', Loan::class);

        $loans = Loan::with(['book', 'bookCopy', 'processedBy'])
            ->where('user_id', $user->id)
            ->latest('loan_date')
            ->paginate(15);

        $stats = [
            'total_loans' => Loan::where('user_id', $user->id)->count(),
            'active_loans' => Loan::where('user_id', $user->id)->where('status', 'active')->count(),
            'overdue_loans' => Loan::where('user_id', $user->id)->overdue()->count(),
            'total_fines' => Loan::where('user_id', $user->id)
                ->where('fine_amount', '>', 0)
                ->sum('fine_amount'),
            'unpaid_fines' => Loan::where('user_id', $user->id)
                ->where('fine_amount', '>', 0)
                ->where('fine_paid', false)
                ->sum('fine_amount'),
        ];

        return Inertia::render('Loans/MemberHistory', [
            'member' => $user,
            'loans' => $loans,
            'stats' => $stats,
        ]);
    }

    /**
     * Get book's loan history.
     */
    public function bookHistory(Book $book): Response
    {
        $this->authorize('viewAny', Loan::class);

        $loans = Loan::with(['user', 'bookCopy', 'processedBy'])
            ->where('book_id', $book->id)
            ->latest('loan_date')
            ->paginate(15);

        $stats = [
            'total_loans' => Loan::where('book_id', $book->id)->count(),
            'active_loans' => Loan::where('book_id', $book->id)->where('status', 'active')->count(),
            'average_loan_duration' => Loan::where('book_id', $book->id)
                ->where('status', 'returned')
                ->selectRaw('AVG(DATEDIFF(return_date, loan_date)) as avg_duration')
                ->value('avg_duration'),
        ];

        return Inertia::render('Loans/BookHistory', [
            'book' => $book->load('category'),
            'loans' => $loans,
            'stats' => $stats,
        ]);
    }
}