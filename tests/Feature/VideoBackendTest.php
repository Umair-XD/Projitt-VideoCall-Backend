<?php

namespace Tests\Feature;

use App\Models\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoBackendTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_full_meeting_flow_with_recording_and_ai_notes(): void
    {
        // Register owner
        $owner = [
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'secret123',
        ];
        $res = $this->postJson('/api/auth/register', $owner)->assertCreated();
        $ownerToken = $res->json('access_token');

        // Create meeting
        $payload = [
            'title' => 'Project Sync',
            'scheduled_at' => now()->addHour()->toDateTimeString(),
            'duration_minutes' => 30,
        ];
        $mRes = $this->withHeaders($this->authHeaders($ownerToken))
            ->postJson('/api/meetings', $payload)
            ->assertCreated();
        $meetingId = $mRes->json('meeting.id');

        // Update meeting
        $upd = $this->withHeaders($this->authHeaders($ownerToken))
            ->putJson("/api/meetings/{$meetingId}", [
                'title' => 'Project Sync - Updated',
                'duration_minutes' => 45,
            ])->assertOk()->json();
        $this->assertSame('Project Sync - Updated', $upd['title']);
        $this->assertSame(45, $upd['duration_minutes']);

        // Invite participant by email
        $inviteEmail = 'guest@example.com';
        $this->withHeaders($this->authHeaders($ownerToken))
            ->postJson("/api/meetings/{$meetingId}/invite", ['invitee_email' => $inviteEmail])
            ->assertCreated();

        // Register participant with the invited email
        $guest = [
            'name' => 'Guest',
            'email' => $inviteEmail,
            'password' => 'secret123',
        ];
        $gRes = $this->postJson('/api/auth/register', $guest)->assertCreated();
        $guestToken = $gRes->json('access_token');

        // Accept the invitation
        $invitation = Invitation::first();
        $this->withHeaders($this->authHeaders($guestToken))
            ->postJson("/api/invitations/{$invitation->id}/accept")
            ->assertOk();

        // Start recording (mock)
        $this->withHeaders($this->authHeaders($ownerToken))
            ->postJson("/api/meetings/{$meetingId}/recordings/start")
            ->assertCreated();

        // End recording (mock) -> creates a dummy file
        $this->withHeaders($this->authHeaders($ownerToken))
            ->postJson("/api/meetings/{$meetingId}/recordings/end")
            ->assertOk();

        // List recordings
        $list = $this->withHeaders($this->authHeaders($ownerToken))
            ->getJson("/api/meetings/{$meetingId}/recordings")
            ->assertOk()
            ->json('data');
        $this->assertNotEmpty($list);
        $recordingId = $list[0]['id'];

        // Generate AI notes (mock)
        $this->withHeaders($this->authHeaders($ownerToken))
            ->postJson("/api/meetings/{$meetingId}/notes")
            ->assertCreated();

        // End meeting
        $this->withHeaders($this->authHeaders($ownerToken))
            ->postJson("/api/meetings/{$meetingId}/end")
            ->assertOk();

        // Download recording
        $this->withHeaders($this->authHeaders($ownerToken))
            ->get("/api/recordings/{$recordingId}/download")
            ->assertOk();
    }
}
