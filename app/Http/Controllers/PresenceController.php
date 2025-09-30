<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Manage presence for WebRTC sessions and list participants.
 */
class PresenceController extends Controller
{
    /** Join presence for a meeting (owner or accepted invitee). */
    public function join(Meeting $meeting)
    {
        $user = Auth::user();
        $this->authorizeJoin($meeting, $user->id, $user->email);

        $participant = MeetingParticipant::updateOrCreate(
            ['meeting_id' => $meeting->id, 'user_id' => $user->id],
            ['joined_at' => now(), 'left_at' => null]
        );

        return response()->json($participant, 201);
    }

    /** List participants; owner-only. Optional active filter (left_at is null). */
    public function participants(Meeting $meeting, Request $request)
    {
        if ($meeting->created_by !== Auth::id()) {
            throw new HttpResponseException(response()->json([
                'error' => 'forbidden',
                'message' => 'Only the meeting owner can list participants.',
            ], 403));
        }
        $request->validate([
            'active' => ['sometimes', 'boolean'],
        ]);
        $query = MeetingParticipant::where('meeting_id', $meeting->id);
        if ($request->boolean('active')) {
            $query->whereNull('left_at');
        }
        return response()->json($query->orderBy('joined_at', 'desc')->get());
    }

    public function leave(Meeting $meeting)
    {
        $userId = Auth::id();
        $participant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $userId)
            ->firstOrFail();
        $participant->update(['left_at' => now()]);
        return response()->json($participant);
    }

    private function authorizeJoin(Meeting $meeting, int $userId, string $email): void
    {
        if ($meeting->created_by === $userId) {
            return;
        }
        $inv = Invitation::where('meeting_id', $meeting->id)
            ->where(function ($q) use ($userId, $email) {
                $q->where('invitee_user_id', $userId)->orWhere('invitee_email', $email);
            })
            ->where('status', 'accepted')
            ->first();
        if (!$inv) {
            throw new HttpResponseException(response()->json([
                'error' => 'forbidden',
                'message' => 'Not invited or not accepted.',
            ], 403));
        }
    }
}
