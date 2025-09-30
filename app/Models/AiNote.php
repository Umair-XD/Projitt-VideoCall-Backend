<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiNote extends Model
{
    use HasFactory;

    protected $table = 'ai_notes';

    protected $fillable = [
        'meeting_id',
        'transcript_text',
        'key_points',
        'sentiment',
    ];

    protected $casts = [
        'key_points' => 'array',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }
}
