# Projitt Video Conferencing Backend (Laravel + MySQL)

JWT-secured API that supports:
- Meeting scheduling (secure join link)
- Peer-to-peer participant invitations and responses
- Mock meeting recordings (start/end, list, download)
- Mock AI transcript and notes generation
- WebRTC presence and REST-based signaling (like Google Meet: join/leave, offer/answer/candidate exchange)

## Tech Stack
- Laravel 11, PHP 8.2+
- MySQL (dev), SQLite in-memory for tests
- JWT Auth: php-open-source-saver/jwt-auth

## Quick Start
1) Install dependencies
```
composer install
```

2) Configure environment (.env)
```
APP_KEY=base64:generate_using_php_artisan_key:generate --show
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=test_db
DB_USERNAME=root
DB_PASSWORD=
```

3) Run migrations
```
php artisan migrate
```

4) (Optional) Seed demo data
```
php artisan db:seed --class=DemoSeeder
```

Demo accounts:
- owner@example.com / secret123
- guest@example.com / secret123

5) Serve the app
```
php artisan serve
```

## Authentication
- Register: `POST /api/auth/register` (returns JWT)
- Login: `POST /api/auth/login`
- Me: `GET /api/auth/me`
- Refresh: `POST /api/auth/refresh`
- Logout: `POST /api/auth/logout`

For protected endpoints, set header: `Authorization: Bearer {token}`

## API Endpoints
- Meetings
  - `GET /api/meetings` (list by owner)
    - Query: `per_page` (1..100), `sort_by` (scheduled_at|created_at|title|status), `sort_dir` (asc|desc)
    - Example:
      - `GET /api/meetings?per_page=5&sort_by=scheduled_at&sort_dir=desc`
  - `GET /api/meetings/{meeting}`
  - `POST /api/meetings` (title, scheduled_at, duration_minutes)
  - `POST /api/meetings/{meeting}/start`
  - `POST /api/meetings/{meeting}/end`
- Invitations
  - `POST /api/meetings/{meeting}/invite` (invitee_email or invitee_user_id)
  - `POST /api/invitations/{invitation}/accept`
  - `POST /api/invitations/{invitation}/reject`
  - `POST /api/invitations/{invitation}/propose` (proposed_time)
- Recordings (mock)
  - `GET /api/meetings/{meeting}/recordings`
    - Query: `per_page` (1..100), `sort_by` (created_at|started_at|ended_at), `sort_dir` (asc|desc)
    - Example: `GET /api/meetings/1/recordings?per_page=10&sort_by=created_at&sort_dir=desc`
  - `POST /api/meetings/{meeting}/recordings/start`
  - `POST /api/meetings/{meeting}/recordings/end` (creates a dummy file under storage/app/recordings)
  - `GET /api/recordings/{recording}/download`
- AI Notes (mock)
  - `POST /api/meetings/{meeting}/notes` (persists transcript and key points)
- Presence (WebRTC)
  - `POST /api/meetings/{meeting}/presence/join`
  - `POST /api/meetings/{meeting}/presence/leave`
  - `GET /api/meetings/{meeting}/participants` (owner-only)
    - Query: `active=1` to return only currently joined participants
- WebRTC Signaling (REST polling)
  - `POST /api/meetings/{meeting}/rtc/send` (body: to_user_id, type: offer|answer|candidate, payload: json)
  - `GET /api/meetings/{meeting}/rtc/inbox` (optional: since_id)
  - `POST /api/meetings/{meeting}/rtc/ack` (body: ids: [int])

## Postman Collection
Import: `docs/postman_collection.json`

## OpenAPI (Swagger)
File: `docs/openapi.yaml`
You can load this in Swagger UI/Editor or Insomnia.

## Curl Examples
Login as demo owner and create a meeting, invite guest, and start/end recording.

1) Login (owner)
```
curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"owner@example.com","password":"secret123"}'
```

2) Export token (PowerShell example):
```
$res = curl -s -X POST http://localhost:8000/api/auth/login -H "Content-Type: application/json" -d '{"email":"owner@example.com","password":"secret123"}'
$token = ($res | ConvertFrom-Json).access_token
```

