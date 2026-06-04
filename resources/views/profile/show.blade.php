<x-layouts.app :title="__('messages.profile.title')">
    @php
        $statusTone = fn (?string $status) => match ($status) {
            'present', 'late_validated', 'severe_late_validated', 'justified' => 'bg-emerald-100 text-emerald-700',
            'absent', 'late_rejected', 'severe_late_rejected' => 'bg-rose-100 text-rose-700',
            'late_pending', 'severe_late_pending', 'pending' => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-700',
        };
        $viewer = auth()->user();
        $canEditCni = $profile->isStagiaire()
            && ($viewer->id === $profile->id || $viewer->isDirecteur() || $viewer->isSurveillant());
    @endphp
    <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
        <aside class="sc-card p-5">
            <h2 class="text-xl font-bold">{{ $profile->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $profile->roleLabel() }}{{ $profile->group ? ' | '.$profile->group->code : '' }}</p>
            <div class="mt-5 space-y-3 text-sm">
                <div>
                    <div class="sc-label">{{ __('messages.common.email') }}</div>
                    <div class="mt-1">{{ $profile->email }}</div>
                </div>
                <div>
                    <div class="sc-label">{{ __('messages.common.status') }}</div>
                    <div class="mt-1 capitalize">{{ __('messages.status.'.$profile->approval_status) }}</div>
                </div>
                @if ($profile->isStagiaire())
                    <div>
                        <div class="sc-label">{{ __('messages.common.cni') }}</div>
                        <div class="mt-1 font-mono font-semibold">{{ $profile->cni ?? __('messages.common.not_provided') }}</div>
                    </div>
                    <div>
                        <div class="sc-label">{{ __('messages.common.filiere') }}</div>
                        <div class="mt-1">{{ $profile->filiereName() }}</div>
                    </div>
                    @if ($viewer->id === $profile->id)
                        <a href="{{ route('stagiaire.badge') }}" class="sc-btn sc-btn-primary w-full">{{ __('messages.profile.view_badge') }}</a>
                    @endif
                @endif
                @if ($canEditCni)
                    <form method="POST" action="{{ route('profile.update', $profile) }}" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        @csrf
                        @method('PUT')
                        <x-form.label>{{ __('messages.profile.edit_cni') }}</x-form.label>
                        <input class="sc-input mt-1 uppercase" name="cni" value="{{ old('cni', $profile->cni) }}" required>
                        <button class="sc-btn sc-btn-secondary mt-3 w-full">{{ __('messages.profile.save_cni') }}</button>
                    </form>
                @endif
                @if ($profile->isStagiaire() && $profile->riskScore)
                    <div class="rounded-lg {{ $profile->riskScore->level === 'High' ? 'bg-rose-50 text-rose-800' : ($profile->riskScore->level === 'Medium' ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800') }} p-4">
                        <div class="text-sm font-semibold">{{ $profile->riskScore->level }} Risk</div>
                        <div class="mt-1 text-3xl font-bold">{{ $profile->riskScore->score }}</div>
                        <div class="mt-2 text-xs">{{ implode(' | ', $profile->riskScore->reasons ?? []) }}</div>
                    </div>
                @endif
                @if ($profile->isStagiaire() && $profile->presenceProfile)
                    <div class="rounded-lg bg-campus-50 p-4 text-campus-800">
                        <div class="text-sm font-semibold">Podium</div>
                        <div class="mt-1 text-3xl font-bold">{{ $profile->presenceProfile->xp_points }}</div>
                        <div class="mt-2 text-xs">{{ $profile->presenceProfile->rank_level }} | streak {{ $profile->presenceProfile->attendance_streak }}</div>
                    </div>
                @endif
            </div>
        </aside>

        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">{{ __('messages.profile.attendance_history') }}</h2>
            @if ($profile->isStagiaire())
                <div class="mt-4 grid gap-3">
                    @forelse ($profile->attendances->sortByDesc('marked_at') as $attendance)
                        <div class="rounded-lg border border-slate-200 p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="font-semibold">{{ $attendance->session->module->name }}</div>
                                <span class="sc-badge {{ $statusTone($attendance->status) }}">{{ $attendance->status }}</span>
                            </div>
                            <div class="mt-1 text-sm text-slate-500">{{ $attendance->session->timeLabel() }} | {{ $attendance->method }} | {{ $attendance->marked_at?->format('Y-m-d H:i') }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No attendance records yet.</p>
                    @endforelse
                </div>
            @else
                <p class="mt-4 text-sm text-slate-500">Attendance history applies to stagiaires.</p>
            @endif
        </section>
    </div>
</x-layouts.app>
