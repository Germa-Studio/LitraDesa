<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
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
    return Inertia::render('Dashboard');
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

require __DIR__.'/auth.php';
