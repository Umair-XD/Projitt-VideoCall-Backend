<?php

namespace App\Events;

use App\Models\Meeting;

class MeetingScheduled
{
    public function __construct(public Meeting $meeting)
    {
    }
}
