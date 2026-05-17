<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $member;
    private Book $book;
    private BookCopy $bookCopy;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create member user
        $this->member = User::factory()->create([
            'role' => 'member',
            'status' => 'active',
        ]);

        // Create category
        $category = BookCategory::factory()->create();

        // Create book
        $this->book = Book::factory()->create([
            'book_category_id' => $category->id,
            'total_copies' => 1,
            'available_copies' => 1,
            'is_available' => true,
        ]);

        // Create book copy
        $this->bookCopy = BookCopy::factory()->create([
            'book_id' => $this->book->id,
            'status' => 'available',
        ]);
    }

    /** @test */
    public function admin_can_view_loans_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('loans.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Loans/Index'));
    }

    /** @test */
    public function member_cannot_view_loans_index(): void
    {
        $response = $this->actingAs($this->member)->get(route('loans.index'));

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_view_create_loan_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('loans.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Loans/Create')
            ->has('members')
            ->has('books')
        );
    }

    /** @test */
    public function admin_can_create_loan(): void
    {
        $response = $this->actingAs($this->admin)->post(route('loans.store'), [
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'book_copy_id' => $this->bookCopy->id,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'book_condition_at_loan' => 'good',
        ]);

        $response->assertRedirect(route('loans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('loans', [
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'active',
        ]);

        // Check book copy status updated
        $this->bookCopy->refresh();
        $this->assertEquals('borrowed', $this->bookCopy->status);

        // Check book availability updated
        $this->book->refresh();
        $this->assertEquals(0, $this->book->available_copies);
    }

    /** @test */
    public function member_cannot_create_loan(): void
    {
        $response = $this->actingAs($this->member)->post(route('loans.store'), [
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function cannot_create_loan_for_inactive_member(): void
    {
        $inactiveMember = User::factory()->create([
            'role' => 'member',
            'status' => 'suspended',
        ]);

        $response = $this->actingAs($this->admin)->post(route('loans.store'), [
            'user_id' => $inactiveMember->id,
            'book_id' => $this->book->id,
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    /** @test */
    public function cannot_create_loan_when_member_has_max_loans(): void
    {
        // Create 3 active loans for member
        for ($i = 0; $i < 3; $i++) {
            $book = Book::factory()->create(['available_copies' => 1]);
            $copy = BookCopy::factory()->create(['book_id' => $book->id]);
            
            Loan::factory()->create([
                'user_id' => $this->member->id,
                'book_id' => $book->id,
                'book_copy_id' => $copy->id,
                'status' => 'active',
            ]);
        }

        $response = $this->actingAs($this->admin)->post(route('loans.store'), [
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    /** @test */
    public function cannot_create_loan_when_member_has_overdue_loans(): void
    {
        Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => Book::factory()->create(),
            'status' => 'overdue',
            'due_date' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->admin)->post(route('loans.store'), [
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    /** @test */
    public function cannot_create_loan_for_unavailable_book(): void
    {
        $this->book->update(['available_copies' => 0, 'is_available' => false]);

        $response = $this->actingAs($this->admin)->post(route('loans.store'), [
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
        ]);

        $response->assertSessionHasErrors('book_id');
    }

    /** @test */
    public function admin_can_view_loan_details(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'book_copy_id' => $this->bookCopy->id,
            'processed_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('loans.show', $loan));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Loans/Show')
            ->has('loan')
        );
    }

    /** @test */
    public function admin_can_view_return_form(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('loans.return-form', $loan));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Loans/Return'));
    }

    /** @test */
    public function admin_can_process_return(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'book_copy_id' => $this->bookCopy->id,
            'status' => 'active',
            'loan_date' => now()->subDays(7),
            'due_date' => now()->addDays(7),
        ]);

        $this->bookCopy->update(['status' => 'borrowed']);
        $this->book->update(['available_copies' => 0]);

        $response = $this->actingAs($this->admin)->post(route('loans.process-return'), [
            'loan_id' => $loan->id,
            'return_date' => now()->toDateString(),
            'book_condition_at_return' => 'good',
            'return_notes' => 'Book returned in good condition',
        ]);

        $response->assertRedirect(route('loans.index'));
        $response->assertSessionHas('success');

        $loan->refresh();
        $this->assertEquals('returned', $loan->status);
        $this->assertNotNull($loan->return_date);
        $this->assertEquals($this->admin->id, $loan->returned_by);

        // Check book copy status updated
        $this->bookCopy->refresh();
        $this->assertEquals('available', $this->bookCopy->status);

        // Check book availability updated
        $this->book->refresh();
        $this->assertEquals(1, $this->book->available_copies);
    }

    /** @test */
    public function return_calculates_overdue_days_and_fine(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'book_copy_id' => $this->bookCopy->id,
            'status' => 'active',
            'loan_date' => now()->subDays(20),
            'due_date' => now()->subDays(5), // 5 days overdue
        ]);

        $response = $this->actingAs($this->admin)->post(route('loans.process-return'), [
            'loan_id' => $loan->id,
            'return_date' => now()->toDateString(),
            'book_condition_at_return' => 'good',
        ]);

        $loan->refresh();
        $this->assertEquals(5, $loan->days_overdue);
        $this->assertEquals(5000, $loan->fine_amount); // 5 days * Rp 1,000
    }

    /** @test */
    public function cannot_return_already_returned_loan(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'returned',
            'return_date' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->admin)->post(route('loans.process-return'), [
            'loan_id' => $loan->id,
            'return_date' => now()->toDateString(),
            'book_condition_at_return' => 'good',
        ]);

        $response->assertSessionHasErrors('loan_id');
    }

    /** @test */
    public function admin_can_mark_book_as_lost(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'book_copy_id' => $this->bookCopy->id,
            'status' => 'active',
        ]);

        $this->bookCopy->update(['status' => 'borrowed']);

        $response = $this->actingAs($this->admin)->post(route('loans.mark-lost', $loan), [
            'notes' => 'Book reported lost by member',
        ]);

        $response->assertRedirect(route('loans.index'));

        $loan->refresh();
        $this->assertEquals('lost', $loan->status);

        $this->bookCopy->refresh();
        $this->assertEquals('lost', $this->bookCopy->status);
    }

    /** @test */
    public function loan_from_reservation_marks_reservation_completed(): void
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'ready',
        ]);

        $response = $this->actingAs($this->admin)->post(route('loans.store'), [
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'reservation_id' => $reservation->id,
        ]);

        $response->assertRedirect(route('loans.index'));

        $reservation->refresh();
        $this->assertEquals('completed', $reservation->status);
        $this->assertNotNull($reservation->picked_up_at);
    }

    /** @test */
    public function member_can_view_their_loan_history(): void
    {
        Loan::factory()->count(3)->create([
            'user_id' => $this->member->id,
        ]);

        $response = $this->actingAs($this->member)->get(route('loans.history'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Loans/History')
            ->has('loans.data', 3)
        );
    }

    /** @test */
    public function admin_can_view_overdue_report(): void
    {
        Loan::factory()->count(2)->create([
            'status' => 'overdue',
            'due_date' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->admin)->get(route('loans.overdue-report'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Loans/OverdueReport'));
    }

    /** @test */
    public function admin_can_view_popular_books_report(): void
    {
        $popularBook = Book::factory()->create();
        
        Loan::factory()->count(5)->create([
            'book_id' => $popularBook->id,
            'created_at' => now()->subMonth(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('loans.popular-books'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Loans/PopularBooksReport'));
    }

    /** @test */
    public function loan_status_automatically_updates_to_overdue(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'active',
            'due_date' => now()->subDays(1),
        ]);

        // Trigger update (simulating model observer or scheduled task)
        $loan->update(['notes' => 'test update']);

        $loan->refresh();
        $this->assertEquals('overdue', $loan->status);
        $this->assertGreaterThan(0, $loan->days_overdue);
    }

    /** @test */
    public function notifies_next_in_waitlist_when_book_returned(): void
    {
        // Create a reservation in waitlist
        $waitlistReservation = Reservation::factory()->create([
            'user_id' => User::factory()->create(['role' => 'member', 'status' => 'active']),
            'book_id' => $this->book->id,
            'status' => 'pending',
        ]);

        // Create and return a loan
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'book_copy_id' => $this->bookCopy->id,
            'status' => 'active',
        ]);

        $this->bookCopy->update(['status' => 'borrowed']);
        $this->book->update(['available_copies' => 0]);

        $this->actingAs($this->admin)->post(route('loans.process-return'), [
            'loan_id' => $loan->id,
            'return_date' => now()->toDateString(),
            'book_condition_at_return' => 'good',
        ]);

        // Check that waitlist reservation is now ready
        $waitlistReservation->refresh();
        $this->assertEquals('ready', $waitlistReservation->status);
        $this->assertNotNull($waitlistReservation->notified_at);
    }

    /** @test */
    public function loan_defaults_are_set_correctly(): void
    {
        $response = $this->actingAs($this->admin)->post(route('loans.store'), [
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            // Not providing loan_date, due_date, book_condition_at_loan
        ]);

        $response->assertRedirect(route('loans.index'));

        $loan = Loan::where('user_id', $this->member->id)->first();
        
        $this->assertEquals(now()->toDateString(), $loan->loan_date->toDateString());
        $this->assertEquals(now()->addDays(14)->toDateString(), $loan->due_date->toDateString());
        $this->assertEquals('good', $loan->book_condition_at_loan);
    }
}
