@props([
    'title' => 'Confirmation',
    'message' => 'Êtes-vous sûr de vouloir continuer ?',
    'confirmText' => 'Confirmer',
    'cancelText' => 'Annuler',
    'type' => 'primary', // primary, danger
    'isAlert' => false,
])

<div x-data="{ 
        open: false,
        triggerButton: null,
        title: '{{ addslashes($title) }}',
        message: '{{ addslashes($message) }}',
        type: '{{ $type }}',
        confirm() {
            if ({{ $isAlert ? 'true' : 'false' }}) {
                this.open = false;
                this.$dispatch('confirmed');
                return;
            }
            this.open = false;
            let form = this.$refs.triggerWrapper.closest('form');
            if (form) {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit(this.triggerButton);
                } else {
                    form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                }
            } else {
                this.$dispatch('confirmed');
            }
        }
    }" 
    class="inline-block" 
    x-ref="triggerWrapper"
    @keydown.escape.window="open = false"
    @open-confirmation.window="if ($event.detail === $el) open = true"
    @show-alert.window="
        if ({{ $isAlert ? 'true' : 'false' }}) {
            title = $event.detail.title || '{{ addslashes($title) }}';
            message = $event.detail.message || '{{ addslashes($message) }}';
            type = $event.detail.type || '{{ $type }}';
            open = true;
        }
    ">
    
    <div @click.capture.prevent.stop="
        let btn = $event.target.closest('button[type=submit]');
        if (btn) triggerButton = btn;
        open = true;
    " class="inline-flex w-full h-full">
        {{ $slot }}
    </div>

    <template x-teleport="body">
        <div x-show="open"
             style="display: none"
             class="relative z-[100]"
             aria-labelledby="modal-title"
             role="dialog"
             aria-modal="true"
             x-cloak>
            
            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity dark:bg-slate-900/90"></div>

            <div class="fixed inset-0 z-[101] w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div x-show="open"
                         @click.outside="open = false"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                        
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 dark:bg-slate-800">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10"
                                     :class="type === 'danger' ? 'bg-rose-100 dark:bg-rose-900/30' : 'bg-blue-100 dark:bg-blue-900/30'">
                                    <template x-if="type === 'danger'">
                                        <svg class="h-6 w-6 text-rose-600 dark:text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </template>
                                    <template x-if="type !== 'danger'">
                                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                        </svg>
                                    </template>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-black leading-6 text-slate-900 dark:text-white" id="modal-title" x-text="title"></h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-slate-500 dark:text-slate-400 whitespace-pre-line" x-text="message"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700">
                            <button type="button" @click="confirm()" class="inline-flex w-full justify-center rounded-xl px-3 py-2 text-sm font-semibold text-white shadow-sm sm:ml-3 sm:w-auto transition-colors"
                                    :class="type === 'danger' ? 'bg-rose-600 hover:bg-rose-500' : 'bg-primary hover:bg-primary-dark'">
                                {{ $confirmText }}
                            </button>
                            @if(!$isAlert)
                                <button type="button" @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto dark:bg-slate-700 dark:text-slate-200 dark:ring-slate-600 dark:hover:bg-slate-600">
                                    {{ $cancelText }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
