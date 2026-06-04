<x-layouts.app :title="__('messages.announcements.title')">
    @php
        $user = auth()->user();
    @endphp

    <div x-data="{ showCreateModal: @js($errors->any()) }" class="space-y-6">
        <section class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800">{{ __('messages.announcements.heading') }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">{{ __('messages.announcements.subtitle') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="sc-badge bg-slate-100 text-slate-700">{{ $user->roleLabel() }}</span>
                @if ($canPublish)
                    <button type="button" class="sc-btn sc-btn-primary" @click="showCreateModal = true">
                        <x-ui.icon name="megaphone" size="size-4" />
                        {{ __('messages.announcements.new') }}
                    </button>
                @endif
            </div>
        </section>

        <section class="grid gap-4">
            @forelse ($announcements as $announcement)
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-lg font-black text-slate-900">{{ $announcement->title }}</h2>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                                <span>{{ __('messages.announcements.sent_by', ['name' => $announcement->sender?->name ?? __('messages.announcements.system_sender')]) }}</span>
                                <span>|</span>
                                <span>{{ $announcement->sender?->roleLabel() ?? __('messages.announcements.administration') }}</span>
                                <span>|</span>
                                <span>{{ __('messages.announcements.sent_at', ['time' => $announcement->sent_at->format('d/m/Y H:i')]) }}</span>
                            </div>
                        </div>
                        <span class="sc-badge bg-campus-50 text-campus-700">
                            {{ __('messages.announcements.mail_recipients', ['count' => $announcement->recipient_count]) }}
                        </span>
                    </div>

                    <div class="mt-4 whitespace-pre-line rounded-lg bg-slate-50 p-4 text-sm leading-6 text-slate-700">{{ $announcement->body }}</div>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
                    {{ __('messages.announcements.empty') }}
                </div>
            @endforelse
        </section>

        <div>{{ $announcements->links() }}</div>

        @if ($canPublish)
            <template x-teleport="body">
                <div x-show="showCreateModal" x-cloak class="sc-modal-backdrop" @keydown.escape.window="showCreateModal = false">
                    <section class="sc-modal sc-modal-lg" role="dialog" aria-modal="true" @click.outside="showCreateModal = false">
                        <div class="sc-modal-header">
                            <div>
                                <h2 class="text-lg font-black text-slate-900">{{ __('messages.announcements.new') }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ __('messages.announcements.audience') }}</p>
                            </div>
                            <button type="button" class="sc-modal-close" @click="showCreateModal = false" aria-label="{{ __('messages.common.cancel') }}">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('announcements.store') }}">
                            @csrf
                            <div class="sc-modal-body space-y-4">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                                    <div class="text-xs font-black uppercase text-slate-400">{{ __('messages.announcements.sender') }}</div>
                                    <div class="mt-1 font-bold text-slate-800">{{ $user->name }} | {{ $user->roleLabel() }}</div>
                                </div>

                                <div>
                                    <x-form.label>{{ __('messages.announcements.title_label') }}</x-form.label>
                                    <input name="title" value="{{ old('title') }}" class="sc-input mt-1" required maxlength="160">
                                </div>

                                <div>
                                    <x-form.label>{{ __('messages.announcements.body_label') }}</x-form.label>
                                    <textarea name="body" class="sc-input mt-1 min-h-40" required minlength="10" maxlength="3000" placeholder="{{ __('messages.announcements.body_placeholder') }}">{{ old('body') }}</textarea>
                                </div>
                            </div>

                            <div class="sc-modal-footer">
                                <button type="button" class="sc-btn sc-btn-secondary" @click="showCreateModal = false">{{ __('messages.common.cancel') }}</button>
                                <button class="sc-btn sc-btn-primary">
                                    <x-ui.icon name="megaphone" size="size-4" />
                                    {{ __('messages.announcements.send') }}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </template>
        @endif
    </div>
</x-layouts.app>
