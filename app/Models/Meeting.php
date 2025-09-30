<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'title',
        'scheduled_at',
        'duration_minutes',
        'join_code',
        'status',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    public function recordings()
    {
        return $this->hasMany(Recording::class);
    }

    public function aiNotes()
    {
        return $this->hasMany(AiNote::class);
    }

    public function participants()
    {
        return $this->hasMany(MeetingParticipant::class);
    }
}
