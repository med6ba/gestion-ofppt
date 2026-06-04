<x-layouts.app title="Mon Suivi">
    @php
        $statusLabels = [
            'present' => 'Present',
            'absent' => 'Absent',
            'late_pending' => 'Retard en attente',
            'late_validated' => 'Retard valide',
            'late_rejected' => 'Retard refuse',
            'severe_late_pending' => 'Retard important en attente',
            'severe_late_validated' => 'Retard important valide',
            'severe_late_rejected' => 'Retard important refuse',
            'justified' => 'Justifie',
        ];
        $statusTone = fn (?string $status) => match ($status) {
            'present', 'late_validated', 'severe_late_validated', 'justified' => 'bg-emerald-100 text-emerald-700',
            'absent', 'late_rejected', 'severe_late_rejected' => 'bg-rose-100 text-rose-700',
            'late_pending', 'severe_late_pending' => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-700',
        };
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <section class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">Podium</div>
            <div class="mt-3 text-3xl font-bold">{{ $presenceProfile->xp_points }} XP</div>
            <div class="mt-1 text-xs font-semibold text-slate-500">{{ $presenceProfile->rank_level }}</div>
        </section>
        @foreach ([
            ['label' => 'Presents', 'value' => $summary['present']],
            ['label' => 'Retards valides', 'value' => $summary['acceptedLate']],
            ['label' => 'Retards en attente', 'value' => $summary['pendingLate']],
            ['label' => 'Absences', 'value' => $summary['absent']],
        ] as $item)
            <div class="sc-card p-5">
                <div class="text-sm font-medium text-slate-500">{{ $item['label'] }}</div>
                <div class="mt-3 text-3xl font-bold">{{ $item['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="sc-card">
            <div class="border-b border-slate-100 p-5 flex items-center justify-between">
                <h2 class="font-bold">Dernières absences</h2>
                <a href="{{ route('attendance.leaderboard') }}" class="text-sm font-medium text-campus-600 hover:text-campus-700">
                    Voir le Podium
                </a>
            </div>

        <div class="mt-4 grid gap-3">
            @forelse ($attendances as $attendance)
                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate font-semibold">{{ $attendance->session?->module?->name ?? 'Seance' }}</div>
                            <div class="mt-1 text-sm text-slate-500">
                                {{ $attendance->session?->timeLabel() ?? '--:--' }}
                                {{ $attendance->session?->room ? ' | '.$attendance->session->room->code : '' }}
                                {{ $attendance->session?->formateur ? ' | '.$attendance->session->formateur->name : '' }}
                            </div>
                        </div>
                        <span class="sc-badge {{ $statusTone($attendance->status) }}">{{ $statusLabels[$attendance->status] ?? $attendance->status }}</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-500">{{ $attendance->marked_at?->format('Y-m-d H:i') ?? $attendance->created_at?->format('Y-m-d H:i') }} | {{ $attendance->method }}</div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Aucun historique pour le moment.</p>
            @endforelse
        </div>

        <div class="mt-4">{{ $attendances->links() }}</div>
    </section>
</x-layouts.app>
