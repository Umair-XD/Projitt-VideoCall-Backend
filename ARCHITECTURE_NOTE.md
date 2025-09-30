# Architecture Note

This document explains the backend design choices for the **Projitt Mini Video Conferencing App**.

---

## 🎥 Recording API Design
- When a meeting recording is **started**, a new row is created in the `recordings` table:
  - `meeting_id`
  - `participants` (snapshot at the time of start)
  - `started_at`
- When a recording is **ended**:
  - A dummy `.txt` file is generated under `storage/app/recordings/`.
  - `file_path` and `ended_at` are updated in the database.
- The **download** endpoint streams the file if it exists.  
➡️ This mocks real video/audio recording without relying on external services.

---

## 🤖 AI Transcription & Notes Flow
- After a meeting, the endpoint `/meetings/{id}/notes` generates a mocked transcript and notes.
- Data stored in the `ai_notes` table includes:
  - `transcript_text` → mocked meeting transcript
  - `key_points` → JSON array of bullet points
  - `sentiment` → one of: `neutral`, `positive`, `negative`
- This simulates **AI-powered summarization** without requiring ML infrastructure.

---

## 👥 Presence & WebRTC Signaling
### Presence
- Presence is tracked in `meeting_participants`.
- A user must `join` a meeting before sending/receiving RTC messages.
- Participants are listed with join/leave timestamps.

### Signaling
- Signaling messages (offer/answer/ICE candidate) are stored in `rtc_signals`.
- Each signal has `from_user_id` → `to_user_id`.
- Clients poll `/rtc/inbox` for new messages and then acknowledge them using `/rtc/ack`.
- REST-based signaling avoids the need for a WebSocket server but demonstrates the backend contract for WebRTC.

---

## 🔐 Data Ownership & Security
- **Meeting owner** can:
  - Create, update, start/end meetings
  - View participants
  - Manage recordings and notes
- **Invitees** can:
  - Accept/reject/propose invites sent to them
  - Join/leave meetings
  - Exchange RTC messages if accepted
- All protected endpoints are secured with **JWT authentication**.

---

## 🗄️ Storage & Database
- Default DB: **SQLite** (file-based, no setup required).
- Schema includes:
  - `users`
  - `meetings`
  - `invitations`
  - `meeting_participants`
  - `recordings`
  - `ai_notes`
  - `rtc_signals`
- Easily switchable to **MySQL** or **Postgres** by updating `.env`.

---

## ⚠️ Limitations
- No real-time video/audio streaming; backend simulates presence & signaling only.
- Recording and AI features are **mocked**.
- REST polling for signaling is less efficient than WebSockets but is simpler to implement for demo purposes.

---
