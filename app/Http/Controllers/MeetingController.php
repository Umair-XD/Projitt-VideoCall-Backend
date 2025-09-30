<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Events\MeetingScheduled;
use App\Events\MeetingStarted;
use App\Events\MeetingEnded;
use App\Events\MeetingUpdated;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Manage meetings: list, show, create, update, start, end.
 *
 * All actions are scoped to the authenticated owner of the meeting.
 */
class MeetingController extends Controller
{
    /**
     * List meetings belonging to the authenticated user with pagination/sorting.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['sometimes', 'in:scheduled_at,created_at,title,status'],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
        ]);

        $perPage = $data['per_page'] ?? 10;
        $sortBy = $data['sort_by'] ?? 'scheduled_at';
        $sortDir = $data['sort_dir'] ?? 'desc';

        $meetings = Meeting::where('created_by', Auth::id())
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);

        return response()->json($meetings);
    }

    /** Show a single meeting (owner only). */
    public function show(Meeting $meeting)
    {
        $this->authorizeOwner($meeting);
        return response()->json($meeting);
    }

    /** Create a new meeting and dispatch MeetingScheduled. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
        ]);

        $meeting = Meeting::create([
            'created_by' => Auth::id(),
            'title' => $data['title'],
            'scheduled_at' => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'],
            'join_code' => Str::random(12),
            'status' => 'scheduled',
        ]);

        event(new MeetingScheduled($meeting));

        return response()->json([
            'meeting' => $meeting,
            'join_link' => url('/join/'.$meeting->join_code),
        ], 201);
    }

    /** Mark a meeting as started (owner only) and dispatch MeetingStarted. */
    public function start(Meeting $meeting)
    {
        $this->authorizeOwner($meeting);
        if ($meeting->status === 'started') {
            return response()->json(['error' => 'already_started', 'message' => 'Already started'], 200);
        }
        $meeting->update([
            'status' => 'started',
            'started_at' => now(),
        ]);
        event(new MeetingStarted($meeting));
        return response()->json($meeting);
    }

    /** Update meeting details (owner only) and dispatch MeetingUpdated. */
    public function update(Meeting $meeting, Request $request)
    {
        $this->authorizeOwner($meeting);
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'scheduled_at' => ['sometimes', 'date'],
            'duration_minutes' => ['sometimes', 'integer', 'min:1'],
        ]);
        $meeting->update($data);
        event(new MeetingUpdated($meeting));
        return response()->json($meeting);
    }

    /** Mark a meeting as ended (owner only) and dispatch MeetingEnded. */
    public function end(Meeting $meeting)
    {
        $this->authorizeOwner($meeting);
        if ($meeting->status === 'ended') {
            return response()->json(['error' => 'already_ended', 'message' => 'Already ended'], 200);
        }
        $meeting->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);
        event(new MeetingEnded($meeting));
        return response()->json($meeting);
    }

    private function authorizeOwner(Meeting $meeting): void
    {
        if ($meeting->created_by !== Auth::id()) {
            throw new HttpResponseException(response()->json([
                'error' => 'forbidden',
                'message' => 'You do not own this meeting.',
            ], 403));
        }
    }
}
