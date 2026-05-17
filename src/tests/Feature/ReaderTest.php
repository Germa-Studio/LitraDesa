<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\Bookmark;
use App\Models\ReadingProgress;
use App\Models\Softbook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReaderTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $member;
    protected User $pendingMember;
    protected Softbook $softbook;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->pendingMember = User::factory()->create(['role' => 'member', 'status' => 'pending']);

        // Create softbook
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);
        $this->softbook = Softbook::factory()->create([
            'book_id' => $book->id,
            'uploaded_by' => $this->admin->id,
            'is_active' => true,
            'format' => 'pdf',
            'pages' => 100,
        ]);

        Storage::fake('private');
    }

    /** @test */
    public function active_member_can_open_reader(): void
    {
        $response = $this->actingAs($this->member)
            ->get(route('reader.read', $this->softbook));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Reader/Index')
            ->has('softbook')
            ->has('progress')
            ->has('bookmarks')
        );
    }

    /** @test */
    public function pending_member_cannot_open_reader(): void
    {
        $response = $this->actingAs($this->pendingMember)
            ->get(route('reader.read', $this->softbook));

        $response->assertForbidden();
    }

    /** @test */
    public function cannot_open_reader_for_inactive_softbook(): void
    {
        $this->softbook->update(['is_active' => false]);

        $response = $this->actingAs($this->member)
            ->get(route('reader.read', $this->softbook));

        $response->assertForbidden();
    }

    /** @test */
    public function opening_reader_creates_reading_progress(): void
    {
        $this->actingAs($this->member)
            ->get(route('reader.read', $this->softbook));

        $this->assertDatabaseHas('reading_progress', [
            'user_id' => $this->member->id,
            'softbook_id' => $this->softbook->id,
            'current_page' => 1,
        ]);
    }

    /** @test */
    public function can_get_softbook_content(): void
    {
        Storage::disk('private')->put($this->softbook->file_path, 'fake pdf content');

        $response = $this->actingAs($this->member)
            ->get(route('reader.content', $this->softbook));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /** @test */
    public function cannot_get_content_for_inactive_softbook(): void
    {
        $this->softbook->update(['is_active' => false]);

        $response = $this->actingAs($this->member)
            ->get(route('reader.content', $this->softbook));

        $response->assertForbidden();
    }

    /** @test */
    public function can_update_reading_progress(): void
    {
        $response = $this->actingAs($this->member)
            ->postJson(route('reader.update-progress', $this->softbook), [
                'current_page' => 50,
                'total_pages' => 100,
                'reading_time' => 300,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('reading_progress', [
            'user_id' => $this->member->id,
            'softbook_id' => $this->softbook->id,
            'current_page' => 50,
            'total_pages' => 100,
            'progress_percentage' => 50.00,
        ]);
    }

    /** @test */
    public function reading_progress_calculates_percentage_correctly(): void
    {
        $this->actingAs($this->member)
            ->postJson(route('reader.update-progress', $this->softbook), [
                'current_page' => 75,
                'total_pages' => 100,
            ]);

        $progress = ReadingProgress::where('user_id', $this->member->id)
            ->where('softbook_id', $this->softbook->id)
            ->first();

        $this->assertEquals(75.00, $progress->progress_percentage);
    }

    /** @test */
    public function can_get_reading_progress(): void
    {
        ReadingProgress::create([
            'user_id' => $this->member->id,
            'softbook_id' => $this->softbook->id,
            'current_page' => 25,
            'total_pages' => 100,
            'progress_percentage' => 25.00,
        ]);

        $response = $this->actingAs($this->member)
            ->getJson(route('reader.get-progress', $this->softbook));

        $response->assertOk();
        $response->assertJson([
            'progress' => [
                'current_page' => 25,
                'total_pages' => 100,
                'progress_percentage' => '25.00',
            ]
        ]);
    }

    /** @test */
    public function can_create_bookmark(): void
    {
        $response = $this->actingAs($this->member)
            ->postJson(route('reader.create-bookmark', $this->softbook), [
                'page_number' => 42,
                'title' => 'Important Chapter',
                'note' => 'Remember to review this',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $this->member->id,
            'softbook_id' => $this->softbook->id,
            'page_number' => 42,
            'title' => 'Important Chapter',
            'note' => 'Remember to review this',
        ]);
    }

    /** @test */
    public function can_create_bookmark_with_epub_position(): void
    {
        $epubSoftbook = Softbook::factory()->create([
            'book_id' => $this->softbook->book_id,
            'uploaded_by' => $this->admin->id,
            'format' => 'epub',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->member)
            ->postJson(route('reader.create-bookmark', $epubSoftbook), [
                'position' => 'epubcfi(/6/4[chap01ref]!/4/2/2[para05]/1:0)',
                'title' => 'EPUB Bookmark',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $this->member->id,
            'softbook_id' => $epubSoftbook->id,
            'position' => 'epubcfi(/6/4[chap01ref]!/4/2/2[para05]/1:0)',
        ]);
    }

    /** @test */
    public function can_update_bookmark(): void
    {
        $bookmark = Bookmark::create([
            'user_id' => $this->member->id,
            'softbook_id' => $this->softbook->id,
            'page_number' => 10,
            'title' => 'Old Title',
        ]);

        $response = $this->actingAs($this->member)
            ->patchJson(route('reader.update-bookmark', $bookmark), [
                'title' => 'New Title',
                'note' => 'Added note',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'title' => 'New Title',
            'note' => 'Added note',
        ]);
    }

    /** @test */
    public function cannot_update_other_users_bookmark(): void
    {
        $otherUser = User::factory()->create(['role' => 'member', 'status' => 'active']);
        
        $bookmark = Bookmark::create([
            'user_id' => $otherUser->id,
            'softbook_id' => $this->softbook->id,
            'page_number' => 10,
        ]);

        $response = $this->actingAs($this->member)
            ->patchJson(route('reader.update-bookmark', $bookmark), [
                'title' => 'Hacked',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function can_delete_bookmark(): void
    {
        $bookmark = Bookmark::create([
            'user_id' => $this->member->id,
            'softbook_id' => $this->softbook->id,
            'page_number' => 10,
        ]);

        $response = $this->actingAs($this->member)
            ->deleteJson(route('reader.delete-bookmark', $bookmark));

        $response->assertOk();

        $this->assertDatabaseMissing('bookmarks', [
            'id' => $bookmark->id,
        ]);
    }

    /** @test */
    public function cannot_delete_other_users_bookmark(): void
    {
        $otherUser = User::factory()->create(['role' => 'member', 'status' => 'active']);
        
        $bookmark = Bookmark::create([
            'user_id' => $otherUser->id,
            'softbook_id' => $this->softbook->id,
            'page_number' => 10,
        ]);

        $response = $this->actingAs($this->member)
            ->deleteJson(route('reader.delete-bookmark', $bookmark));

        $response->assertForbidden();
    }

    /** @test */
    public function can_get_all_bookmarks_for_softbook(): void
    {
        Bookmark::factory()->count(3)->create([
            'user_id' => $this->member->id,
            'softbook_id' => $this->softbook->id,
        ]);

        // Create bookmark for another user (should not be returned)
        $otherUser = User::factory()->create(['role' => 'member', 'status' => 'active']);
        Bookmark::factory()->create([
            'user_id' => $otherUser->id,
            'softbook_id' => $this->softbook->id,
        ]);

        $response = $this->actingAs($this->member)
            ->getJson(route('reader.get-bookmarks', $this->softbook));

        $response->assertOk();
        $response->assertJsonCount(3, 'bookmarks');
    }

    /** @test */
    public function can_get_reading_statistics(): void
    {
        // Create some reading progress
        ReadingProgress::factory()->count(3)->create([
            'user_id' => $this->member->id,
            'progress_percentage' => 100,
        ]);

        ReadingProgress::factory()->count(2)->create([
            'user_id' => $this->member->id,
            'progress_percentage' => 50,
        ]);

        Bookmark::factory()->count(5)->create([
            'user_id' => $this->member->id,
        ]);

        $response = $this->actingAs($this->member)
            ->getJson(route('reader.statistics'));

        $response->assertOk();
        $response->assertJson([
            'total_books_read' => 5,
            'completed_books' => 3,
            'in_progress_books' => 2,
            'total_bookmarks' => 5,
        ]);
    }

    /** @test */
    public function can_get_recently_read_books(): void
    {
        $softbooks = Softbook::factory()->count(5)->create([
            'book_id' => $this->softbook->book_id,
            'uploaded_by' => $this->admin->id,
            'is_active' => true,
        ]);

        foreach ($softbooks as $index => $softbook) {
            ReadingProgress::create([
                'user_id' => $this->member->id,
                'softbook_id' => $softbook->id,
                'current_page' => 1,
                'last_read_at' => now()->subDays($index),
            ]);
        }

        $response = $this->actingAs($this->member)
            ->getJson(route('reader.recently-read'));

        $response->assertOk();
        $response->assertJsonCount(5, 'recent_books');
        
        // Check that books are ordered by last_read_at (most recent first)
        $books = $response->json('recent_books');
        $this->assertEquals($softbooks[0]->id, $books[0]['softbook_id']);
    }

    /** @test */
    public function reading_progress_tracks_total_reading_time(): void
    {
        $progress = ReadingProgress::create([
            'user_id' => $this->member->id,
            'softbook_id' => $this->softbook->id,
            'current_page' => 1,
            'total_reading_time' => 0,
        ]);

        // Simulate reading for 5 minutes (300 seconds)
        $this->actingAs($this->member)
            ->postJson(route('reader.update-progress', $this->softbook), [
                'current_page' => 10,
                'total_pages' => 100,
                'reading_time' => 300,
            ]);

        $progress->refresh();
        $this->assertEquals(300, $progress->total_reading_time);
    }

    /** @test */
    public function bookmark_validation_requires_valid_data(): void
    {
        $response = $this->actingAs($this->member)
            ->postJson(route('reader.create-bookmark', $this->softbook), [
                'title' => str_repeat('a', 300), // Too long
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');
    }

    /** @test */
    public function progress_validation_requires_valid_page_numbers(): void
    {
        $response = $this->actingAs($this->member)
            ->postJson(route('reader.update-progress', $this->softbook), [
                'current_page' => 0, // Invalid
                'total_pages' => 100,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('current_page');
    }

    /** @test */
    public function reading_progress_updates_last_read_at(): void
    {
        $progress = ReadingProgress::create([
            'user_id' => $this->member->id,
            'softbook_id' => $this->softbook->id,
            'current_page' => 1,
            'last_read_at' => now()->subDay(),
        ]);

        $oldTimestamp = $progress->last_read_at;

        $this->actingAs($this->member)
            ->postJson(route('reader.update-progress', $this->softbook), [
                'current_page' => 10,
                'total_pages' => 100,
            ]);

        $progress->refresh();
        $this->assertTrue($progress->last_read_at->isAfter($oldTimestamp));
    }

    /** @test */
    public function bookmarks_are_ordered_by_page_number(): void
    {
        Bookmark::create([
            'user_id' => $this->member->id,
            'softbook_id' => $this->softbook->id,
            'page_number' => 50,
        ]);

        Bookmark::create([
            'user_id' => $this->member->id,
            'softbook_id' => $this->softbook->id,
            'page_number' => 10,
        ]);

        Bookmark::create([
            'user_id' => $this->member->id,
            'softbook_id' => $this->softbook->id,
            'page_number' => 30,
        ]);

        $response = $this->actingAs($this->member)
            ->getJson(route('reader.get-bookmarks', $this->softbook));

        $bookmarks = $response->json('bookmarks');
        $this->assertEquals(10, $bookmarks[0]['page_number']);
        $this->assertEquals(30, $bookmarks[1]['page_number']);
        $this->assertEquals(50, $bookmarks[2]['page_number']);
    }
}
