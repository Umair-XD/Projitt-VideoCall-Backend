<?php

namespace App\Events;

use App\Models\Invitation;

class InvitationSent
{
    public function __construct(public Invitation $invitation)
    {
    }
}
