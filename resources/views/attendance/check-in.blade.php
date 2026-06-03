<x-layouts.app title="Attendance Check In">
    <section class="mx-auto max-w-md sc-card p-8 text-center">
        <x-ui.icon name="qr" class="mx-auto h-20 w-20 text-slate-400 mb-4" />
        <h2 class="text-2xl font-bold text-slate-800">Présence par QR Code</h2>
        <p class="mt-4 text-base text-slate-600">Pour marquer votre présence :</p>
        <ul class="mt-4 space-y-3 text-left text-sm text-slate-600 bg-slate-50 p-4 rounded-xl">
            <li class="flex items-start gap-2">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">1</span>
                Ouvrez l'application <b>Appareil Photo</b> de votre téléphone.
            </li>
            <li class="flex items-start gap-2">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">2</span>
                Scannez le grand QR code affiché par votre formateur.
            </li>
            <li class="flex items-start gap-2">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">3</span>
                Cliquez sur le lien qui apparaît pour valider.
            </li>
        </ul>
        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700 text-left">
            <strong>Attention :</strong> Ce téléphone sera enregistré comme votre appareil unique.
        </div>
    </section>
</x-layouts.app>
