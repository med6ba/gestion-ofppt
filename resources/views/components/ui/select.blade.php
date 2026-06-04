@props([
    'alpineOptions' => '[]',
    'valueField' => 'id',
    'labelField' => 'name',
    'labelExpr' => null, // e.g. "opt.code + ' - ' + opt.name"
    'placeholder' => '— Choisir —',
    'buttonClass' => null,
    'initialValue' => null,
])

<div
    x-data="{
        open: false,
        value: '{{ $initialValue }}',
        get optionsList() {
            return {{ $alpineOptions }};
        },
        getLabel(opt) {
            if (!opt) return '{{ $placeholder }}';
            @if($labelExpr)
                return eval(`(function(opt){ return {{ $labelExpr }}; })(opt)`);
            @else
                return opt['{{ $labelField }}'];
            @endif
        },
        get selectedLabel() {
            if (!this.value) return '{{ $placeholder }}';
            let opt = this.optionsList.find(o => String(o['{{ $valueField }}']) === String(this.value));
            return this.getLabel(opt);
        }
    }"
    x-modelable="value"
    {{ $attributes->only('x-model') }}
    @click.outside="open = false"
    class="relative w-full"
>
    <input type="hidden" :name="'{{ $attributes->get('name') ?? '' }}'" :value="value" />

    <button
        type="button"
        @click="open = !open"
        class="{{ $buttonClass ?? 'sc-input mt-1 w-full' }} flex items-center justify-between text-left"
        :class="{'ring-2 ring-primary/20 border-primary': open}"
    >
        <span x-text="selectedLabel" class="block truncate pr-4" :class="{'text-slate-400': !value}"></span>
        <svg class="size-4 shrink-0 text-slate-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
        style="display: none;"
    >
        <div class="p-1">
            <template x-for="opt in optionsList" :key="opt['{{ $valueField }}']">
                <div
                    @click="value = opt['{{ $valueField }}']; $nextTick(() => { let i = $el.closest('[x-data]').querySelector('input[type=hidden]'); if(i) i.dispatchEvent(new Event('change', {bubbles: true})) }); open = false;"
                    class="px-3 py-2 text-sm cursor-pointer rounded-md transition-colors"
                    :class="value == opt['{{ $valueField }}'] ? 'bg-primary text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                    x-text="getLabel(opt)"
                ></div>
            </template>
            <div x-show="optionsList.length === 0" class="px-3 py-2 text-sm text-slate-400 italic">
                Aucune option
            </div>
        </div>
    </div>
</div>
