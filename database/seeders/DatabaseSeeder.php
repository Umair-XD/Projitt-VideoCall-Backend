<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            MeetingSeeder::class,
            InvitationSeeder::class,
            RecordingSeeder::class,
            AiNoteSeeder::class,
        ]);
    }
}
