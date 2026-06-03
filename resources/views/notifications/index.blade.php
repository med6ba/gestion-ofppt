<x-layouts.app title="Notifications">
    <section class="sc-card p-5">
        <h2 class="text-lg font-bold">Notification center</h2>
        <div class="mt-4 grid gap-3">
            @forelse ($notifications as $notification)
                <div class="rounded-lg border border-slate-200 p-4 {{ $notification->read_at ? 'bg-white' : 'bg-campus-50' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold">{{ $notification->data['title'] ?? 'Notification' }}</div>
                            <div class="mt-1 text-sm text-slate-600">{{ $notification->data['body'] ?? '' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $notification->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="flex gap-2">
                            @if (!empty($notification->data['url']))
                                <a class="sc-btn sc-btn-secondary" href="{{ $notification->data['url'] }}">Open</a>
                            @endif
                            @unless ($notification->read_at)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <button class="sc-btn sc-btn-primary">Mark read</button>
                                </form>
                            @endunless
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No notifications yet.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $notifications->links() }}</div>
    </section>
</x-layouts.app>
