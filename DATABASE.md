# Smart Campus OFPPT Database

## Core Tables

- `users`: unified accounts with `role`, `approval_status`, `group_id`, and login metadata.
- `filieres`: OFPPT training paths.
- `groups`: student groups linked to filieres.
- `modules`: training subjects.
- `rooms`: classrooms, labs, workshops, amphitheaters.
- `timetable_sessions`: recurring weekly/date-range sessions.
- `attendances`: one attendance record per session and stagiaire.
- `qr_attendance_sessions`: secure QR token and fallback code per session.
- `attendance_attempts`: suspicious or rejected attendance attempts.
- `conversations`: private or administrative chat threads.
- `conversation_participants`: authorized conversation users and per-user `last_read_at` state.
- `messages`: escaped chat message history with `is_read` for private unread badges.
- `risk_scores`: calculated stagiaire risk indicators.
- `passkeys`: passkey-ready credential storage.
- `notifications`: Laravel database notifications.

## Key Relationships

- A stagiaire `users.group_id` belongs to one `groups.id`.
- A group belongs to a filiere.
- A formateur teaches groups/modules through `formateur_group` and `formateur_module`.
- A timetable session belongs to one group, module, formateur, and room.
- Attendance belongs to a timetable session and stagiaire.
- Conversations have many participants and messages.
- Risk score belongs to a stagiaire.

## Business Rules

- Exactly four roles are supported: `directeur`, `surveillant`, `formateur`, `stagiaire`.
- Login is passkey-first in the UI, while email/password remains the stable fallback.
- Self-registered stagiaires default to `pending` and cannot access the app.
- Directeur and Surveillant General can approve or reject stagiaires.
- Directeur can create Surveillant General and Formateur accounts.
- Timetable creation blocks conflicts for room, formateur, and group.
- QR/code attendance requires authenticated approved stagiaire, correct group, valid token/code, unexpired session, no duplicate check-in, and allowed campus IP.
- Suspicious attempts are indicators only; they do not automatically punish a stagiaire.
- Chat contacts are backend-filtered by role and teaching group permissions.
- Stagiaire accounts cannot message the Directeur, other stagiaires, or unauthorized groups.
- CampusAI receives only role-authorized context.
