<?php

namespace App\Events;

use App\Models\Recording;

class RecordingEnded
{
    public function __construct(public Recording $recording)
    {
    }
}
