<x-layouts.app :title="__('messages.badge.title')">
    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-black text-slate-800">{{ __('messages.badge.heading') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('messages.badge.subtitle') }}</p>
                </div>
                <a href="{{ route('stagiaire.badge.pdf') }}" class="sc-btn sc-btn-primary">{{ __('messages.common.download_pdf') }}</a>
            </div>

            <div class="mt-6 overflow-x-auto">
                <div class="mx-auto grid min-h-[340px] max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg md:grid-cols-[230px_1fr]">
                    <div class="relative bg-primary p-5 text-white">
                        <div class="absolute inset-x-0 bottom-0 h-24 bg-campus-500"></div>
                        <img class="relative h-16 w-16 rounded-lg bg-white object-contain p-1" src="{{ asset('logo/ofppt-logo.png') }}" alt="OFPPT logo">
                        <div class="relative mt-8 flex h-40 items-center justify-center rounded-xl border border-white/25 bg-white/15 text-center text-sm font-bold uppercase text-white/80">
                            {{ __('messages.badge.photo') }}
                        </div>
                        <div class="relative mt-5 text-xs font-semibold uppercase tracking-normal text-white/80">Smart Campus OFPPT</div>
                    </div>

                    <div class="grid gap-5 p-6 md:grid-cols-[1fr_230px]">
                        <div>
                            <div class="text-xs font-black uppercase text-primary">{{ __('messages.badge.student') }}</div>
                            <h3 class="mt-2 text-3xl font-black text-slate-900">{{ $stagiaire->name }}</h3>
                            <div class="mt-5 grid gap-3 text-sm">
                                <div>
                                    <div class="sc-label">{{ __('messages.common.filiere') }}</div>
                                    <div class="mt-1 font-bold text-slate-800">{{ $stagiaire->filiereName() }}</div>
                                </div>
                                <div>
                                    <div class="sc-label">{{ __('messages.common.group') }}</div>
                                    <div class="mt-1 font-bold text-slate-800">{{ $stagiaire->group?->code ?? __('messages.common.no_group') }}</div>
                                </div>
                                <div>
                                    <div class="sc-label">{{ __('messages.badge.badge_id') }}</div>
                                    <div class="mt-1 font-mono text-sm font-bold text-slate-800">{{ $stagiaire->badge_id }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <img class="h-48 w-48 rounded-lg bg-white p-2" src="{{ $qrDataUri }}" alt="QR login code">
                            <div class="mt-3 text-center text-xs font-bold text-slate-500">{{ __('messages.badge.secure_qr') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <aside class="space-y-4">
            <section class="sc-card p-5">
                <h2 class="text-lg font-black">{{ __('messages.badge.information') }}</h2>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">{{ __('messages.common.email') }}</span>
                        <span class="text-right font-semibold">{{ $stagiaire->email }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">{{ __('messages.common.status') }}</span>
                        <span class="sc-badge bg-emerald-100 text-emerald-700">{{ __('messages.status.'.$stagiaire->approval_status) }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">{{ __('messages.common.matricule') }}</span>
                        <span class="font-semibold">{{ $stagiaire->registration_number ?? __('messages.common.not_provided') }}</span>
                    </div>
                </div>
            </section>

            <section class="sc-card p-5">
                <h2 class="text-lg font-black">{{ __('messages.badge.cni_note_title') }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ __('messages.badge.cni_note_text') }}</p>
                <a href="{{ route('profile.show', $stagiaire) }}" class="sc-btn sc-btn-secondary mt-4 w-full">{{ __('messages.badge.edit_profile') }}</a>
            </section>
        </aside>
    </div>
</x-layouts.app>
