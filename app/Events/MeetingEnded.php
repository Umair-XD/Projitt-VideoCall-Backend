<?php

namespace App\Events;

use App\Models\Meeting;

class MeetingEnded
{
    public function __construct(public Meeting $meeting)
    {
    }
}
