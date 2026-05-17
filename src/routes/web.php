<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookCopyController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $user = Auth::user();

    $dashboardData = [
        'user' => [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'qr_code' => $user->qr_code,
        ],
    ];

    // Add admin-specific data
    if ($user->isAdmin()) {
        $dashboardData['stats'] = [
            'total_members' => User::members()->count(),
            'pending_members' => User::members()->pending()->count(),
            'active_members' => User::members()->active()->count(),
            'suspended_members' => User::members()->where('status', 'suspended')->count(),
        ];

        $dashboardData['recent_members'] = User::members()
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'status' => $member->status,
                'created_at' => $member->created_at->format('d/m/Y H:i'),
            ]);
    }

    return Inertia::render('Dashboard', $dashboardData);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Member Management Routes
Route::prefix('members')->name('members.')->group(function () {
    // Public registration
    Route::get('/register', [MemberController::class, 'create'])->name('create');
    Route::post('/register', [MemberController::class, 'store'])->name('store');

    // Authenticated member routes
    Route::middleware(['auth'])->group(function () {
        Route::get('/{member}', [MemberController::class, 'show'])->name('show');
        Route::get('/{member}/edit', [MemberController::class, 'edit'])->name('edit');
        Route::patch('/{member}', [MemberController::class, 'update'])->name('update');

        // QR Code routes
        Route::get('/{member}/qr-code', [MemberController::class, 'qrCode'])->name('qr-code');
        Route::post('/verify-qr', [MemberController::class, 'verifyQrCode'])->name('verify-qr');
    });

    // Admin-only routes
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [MemberController::class, 'index'])->name('index');
        Route::get('/pending/list', [MemberController::class, 'pending'])->name('pending');
        Route::post('/{member}/approve', [MemberController::class, 'approve'])->name('approve');
        Route::post('/{member}/suspend', [MemberController::class, 'suspend'])->name('suspend');
        Route::post('/{member}/reactivate', [MemberController::class, 'reactivate'])->name('reactivate');
        Route::delete('/{member}', [MemberController::class, 'destroy'])->name('destroy');
    });
});

// Book Management Routes
Route::prefix('books')->name('books.')->middleware(['auth'])->group(function () {
    // Public book browsing
    Route::get('/', [BookController::class, 'index'])->name('index');

    // Admin-only routes (must come before {book} wildcard route)
    Route::middleware(['admin'])->group(function () {
        Route::get('/create', [BookController::class, 'create'])->name('create');
        Route::post('/', [BookController::class, 'store'])->name('store');
        Route::post('/bulk-import', [BookController::class, 'bulkImport'])->name('bulk-import');
    });

    // Wildcard routes (must come after specific routes)
    Route::get('/{book}', [BookController::class, 'show'])->name('show');

    // Admin-only wildcard routes
    Route::middleware(['admin'])->group(function () {
        Route::get('/{book}/edit', [BookController::class, 'edit'])->name('edit');
        Route::patch('/{book}', [BookController::class, 'update'])->name('update');
        Route::delete('/{book}', [BookController::class, 'destroy'])->name('destroy');
        Route::get('/{book}/qr-code', [BookController::class, 'downloadQrCode'])->name('qr-code');

        // Book Copy Management Routes
        Route::post('/{book}/copies', [BookCopyController::class, 'store'])->name('copies.store');
        Route::get('/{book}/copies/qr-codes', [BookCopyController::class, 'downloadAllQrCodes'])->name('copies.qr-codes');
    });
});

// Book Copy Routes (Admin only)
Route::prefix('book-copies')->name('book-copies.')->middleware(['auth', 'admin'])->group(function () {
    Route::patch('/{bookCopy}', [BookCopyController::class, 'update'])->name('update');
    Route::post('/{bookCopy}/mark-damaged', [BookCopyController::class, 'markAsDamaged'])->name('mark-damaged');
    Route::post('/{bookCopy}/mark-lost', [BookCopyController::class, 'markAsLost'])->name('mark-lost');
    Route::post('/{bookCopy}/mark-available', [BookCopyController::class, 'markAsAvailable'])->name('mark-available');
    Route::get('/{bookCopy}/qr-code', [BookCopyController::class, 'downloadQrCode'])->name('qr-code');
    Route::delete('/{bookCopy}', [BookCopyController::class, 'destroy'])->name('destroy');
});

