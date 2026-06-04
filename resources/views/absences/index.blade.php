<x-layouts.app :title="__('messages.absences.title')">
    @php
        $tone = fn (string $status) => match ($status) {
            'approved' => 'bg-emerald-100 text-emerald-700',
            'rejected' => 'bg-rose-100 text-rose-700',
            default => 'bg-amber-100 text-amber-700',
        };
    @endphp

    <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
        <aside class="sc-card p-5">
            <h2 class="text-lg font-black">{{ __('messages.absences.new_request') }}</h2>
            <form method="POST" action="{{ route('absences.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-4">
                @csrf
                <div>
                    <label class="sc-label">{{ __('messages.absences.absence_date') }}</label>
                    <input class="sc-input mt-1" type="date" name="absence_date" value="{{ old('absence_date') }}" required>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="sc-label">{{ __('messages.absences.start_time') }}</label>
                        <input class="sc-input mt-1" type="time" name="starts_at" value="{{ old('starts_at') }}" required>
                    </div>
                    <div>
                        <label class="sc-label">{{ __('messages.absences.end_time') }}</label>
                        <input class="sc-input mt-1" type="time" name="ends_at" value="{{ old('ends_at') }}" required>
                    </div>
                </div>
                <div>
                    <label class="sc-label">{{ __('messages.absences.reason') }}</label>
                    <textarea class="sc-input mt-1 min-h-28" name="reason" required>{{ old('reason') }}</textarea>
                </div>
                <div>
                    <label class="sc-label">{{ __('messages.common.attachment') }}</label>
                    <input class="sc-input mt-1" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <button class="sc-btn sc-btn-primary">{{ __('messages.absences.send_request') }}</button>
            </form>
        </aside>

        <section class="sc-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-black">{{ __('messages.absences.my_requests') }}</h2>
                <span class="sc-badge bg-slate-100 text-slate-600">{{ __('messages.absences.requests_count', ['count' => $requests->total()]) }}</span>
            </div>

            <div class="mt-4 grid gap-3">
                @forelse ($requests as $requestItem)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="font-bold">{{ $requestItem->absence_date->format('Y-m-d') }} | {{ substr($requestItem->starts_at, 0, 5) }} - {{ substr($requestItem->ends_at, 0, 5) }}</div>
                                <div class="mt-2 text-sm text-slate-600">{{ $requestItem->reason }}</div>
                                @if ($requestItem->surveillant_note)
                                    <div class="mt-2 text-sm text-slate-500">{{ __('messages.common.note') }}: {{ $requestItem->surveillant_note }}</div>
                                @endif
                                @if ($requestItem->attachment_path)
                                    <a href="{{ route('absences.attachment', $requestItem) }}" class="mt-2 inline-flex text-xs font-bold text-primary">{{ __('messages.common.attachment') }}</a>
                                @endif
                            </div>
                            <span class="sc-badge {{ $tone($requestItem->status) }}">{{ __('messages.status.'.$requestItem->status) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('messages.absences.empty') }}</p>
                @endforelse
            </div>

            <div class="mt-4">{{ $requests->links() }}</div>
        </section>
    </div>
</x-layouts.app>
