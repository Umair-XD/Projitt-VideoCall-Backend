<?php

namespace App\Events;

use App\Models\AiNote;

class AiNotesGenerated
{
    public function __construct(public AiNote $note)
    {
    }
}