// Book Category Routes
Route::prefix('categories')->name('categories.')->middleware(['auth'])->group(function () {
    // Public category browsing
    Route::get('/', [App\Http\Controllers\BookCategoryController::class, 'index'])->name('index');

    // Admin-only routes (must come before wildcard routes)
    Route::middleware(['admin'])->group(function () {
        Route::get('/create', [App\Http\Controllers\BookCategoryController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\BookCategoryController::class, 'store'])->name('store');
        Route::post('/reorder', [App\Http\Controllers\BookCategoryController::class, 'reorder'])->name('reorder');
        Route::get('/{category}/edit', [App\Http\Controllers\BookCategoryController::class, 'edit'])->name('edit');
        Route::patch('/{category}', [App\Http\Controllers\BookCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [App\Http\Controllers\BookCategoryController::class, 'destroy'])->name('destroy');
        Route::post('/{category}/toggle-active', [App\Http\Controllers\BookCategoryController::class, 'toggleActive'])->name('toggle-active');
    });

    // Wildcard routes (must come last)
    Route::get('/{category}', [App\Http\Controllers\BookCategoryController::class, 'show'])->name('show');
});

// Search Routes
Route::prefix('search')->name('search.')->middleware(['auth'])->group(function () {
    Route::get('/', [App\Http\Controllers\SearchController::class, 'index'])->name('index');
    Route::get('/advanced', [App\Http\Controllers\SearchController::class, 'advanced'])->name('advanced');
    Route::get('/autocomplete', [App\Http\Controllers\SearchController::class, 'autocomplete'])->name('autocomplete');
    Route::get('/popular', [App\Http\Controllers\SearchController::class, 'popular'])->name('popular');
});

// Loan Management Routes
Route::prefix('loans')->name('loans.')->middleware(['auth'])->group(function () {
    // Member can view their own loan history
    Route::get('/history', [App\Http\Controllers\LoanController::class, 'history'])->name('history');

    // Admin-only routes
    Route::middleware(['admin'])->group(function () {
        Route::get('/', [App\Http\Controllers\LoanController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\LoanController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\LoanController::class, 'store'])->name('store');
        Route::get('/overdue-report', [App\Http\Controllers\LoanController::class, 'overdueReport'])->name('overdue-report');
        Route::get('/popular-books', [App\Http\Controllers\LoanController::class, 'popularBooksReport'])->name('popular-books');
        Route::get('/{loan}', [App\Http\Controllers\LoanController::class, 'show'])->name('show');
        Route::get('/{loan}/return', [App\Http\Controllers\LoanController::class, 'returnForm'])->name('return-form');
        Route::post('/return', [App\Http\Controllers\LoanController::class, 'processReturn'])->name('process-return');
        Route::post('/{loan}/mark-lost', [App\Http\Controllers\LoanController::class, 'markAsLost'])->name('mark-lost');
        Route::get('/user/{user}/history', [App\Http\Controllers\LoanController::class, 'history'])->name('user-history');
    });
});

// Reservation Management Routes
Route::prefix('reservations')->name('reservations.')->middleware(['auth'])->group(function () {
    // Member routes
    Route::get('/', [App\Http\Controllers\ReservationController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\ReservationController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\ReservationController::class, 'store'])->name('store');
    Route::get('/history', [App\Http\Controllers\ReservationController::class, 'history'])->name('history');
    Route::get('/{reservation}', [App\Http\Controllers\ReservationController::class, 'show'])->name('show');
    Route::post('/{reservation}/cancel', [App\Http\Controllers\ReservationController::class, 'cancel'])->name('cancel');

    // Admin-only routes
    Route::middleware(['admin'])->group(function () {
        Route::get('/statistics', [App\Http\Controllers\ReservationController::class, 'statistics'])->name('statistics');
        Route::post('/{reservation}/mark-ready', [App\Http\Controllers\ReservationController::class, 'markAsReady'])->name('mark-ready');
        Route::post('/process-expired', [App\Http\Controllers\ReservationController::class, 'processExpired'])->name('process-expired');
    });
});

require __DIR__ . '/auth.php';
