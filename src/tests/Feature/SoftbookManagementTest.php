<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\Softbook;
use App\Models\SoftbookDownload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SoftbookManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $librarian;
    protected User $member;
    protected User $pendingMember;
    protected Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->librarian = User::factory()->create(['role' => 'librarian', 'status' => 'active']);
        $this->member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->pendingMember = User::factory()->create(['role' => 'member', 'status' => 'pending']);

        // Create book
        $category = BookCategory::factory()->create();
        $this->book = Book::factory()->create(['category_id' => $category->id]);

        // Setup storage
        Storage::fake('private');
    }

    /** @test */
    public function admin_can_view_softbooks_index(): void
    {
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('softbooks.index'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Softbooks/Index')
            ->has('softbooks.data', 1)
            ->has('stats')
        );
    }

    /** @test */
    public function member_can_view_softbooks_index(): void
    {
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('softbooks.index'));

        $response->assertOk();
    }

    /** @test */
    public function admin_can_upload_softbook(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->admin)
            ->post(route('softbooks.store'), [
                'book_id' => $this->book->id,
                'file' => $file,
                'format' => 'pdf',
                'pages' => 100,
                'description' => 'Test softbook',
                'download_limit' => 5,
                'is_encrypted' => false,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('softbooks', [
            'book_id' => $this->book->id,
            'format' => 'pdf',
            'pages' => 100,
            'download_limit' => 5,
            'uploaded_by' => $this->admin->id,
        ]);
    }

    /** @test */
    public function librarian_can_upload_softbook(): void
    {
        $file = UploadedFile::fake()->create('test.epub', 1024, 'application/epub+zip');

        $response = $this->actingAs($this->librarian)
            ->post(route('softbooks.store'), [
                'book_id' => $this->book->id,
                'file' => $file,
                'format' => 'epub',
                'download_limit' => 5,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('softbooks', [
            'book_id' => $this->book->id,
            'format' => 'epub',
            'uploaded_by' => $this->librarian->id,
        ]);
    }

    /** @test */
    public function member_cannot_upload_softbook(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->member)
            ->post(route('softbooks.store'), [
                'book_id' => $this->book->id,
                'file' => $file,
                'format' => 'pdf',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function softbook_upload_validates_file_format(): void
    {
        $file = UploadedFile::fake()->create('test.txt', 1024, 'text/plain');

        $response = $this->actingAs($this->admin)
            ->post(route('softbooks.store'), [
                'book_id' => $this->book->id,
                'file' => $file,
                'format' => 'pdf',
            ]);

        $response->assertSessionHasErrors('file');
    }

    /** @test */
    public function softbook_upload_validates_file_size(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 51201, 'application/pdf'); // 50MB + 1KB

        $response = $this->actingAs($this->admin)
            ->post(route('softbooks.store'), [
                'book_id' => $this->book->id,
                'file' => $file,
                'format' => 'pdf',
            ]);

        $response->assertSessionHasErrors('file');
    }

    /** @test */
    public function anyone_can_view_softbook_details(): void
    {
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('softbooks.show', $softbook));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Softbooks/Show')
            ->has('softbook')
        );
    }

    /** @test */
    public function active_member_can_generate_download_token(): void
    {
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->member)
            ->post(route('softbooks.generate-token', $softbook));

        $response->assertRedirect();
        $this->assertDatabaseHas('softbook_downloads', [
            'softbook_id' => $softbook->id,
            'user_id' => $this->member->id,
            'is_completed' => false,
        ]);
    }

    /** @test */
    public function pending_member_cannot_generate_download_token(): void
    {
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->pendingMember)
            ->post(route('softbooks.generate-token', $softbook));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function member_cannot_download_inactive_softbook(): void
    {
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->member)
            ->post(route('softbooks.generate-token', $softbook));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function download_limit_is_enforced(): void
    {
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
            'download_limit' => 2,
            'is_active' => true,
        ]);

        // Create 2 completed downloads
        SoftbookDownload::factory()->count(2)->create([
            'softbook_id' => $softbook->id,
            'user_id' => $this->member->id,
            'is_completed' => true,
        ]);

        $response = $this->actingAs($this->member)
            ->post(route('softbooks.generate-token', $softbook));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function member_can_download_with_valid_token(): void
    {
        Storage::fake('private');
        
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
            'file_path' => 'softbooks/test.pdf',
            'is_active' => true,
        ]);

        // Create fake file
        Storage::disk('private')->put('softbooks/test.pdf', 'fake pdf content');

        $download = SoftbookDownload::factory()->create([
            'softbook_id' => $softbook->id,
            'user_id' => $this->member->id,
            'is_completed' => false,
            'token_expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('softbooks.download', $download->download_token));

        $response->assertOk();
        
        $download->refresh();
        $this->assertTrue($download->is_completed);
    }

    /** @test */
    public function expired_token_cannot_be_used(): void
    {
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
        ]);

        $download = SoftbookDownload::factory()->create([
            'softbook_id' => $softbook->id,
            'user_id' => $this->member->id,
            'is_completed' => false,
            'token_expires_at' => now()->subHour(), // Expired
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('softbooks.download', $download->download_token));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function admin_can_update_softbook(): void
    {
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
            'pages' => 100,
            'download_limit' => 5,
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('softbooks.update', $softbook), [
                'pages' => 150,
                'download_limit' => 10,
                'description' => 'Updated description',
                'is_active' => false,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('softbooks', [
            'id' => $softbook->id,
            'pages' => 150,
            'download_limit' => 10,
            'description' => 'Updated description',
            'is_active' => false,
        ]);
    }

    /** @test */
    public function member_cannot_update_softbook(): void
    {
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->member)
            ->patch(route('softbooks.update', $softbook), [
                'pages' => 150,
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_toggle_softbook_active_status(): void
    {
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('softbooks.toggle-active', $softbook));

        $response->assertRedirect();
        $softbook->refresh();
        $this->assertFalse($softbook->is_active);
    }

    /** @test */
    public function admin_can_delete_softbook(): void
    {
        Storage::fake('private');
        
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
            'file_path' => 'softbooks/test.pdf',
        ]);

        Storage::disk('private')->put('softbooks/test.pdf', 'fake content');

        $response = $this->actingAs($this->admin)
            ->delete(route('softbooks.destroy', $softbook));

        $response->assertRedirect();
        $this->assertSoftDeleted('softbooks', ['id' => $softbook->id]);
    }

    /** @test */
    public function member_cannot_delete_softbook(): void
    {
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->member)
            ->delete(route('softbooks.destroy', $softbook));

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_view_download_history(): void
    {
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
        ]);

        SoftbookDownload::factory()->count(3)->create([
            'softbook_id' => $softbook->id,
            'is_completed' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('softbooks.download-history', $softbook));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Softbooks/DownloadHistory')
            ->has('downloads.data', 3)
        );
    }

    /** @test */
    public function member_can_view_their_download_history(): void
    {
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
        ]);

        SoftbookDownload::factory()->count(2)->create([
            'softbook_id' => $softbook->id,
            'user_id' => $this->member->id,
            'is_completed' => true,
        ]);

        $response = $this->actingAs($this->member)
            ->get(route('softbooks.my-downloads'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Softbooks/MyDownloads')
            ->has('downloads.data', 2)
        );
    }

    /** @test */
    public function softbook_search_filters_by_format(): void
    {
        Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
            'format' => 'pdf',
        ]);

        Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
            'format' => 'epub',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('softbooks.index', ['format' => 'pdf']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->has('softbooks.data', 1)
            ->where('softbooks.data.0.format', 'pdf')
        );
    }

    /** @test */
    public function softbook_search_filters_by_active_status(): void
    {
        Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
            'is_active' => true,
        ]);

        Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('softbooks.index', ['is_active' => '1']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->has('softbooks.data', 1)
            ->where('softbooks.data.0.is_active', true)
        );
    }

    /** @test */
    public function download_increments_total_downloads_counter(): void
    {
        Storage::fake('private');
        
        $softbook = Softbook::factory()->create([
            'book_id' => $this->book->id,
            'uploaded_by' => $this->admin->id,
            'file_path' => 'softbooks/test.pdf',
            'total_downloads' => 5,
        ]);

        Storage::disk('private')->put('softbooks/test.pdf', 'fake content');

        $download = SoftbookDownload::factory()->create([
            'softbook_id' => $softbook->id,
            'user_id' => $this->member->id,
            'is_completed' => false,
            'token_expires_at' => now()->addHour(),
        ]);

        $this->actingAs($this->member)
            ->get(route('softbooks.download', $download->download_token));

        $softbook->refresh();
        $this->assertEquals(6, $softbook->total_downloads);
    }
}
