<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->member = User::factory()->create([
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    public function test_authenticated_user_can_access_search_page(): void
    {
        $response = $this->actingAs($this->member)
            ->get(route('search.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Search/Index')
            ->has('books')
            ->has('categories')
            ->has('languages')
        );
    }

    public function test_guest_cannot_access_search_page(): void
    {
        $response = $this->get(route('search.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_can_search_books_by_title(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->create([
            'title' => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
            'book_category_id' => $category->id,
        ]);
        Book::factory()->create([
            'title' => 'To Kill a Mockingbird',
            'author' => 'Harper Lee',
            'book_category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('search.index', ['q' => 'Gatsby']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('books.data', 1)
            ->where('books.data.0.title', 'The Great Gatsby')
        );
    }

    public function test_can_search_books_by_author(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->create([
            'title' => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
            'book_category_id' => $category->id,
        ]);
        Book::factory()->create([
            'title' => 'To Kill a Mockingbird',
            'author' => 'Harper Lee',
            'book_category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('search.index', ['q' => 'Fitzgerald']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('books.data', 1)
            ->where('books.data.0.author', 'F. Scott Fitzgerald')
        );
    }

    public function test_can_filter_books_by_category(): void
    {
        $fiction = BookCategory::factory()->create(['name' => 'Fiction']);
        $nonFiction = BookCategory::factory()->create(['name' => 'Non-Fiction']);

        Book::factory()->create(['book_category_id' => $fiction->id]);
        Book::factory()->create(['book_category_id' => $nonFiction->id]);

        $response = $this->actingAs($this->member)
            ->get(route('search.index', ['category_id' => $fiction->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('books.data', 1)
            ->where('books.data.0.category.id', $fiction->id)
        );
    }

    public function test_can_filter_books_by_availability(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->create([
            'book_category_id' => $category->id,
            'is_available' => true,
            'available_copies' => 1,
        ]);
        Book::factory()->create([
            'book_category_id' => $category->id,
            'is_available' => false,
            'available_copies' => 0,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('search.index', ['available' => '1']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('books.data', 1)
            ->where('books.data.0.is_available', true)
        );
    }

    public function test_can_filter_books_by_publication_year_range(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->create([
            'book_category_id' => $category->id,
            'publication_year' => 2020,
        ]);
        Book::factory()->create([
            'book_category_id' => $category->id,
            'publication_year' => 2015,
        ]);
        Book::factory()->create([
            'book_category_id' => $category->id,
            'publication_year' => 2010,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('search.index', [
                'year_from' => 2015,
                'year_to' => 2020,
            ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('books.data', 2)
        );
    }

    public function test_can_filter_books_by_language(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->create([
            'book_category_id' => $category->id,
            'language' => 'id',
        ]);
        Book::factory()->create([
            'book_category_id' => $category->id,
            'language' => 'en',
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('search.index', ['language' => 'id']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('books.data', 1)
            ->where('books.data.0.language', 'id')
        );
    }

    public function test_can_sort_books_by_title(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->create([
            'title' => 'Zebra Book',
            'book_category_id' => $category->id,
        ]);
        Book::factory()->create([
            'title' => 'Alpha Book',
            'book_category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('search.index', [
                'sort_by' => 'title',
                'sort_order' => 'asc',
            ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('books.data.0.title', 'Alpha Book')
            ->where('books.data.1.title', 'Zebra Book')
        );
    }

    public function test_can_sort_books_by_newest(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->create([
            'book_category_id' => $category->id,
            'publication_year' => 2020,
        ]);
        Book::factory()->create([
            'book_category_id' => $category->id,
            'publication_year' => 2023,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('search.index', ['sort_by' => 'newest']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('books.data.0.publication_year', 2023)
        );
    }

    public function test_autocomplete_returns_suggestions(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->create([
            'title' => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
            'book_category_id' => $category->id,
            'is_available' => true,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('search.autocomplete', ['q' => 'Gatsby']));

        $response->assertOk();
        $response->assertJson([
            'suggestions' => [
                [
                    'title' => 'The Great Gatsby',
                    'author' => 'F. Scott Fitzgerald',
                ],
            ],
        ]);
    }

    public function test_autocomplete_requires_minimum_2_characters(): void
    {
        $response = $this->actingAs($this->member)
            ->get(route('search.autocomplete', ['q' => 'a']));

        $response->assertStatus(422);
    }

    public function test_autocomplete_only_returns_available_books(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->create([
            'title' => 'Available Book',
            'book_category_id' => $category->id,
            'is_available' => true,
        ]);
        Book::factory()->create([
            'title' => 'Unavailable Book',
            'book_category_id' => $category->id,
            'is_available' => false,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('search.autocomplete', ['q' => 'Book']));

        $response->assertOk();
        $response->assertJsonCount(1, 'suggestions');
    }

    public function test_advanced_search_page_is_accessible(): void
    {
        $response = $this->actingAs($this->member)
            ->get(route('search.advanced'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Search/Advanced')
            ->has('books')
            ->has('categories')
            ->has('languages')
        );
    }

    public function test_advanced_search_with_multiple_filters(): void
    {
        $category = BookCategory::factory()->create(['name' => 'Fiction']);
        Book::factory()->create([
            'title' => 'Test Book',
            'author' => 'John Doe',
            'isbn' => '1234567890',
            'book_category_id' => $category->id,
            'publication_year' => 2020,
            'language' => 'id',
            'is_available' => true,
        ]);
        Book::factory()->create([
            'title' => 'Other Book',
            'author' => 'Jane Smith',
            'book_category_id' => $category->id,
            'publication_year' => 2015,
            'language' => 'en',
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('search.advanced', [
                'title' => 'Test',
                'author' => 'John',
                'category_id' => $category->id,
                'year_from' => 2019,
                'year_to' => 2021,
                'language' => 'id',
                'available_only' => true,
            ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('books.data', 1)
            ->where('books.data.0.title', 'Test Book')
        );
    }

    public function test_search_with_category_includes_descendant_categories(): void
    {
        $parent = BookCategory::factory()->create(['name' => 'Fiction']);
        $child = BookCategory::factory()->create([
            'name' => 'Science Fiction',
            'parent_id' => $parent->id,
        ]);

        Book::factory()->create(['book_category_id' => $parent->id]);
        Book::factory()->create(['book_category_id' => $child->id]);

        $response = $this->actingAs($this->member)
            ->get(route('search.index', ['category_id' => $parent->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('books.data', 2)
        );
    }

    public function test_search_returns_empty_results_for_no_matches(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->create([
            'title' => 'The Great Gatsby',
            'book_category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('search.index', ['q' => 'NonexistentBook']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('books.data', 0)
        );
    }

    public function test_search_is_case_insensitive(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->create([
            'title' => 'The Great Gatsby',
            'book_category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('search.index', ['q' => 'GATSBY']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('books.data', 1)
        );
    }

    public function test_popular_search_endpoint_returns_data(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->create([
            'book_category_id' => $category->id,
            'total_copies' => 5,
            'available_copies' => 1,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('search.popular'));

        $response->assertOk();
        $response->assertJsonStructure([
            'popular' => [
                '*' => ['term', 'type'],
            ],
        ]);
    }
}
