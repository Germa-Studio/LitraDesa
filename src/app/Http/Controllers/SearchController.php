<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    /**
     * Display the search page with results.
     */
    public function index(Request $request): Response
    {
        $query = Book::with(['category'])
            ->withCount(['copies', 'availableCopies']);

        // Full-text search using PostgreSQL
        if ($request->filled('q')) {
            $searchTerm = $request->q;
            
            // Use PostgreSQL full-text search with Indonesian language support
            $query->whereRaw(
                "to_tsvector('indonesian', coalesce(title, '') || ' ' || coalesce(author, '') || ' ' || coalesce(description, '')) @@ plainto_tsquery('indonesian', ?)",
                [$searchTerm]
            );
            
            // Add relevance ranking
            $query->selectRaw(
                "books.*, ts_rank(to_tsvector('indonesian', coalesce(title, '') || ' ' || coalesce(author, '') || ' ' || coalesce(description, '')), plainto_tsquery('indonesian', ?)) as relevance",
                [$searchTerm]
            );
        }

        // Filter by category (including descendants)
        if ($request->filled('category_id')) {
            $category = BookCategory::find($request->category_id);
            if ($category) {
                $categoryIds = $this->getCategoryWithDescendants($category);
                $query->whereIn('book_category_id', $categoryIds);
            }
        }

        // Filter by availability
        if ($request->has('available')) {
            if ($request->boolean('available')) {
                $query->where('is_available', true)
                    ->where('available_copies', '>', 0);
            }
        }

        // Filter by publication year
        if ($request->filled('year_from')) {
            $query->where('publication_year', '>=', $request->year_from);
        }
        if ($request->filled('year_to')) {
            $query->where('publication_year', '<=', $request->year_to);
        }

        // Filter by author
        if ($request->filled('author')) {
            $query->where('author', 'ILIKE', '%' . $request->author . '%');
        }

        // Filter by language
        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        // Sort results
        $sortBy = $request->get('sort_by', 'relevance');
        $sortOrder = $request->get('sort_order', 'desc');

        switch ($sortBy) {
            case 'relevance':
                if ($request->filled('q')) {
                    $query->orderBy('relevance', 'desc');
                } else {
                    $query->latest();
                }
                break;
            case 'title':
                $query->orderBy('title', $sortOrder);
                break;
            case 'author':
                $query->orderBy('author', $sortOrder);
                break;
            case 'newest':
                $query->orderBy('publication_year', 'desc')
                    ->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('publication_year', 'asc')
                    ->orderBy('created_at', 'asc');
                break;
            case 'most_borrowed':
                // This would require a loans table, for now use available_copies as proxy
                $query->orderByRaw('(total_copies - available_copies) DESC');
                break;
            default:
                $query->latest();
        }

        $books = $query->paginate(20)->withQueryString();

        // Get categories for filter
        $categories = BookCategory::with('children')
            ->whereNull('parent_id')
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Get available languages
        $languages = Book::select('language')
            ->distinct()
            ->orderBy('language')
            ->pluck('language');

        // Get year range
        $yearRange = Book::selectRaw('MIN(publication_year) as min_year, MAX(publication_year) as max_year')
            ->first();

        return Inertia::render('Search/Index', [
            'books' => $books,
            'categories' => $categories,
            'languages' => $languages,
            'yearRange' => $yearRange,
            'filters' => $request->only([
                'q',
                'category_id',
                'available',
                'year_from',
                'year_to',
                'author',
                'language',
                'sort_by',
                'sort_order',
            ]),
        ]);
    }

    /**
     * Autocomplete search for books.
     */
    public function autocomplete(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'limit' => 'integer|min:1|max:20',
        ]);

        $searchTerm = $request->q;
        $limit = $request->get('limit', 10);

        // Search in title and author
        $books = Book::select('id', 'title', 'author', 'cover_image')
            ->where(function ($query) use ($searchTerm) {
                $query->where('title', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('author', 'ILIKE', "%{$searchTerm}%");
            })
            ->where('is_available', true)
            ->limit($limit)
            ->get()
            ->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'cover_image' => $book->cover_image,
                    'label' => "{$book->title} - {$book->author}",
                ];
            });

        return response()->json([
            'suggestions' => $books,
        ]);
    }

    /**
     * Get popular search terms.
     */
    public function popular()
    {
        // This would typically come from a search_logs table
        // For now, return most borrowed books as popular searches
        $popularBooks = Book::select('title', 'author')
            ->orderByRaw('(total_copies - available_copies) DESC')
            ->limit(10)
            ->get()
            ->map(function ($book) {
                return [
                    'term' => $book->title,
                    'type' => 'book',
                ];
            });

        return response()->json([
            'popular' => $popularBooks,
        ]);
    }

    /**
     * Get category with all its descendants.
     */
    private function getCategoryWithDescendants(BookCategory $category): array
    {
        $ids = [$category->id];
        
        foreach ($category->children as $child) {
            $ids = array_merge($ids, $this->getCategoryWithDescendants($child));
        }
        
        return $ids;
    }

    /**
     * Advanced search with multiple filters.
     */
    public function advanced(Request $request): Response
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'author' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:20',
            'category_id' => 'nullable|exists:book_categories,id',
            'keywords' => 'nullable|string|max:500',
            'year_from' => 'nullable|integer|min:1000|max:' . date('Y'),
            'year_to' => 'nullable|integer|min:1000|max:' . date('Y'),
            'language' => 'nullable|string|max:50',
            'available_only' => 'boolean',
        ]);

        $query = Book::with(['category'])
            ->withCount(['copies', 'availableCopies']);

        // Title search
        if ($request->filled('title')) {
            $query->where('title', 'ILIKE', '%' . $request->title . '%');
        }

        // Author search
        if ($request->filled('author')) {
            $query->where('author', 'ILIKE', '%' . $request->author . '%');
        }

        // ISBN search
        if ($request->filled('isbn')) {
            $query->where('isbn', 'ILIKE', '%' . $request->isbn . '%');
        }

        // Category filter
        if ($request->filled('category_id')) {
            $category = BookCategory::find($request->category_id);
            if ($category) {
                $categoryIds = $this->getCategoryWithDescendants($category);
                $query->whereIn('book_category_id', $categoryIds);
            }
        }

        // Keywords search (full-text)
        if ($request->filled('keywords')) {
            $keywords = $request->keywords;
            $query->whereRaw(
                "to_tsvector('indonesian', coalesce(title, '') || ' ' || coalesce(author, '') || ' ' || coalesce(description, '')) @@ plainto_tsquery('indonesian', ?)",
                [$keywords]
            );
        }

        // Year range
        if ($request->filled('year_from')) {
            $query->where('publication_year', '>=', $request->year_from);
        }
        if ($request->filled('year_to')) {
            $query->where('publication_year', '<=', $request->year_to);
        }

        // Language filter
        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        // Availability filter
        if ($request->boolean('available_only')) {
            $query->where('is_available', true)
                ->where('available_copies', '>', 0);
        }

        $books = $query->latest()->paginate(20)->withQueryString();

        $categories = BookCategory::with('children')
            ->whereNull('parent_id')
            ->active()
            ->orderBy('name')
            ->get();

        $languages = Book::select('language')
            ->distinct()
            ->orderBy('language')
            ->pluck('language');

        return Inertia::render('Search/Advanced', [
            'books' => $books,
            'categories' => $categories,
            'languages' => $languages,
            'filters' => $request->only([
                'title',
                'author',
                'isbn',
                'category_id',
                'keywords',
                'year_from',
                'year_to',
                'language',
                'available_only',
            ]),
        ]);
    }
}
