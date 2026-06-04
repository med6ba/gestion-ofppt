<x-layouts.app :title="__('messages.attestations.manage_title')">
    @php
        $tone = fn (string $status) => match ($status) {
            'approved' => 'bg-emerald-100 text-emerald-700',
            'rejected' => 'bg-rose-100 text-rose-700',
            default => 'bg-amber-100 text-amber-700',
        };
    @endphp

    <section class="sc-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-black">{{ __('messages.attestations.manage_heading') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('messages.attestations.manage_subtitle') }}</p>
            </div>
            <span class="sc-badge bg-amber-100 text-amber-700">{{ __('messages.attestations.pending_count', ['count' => $pendingCount]) }}</span>
        </div>

        <div class="mt-5 grid gap-4">
            @forelse ($requests as $requestItem)
                <article class="rounded-lg border border-slate-200 p-4">
                    <div class="grid gap-4 xl:grid-cols-[1fr_420px]">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-black text-slate-800">{{ $requestItem->full_name }}</h3>
                                <span class="sc-badge {{ $tone($requestItem->status) }}">{{ __('messages.status.'.$requestItem->status) }}</span>
                            </div>
                            <div class="mt-2 text-sm text-slate-500">
                                {{ $requestItem->stagiaire?->group?->code ?? __('messages.common.no_group') }} | {{ $requestItem->filiere ?? __('messages.common.not_provided') }} | {{ __('messages.common.cni') }} {{ $requestItem->cni }}
                            </div>
                            <div class="mt-1 text-xs text-slate-400">{{ __('messages.common.created_at') }} {{ $requestItem->created_at->format('Y-m-d H:i') }}</div>
                            @if ($requestItem->surveillant_note)
                                <div class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">{{ __('messages.common.note') }}: {{ $requestItem->surveillant_note }}</div>
                            @endif
                            @if ($requestItem->status === 'approved')
                                <a href="{{ route('attestations.pdf', $requestItem) }}" class="sc-btn sc-btn-secondary mt-3">Download PDF</a>
                            @endif
                        </div>

                        @if ($requestItem->status === 'pending')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <form method="POST" action="{{ route('attestations.approve', $requestItem) }}" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                                    @csrf
                                    <label class="sc-label text-emerald-700">{{ __('messages.common.note') }}</label>
                                    <textarea name="surveillant_note" class="sc-input mt-1 min-h-20 bg-white" placeholder="{{ __('messages.common.optional_note') }}"></textarea>
                                    <button class="sc-btn mt-3 w-full border border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-100">{{ __('messages.common.approve') }}</button>
                                </form>
                                <form method="POST" action="{{ route('attestations.reject', $requestItem) }}" class="rounded-lg border border-rose-200 bg-rose-50 p-3">
                                    @csrf
                                    <label class="sc-label text-rose-700">{{ __('messages.common.note') }}</label>
                                    <textarea name="surveillant_note" class="sc-input mt-1 min-h-20 bg-white" placeholder="{{ __('messages.common.optional_note') }}"></textarea>
                                    <button class="sc-btn mt-3 w-full border border-rose-200 bg-white text-rose-700 hover:bg-rose-100">{{ __('messages.common.reject') }}</button>
                                </form>
                            </div>
                        @else
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                                {{ __('messages.attestations.processed_by', ['name' => $requestItem->reviewer?->name ?? 'Administration', 'date' => $requestItem->reviewed_at?->format('Y-m-d H:i') ?? '-']) }}
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <p class="text-sm text-slate-500">{{ __('messages.attestations.empty') }}</p>
            @endforelse
        </div>

        <div class="mt-5">{{ $requests->links() }}</div>
    </section>
</x-layouts.app>
