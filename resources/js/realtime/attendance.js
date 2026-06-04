export function initAttendanceRealtime(groupId, sessionId) {
    if (!window.Echo) return;

    if (groupId) {
        window.Echo.private(`timetable.group.${groupId}`)
            .listen('.attendance.session.closed', (e) => {
                window.dispatchEvent(new CustomEvent('attendance-session-closed', { detail: e }));
            });
    }

    if (sessionId) {
        window.Echo.private(`attendance.session.${sessionId}`)
            .listen('.attendance.session.started', (e) => {
                window.dispatchEvent(new CustomEvent('attendance-session-started', { detail: e }));
            })
            .listen('.attendance.marked', (e) => {
                window.dispatchEvent(new CustomEvent('attendance-marked', { detail: e }));
            })
            .listen('.late.request.created', (e) => {
                window.dispatchEvent(new CustomEvent('late-request-created', { detail: e }));
            })
            .listen('.late.request.reviewed', (e) => {
                window.dispatchEvent(new CustomEvent('late-request-reviewed', { detail: e }));
            });
    }
}
