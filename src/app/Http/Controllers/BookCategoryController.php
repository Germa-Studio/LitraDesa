<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BookCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BookCategoryController extends Controller
{
    /**
     * Display a listing of book categories.
     */
    public function index(Request $request): Response
    {
        $query = BookCategory::withCount('books')->orderBy('name');

        // Search functionality
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $categories = $query->paginate(10)->withQueryString();

        return Inertia::render('BookCategories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): Response
    {
        return Inertia::render('BookCategories/Create');
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:book_categories,name',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active');

        BookCategory::create($validated);

        return redirect()
            ->route('book-categories.index')
            ->with('success', 'Kategori buku berhasil dibuat.');
    }

    /**
     * Display the specified category.
     */
    public function show(BookCategory $bookCategory): Response
    {
        return Inertia::render('BookCategories/Show', [
            'category' => $bookCategory,
        ]);
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(BookCategory $bookCategory): Response
    {
        return Inertia::render('BookCategories/Edit', [
            'category' => $bookCategory,
        ]);
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, BookCategory $bookCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:book_categories,name,' . $bookCategory->id,
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active');

        $bookCategory->update($validated);

        return redirect()
            ->route('book-categories.index')
            ->with('success', 'Kategori buku berhasil diperbarui.');
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(BookCategory $bookCategory): RedirectResponse
    {
        // Check if category has books
        if ($bookCategory->books()->count() > 0) {
            return back()->with('error', 'Kategori ini tidak dapat dihapus karena masih memiliki buku.');
        }

        $bookCategory->delete();

        return redirect()
            ->route('book-categories.index')
            ->with('success', 'Kategori buku berhasil dihapus.');
    }
}
