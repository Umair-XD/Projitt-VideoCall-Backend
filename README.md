# Projitt Video Conferencing Backend (Laravel + SQLite)

Backend API for a mini video conferencing app.
Supports meetings, invitations, recordings (mocked), AI notes (mocked), and WebRTC presence/signaling.
Authentication is handled using **JWT**.

---

## 🚀 Features

* JWT Authentication (register, login, refresh, logout, get profile).
* Meetings: schedule, update, start, end with secure join codes.
* Invitations: invite by email or user ID, accept/reject/propose time.
* Recordings (mocked): start/end recordings, store metadata, download file.
* AI Notes (mocked): generate transcript, key points, sentiment.
* Presence & WebRTC signaling: join/leave meetings, list participants, send/receive RTC signals.

---

## 🛠️ Tech Stack

* **Framework:** Laravel 11
* **Auth:** JWT (php-open-source-saver/jwt-auth)
* **Database:** SQLite (simple file-based, no server needed)
* **Storage:** Local storage for recordings
* **Docs:** Postman collection & environment provided

---

## ⚙️ Setup Instructions

### 1. Clone & Install

```bash
git clone https://github.com/your-username/projitt-backend.git
cd projitt-backend
composer install
```

### 2. Environment Config

Copy `.env.example` to `.env`:

```bash
cp .env.example .env
```

Generate app key (this writes `APP_KEY` into `.env`)

```bash
php artisan key:generate
```

Already set in `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=./database/database.sqlite
DB_FOREIGN_KEYS=true
```

Generate JWT secret:

```bash
php artisan jwt:secret
```

### 3. Prepare Database

Create SQLite DB file:

```bash
touch database/database.sqlite
```

Run migrations & seeders:

```bash
php artisan migrate --seed
```

### 4. Run Server

```bash
php artisan serve
```

API base URL:
👉 [http://127.0.0.1:8000/api](http://127.0.0.1:8000/api)

---

## 📬 Postman Setup

1. Import **projitt-collection.json**.
2. Import **projitt-env.json**.
3. First call: `Auth → Register` or `Auth → Login`.
4. Token auto-saves into `{{token}}`.
5. All requests use `Authorization: Bearer {{token}}`.

---

## 🧪 Example Endpoints

* `POST /auth/register` → register user
* `POST /auth/login` → login and get token
* `GET /meetings` → list owner’s meetings
* `POST /meetings` → create meeting
* `PUT /meetings/{id}` → update meeting
* `POST /meetings/{id}/start` → start meeting
* `POST /meetings/{id}/end` → end meeting
* `POST /meetings/{id}/invite` → send invitation
* `POST /invitations/{id}/accept` → accept invitation
* `POST /invitations/{id}/reject` → reject invitation
* `POST /invitations/{id}/propose` → propose new time
* `POST /meetings/{id}/recordings/start` → start recording
* `POST /meetings/{id}/recordings/end` → end recording
* `GET /recordings/{id}/download` → download recording
* `POST /meetings/{id}/notes` → generate AI notes
* `POST /meetings/{id}/presence/join` → join meeting
* `POST /meetings/{id}/presence/leave` → leave meeting
* `GET /meetings/{id}/participants` → list participants
* `POST /meetings/{id}/rtc/send` → send RTC message
* `GET /meetings/{id}/rtc/inbox` → poll inbox
* `POST /meetings/{id}/rtc/ack` → acknowledge signals

---

## 📌 Assumptions & Limitations

* **Recording**: mocked as `.txt` files under `storage/app/recordings`.
* **AI Notes**: mocked with placeholder transcript, key points, sentiment.
* **Video/Audio**: not implemented, presence & signaling only simulate RTC.
* **SQLite**: chosen for easy setup (no MySQL/Postgres required).

---
## 📑 Documentation

- [Architecture Note](./ARCHITECTURE_NOTE.md)
- [Postman Collection](./docs/projitt-collection.json)
- [Postman Environment](./docs/projitt-env.json)
