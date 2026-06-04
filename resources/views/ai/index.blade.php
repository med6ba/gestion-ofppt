<x-layouts.app title="CampusAI">
    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="sc-card p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold">CampusAI assistant</h2>
                    <p class="mt-1 text-sm text-slate-500">Role-aware answers from Smart Campus data only.</p>
                </div>
                <span class="sc-badge {{ $aiAvailable ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $aiAvailable ? 'Groq enabled' : 'Fallback mode' }}</span>
            </div>

            <form method="POST" action="{{ route('ai.ask') }}" class="mt-6 space-y-3">
                @csrf
                <x-form.label>Question</x-form.label>
                <textarea class="sc-input" name="question" rows="4" placeholder="Ask about today schedule, attendance, room information, or campus insights." required>{{ old('question', $question) }}</textarea>
                <button class="sc-btn sc-btn-primary">Ask CampusAI</button>
            </form>

            @if ($answer)
                <div class="mt-6 rounded-lg border border-campus-100 bg-campus-50 p-5">
                    <div class="text-sm font-semibold uppercase text-campus-700">Answer</div>
                    <div class="mt-2 whitespace-pre-wrap text-sm leading-6 text-slate-800">{{ $answer }}</div>
                </div>
            @endif
        </section>

        <aside class="sc-card p-5">
            <h2 class="text-lg font-bold">Allowed topics</h2>
            <div class="mt-4 space-y-3 text-sm text-slate-600">
                <p>Schedules and next sessions.</p>
                <p>Attendance counts and summaries.</p>
                <p>Risk indicators for authorized roles.</p>
                <p>Notifications and room information.</p>
            </div>
        </aside>
    </div>
</x-layouts.app>
