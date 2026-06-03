# Smart Campus OFPPT Hackathon Notes

This file translates the provided PDF content into an implementation and demo checklist for the local Laravel app.

## Competition Objective

Build an innovative Smart Campus solution for one OFPPT establishment. The app must solve daily campus problems with a working demo, clear UX, database-backed modules, and a short final pitch.

## Mandatory Base

- Authentication with login, logout, roles, and secure sessions.
- Database-backed dashboard showing user information, notifications, statistics, and navigation.
- Modern responsive UI for desktop and mobile.
- Local deployment ready on port `9999`.
- Source code, technical documentation, and final pitch.

## Authentication Module

- One unified login portal for Directeur, Surveillant General, Formateur, and Stagiaire.
- Passkey/biometric access is presented first through the `Continue with Passkey` action.
- Email/password remains available as fallback.
- Directeur creates Surveillant General and Formateur accounts.
- Stagiaire self-registers and starts as pending.
- Pending stagiaire sees: `Your account is waiting for approval.`
- Directeur and Surveillant General can approve or reject stagiaires.

## Attendance Module

- Formateur can mark Present, Absent, Late, and Justified manually.
- Formateur can generate QR attendance for a selected timetable session.
- QR has a secure token, group, session, and expiry time.
- Short fallback code is displayed under the QR.
- Stagiaire check-in validates authentication, approval, group, expiry, duplicate attendance, and allowed IP ranges.
- Unauthorized home/mobile/VPN network attempts are rejected and recorded as suspicious attempts.
- Reports show attendance rate, present, absent, late, justified, suspicious attempts, repeated absences, and risk indicators.

## Chat Module

- Directeur can contact Surveillant General, Formateurs, and Stagiaires.
- Surveillant General can contact all operational roles.
- Formateur can contact Directeur, Surveillant General, and stagiaires from groups he teaches.
- Stagiaire can contact only own formateurs and Surveillant General.
- Stagiaire cannot contact Directeur, other stagiaires, or unauthorized groups.
- Backend checks every contact, conversation, and send action.
- UI includes message history, unread badges, responsive layout, and polling fallback for local demo.

## Suggested Demo Flow

1. Login as Directeur and show global dashboard.
2. Approve the pending stagiaire account.
3. Login as Surveillant General and create/edit a timetable session.
4. Attempt a room, formateur, or group conflict and show the blocked save.
5. Login as Formateur and open today's attendance session.
6. Mark manual attendance, then generate QR/code attendance.
7. Login as Stagiaire and enter the code.
8. Explain group, token, expiry, duplicate, and IP validation.
9. Open attendance reports to show repeated absences and suspicious attempts.
10. Open chat and show authorized contacts only.
11. Show mobile/PWA install and offline fallback.

## Evaluation Alignment

- Innovation: QR/code attendance, IP anti-fraud, risk score, CampusAI fallback.
- Features: auth, timetable, attendance, chat, dashboards, notifications, PWA.
- Technical quality: Laravel MVC, migrations, requests, middleware, policies, services, tests.
- UX/UI: Manar-inspired clean shell, responsive cards, mobile-friendly chat and attendance.
- Presentation: 5-10 minute flow focused on problem, solution, demo, value added.
