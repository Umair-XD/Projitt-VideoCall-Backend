<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invitation;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Str;

class InvitationSeeder extends Seeder
{
    public function run(): void
    {
        Invitation::truncate();

        $meeting = Meeting::first();
        $invitee = User::where('email', 'invitee@example.com')->first();
        $owner = User::where('email', 'owner@example.com')->first();

        Invitation::create([
            'meeting_id' => $meeting->id,
            'inviter_id' => $owner->id,
            'invitee_user_id' => $invitee->id,
            'status' => 'pending',
            'token' => Str::random(32),
        ]);
    }
}
