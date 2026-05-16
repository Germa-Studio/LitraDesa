<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $member;
    protected BookCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create regular member
        $this->member = User::factory()->create([
            'role' => 'member',
            'status' => 'active',
        ]);

        // Create book category
        $this->category = BookCategory::create([
            'name' => 'Fiksi',
            'slug' => 'fiksi',
            'description' => 'Buku fiksi',
        ]);

        Storage::fake('public');
    }

    /** @test */
    public function admin_can_view_book_catalog(): void
    {
        $books = Book::factory()->count(3)->create([
            'book_category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('books.index'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Books/Index')
            ->has('books.data', 3)
        );
    }

    /** @test */
    public function member_can_view_book_catalog(): void
    {
        Book::factory()->count(2)->create([
            'book_category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('books.index'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Books/Index')
            ->has('books.data', 2)
        );
    }

    /** @test */
    public function guest_cannot_view_book_catalog(): void
    {
        $response = $this->get(route('books.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_can_create_book(): void
    {
        $bookData = [
            'title' => 'Laskar Pelangi',
            'author' => 'Andrea Hirata',
            'isbn' => '9789793062792',
            'book_category_id' => $this->category->id,
            'description' => 'Novel tentang perjuangan anak-anak di Belitung',
            'publisher' => 'Bentang Pustaka',
            'publication_year' => 2005,
            'language' => 'id',
            'total_copies' => 3,
            'location' => 'Rak A-1',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('books.store'), $bookData);

        $response->assertRedirect();
        $this->assertDatabaseHas('books', [
            'title' => 'Laskar Pelangi',
            'author' => 'Andrea Hirata',
            'isbn' => '9789793062792',
            'total_copies' => 3,
            'available_copies' => 3,
        ]);

        $book = Book::where('title', 'Laskar Pelangi')->first();
        $this->assertNotNull($book->qr_code);
        $this->assertTrue(str_starts_with($book->qr_code, 'BK-'));
    }

    /** @test */
    public function admin_can_create_book_with_cover_image(): void
    {
        $coverImage = UploadedFile::fake()->image('cover.jpg', 600, 800);

        $bookData = [
            'title' => 'Test Book',
            'author' => 'Test Author',
            'book_category_id' => $this->category->id,
            'total_copies' => 1,
            'cover_image' => $coverImage,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('books.store'), $bookData);

        $response->assertRedirect();
        
        $book = Book::where('title', 'Test Book')->first();
        $this->assertNotNull($book->cover_image);
        Storage::disk('public')->assertExists($book->cover_image);
    }

    /** @test */
    public function member_cannot_create_book(): void
    {
        $bookData = [
            'title' => 'Test Book',
            'author' => 'Test Author',
            'book_category_id' => $this->category->id,
            'total_copies' => 1,
        ];

        $response = $this->actingAs($this->member)
            ->post(route('books.store'), $bookData);

        $response->assertForbidden();
        $this->assertDatabaseMissing('books', ['title' => 'Test Book']);
    }

    /** @test */
    public function admin_can_update_book(): void
    {
        $book = Book::factory()->create([
            'book_category_id' => $this->category->id,
            'title' => 'Original Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'author' => $book->author,
            'book_category_id' => $this->category->id,
            'total_copies' => $book->total_copies,
        ];

        $response = $this->actingAs($this->admin)
            ->patch(route('books.update', $book), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function admin_can_delete_book(): void
    {
        $book = Book::factory()->create([
            'book_category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect();
        $this->assertSoftDeleted('books', ['id' => $book->id]);
    }

    /** @test */
    public function can_search_books_by_title(): void
    {
        Book::factory()->create([
            'book_category_id' => $this->category->id,
            'title' => 'Laskar Pelangi',
            'author' => 'Andrea Hirata',
        ]);

        Book::factory()->create([
            'book_category_id' => $this->category->id,
            'title' => 'Bumi Manusia',
            'author' => 'Pramoedya Ananta Toer',
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('books.index', ['search' => 'Laskar']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Books/Index')
            ->has('books.data', 1)
            ->where('books.data.0.title', 'Laskar Pelangi')
        );
    }

    /** @test */
    public function can_search_books_by_author(): void
    {
        Book::factory()->create([
            'book_category_id' => $this->category->id,
            'title' => 'Laskar Pelangi',
            'author' => 'Andrea Hirata',
        ]);

        Book::factory()->create([
            'book_category_id' => $this->category->id,
            'title' => 'Bumi Manusia',
            'author' => 'Pramoedya Ananta Toer',
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('books.index', ['search' => 'Pramoedya']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Books/Index')
            ->has('books.data', 1)
            ->where('books.data.0.author', 'Pramoedya Ananta Toer')
        );
    }

    /** @test */
    public function can_filter_books_by_category(): void
    {
        $category2 = BookCategory::create([
            'name' => 'Non-Fiksi',
            'slug' => 'non-fiksi',
        ]);

        Book::factory()->create([
            'book_category_id' => $this->category->id,
            'title' => 'Fiction Book',
        ]);

        Book::factory()->create([
            'book_category_id' => $category2->id,
            'title' => 'Non-Fiction Book',
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('books.index', ['category' => $category2->id]));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Books/Index')
            ->has('books.data', 1)
            ->where('books.data.0.title', 'Non-Fiction Book')
        );
    }

    /** @test */
    public function can_filter_available_books_only(): void
    {
        Book::factory()->create([
            'book_category_id' => $this->category->id,
            'title' => 'Available Book',
            'total_copies' => 3,
            'available_copies' => 2,
            'is_available' => true,
        ]);

        Book::factory()->create([
            'book_category_id' => $this->category->id,
            'title' => 'Unavailable Book',
            'total_copies' => 1,
            'available_copies' => 0,
            'is_available' => false,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('books.index', ['available' => 'true']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Books/Index')
            ->has('books.data', 1)
            ->where('books.data.0.title', 'Available Book')
        );
    }

    /** @test */
    public function book_generates_unique_qr_code_on_creation(): void
    {
        $book1 = Book::factory()->create([
            'book_category_id' => $this->category->id,
        ]);

        $book2 = Book::factory()->create([
            'book_category_id' => $this->category->id,
        ]);

        $this->assertNotNull($book1->qr_code);
        $this->assertNotNull($book2->qr_code);
        $this->assertNotEquals($book1->qr_code, $book2->qr_code);
        $this->assertTrue(str_starts_with($book1->qr_code, 'BK-'));
        $this->assertTrue(str_starts_with($book2->qr_code, 'BK-'));
    }

    /** @test */
    public function book_available_copies_equals_total_copies_on_creation(): void
    {
        $book = Book::factory()->create([
            'book_category_id' => $this->category->id,
            'total_copies' => 5,
        ]);

        $this->assertEquals(5, $book->available_copies);
        $this->assertTrue($book->is_available);
    }

    /** @test */
    public function admin_can_download_book_qr_code(): void
    {
        $book = Book::factory()->create([
            'book_category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('books.qr-code', $book));

        $response->assertOk();
        $response->assertDownload($book->qr_code . '.png');
    }

    /** @test */
    public function book_validation_requires_title_and_author(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('books.store'), [
                'book_category_id' => $this->category->id,
                'total_copies' => 1,
            ]);

        $response->assertSessionHasErrors(['title', 'author']);
    }

    /** @test */
    public function book_validation_requires_valid_category(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('books.store'), [
                'title' => 'Test Book',
                'author' => 'Test Author',
                'book_category_id' => 99999,
                'total_copies' => 1,
            ]);

        $response->assertSessionHasErrors(['book_category_id']);
    }

    /** @test */
    public function book_validation_requires_positive_total_copies(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('books.store'), [
                'title' => 'Test Book',
                'author' => 'Test Author',
                'book_category_id' => $this->category->id,
                'total_copies' => 0,
            ]);

        $response->assertSessionHasErrors(['total_copies']);
    }

    /** @test */
    public function book_isbn_must_be_unique(): void
    {
        Book::factory()->create([
            'book_category_id' => $this->category->id,
            'isbn' => '9789793062792',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('books.store'), [
                'title' => 'Another Book',
                'author' => 'Another Author',
                'isbn' => '9789793062792',
                'book_category_id' => $this->category->id,
                'total_copies' => 1,
            ]);

        $response->assertSessionHasErrors(['isbn']);
    }

    /** @test */
    public function search_returns_results_in_less_than_one_second(): void
    {
        // Create 100 books for performance testing
        Book::factory()->count(100)->create([
            'book_category_id' => $this->category->id,
        ]);

        $startTime = microtime(true);

        $response = $this->actingAs($this->member)
            ->get(route('books.index', ['search' => 'test']));

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertOk();
        $this->assertLessThan(1.0, $executionTime, 'Search took longer than 1 second');
    }
}