3) Create meeting
```
curl -s -X POST http://localhost:8000/api/meetings \
  -H "Authorization: Bearer $token" -H "Content-Type: application/json" \
  -d '{"title":"Project Sync","scheduled_at":"2025-09-30 12:00:00","duration_minutes":30}'
```

4) Invite guest
```
curl -s -X POST http://localhost:8000/api/meetings/MEETING_ID/invite \
  -H "Authorization: Bearer $token" -H "Content-Type: application/json" \
  -d '{"invitee_email":"guest@example.com"}'
```

5) Guest accepts (login as guest, then):
```
curl -s -X POST http://localhost:8000/api/invitations/INVITATION_ID/accept \
  -H "Authorization: Bearer GUEST_TOKEN"
```

6) Start and end recording
```
curl -s -X POST http://localhost:8000/api/meetings/MEETING_ID/recordings/start -H "Authorization: Bearer $token"
curl -s -X POST http://localhost:8000/api/meetings/MEETING_ID/recordings/end -H "Authorization: Bearer $token"
```

7) Generate AI notes
```
curl -s -X POST http://localhost:8000/api/meetings/MEETING_ID/notes -H "Authorization: Bearer $token"
```

8) End meeting
9) WebRTC presence + signaling (conceptual)
```
# Both participants join presence
curl -s -X POST http://localhost:8000/api/meetings/MEETING_ID/presence/join -H "Authorization: Bearer OWNER_TOKEN"
curl -s -X POST http://localhost:8000/api/meetings/MEETING_ID/presence/join -H "Authorization: Bearer GUEST_TOKEN"

# List meetings with pagination/sorting
curl -s -X GET "http://localhost:8000/api/meetings?per_page=5&sort_by=scheduled_at&sort_dir=desc" -H "Authorization: Bearer $token"

# Owner sends offer to guest (id 2)
curl -s -X POST http://localhost:8000/api/meetings/MEETING_ID/rtc/send -H "Authorization: Bearer OWNER_TOKEN" -H "Content-Type: application/json" -d '{"to_user_id":2,"type":"offer","payload":{"sdp":"v=0..."}}'

# Guest polls inbox and acknowledges
curl -s http://localhost:8000/api/meetings/MEETING_ID/rtc/inbox -H "Authorization: Bearer GUEST_TOKEN"
curl -s -X POST http://localhost:8000/api/meetings/MEETING_ID/rtc/ack -H "Authorization: Bearer GUEST_TOKEN" -H "Content-Type: application/json" -d '{"ids":[1]}'
```
```
curl -s -X POST http://localhost:8000/api/meetings/MEETING_ID/end -H "Authorization: Bearer $token"
```

## Testing
```
php artisan test
```
By default, tests use SQLite in-memory via phpunit.xml.

## Error handling
- Validation errors: HTTP 422 with `errors` map (Laravel default)
- Authorization failures: HTTP 403 with JSON body `{ "error": "forbidden", "message": "..." }`
- Idempotent states (e.g., already started/ended): HTTP 200 with JSON `{ "error": "already_started|already_ended", "message": "..." }`
- Not found resources (e.g., no active recording, file missing): HTTP 404 with JSON `{ "error": "no_active_recording|file_unavailable", "message": "..." }`

## Webhooks for domain events
Set `DOMAIN_EVENTS_WEBHOOK` in `.env` to receive POST callbacks for all domain events.
Payload example:
```
{
  "type": "MeetingScheduled",
  "timestamp": "2025-09-30T12:34:56Z",
  "payload": { /* event fields */ }
}
```

## Architecture Notes
- Meetings own invitations, recordings, and AI notes. Owner-only authorization guards all actions.
- Secure join link: a unique `join_code` is generated and returned as `join_link = {APP_URL}/join/{join_code}`.
- Mock Recording: when ending a recording we persist a small text file to `storage/app/recordings/...` and store the path in DB for download.
- Mock AI Notes: a deterministic placeholder transcript + key points + sentiment are generated and stored in `ai_notes`.
- WebRTC Signaling: presence tracked in `meeting_participants`; signaling messages persisted in `rtc_signals` and delivered via REST inbox polling. Messages are returned ordered by id; clients may pass `since_id` and should acknowledge processed message ids.

## Repository Hygiene
- Feature branch: `feat/video-backend-assignment`
- Run tests and style checks before pushing.

## License
MIT
