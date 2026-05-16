<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $member;

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
            'qr_code' => 'MBR-TEST123456',
        ]);
    }

    /** @test */
    public function guest_can_access_registration_page(): void
    {
        $response = $this->get(route('members.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Members/Register'));
    }

    /** @test */
    public function guest_can_register_as_member(): void
    {
        Storage::fake('public');

        $ktpPhoto = UploadedFile::fake()->image('ktp.jpg');

        $response = $this->post(route('members.store'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '081234567890',
            'address' => 'Jl. Test No. 123, Jakarta',
            'ktp_number' => '1234567890123456',
            'ktp_photo' => $ktpPhoto,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => 'member',
            'status' => 'pending',
            'ktp_number' => '1234567890123456',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->qr_code);
        $this->assertStringStartsWith('MBR-', $user->qr_code);

        Storage::disk('public')->assertExists($user->ktp_photo_path);
    }

    /** @test */
    public function registration_validates_required_fields(): void
    {
        $response = $this->post(route('members.store'), []);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
            'phone',
            'address',
            'ktp_number',
        ]);
    }

    /** @test */
    public function registration_validates_ktp_number_format(): void
    {
        $response = $this->post(route('members.store'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '081234567890',
            'address' => 'Jl. Test No. 123',
            'ktp_number' => '12345', // Invalid: too short
        ]);

        $response->assertSessionHasErrors('ktp_number');
    }

    /** @test */
    public function admin_can_view_members_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('members.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Members/Index')
            ->has('members.data')
        );
    }

    /** @test */
    public function member_cannot_view_members_list(): void
    {
        $response = $this->actingAs($this->member)->get(route('members.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_pending_members(): void
    {
        $pendingMember = User::factory()->create([
            'role' => 'member',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get(route('members.pending'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Members/Pending')
            ->has('pendingMembers.data')
        );
    }

    /** @test */
    public function admin_can_approve_pending_member(): void
    {
        $pendingMember = User::factory()->create([
            'role' => 'member',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('members.approve', $pendingMember),
            ['status' => 'active']
        );

        $response->assertRedirect(route('members.pending'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $pendingMember->id,
            'status' => 'active',
            'approved_by' => $this->admin->id,
        ]);

        $pendingMember->refresh();
        $this->assertNotNull($pendingMember->approved_at);
    }

    /** @test */
    public function admin_can_reject_pending_member(): void
    {
        $pendingMember = User::factory()->create([
            'role' => 'member',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('members.approve', $pendingMember),
            [
                'status' => 'rejected',
                'rejection_reason' => 'Data tidak valid',
            ]
        );

        $response->assertRedirect(route('members.pending'));

        $this->assertDatabaseHas('users', [
            'id' => $pendingMember->id,
            'status' => 'rejected',
            'rejection_reason' => 'Data tidak valid',
        ]);
    }

    /** @test */
    public function rejection_requires_reason(): void
    {
        $pendingMember = User::factory()->create([
            'role' => 'member',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('members.approve', $pendingMember),
            ['status' => 'rejected']
        );

        $response->assertSessionHasErrors('rejection_reason');
    }

    /** @test */
    public function member_can_view_own_profile(): void
    {
        $response = $this->actingAs($this->member)->get(
            route('members.show', $this->member)
        );

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Members/Show')
            ->has('member')
            ->where('member.id', $this->member->id)
        );
    }

    /** @test */
    public function admin_can_view_any_member_profile(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('members.show', $this->member)
        );

        $response->assertStatus(200);
    }

    /** @test */
    public function member_cannot_view_other_member_profile(): void
    {
        $otherMember = User::factory()->create([
            'role' => 'member',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->member)->get(
            route('members.show', $otherMember)
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function member_can_edit_own_profile(): void
    {
        $response = $this->actingAs($this->member)->get(
            route('members.edit', $this->member)
        );

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Members/Edit'));
    }

    /** @test */
    public function member_can_update_own_profile(): void
    {
        $response = $this->actingAs($this->member)->patch(
            route('members.update', $this->member),
            [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'phone' => '089876543210',
                'address' => 'Updated Address',
            ]
        );

        $response->assertRedirect(route('members.show', $this->member));

        $this->assertDatabaseHas('users', [
            'id' => $this->member->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '089876543210',
        ]);
    }

    /** @test */
    public function member_cannot_update_other_member_profile(): void
    {
        $otherMember = User::factory()->create([
            'role' => 'member',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->member)->patch(
            route('members.update', $otherMember),
            ['name' => 'Hacked Name']
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_suspend_active_member(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('members.suspend', $this->member)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $this->member->id,
            'status' => 'suspended',
        ]);
    }

    /** @test */
    public function admin_can_reactivate_suspended_member(): void
    {
        $this->member->update(['status' => 'suspended']);

        $response = $this->actingAs($this->admin)->post(
            route('members.reactivate', $this->member)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $this->member->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function admin_can_delete_member(): void
    {
        Storage::fake('public');
        
        $memberToDelete = User::factory()->create([
            'role' => 'member',
            'status' => 'active',
            'ktp_photo_path' => 'ktp-photos/test.jpg',
        ]);

        Storage::disk('public')->put('ktp-photos/test.jpg', 'fake content');

        $response = $this->actingAs($this->admin)->delete(
            route('members.destroy', $memberToDelete)
        );

        $response->assertRedirect(route('members.index'));

        $this->assertSoftDeleted('users', [
            'id' => $memberToDelete->id,
        ]);

        Storage::disk('public')->assertMissing('ktp-photos/test.jpg');
    }

    /** @test */
    public function admin_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->admin)->delete(
            route('members.destroy', $this->admin)
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function member_cannot_delete_any_member(): void
    {
        $otherMember = User::factory()->create([
            'role' => 'member',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->member)->delete(
            route('members.destroy', $otherMember)
        );

        $response->assertStatus(403);
    }
}

// Made with Bob
