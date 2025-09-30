<?php

namespace App\Events;

use App\Models\Meeting;

class MeetingStarted
{
    public function __construct(public Meeting $meeting)
    {
    }
}
