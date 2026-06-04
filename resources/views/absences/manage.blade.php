<x-layouts.app :title="__('messages.absences.manage_title')">
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
                <h2 class="text-xl font-black">{{ __('messages.absences.manage_heading') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('messages.absences.manage_subtitle') }}</p>
            </div>
            <span class="sc-badge bg-amber-100 text-amber-700">{{ __('messages.absences.pending_count', ['count' => $pendingCount]) }}</span>
        </div>

        <div class="mt-5 grid gap-4">
            @forelse ($requests as $requestItem)
                <article class="rounded-lg border border-slate-200 p-4">
                    <div class="grid gap-4 xl:grid-cols-[1fr_420px]">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-black text-slate-800">{{ $requestItem->stagiaire?->name ?? 'Stagiaire' }}</h3>
                                <span class="sc-badge {{ $tone($requestItem->status) }}">{{ __('messages.status.'.$requestItem->status) }}</span>
                            </div>
                            <div class="mt-2 text-sm text-slate-500">
                                {{ $requestItem->stagiaire?->group?->code ?? __('messages.common.no_group') }} | {{ $requestItem->absence_date->format('Y-m-d') }} | {{ substr($requestItem->starts_at, 0, 5) }} - {{ substr($requestItem->ends_at, 0, 5) }}
                            </div>
                            <div class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">{{ $requestItem->reason }}</div>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-400">
                                <span>{{ __('messages.common.created_at') }} {{ $requestItem->created_at->format('Y-m-d H:i') }}</span>
                                @if ($requestItem->attachment_path)
                                    <a href="{{ route('absences.attachment', $requestItem) }}" class="font-bold text-primary">{{ __('messages.common.attachment') }}</a>
                                @endif
                            </div>
                            @if ($requestItem->surveillant_note)
                                <div class="mt-3 rounded-lg bg-white p-3 text-sm text-slate-600 ring-1 ring-slate-200">{{ __('messages.common.note') }}: {{ $requestItem->surveillant_note }}</div>
                            @endif
                        </div>

                        @if ($requestItem->status === 'pending')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <form method="POST" action="{{ route('absences.approve', $requestItem) }}" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                                    @csrf
                                    <label class="sc-label text-emerald-700">{{ __('messages.common.note') }}</label>
                                    <textarea name="surveillant_note" class="sc-input mt-1 min-h-20 bg-white" placeholder="{{ __('messages.common.optional_note') }}"></textarea>
                                    <x-confirmation-modal title="Confirmer l'approbation" message="Êtes-vous sûr de vouloir approuver cette absence ?" confirmText="{{ __('messages.common.approve') }}" cancelText="{{ __('messages.common.cancel') }}" type="primary">
                                        <button class="sc-btn mt-3 w-full border border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-100">{{ __('messages.common.approve') }}</button>
                                    </x-confirmation-modal>
                                </form>
                                <form method="POST" action="{{ route('absences.reject', $requestItem) }}" class="rounded-lg border border-rose-200 bg-rose-50 p-3">
                                    @csrf
                                    <label class="sc-label text-rose-700">{{ __('messages.common.note') }}</label>
                                    <textarea name="surveillant_note" class="sc-input mt-1 min-h-20 bg-white" placeholder="{{ __('messages.common.optional_note') }}"></textarea>
                                    <x-confirmation-modal title="Confirmer le refus" message="Êtes-vous sûr de vouloir refuser cette absence ?" confirmText="{{ __('messages.common.reject') }}" cancelText="{{ __('messages.common.cancel') }}" type="danger">
                                        <button class="sc-btn mt-3 w-full border border-rose-200 bg-white text-rose-700 hover:bg-rose-100">{{ __('messages.common.reject') }}</button>
                                    </x-confirmation-modal>
                                </form>
                            </div>
                        @else
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                                {{ __('messages.absences.processed_by', ['name' => $requestItem->reviewer?->name ?? 'Administration', 'date' => $requestItem->reviewed_at?->format('Y-m-d H:i') ?? '-']) }}
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <p class="text-sm text-slate-500">{{ __('messages.absences.empty') }}</p>
            @endforelse
        </div>

        <div class="mt-5">{{ $requests->links() }}</div>
    </section>
</x-layouts.app>
