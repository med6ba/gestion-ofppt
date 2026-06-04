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

            <div class="mt-6 overflow-x-auto pb-2">
                <div class="mx-auto grid min-h-[360px] max-w-4xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl md:grid-cols-[260px_1fr]">
                    <div class="relative flex flex-col bg-slate-950 p-6 text-white">
                        <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-campus-500 via-ofppt-grey to-primary"></div>
                        <div class="flex items-center gap-3">
                            <img class="h-16 w-16 rounded-2xl bg-white object-contain p-1.5 shadow-lg" src="{{ asset('logo/ofppt-logo.png') }}" alt="OFPPT logo">
                            <div>
                                <div class="text-xs font-black uppercase text-white/60">Smart Campus</div>
                                <div class="text-lg font-black">OFPPT</div>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-1 flex-col justify-center">
                            <div class="mx-auto flex size-32 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-center text-xs font-black uppercase text-white/70 shadow-inner">
                                {{ __('messages.badge.photo') }}
                            </div>
                            <div class="mt-6 rounded-2xl border border-white/15 bg-white/10 p-4">
                                <div class="text-[10px] font-black uppercase text-white/50">{{ __('messages.badge.badge_id') }}</div>
                                <div class="mt-1 break-all font-mono text-xs font-bold text-white">{{ $stagiaire->badge_id }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6 p-6 md:grid-cols-[1fr_230px]">
                        <div class="flex min-w-0 flex-col justify-between">
                            <div>
                                <div class="inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-black uppercase text-primary">{{ __('messages.badge.student') }}</div>
                                <h3 class="mt-4 text-3xl font-black leading-tight text-slate-950">{{ $stagiaire->name }}</h3>
                                <div class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <div class="sc-label">{{ __('messages.common.filiere') }}</div>
                                        <div class="mt-1 font-black text-slate-800">{{ $stagiaire->filiereName() }}</div>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <div class="sc-label">{{ __('messages.common.group') }}</div>
                                        <div class="mt-1 font-black text-slate-800">{{ $stagiaire->group?->code ?? __('messages.common.no_group') }}</div>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <div class="sc-label">{{ __('messages.common.matricule') }}</div>
                                        <div class="mt-1 font-black text-slate-800">{{ $stagiaire->registration_number ?? __('messages.common.not_provided') }}</div>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <div class="sc-label">{{ __('messages.common.cni') }}</div>
                                        <div class="mt-1 font-black text-slate-800">{{ $stagiaire->cni ?? __('messages.common.not_provided') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 rounded-xl border border-campus-100 bg-campus-50 px-4 py-3 text-xs font-bold text-campus-700">
                                {{ __('messages.badge.subtitle') }}
                            </div>
                        </div>

                        <div class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <img class="h-48 w-48 rounded-xl bg-white p-2 shadow-sm" src="{{ $qrDataUri }}" alt="QR login code">
                            <div class="mt-3 text-center text-xs font-black uppercase text-slate-500">{{ __('messages.badge.secure_qr') }}</div>
                            <div class="mt-2 text-center text-[11px] font-semibold text-slate-400">{{ $stagiaire->email }}</div>
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
