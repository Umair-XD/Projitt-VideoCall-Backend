<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Str;

class MeetingSeeder extends Seeder
{
    public function run(): void
    {
        Meeting::truncate();

        $owner = User::where('email', 'owner@example.com')->first();

        Meeting::create([
            'created_by' => $owner->id,
            'title' => 'Kickoff Meeting',
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 60,
            'join_code' => Str::random(12),
            'status' => 'scheduled',
        ]);
    }
}
