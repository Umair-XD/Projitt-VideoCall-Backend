<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Events\InvitationSent;
use App\Events\InvitationResponded;

class InvitationController extends Controller
{
    public function invite(Meeting $meeting, Request $request)
    {
        $this->authorizeOwner($meeting);

        $data = $request->validate([
            'invitee_user_id' => ['nullable', 'exists:users,id'],
            'invitee_email' => ['nullable', 'email'],
        ]);

        if (empty($data['invitee_user_id']) && empty($data['invitee_email'])) {
            return response()->json(['message' => 'Provide invitee_user_id or invitee_email'], 422);
        }

        $invitation = Invitation::create([
            'meeting_id' => $meeting->id,
            'inviter_id' => Auth::id(),
            'invitee_user_id' => $data['invitee_user_id'] ?? null,
            'invitee_email' => $data['invitee_email'] ?? null,
            'status' => 'pending',
            'token' => Str::random(32),
        ]);
        event(new InvitationSent($invitation));
        return response()->json($invitation, 201);
    }

    public function accept(Invitation $invitation)
    {
        $this->ensureInvitationAccess($invitation);
        $invitation->update(['status' => 'accepted', 'responded_at' => now()]);
        event(new InvitationResponded($invitation));
        return response()->json($invitation);
    }

    public function reject(Invitation $invitation)
    {
        $this->ensureInvitationAccess($invitation);
        $invitation->update(['status' => 'rejected', 'responded_at' => now()]);
        event(new InvitationResponded($invitation));
        return response()->json($invitation);
    }

    public function propose(Invitation $invitation, Request $request)
    {
        $this->ensureInvitationAccess($invitation);
        $data = $request->validate([
            'proposed_time' => ['required', 'date'],
        ]);
        $invitation->update(['status' => 'proposed', 'proposed_time' => $data['proposed_time'], 'responded_at' => now()]);
        event(new InvitationResponded($invitation));
        return response()->json($invitation);
    }

    private function authorizeOwner(Meeting $meeting): void
    {
        abort_if($meeting->created_by !== Auth::id(), 403, 'Forbidden');
    }

    private function ensureInvitationAccess(Invitation $invitation): void
    {
        // Allow invitee (by user id or email match with authenticated user) or meeting owner to act
        $user = Auth::user();
        $isOwner = $invitation->meeting->created_by === $user->id;
        $isInvitee = ($invitation->invitee_user_id && $invitation->invitee_user_id === $user->id)
            || ($invitation->invitee_email && $invitation->invitee_email === $user->email);
        abort_unless($isOwner || $isInvitee, 403, 'Forbidden');
    }
}
