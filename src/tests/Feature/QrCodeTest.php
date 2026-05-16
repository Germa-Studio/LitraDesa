<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeTest extends TestCase
{
  use RefreshDatabase;

  protected QrCodeService $qrCodeService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->qrCodeService = new QrCodeService();
  }

  public function test_qr_code_is_generated_for_new_member(): void
  {
    $member = User::factory()->create([
      'role' => 'member',
      'status' => 'active',
      'qr_code' => 'MBR-TEST123456',
    ]);

    $this->assertNotNull($member->qr_code);
    $this->assertStringStartsWith('MBR-', $member->qr_code);
  }

  public function test_qr_code_service_generates_valid_qr_data(): void
  {
    $member = User::factory()->create([
      'role' => 'member',
      'status' => 'active',
      'qr_code' => 'MBR-TEST123456',
    ]);

    $qrData = $this->qrCodeService->generateQrData($member);

    $this->assertStringContainsString('LITRADESA-MEMBER:', $qrData);
    $this->assertStringContainsString((string) $member->id, $qrData);
    $this->assertStringContainsString($member->qr_code, $qrData);
  }

  public function test_qr_code_service_generates_svg_qr_code(): void
  {
    $member = User::factory()->create([
      'role' => 'member',
      'status' => 'active',
      'qr_code' => 'MBR-TEST123456',
    ]);

    $qrCodeSvg = $this->qrCodeService->generateMemberQrCode($member);

    $this->assertStringContainsString('<svg', $qrCodeSvg);
    $this->assertStringContainsString('</svg>', $qrCodeSvg);
  }

  public function test_qr_code_service_generates_base64_qr_code(): void
  {
    $member = User::factory()->create([
      'role' => 'member',
      'status' => 'active',
      'qr_code' => 'MBR-TEST123456',
    ]);

    $qrCodeBase64 = $this->qrCodeService->generateBase64QrCode($member);

    $this->assertStringStartsWith('data:image/png;base64,', $qrCodeBase64);
  }

  public function test_qr_code_verification_succeeds_for_valid_member(): void
  {
    $member = User::factory()->create([
      'role' => 'member',
      'status' => 'active',
      'qr_code' => 'MBR-TEST123456',
    ]);

    $qrData = $this->qrCodeService->generateQrData($member);
    $verifiedMember = $this->qrCodeService->verifyQrCode($qrData);

    $this->assertNotNull($verifiedMember);
    $this->assertEquals($member->id, $verifiedMember->id);
    $this->assertEquals($member->qr_code, $verifiedMember->qr_code);
  }

  public function test_qr_code_verification_fails_for_invalid_format(): void
  {
    $verifiedMember = $this->qrCodeService->verifyQrCode('INVALID-FORMAT');

    $this->assertNull($verifiedMember);
  }

  public function test_qr_code_verification_fails_for_non_existent_member(): void
  {
    $verifiedMember = $this->qrCodeService->verifyQrCode('LITRADESA-MEMBER:99999:NONEXISTENT');

    $this->assertNull($verifiedMember);
  }

  public function test_authenticated_member_can_get_their_qr_code(): void
  {
    $member = User::factory()->create([
      'role' => 'member',
      'status' => 'active',
      'qr_code' => 'MBR-TEST123456',
    ]);

    $response = $this->actingAs($member)->getJson(route('members.qr-code', $member));

    $response->assertOk()
      ->assertJsonStructure([
        'qr_code_svg',
        'qr_code_base64',
        'qr_data',
      ]);
  }

  public function test_member_cannot_get_another_members_qr_code(): void
  {
    $member1 = User::factory()->create([
      'role' => 'member',
      'status' => 'active',
    ]);

    $member2 = User::factory()->create([
      'role' => 'member',
      'status' => 'active',
    ]);

    $response = $this->actingAs($member1)->getJson(route('members.qr-code', $member2));

    $response->assertForbidden();
  }

  public function test_admin_can_verify_qr_code(): void
  {
    $admin = User::factory()->create([
      'role' => 'admin',
      'status' => 'active',
    ]);

    $member = User::factory()->create([
      'role' => 'member',
      'status' => 'active',
      'qr_code' => 'MBR-TEST123456',
    ]);

    $qrData = $this->qrCodeService->generateQrData($member);

    $response = $this->actingAs($admin)->postJson(route('members.verify-qr'), [
      'qr_data' => $qrData,
    ]);

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'member' => [
          'id' => $member->id,
          'name' => $member->name,
          'status' => 'active',
        ],
      ]);
  }

  public function test_qr_verification_fails_for_inactive_member(): void
  {
    $admin = User::factory()->create([
      'role' => 'admin',
      'status' => 'active',
    ]);

    $member = User::factory()->create([
      'role' => 'member',
      'status' => 'suspended',
      'qr_code' => 'MBR-TEST123456',
    ]);

    $qrData = $this->qrCodeService->generateQrData($member);

    $response = $this->actingAs($admin)->postJson(route('members.verify-qr'), [
      'qr_data' => $qrData,
    ]);

    $response->assertStatus(403)
      ->assertJson([
        'success' => false,
      ]);
  }

  public function test_qr_verification_requires_authentication(): void
  {
    $response = $this->postJson(route('members.verify-qr'), [
      'qr_data' => 'LITRADESA-MEMBER:1:TEST123',
    ]);

    $response->assertUnauthorized();
  }

  public function test_qr_verification_validates_required_fields(): void
  {
    $admin = User::factory()->create([
      'role' => 'admin',
      'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->postJson(route('members.verify-qr'), []);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['qr_data']);
  }
}

// Made with Bob