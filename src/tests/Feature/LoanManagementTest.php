<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
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

        // Create active member
        $this->member = User::factory()->create([
            'role' => 'member',
            'status' => 'active',
        ]);

        // Create book with available copy
        $this->book = Book::factory()->create();
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
    public function admin_can_create_loan(): void
    {
        $response = $this->actingAs($this->admin)->post(route('loans.store'), [
            'user_id' => $this->member->id,
            'book_copy_id' => $this->bookCopy->id,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'book_condition_at_loan' => 'good',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('loans', [
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'book_copy_id' => $this->bookCopy->id,
            'status' => 'active',
        ]);

        // Verify book copy status updated
        $this->bookCopy->refresh();
        $this->assertEquals('borrowed', $this->bookCopy->status);
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
            'book_copy_id' => $this->bookCopy->id,
        ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseMissing('loans', [
            'user_id' => $inactiveMember->id,
        ]);
    }

    /** @test */
    public function cannot_create_loan_if_member_has_overdue_books(): void
    {
        // Create overdue loan
        Loan::factory()->create([
            'user_id' => $this->member->id,
            'status' => 'overdue',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('loans.store'), [
            'user_id' => $this->member->id,
            'book_copy_id' => $this->bookCopy->id,
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    /** @test */
    public function cannot_create_loan_if_member_has_max_active_loans(): void
    {
        // Create 3 active loans (maximum)
        for ($i = 0; $i < 3; $i++) {
            $book = Book::factory()->create();
            $copy = BookCopy::factory()->create([
                'book_id' => $book->id,
                'status' => 'borrowed',
            ]);
            
            Loan::factory()->create([
                'user_id' => $this->member->id,
                'book_id' => $book->id,
                'book_copy_id' => $copy->id,
                'status' => 'active',
            ]);
        }

        $response = $this->actingAs($this->admin)->post(route('loans.store'), [
            'user_id' => $this->member->id,
            'book_copy_id' => $this->bookCopy->id,
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    /** @test */
    public function cannot_create_loan_for_unavailable_book_copy(): void
    {
        $this->bookCopy->update(['status' => 'borrowed']);

        $response = $this->actingAs($this->admin)->post(route('loans.store'), [
            'user_id' => $this->member->id,
            'book_copy_id' => $this->bookCopy->id,
        ]);

        $response->assertSessionHasErrors('book_copy_id');
    }

    /** @test */
    public function cannot_create_loan_if_member_already_has_same_book(): void
    {
        // Create active loan for same book
        $anotherCopy = BookCopy::factory()->create([
            'book_id' => $this->book->id,
            'status' => 'borrowed',
        ]);

        Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'book_copy_id' => $anotherCopy->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post(route('loans.store'), [
            'user_id' => $this->member->id,
            'book_copy_id' => $this->bookCopy->id,
        ]);

        $response->assertSessionHasErrors('book_copy_id');
    }

    /** @test */
    public function admin_can_process_book_return(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'book_copy_id' => $this->bookCopy->id,
            'status' => 'active',
            'loan_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDays(4)->toDateString(),
        ]);

        $this->bookCopy->update(['status' => 'borrowed']);

        $response = $this->actingAs($this->admin)->patch(route('loans.update', $loan), [
            'action' => 'return',
            'return_date' => now()->toDateString(),
            'book_condition_at_return' => 'good',
            'return_notes' => 'Returned in good condition',
        ]);

        $response->assertRedirect(route('loans.show', $loan));

        $loan->refresh();
        $this->assertEquals('returned', $loan->status);
        $this->assertNotNull($loan->return_date);
        $this->assertEquals('good', $loan->book_condition_at_return);

        // Verify book copy status updated
        $this->bookCopy->refresh();
        $this->assertEquals('available', $this->bookCopy->status);
    }

    /** @test */
    public function return_calculates_fine_for_overdue_loan(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'book_copy_id' => $this->bookCopy->id,
            'status' => 'overdue',
            'loan_date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        $this->bookCopy->update(['status' => 'borrowed']);

        $response = $this->actingAs($this->admin)->patch(route('loans.update', $loan), [
            'action' => 'return',
            'return_date' => now()->toDateString(),
            'book_condition_at_return' => 'good',
            'fine_paid' => false,
        ]);

        $response->assertRedirect();

        $loan->refresh();
        $this->assertEquals('returned', $loan->status);
        $this->assertGreaterThan(0, $loan->fine_amount);
        $this->assertEquals(5, $loan->days_overdue);
        $this->assertEquals(5000, $loan->fine_amount); // 5 days * Rp 1,000
        $this->assertFalse($loan->fine_paid);
    }

    /** @test */
    public function admin_can_extend_loan(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'book_copy_id' => $this->bookCopy->id,
            'status' => 'active',
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $originalDueDate = $loan->due_date;

        $response = $this->actingAs($this->admin)->patch(route('loans.update', $loan), [
            'action' => 'extend',
            'extend_days' => 7,
        ]);

        $response->assertRedirect();

        $loan->refresh();
        $this->assertEquals('active', $loan->status);
        $this->assertEquals(
            now()->parse($originalDueDate)->addDays(7)->toDateString(),
            $loan->due_date
        );
    }

    /** @test */
    public function cannot_extend_returned_loan(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'status' => 'returned',
            'return_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)->patch(route('loans.update', $loan), [
            'action' => 'extend',
            'extend_days' => 7,
        ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function admin_can_mark_loan_as_lost(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'book_copy_id' => $this->bookCopy->id,
            'status' => 'active',
        ]);

        $this->bookCopy->update(['status' => 'borrowed']);

        $response = $this->actingAs($this->admin)->patch(route('loans.update', $loan), [
            'action' => 'mark_lost',
            'return_notes' => 'Book reported lost by member',
        ]);

        $response->assertRedirect();

        $loan->refresh();
        $this->assertEquals('lost', $loan->status);
        $this->assertNotNull($loan->return_notes);

        // Verify book copy status updated
        $this->bookCopy->refresh();
        $this->assertEquals('lost', $this->bookCopy->status);
    }

    /** @test */
    public function admin_can_view_member_loan_history(): void
    {
        Loan::factory()->count(3)->create([
            'user_id' => $this->member->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('loans.member-history', $this->member));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Loans/MemberHistory')
            ->has('loans.data', 3)
        );
    }

    /** @test */
    public function admin_can_view_book_loan_history(): void
    {
        Loan::factory()->count(5)->create([
            'book_id' => $this->book->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('loans.book-history', $this->book));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Loans/BookHistory')
            ->has('loans.data', 5)
        );
    }

    /** @test */
    public function admin_can_delete_returned_loan(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'status' => 'returned',
            'return_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('loans.destroy', $loan));

        $response->assertRedirect(route('loans.index'));
        $this->assertSoftDeleted('loans', ['id' => $loan->id]);
    }

    /** @test */
    public function cannot_delete_active_loan(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('loans.destroy', $loan));

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('loans', ['id' => $loan->id]);
    }

    /** @test */
    public function loan_automatically_marked_overdue_when_past_due_date(): void
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->member->id,
            'status' => 'active',
            'due_date' => now()->subDays(1)->toDateString(),
        ]);

        // Trigger the updating event
        $loan->touch();

        $loan->refresh();
        $this->assertEquals('overdue', $loan->status);
        $this->assertGreaterThan(0, $loan->days_overdue);
    }

    /** @test */
    public function loan_defaults_to_14_day_period(): void
    {
        $loan = Loan::create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'book_copy_id' => $this->bookCopy->id,
            'processed_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $this->assertEquals(now()->toDateString(), $loan->loan_date);
        $this->assertEquals(now()->addDays(14)->toDateString(), $loan->due_date);
    }

    /** @test */
    public function can_filter_loans_by_status(): void
    {
        Loan::factory()->create(['status' => 'active']);
        Loan::factory()->create(['status' => 'returned']);
        Loan::factory()->create(['status' => 'overdue']);

        $response = $this->actingAs($this->admin)
            ->get(route('loans.index', ['status' => 'active']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('loans.data', 1)
            ->where('loans.data.0.status', 'active')
        );
    }

    /** @test */
    public function can_search_loans_by_member_name(): void
    {
        $member = User::factory()->create([
            'name' => 'John Doe',
            'role' => 'member',
            'status' => 'active',
        ]);

        Loan::factory()->create(['user_id' => $member->id]);
        Loan::factory()->create(['user_id' => $this->member->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('loans.index', ['search' => 'John']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('loans.data', 1)
            ->where('loans.data.0.user.name', 'John Doe')
        );
    }
}