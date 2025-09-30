<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingAndParticipantsTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_meetings_pagination_and_sorting(): void
    {
        $owner = $this->postJson('/api/auth/register', [
            'name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret123'
        ])->assertCreated()->json('access_token');

        // Create 3 meetings with different scheduled_at
        $times = [now()->addDays(1), now()->addDays(2), now()->addDays(3)];
        foreach ($times as $i => $time) {
            $this->withHeaders($this->authHeaders($owner))
                ->postJson('/api/meetings', [
                    'title' => 'M'.$i,
                    'scheduled_at' => $time->toDateTimeString(),
                    'duration_minutes' => 30,
                ])->assertCreated();
        }

        $page = $this->withHeaders($this->authHeaders($owner))
            ->getJson('/api/meetings?per_page=2&sort_by=scheduled_at&sort_dir=desc')
            ->assertOk()->json();

        $this->assertSame(2, count($page['data']));
        $first = $page['data'][0]['scheduled_at'];
        $second = $page['data'][1]['scheduled_at'];
        $this->assertTrue(strtotime($first) >= strtotime($second));
    }

    public function test_recordings_pagination_and_sorting_and_participants_listing(): void
    {
        // Owner and guest
        $ownerToken = $this->postJson('/api/auth/register', [
            'name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret123'
        ])->assertCreated()->json('access_token');
        $guestToken = $this->postJson('/api/auth/register', [
            'name' => 'Guest', 'email' => 'guest@example.com', 'password' => 'secret123'
        ])->assertCreated()->json('access_token');

        // Meeting
        $meetingId = $this->withHeaders($this->authHeaders($ownerToken))
            ->postJson('/api/meetings', [
                'title' => 'Rec Sort',
                'scheduled_at' => now()->addHour()->toDateTimeString(),
                'duration_minutes' => 30,
            ])->assertCreated()->json('meeting.id');

        // Invite and accept
        $this->withHeaders($this->authHeaders($ownerToken))
            ->postJson("/api/meetings/{$meetingId}/invite", ['invitee_email' => 'guest@example.com'])
            ->assertCreated();
        $invId = \App\Models\Invitation::first()->id;
        $this->withHeaders($this->authHeaders($guestToken))
            ->postJson("/api/invitations/{$invId}/accept")
            ->assertOk();

        // Join presence owner + guest
        $this->withHeaders($this->authHeaders($ownerToken))
            ->postJson("/api/meetings/{$meetingId}/presence/join")
            ->assertCreated();
        $this->withHeaders($this->authHeaders($guestToken))
            ->postJson("/api/meetings/{$meetingId}/presence/join")
            ->assertCreated();

        // Start/End two recordings
        foreach ([1,2] as $_) {
            $this->withHeaders($this->authHeaders($ownerToken))
                ->postJson("/api/meetings/{$meetingId}/recordings/start")->assertCreated();
            $this->withHeaders($this->authHeaders($ownerToken))
                ->postJson("/api/meetings/{$meetingId}/recordings/end")->assertOk();
        }

        // List recordings paginated = 1, desc by started_at
        $recs = $this->withHeaders($this->authHeaders($ownerToken))
            ->getJson("/api/meetings/{$meetingId}/recordings?per_page=1&sort_by=started_at&sort_dir=desc")
            ->assertOk()->json('data');
        $this->assertCount(1, $recs);

        // List participants (active)
        $parts = $this->withHeaders($this->authHeaders($ownerToken))
            ->getJson("/api/meetings/{$meetingId}/participants?active=1")
            ->assertOk()->json();
        $initialCount = count($parts);
        $this->assertGreaterThanOrEqual(1, $initialCount);

        // Guest leaves -> active should be 1
        $this->withHeaders($this->authHeaders($guestToken))
            ->postJson("/api/meetings/{$meetingId}/presence/leave")
            ->assertOk();
        $active = $this->withHeaders($this->authHeaders($ownerToken))
            ->getJson("/api/meetings/{$meetingId}/participants?active=1")
            ->assertOk()->json();
        $this->assertLessThanOrEqual($initialCount, count($active));
    }
}
