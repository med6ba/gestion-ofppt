<x-layouts.app title="Attendance Check In">
    <section class="mx-auto max-w-md sc-card p-6">
        <h2 class="text-xl font-bold">Enter attendance code</h2>
        <p class="mt-2 text-sm text-slate-500">Use the code shown by your formateur if you cannot scan the QR.</p>
        <form method="POST" action="{{ route('attendance.code.store') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="sc-label">Code</label>
                <input class="sc-input mt-1 text-center text-2xl font-bold uppercase tracking-widest" name="code" maxlength="12" required autofocus>
            </div>
            <button class="sc-btn sc-btn-primary w-full">Confirm attendance</button>
        </form>
    </section>
</x-layouts.app>
