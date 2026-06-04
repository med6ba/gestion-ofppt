# Smart Campus OFPPT Pitch

## Problem

OFPPT campus life is often split across manual attendance sheets, timetable changes, informal messages, and delayed administrative follow-up. This makes absences, late arrivals, room conflicts, and student risk harder to see early.

## Solution

Smart Campus OFPPT centralizes daily campus operations in one Laravel application for one establishment. It gives each role a focused workspace: administration sees global indicators, the Surveillant General manages planning and approvals, formateurs handle attendance, and stagiaires follow their schedule from mobile.

## Modules

- Authentication, roles, and approval workflow.
- Timetable management with conflict detection.
- Manual and QR/code attendance with campus network anti-fraud checks.
- Internal role-aware chat with unread counters and secure contact rules.
- Notifications.
- Intelligent dashboards.
- Smart risk score.
- PWA mobile support.
- CampusAI assistant with Groq integration when configured.

## Innovations

- QR/code attendance is validated by group, token expiry, duplicate check-in, account approval, and IP range.
- Suspicious attempts are logged for administrative review without automatic punishment.
- Risk score combines absences, late arrivals, suspicious attempts, and attendance rate.
- Room occupancy helps improve room allocation.
- Permanent QR badge login gives stagiaires fast access without exposing passwords.
- CampusAI answers from role-scoped Smart Campus data only.

## Demo Flow

1. Directeur logs in.
2. Directeur checks dashboards and approves pending stagiaire.
3. Surveillant General creates a session and shows conflict blocking.
4. Formateur opens today session.
5. Formateur generates QR/code attendance.
6. Stagiaire scans QR or enters code.
7. Backend validates group/token/IP rules.
8. Attendance appears in dashboards.
9. Surveillant General opens risk report.
10. Users exchange messages in chat.
11. Mobile PWA install/offline fallback is shown.

## Value Added

Smart Campus OFPPT reduces manual work, improves attendance traceability, makes timetable conflicts visible immediately, and gives administration early signals for student follow-up.

## Jury Focus

- Show a real login for each role, not a fake demo screen.
- Demonstrate conflict detection by attempting to save an occupied room/session.
- Generate a QR/code and explain token expiry, group validation, duplicate check-in, and IP rules.
- Open attendance reports to show repeated absences, risk score, and suspicious attempts.
- Use the chat to show that stagiaires only see authorized contacts.
