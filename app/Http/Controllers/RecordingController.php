<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Recording;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Events\RecordingStarted;
use App\Events\RecordingEnded;

/**
 * Manage mock recordings for a meeting (start, end, list, download).
 */
class RecordingController extends Controller
{
    /** List recordings with pagination/sorting. */
    public function index(Meeting $meeting, Request $request)
    {
        $this->authorizeOwner($meeting);
        $data = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['sometimes', 'in:created_at,started_at,ended_at'],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
        ]);
        $perPage = $data['per_page'] ?? 10;
        $sortBy = $data['sort_by'] ?? 'created_at';
        $sortDir = $data['sort_dir'] ?? 'desc';
        $recs = $meeting->recordings()
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
        return response()->json($recs);
    }

    /** Start a mock recording and dispatch RecordingStarted. */
    public function start(Meeting $meeting)
    {
        $this->authorizeOwner($meeting);

        // Snapshot accepted participants (emails or user ids)
        $participants = $meeting->invitations()
            ->where('status', 'accepted')
            ->get()
            ->map(function ($inv) {
                return $inv->invitee_email ?: ('user:'.$inv->invitee_user_id);
            })->values()->all();

        $rec = Recording::create([
            'meeting_id' => $meeting->id,
            'started_at' => now(),
            'participants' => $participants,
        ]);
        event(new RecordingStarted($rec));
        return response()->json($rec, 201);
    }

    /** End the latest active mock recording and dispatch RecordingEnded. */
    public function end(Meeting $meeting)
    {
        $this->authorizeOwner($meeting);
        $rec = $meeting->recordings()->whereNull('ended_at')->latest()->first();
        if (!$rec) {
            return response()->json(['error' => 'no_active_recording', 'message' => 'No active recording'], 404);
        }

        $filename = 'recordings/meeting-'.$meeting->id.'-'.now()->format('Ymd_His').'.txt';
        Storage::disk('local')->put($filename, "Mock recording for meeting {$meeting->id} finished at ".now()->toDateTimeString());

        $rec->update([
            'ended_at' => now(),
            'file_path' => $filename,
        ]);
        event(new RecordingEnded($rec));
        return response()->json($rec);
    }

    /** Download the mock recording file (owner only). */
    public function download(Recording $recording)
    {
        $meeting = $recording->meeting;
        $this->authorizeOwner($meeting);

        if (!$recording->file_path || !Storage::disk('local')->exists($recording->file_path)) {
            return response()->json(['error' => 'file_unavailable', 'message' => 'File not available'], 404);
        }
        return Storage::disk('local')->download($recording->file_path, basename($recording->file_path));
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
