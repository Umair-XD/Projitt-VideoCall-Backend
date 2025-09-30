<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recording;
use App\Models\Meeting;
use Illuminate\Support\Facades\Storage;

class RecordingSeeder extends Seeder
{
    public function run(): void
    {
        Recording::truncate();

        $meeting = Meeting::first();

        // Create a fake recording file
        $filename = 'recordings/meeting-'.$meeting->id.'-seed.txt';
        Storage::disk('local')->put($filename, "This is a seeded mock recording for meeting {$meeting->id}.");

        Recording::create([
            'meeting_id' => $meeting->id,
            'started_at' => now()->subMinutes(30),
            'ended_at' => now()->subMinutes(25),
            'participants' => ['owner@example.com', 'invitee@example.com'],
            'file_path' => $filename,
        ]);
    }
}
