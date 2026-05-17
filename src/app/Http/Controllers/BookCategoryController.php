<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Models\BookCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookCategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index(Request $request): Response
    {
        $query = BookCategory::with(['parent', 'children'])
            ->withCount('books');

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Filter by parent (root categories or children of specific parent)
        if ($request->has('parent_id')) {
            if ($request->parent_id === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'ILIKE', "%{$search}%");
        }

        // Sort
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $categories = $query->paginate(20)->withQueryString();

        // Get root categories for tree view
        $rootCategories = BookCategory::with(['children.children'])
            ->whereNull('parent_id')
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'rootCategories' => $rootCategories,
            'filters' => $request->only(['search', 'active', 'parent_id', 'sort_by', 'sort_order']),
        ]);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(Request $request): Response
    {
        $parentCategories = BookCategory::with('parent')
            ->active()
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->full_path,
                ];
            });

        return Inertia::render('Categories/Create', [
            'parentCategories' => $parentCategories,
            'parentId' => $request->get('parent_id'),
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        $category = BookCategory::create($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil ditambahkan.");
    }

    /**
     * Display the specified category.
     */
    public function show(BookCategory $category): Response
    {
        $category->load([
            'parent',
            'children' => function ($query) {
                $query->withCount('books')->orderBy('sort_order')->orderBy('name');
            },
            'books' => function ($query) {
                $query->with('category')->latest()->take(10);
            },
        ]);

        $category->loadCount('books');

        return Inertia::render('Categories/Show', [
            'category' => $category,
            'ancestors' => $category->ancestors(),
            'totalBooksCount' => $category->total_books_count,
        ]);
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(BookCategory $category): Response
    {
        $category->load('parent');

        // Get all categories except the current one and its descendants
        $descendantIds = $this->getDescendantIds($category);
        $excludeIds = array_merge([$category->id], $descendantIds);

        $parentCategories = BookCategory::whereNotIn('id', $excludeIds)
            ->active()
            ->orderBy('name')
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->full_path,
                ];
            });

        return Inertia::render('Categories/Edit', [
            'category' => $category,
            'parentCategories' => $parentCategories,
        ]);
    }

    /**
     * Update the specified category in storage.
     */
    public function update(CategoryUpdateRequest $request, BookCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil diperbarui.");
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(BookCategory $category): RedirectResponse
    {
        // Check if category has books
        if ($category->books()->exists()) {
            return redirect()
                ->route('categories.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih memiliki buku.');
        }

        // Check if category has children
        if ($category->children()->exists()) {
            return redirect()
                ->route('categories.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih memiliki sub-kategori.');
        }

        $name = $category->name;
        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', "Kategori '{$name}' berhasil dihapus.");
    }

    /**
     * Get all descendant IDs of a category.
     */
    private function getDescendantIds(BookCategory $category): array
    {
        $ids = [];
        
        foreach ($category->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }
        
        return $ids;
    }

    /**
     * Reorder categories.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:book_categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->categories as $categoryData) {
            BookCategory::where('id', $categoryData['id'])
                ->update(['sort_order' => $categoryData['sort_order']]);
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Urutan kategori berhasil diperbarui.');
    }

    /**
     * Toggle category active status.
     */
    public function toggleActive(BookCategory $category): RedirectResponse
    {
        $category->update(['is_active' => !$category->is_active]);

        $status = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil {$status}.");
    }
}
