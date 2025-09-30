<?php

namespace App\Events;

use App\Models\Meeting;

class MeetingUpdated
{
    public function __construct(public Meeting $meeting)
    {
    }
}
