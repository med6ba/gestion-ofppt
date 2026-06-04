export function initTimetableRealtime(groupId) {
    if (!window.Echo || !groupId) return;

    window.Echo.private(`timetable.group.${groupId}`)
        .listen('.timetable.published', (e) => {
            window.dispatchEvent(new CustomEvent('timetable-updated', { detail: e }));
        })
        .listen('.timetable.updated', (e) => {
            window.dispatchEvent(new CustomEvent('timetable-updated', { detail: e }));
        })
        .listen('.session.cancellation.approved', (e) => {
            window.dispatchEvent(new CustomEvent('timetable-updated', { detail: e }));
        });
}
