# Smart Campus OFPPT

Smart Campus OFPPT is a focused Laravel web app for one OFPPT establishment. It replaces the old broad school-management runtime with a clean campus workflow for Directeur, Surveillant General, Formateur, and Stagiaire.

## Features

- Passkey-first login architecture with email/password fallback.
- Four roles only: Directeur, Surveillant General, Formateur, Stagiaire.
- Stagiaire self-registration with pending approval.
- Timetable management with room, group, and formateur conflict detection.
- Manual attendance plus QR/code attendance with IP range validation and suspicious attempt reporting.
- Suspicious attendance attempt logging.
- Role-aware dashboards with Chart.js metrics.
- Internal private chat with backend contact authorization, unread counters, and polling fallback.
- Database notifications for approvals, schedule changes, messages, risk, and security alerts.
- Smart risk score for stagiaires.
- CampusAI service with Groq support when `GROQ_API_KEY` exists and safe fallback answers otherwise.
- PWA manifest, service worker, offline fallback, and icon placeholders.

## Install

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database named `smart_campus_ofppt`, then update `.env` credentials if needed.

```bash
php artisan migrate:fresh --seed
npm run dev
php artisan serve --host=127.0.0.1 --port=9999
```

Open `http://localhost:9999`.

## Demo Accounts

All demo passwords are `password`.

- Directeur: `directeur@ofppt.test`
- Surveillant General: `surveillant@ofppt.test`
- Formateur: `formateur@ofppt.test`
- Stagiaire approved: `stagiaire@ofppt.test`
- Stagiaire pending: `pending@ofppt.test`

## Main Workflows

Attendance:

1. Formateur opens Attendance.
2. Formateur selects today session.
3. Formateur marks manual attendance or generates QR/code.
4. Stagiaire scans QR or enters fallback code.
5. Backend checks account approval, group, token expiry, duplicate check-in, and campus IP.
6. Attendance and dashboards update.
7. Suspicious scans from unauthorized networks are rejected and logged for review.

Timetable:

1. Surveillant General manages resources.
2. Surveillant General creates or edits sessions.
3. Backend blocks same-time conflicts for room, formateur, and group.
4. Affected formateur and stagiaires receive notifications.

Chat:

1. Users open Chat.
2. Contacts are filtered by role and group.
3. Backend verifies every conversation/message action.
4. Messages are escaped in Blade, unread counters are tracked, and the UI refreshes by polling fallback.

CampusAI:

- If `GROQ_API_KEY` is set, CampusAI sends role-filtered campus context to Groq.
- If no key exists, the app returns safe database fallback answers.

## PDF / Jury Alignment

The provided Smart Campus PDFs are now reflected in the app:

- Authentication PDF: unified portal, passkey priority button, role redirects, pending stagiaire approval message, RBAC.
- Attendance PDF: manual marking, QR token, fallback code, campus IP verification, suspicious attempts, repeated absence reporting.
- Chat PDF: role-based contacts, secure 403 checks, unread counters, message history, responsive chat UI, polling fallback for local demo.
- Hackathon PDF: demo-ready dashboard, local port `9999`, documentation, pitch flow, and mobile/PWA support.

## Legacy Cleanup

Old modules such as tenant management, subscriptions/financial features, LMS, achievements, old absence module, EDT React UI, API module, and Lineone admin routes are disabled from runtime. Dirty or untracked legacy folders were not deleted, but their routes/providers/migrations are inactive for Smart Campus.
