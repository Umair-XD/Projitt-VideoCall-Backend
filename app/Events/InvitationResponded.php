<?php

namespace App\Events;

use App\Models\Invitation;

class InvitationResponded
{
    public function __construct(public Invitation $invitation)
    {
    }
}
