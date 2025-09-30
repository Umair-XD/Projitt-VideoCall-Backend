<?php

namespace Tests\Feature;

use App\Models\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebRtcSignalingTest extends TestCase
{
    use RefreshDatabase;

    private function auth(string $email, string $password)
    {
        return $this->postJson('/api/auth/login', compact('email', 'password'))
            ->assertOk()
            ->json('access_token');
    }

    public function test_signaling_flow_between_two_participants(): void
    {
        // Register owner and guest
        $owner = $this->postJson('/api/auth/register', [
            'name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret123'
        ])->assertCreated()->json('access_token');

        $guest = $this->postJson('/api/auth/register', [
            'name' => 'Guest', 'email' => 'guest@example.com', 'password' => 'secret123'
        ])->assertCreated()->json('access_token');

        // Owner creates meeting
        $meeting = $this->withToken($owner)
            ->postJson('/api/meetings', [
                'title' => 'WebRTC Demo',
                'scheduled_at' => now()->addHour()->toDateTimeString(),
                'duration_minutes' => 30,
            ])->assertCreated()->json('meeting');

        // Owner invites guest; guest accepts
        $this->withToken($owner)
            ->postJson("/api/meetings/{$meeting['id']}/invite", ['invitee_email' => 'guest@example.com'])
            ->assertCreated();
        $invitation = Invitation::first();
        $this->withToken($guest)
            ->postJson("/api/invitations/{$invitation->id}/accept")
            ->assertOk();

        // Both join presence
        $this->withToken($owner)->postJson("/api/meetings/{$meeting['id']}/presence/join")->assertCreated();
        $this->withToken($guest)->postJson("/api/meetings/{$meeting['id']}/presence/join")->assertCreated();

        // Owner sends offer to guest
        $offer = $this->withToken($owner)
            ->postJson("/api/meetings/{$meeting['id']}/rtc/send", [
                'to_user_id' => 2, // guest id
                'type' => 'offer',
                'payload' => ['sdp' => 'v=0...'],
            ])->assertCreated()->json();

        // Guest inbox should contain the offer
        $inbox = $this->withToken($guest)
            ->getJson("/api/meetings/{$meeting['id']}/rtc/inbox")
            ->assertOk()->json();
        $this->assertCount(1, $inbox);
        $this->assertSame('offer', $inbox[0]['type']);

        // Guest acknowledges
        $this->withToken($guest)
            ->postJson("/api/meetings/{$meeting['id']}/rtc/ack", ['ids' => [$offer['id']]])
            ->assertOk();

        // Guest sends answer
        $answer = $this->withToken($guest)
            ->postJson("/api/meetings/{$meeting['id']}/rtc/send", [
                'to_user_id' => 1, // owner id
                'type' => 'answer',
                'payload' => ['sdp' => 'v=0...answer'],
            ])->assertCreated()->json();

        // Owner polls inbox since last id
        $ownerInbox = $this->withToken($owner)
            ->getJson("/api/meetings/{$meeting['id']}/rtc/inbox?since_id={$offer['id']}")
            ->assertOk()->json();
        $this->assertCount(1, $ownerInbox);
        $this->assertSame('answer', $ownerInbox[0]['type']);
    }
}
