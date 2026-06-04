<x-layouts.app :title="__('messages.attestations.title')">
    @php
        $tone = fn (string $status) => match ($status) {
            'approved' => 'bg-emerald-100 text-emerald-700',
            'rejected' => 'bg-rose-100 text-rose-700',
            default => 'bg-amber-100 text-amber-700',
        };
    @endphp

    <div class="grid gap-6 xl:grid-cols-[380px_1fr]">
        <aside class="sc-card p-5">
            <h2 class="text-lg font-black">{{ __('messages.attestations.new_request') }}</h2>
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
                <div class="font-bold text-slate-800">{{ $stagiaire->name }}</div>
                <div class="mt-1 text-slate-500">{{ $stagiaire->filiereName() }} | {{ $stagiaire->group?->code ?? __('messages.common.no_group') }}</div>
                <div class="mt-2 text-xs font-bold uppercase text-slate-500">{{ __('messages.common.cni') }}</div>
                <div class="mt-1 font-mono font-bold">{{ $stagiaire->cni ?? __('messages.common.not_provided') }}</div>
            </div>
            <form method="POST" action="{{ route('attestations.store') }}" class="mt-4">
                @csrf
                <button class="sc-btn sc-btn-primary w-full">{{ __('messages.attestations.request_button') }}</button>
            </form>
            @if (blank($stagiaire->cni))
                <a href="{{ route('profile.show', $stagiaire) }}" class="sc-btn sc-btn-secondary mt-3 w-full">{{ __('messages.attestations.add_cni') }}</a>
            @endif
        </aside>

        <section class="sc-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-black">{{ __('messages.attestations.my_requests') }}</h2>
                <span class="sc-badge bg-slate-100 text-slate-600">{{ __('messages.attestations.requests_count', ['count' => $requests->total()]) }}</span>
            </div>

            <div class="mt-4 grid gap-3">
                @forelse ($requests as $requestItem)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="font-bold">{{ __('messages.attestations.document_title') }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ $requestItem->created_at->format('Y-m-d H:i') }} | {{ $requestItem->filiere ?? __('messages.common.not_provided') }}</div>
                                @if ($requestItem->surveillant_note)
                                    <div class="mt-2 text-sm text-slate-600">{{ __('messages.common.note') }}: {{ $requestItem->surveillant_note }}</div>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="sc-badge {{ $tone($requestItem->status) }}">{{ __('messages.status.'.$requestItem->status) }}</span>
                                @if ($requestItem->status === 'approved')
                                    <a href="{{ route('attestations.pdf', $requestItem) }}" class="sc-btn sc-btn-secondary">PDF</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('messages.attestations.empty') }}</p>
                @endforelse
            </div>

            <div class="mt-4">{{ $requests->links() }}</div>
        </section>
    </div>
</x-layouts.app>
