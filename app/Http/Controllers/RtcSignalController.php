<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\RtcSignal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RtcSignalController extends Controller
{
    public function send(Meeting $meeting, Request $request)
    {
        $user = Auth::user();
        $this->ensureParticipant($meeting, $user->id);

        $data = $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'in:offer,answer,candidate'],
            'payload' => ['required', 'array'],
        ]);

        // Recipient may not have joined yet; allow queueing messages

        $signal = RtcSignal::create([
            'meeting_id' => $meeting->id,
            'from_user_id' => $user->id,
            'to_user_id' => (int) $data['to_user_id'],
            'type' => $data['type'],
            'payload' => $data['payload'],
        ]);

        return response()->json($signal, 201);
    }

    public function inbox(Meeting $meeting, Request $request)
    {
        $userId = Auth::id();
        $this->ensureParticipant($meeting, $userId);
        $sinceId = (int) $request->query('since_id', 0);

        $signals = RtcSignal::where('meeting_id', $meeting->id)
            ->when($sinceId > 0, fn($q) => $q->where('id', '>', $sinceId))
            ->orderBy('id')
            ->get();

        return response()->json($signals);
    }

    public function ack(Meeting $meeting, Request $request)
    {
        $userId = Auth::id();
        $this->ensureParticipant($meeting, $userId);
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = RtcSignal::where('meeting_id', $meeting->id)
            ->where('to_user_id', $userId)
            ->whereIn('id', $data['ids'])
            ->update(['acknowledged_at' => now()]);

        return response()->json(['acknowledged' => $count]);
    }

    private function ensureParticipant(Meeting $meeting, int $userId): void
    {
        $exists = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->exists();
        abort_unless($exists || $meeting->created_by === $userId, 403, 'Join meeting first');
    }
}
