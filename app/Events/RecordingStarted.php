<?php

namespace App\Events;

use App\Models\Recording;

class RecordingStarted
{
    public function __construct(public Recording $recording)
    {
    }
}
