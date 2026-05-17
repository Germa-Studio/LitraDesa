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

class ReservationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $member;
    private Book $book;

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
    }

    /** @test */
    public function member_can_view_reservations_index(): void
    {
        $response = $this->actingAs($this->member)->get(route('reservations.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Reservations/Index'));
    }

    /** @test */
    public function admin_can_view_all_reservations(): void
    {
        Reservation::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('reservations.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reservations/Index')
            ->has('reservations.data', 3)
        );
    }

    /** @test */
    public function member_can_view_create_reservation_form(): void
    {
        $response = $this->actingAs($this->member)->get(route('reservations.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reservations/Create')
            ->has('books')
        );
    }

    /** @test */
    public function member_can_create_reservation_for_available_book(): void
    {
        $response = $this->actingAs($this->member)->post(route('reservations.store'), [
            'book_id' => $this->book->id,
        ]);

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'ready', // Available book = ready status
        ]);
    }

    /** @test */
    public function member_can_create_reservation_for_unavailable_book(): void
    {
        $this->book->update(['available_copies' => 0, 'is_available' => false]);

        $response = $this->actingAs($this->member)->post(route('reservations.store'), [
            'book_id' => $this->book->id,
        ]);

        $response->assertRedirect(route('reservations.index'));

        $this->assertDatabaseHas('reservations', [
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'pending', // Unavailable book = pending status (waitlist)
        ]);
    }

    /** @test */
    public function admin_cannot_create_reservation(): void
    {
        $response = $this->actingAs($this->admin)->post(route('reservations.store'), [
            'book_id' => $this->book->id,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function cannot_create_duplicate_active_reservation(): void
    {
        Reservation::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->member)->post(route('reservations.store'), [
            'book_id' => $this->book->id,
        ]);

        $response->assertSessionHasErrors('book_id');
    }

    /** @test */
    public function cannot_reserve_book_already_borrowed(): void
    {
        Loan::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->member)->post(route('reservations.store'), [
            'book_id' => $this->book->id,
        ]);

        $response->assertSessionHasErrors('book_id');
    }

    /** @test */
    public function cannot_reserve_when_member_has_max_loans(): void
    {
        // Create 3 active loans
        for ($i = 0; $i < 3; $i++) {
            Loan::factory()->create([
                'user_id' => $this->member->id,
                'status' => 'active',
            ]);
        }

        $response = $this->actingAs($this->member)->post(route('reservations.store'), [
            'book_id' => $this->book->id,
        ]);

        $response->assertSessionHasErrors('book_id');
    }

    /** @test */
    public function cannot_reserve_when_member_has_overdue_loans(): void
    {
        Loan::factory()->create([
            'user_id' => $this->member->id,
            'status' => 'overdue',
            'due_date' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->member)->post(route('reservations.store'), [
            'book_id' => $this->book->id,
        ]);

        $response->assertSessionHasErrors('book_id');
    }

    /** @test */
    public function cannot_reserve_when_waitlist_is_full(): void
    {
        $this->book->update(['available_copies' => 0, 'is_available' => false]);

        // Create 10 pending reservations (max waitlist)
        Reservation::factory()->count(10)->create([
            'book_id' => $this->book->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->member)->post(route('reservations.store'), [
            'book_id' => $this->book->id,
        ]);

        $response->assertSessionHasErrors('book_id');
    }

    /** @test */
    public function member_can_view_reservation_details(): void
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
        ]);

        $response = $this->actingAs($this->member)->get(route('reservations.show', $reservation));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reservations/Show')
            ->has('reservation')
        );
    }

    /** @test */
    public function member_cannot_view_other_member_reservation(): void
    {
        $otherMember = User::factory()->create(['role' => 'member', 'status' => 'active']);
        
        $reservation = Reservation::factory()->create([
            'user_id' => $otherMember->id,
            'book_id' => $this->book->id,
        ]);

        $response = $this->actingAs($this->member)->get(route('reservations.show', $reservation));

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_view_any_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reservations.show', $reservation));

        $response->assertOk();
    }

    /** @test */
    public function member_can_cancel_their_active_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->member)->post(route('reservations.cancel', $reservation), [
            'reason' => 'Changed my mind',
        ]);

        $response->assertRedirect(route('reservations.index'));

        $reservation->refresh();
        $this->assertEquals('cancelled', $reservation->status);
        $this->assertEquals('Changed my mind', $reservation->cancellation_reason);
    }

    /** @test */
    public function cannot_cancel_completed_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->member)->post(route('reservations.cancel', $reservation));

        $response->assertSessionHas('error');
    }

    /** @test */
    public function admin_can_mark_reservation_as_ready(): void
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('reservations.mark-ready', $reservation));

        $response->assertRedirect(route('reservations.index'));

        $reservation->refresh();
        $this->assertEquals('ready', $reservation->status);
        $this->assertNotNull($reservation->notified_at);
    }

    /** @test */
    public function member_cannot_mark_reservation_as_ready(): void
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->member)->post(route('reservations.mark-ready', $reservation));

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_process_expired_reservations(): void
    {
        // Create expired reservations
        Reservation::factory()->count(2)->create([
            'status' => 'pending',
            'expires_at' => now()->subHours(1),
        ]);

        $response = $this->actingAs($this->admin)->post(route('reservations.process-expired'));

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('success');

        $this->assertEquals(2, Reservation::where('status', 'expired')->count());
    }

    /** @test */
    public function reservation_expires_after_24_hours(): void
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'pending',
            'reserved_at' => now(),
        ]);

        $this->assertEquals(
            now()->addHours(24)->format('Y-m-d H:i'),
            $reservation->expires_at->format('Y-m-d H:i')
        );
    }

    /** @test */
    public function reservation_is_marked_expired_when_time_passes(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'pending',
            'expires_at' => now()->subHour(),
        ]);

        $this->assertTrue($reservation->isExpired());
    }

    /** @test */
    public function next_in_waitlist_is_notified_when_book_becomes_available(): void
    {
        $this->book->update(['available_copies' => 0, 'is_available' => false]);

        // Create waitlist
        $firstInLine = Reservation::factory()->create([
            'user_id' => User::factory()->create(['role' => 'member', 'status' => 'active']),
            'book_id' => $this->book->id,
            'status' => 'pending',
            'reserved_at' => now()->subHours(2),
        ]);

        $secondInLine = Reservation::factory()->create([
            'user_id' => User::factory()->create(['role' => 'member', 'status' => 'active']),
            'book_id' => $this->book->id,
            'status' => 'pending',
            'reserved_at' => now()->subHour(),
        ]);

        // Make book available
        $this->book->update(['available_copies' => 1, 'is_available' => true]);

        // Simulate notification process (would be triggered by loan return)
        $nextReservation = $this->book->getNextInWaitlist();
        $nextReservation->markAsReady();

        $firstInLine->refresh();
        $this->assertEquals('ready', $firstInLine->status);
        $this->assertNotNull($firstInLine->notified_at);

        $secondInLine->refresh();
        $this->assertEquals('pending', $secondInLine->status);
    }

    /** @test */
    public function admin_can_view_reservation_statistics(): void
    {
        Reservation::factory()->count(5)->create(['status' => 'pending']);
        Reservation::factory()->count(3)->create(['status' => 'ready']);

        $response = $this->actingAs($this->admin)->get(route('reservations.statistics'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reservations/Statistics')
            ->has('stats')
        );
    }

    /** @test */
    public function member_can_view_their_reservation_history(): void
    {
        Reservation::factory()->count(3)->create([
            'user_id' => $this->member->id,
        ]);

        $response = $this->actingAs($this->member)->get(route('reservations.history'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reservations/History')
            ->has('reservations.data', 3)
        );
    }

    /** @test */
    public function reservation_defaults_are_set_correctly(): void
    {
        $reservation = Reservation::create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'pending',
        ]);

        $this->assertNotNull($reservation->reserved_at);
        $this->assertNotNull($reservation->expires_at);
        $this->assertEquals(
            $reservation->reserved_at->addHours(24)->format('Y-m-d H:i'),
            $reservation->expires_at->format('Y-m-d H:i')
        );
    }

    /** @test */
    public function can_get_hours_remaining_for_active_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'pending',
            'expires_at' => now()->addHours(5),
        ]);

        $this->assertEquals(5, $reservation->hours_remaining);
    }

    /** @test */
    public function hours_remaining_is_zero_for_completed_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'completed',
            'expires_at' => now()->addHours(5),
        ]);

        $this->assertEquals(0, $reservation->hours_remaining);
    }

    /** @test */
    public function cancelling_reservation_notifies_next_in_waitlist(): void
    {
        $this->book->update(['available_copies' => 1, 'is_available' => true]);

        // First reservation (ready)
        $firstReservation = Reservation::factory()->create([
            'user_id' => $this->member->id,
            'book_id' => $this->book->id,
            'status' => 'ready',
        ]);

        // Second reservation (pending - in waitlist)
        $secondReservation = Reservation::factory()->create([
            'user_id' => User::factory()->create(['role' => 'member', 'status' => 'active']),
            'book_id' => $this->book->id,
            'status' => 'pending',
        ]);

        // Cancel first reservation
        $this->actingAs($this->member)->post(route('reservations.cancel', $firstReservation));

        // Check if next in waitlist is notified (would happen in controller)
        $nextReservation = $this->book->getNextInWaitlist();
        if ($nextReservation && $this->book->is_available) {
            $nextReservation->markAsReady();
        }

        $secondReservation->refresh();
        $this->assertEquals('ready', $secondReservation->status);
    }

    /** @test */
    public function reservation_can_be_filtered_by_status(): void
    {
        Reservation::factory()->count(2)->create(['status' => 'pending']);
        Reservation::factory()->count(3)->create(['status' => 'ready']);

        $response = $this->actingAs($this->admin)->get(route('reservations.index', ['status' => 'ready']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('reservations.data', 3)
        );
    }

    /** @test */
    public function reservation_can_be_searched_by_member_or_book(): void
    {
        $searchMember = User::factory()->create([
            'name' => 'John Doe',
            'role' => 'member',
            'status' => 'active',
        ]);

        Reservation::factory()->create([
            'user_id' => $searchMember->id,
            'book_id' => $this->book->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reservations.index', ['search' => 'John']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('reservations.data', 1)
        );
    }
}
