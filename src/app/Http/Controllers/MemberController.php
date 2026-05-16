<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MemberApprovalRequest;
use App\Http\Requests\MemberProfileUpdateRequest;
use App\Http\Requests\MemberRegistrationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Display a listing of members (Admin only).
     */
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $members = User::members()
            ->with('approver:id,name')
            ->latest()
            ->paginate(15)
            ->through(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'ktp_number' => $member->ktp_number,
                'status' => $member->status,
                'qr_code' => $member->qr_code,
                'approved_by' => $member->approver?->name,
                'approved_at' => $member->approved_at?->format('d/m/Y H:i'),
                'created_at' => $member->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Members/Index', [
            'members' => $members,
            'filters' => request()->only(['search', 'status']),
        ]);
    }

    /**
     * Display pending members for approval (Admin only).
     */
    public function pending(): Response
    {
        $this->authorize('viewAny', User::class);

        $pendingMembers = User::members()
            ->pending()
            ->latest()
            ->paginate(15)
            ->through(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'address' => $member->address,
                'ktp_number' => $member->ktp_number,
                'ktp_photo_url' => $member->ktp_photo_path ? Storage::url($member->ktp_photo_path) : null,
                'created_at' => $member->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Members/Pending', [
            'pendingMembers' => $pendingMembers,
        ]);
    }

    /**
     * Show the form for creating a new member (Public registration).
     */
    public function create(): Response
    {
        return Inertia::render('Members/Register');
    }

    /**
     * Store a newly created member in storage.
     */
    public function store(MemberRegistrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Handle KTP photo upload
        $ktpPhotoPath = null;
        if ($request->hasFile('ktp_photo')) {
            $ktpPhotoPath = $request->file('ktp_photo')->store('ktp-photos', 'public');
        }

        // Generate unique QR code
        $qrCode = 'MBR-' . strtoupper(Str::random(10));

        // Create member
        $member = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'member',
            'status' => 'pending',
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'ktp_number' => $validated['ktp_number'],
            'ktp_photo_path' => $ktpPhotoPath,
            'qr_code' => $qrCode,
        ]);

        return redirect()->route('login')->with('success', 'Pendaftaran berhasil! Akun Anda menunggu persetujuan admin.');
    }

    /**
     * Display the specified member.
     */
    public function show(User $member): Response
    {
        $this->authorize('view', $member);

        return Inertia::render('Members/Show', [
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'address' => $member->address,
                'ktp_number' => $member->ktp_number,
                'ktp_photo_url' => $member->ktp_photo_path ? Storage::url($member->ktp_photo_path) : null,
                'qr_code' => $member->qr_code,
                'status' => $member->status,
                'approved_by' => $member->approver?->name,
                'approved_at' => $member->approved_at?->format('d/m/Y H:i'),
                'rejection_reason' => $member->rejection_reason,
                'created_at' => $member->created_at->format('d/m/Y H:i'),
            ],
        ]);
    }

    /**
     * Show the form for editing the member profile.
     */
    public function edit(User $member): Response
    {
        $this->authorize('update', $member);

        return Inertia::render('Members/Edit', [
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'address' => $member->address,
            ],
        ]);
    }

    /**
     * Update the specified member profile.
     */
    public function update(MemberProfileUpdateRequest $request, User $member): RedirectResponse
    {
        $validated = $request->validated();

        $member->update($validated);

        return redirect()->route('members.show', $member)->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Approve or reject a pending member (Admin only).
     */
    public function approve(MemberApprovalRequest $request, User $member): RedirectResponse
    {
        $this->authorize('approve', $member);

        if (!$member->isPending()) {
            return back()->with('error', 'Hanya anggota dengan status pending yang dapat disetujui.');
        }

        $validated = $request->validated();

        $member->update([
            'status' => $validated['status'],
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        $message = $validated['status'] === 'active'
            ? 'Anggota berhasil disetujui.'
            : 'Anggota berhasil ditolak.';

        return redirect()->route('members.pending')->with('success', $message);
    }

    /**
     * Suspend an active member (Admin only).
     */
    public function suspend(User $member): RedirectResponse
    {
        $this->authorize('suspend', $member);

        if (!$member->isActive()) {
            return back()->with('error', 'Hanya anggota aktif yang dapat disuspend.');
        }

        $member->update(['status' => 'suspended']);

        return back()->with('success', 'Anggota berhasil disuspend.');
    }

    /**
     * Reactivate a suspended member (Admin only).
     */
    public function reactivate(User $member): RedirectResponse
    {
        $this->authorize('reactivate', $member);

        if (!$member->isSuspended()) {
            return back()->with('error', 'Hanya anggota yang disuspend yang dapat diaktifkan kembali.');
        }

        $member->update(['status' => 'active']);

        return back()->with('success', 'Anggota berhasil diaktifkan kembali.');
    }

    /**
     * Remove the specified member from storage (Soft delete).
     */
    public function destroy(User $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        // Delete KTP photo if exists
        if ($member->ktp_photo_path) {
            Storage::disk('public')->delete($member->ktp_photo_path);
        }

        $member->delete();

        return redirect()->route('members.index')->with('success', 'Anggota berhasil dihapus.');
    }
}

// Made with Bob
