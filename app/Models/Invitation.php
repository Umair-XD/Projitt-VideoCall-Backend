<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'inviter_id',
        'invitee_user_id',
        'invitee_email',
        'status',
        'proposed_time',
        'token',
        'responded_at',
    ];

    protected $casts = [
        'proposed_time' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function invitee()
    {
        return $this->belongsTo(User::class, 'invitee_user_id');
    }
}
