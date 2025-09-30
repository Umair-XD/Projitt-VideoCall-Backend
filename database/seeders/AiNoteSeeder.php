<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AiNote;
use App\Models\Meeting;

class AiNoteSeeder extends Seeder
{
    public function run(): void
    {
        AiNote::truncate();

        $meeting = Meeting::first();

        AiNote::create([
            'meeting_id' => $meeting->id,
            'transcript_text' => "This is a mocked transcript for '{$meeting->title}' created by seeder.",
            'key_points' => [
                'Introductions',
                'Project kickoff discussion',
                'Next steps assigned'
            ],
            'sentiment' => 'positive',
        ]);
    }
}
