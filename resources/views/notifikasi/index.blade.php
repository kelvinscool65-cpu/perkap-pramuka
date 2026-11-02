@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.user', ['title' => 'Notifikasi'])

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-bold">Notifikasi</h1>
        <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
            @csrf @method('PATCH')
            <button type="submit" class="text-sm font-medium text-accent hover:underline">Tandai semua dibaca</button>
        </form>
    </div>

    <div class="card-base divide-y divide-secondary/5 !p-0 dark:divide-white/5 dark:border-white/10 dark:bg-white/[0.03]">
        @forelse($notifikasi as $notif)
            <form method="POST" action="{{ route('notifikasi.baca', $notif->id) }}">
                @csrf @method('PATCH')
                <button type="submit" class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-surface dark:hover:bg-white/5 {{ $notif->is_read ? 'opacity-50' : '' }}">
                    <span @class([
                        'mt-1.5 h-2 w-2 flex-shrink-0 rounded-full',
                        'bg-success' => $notif->warna === 'success',
                        'bg-warning' => $notif->warna === 'warning',
                        'bg-danger' => $notif->warna === 'danger',
                        'bg-accent' => $notif->warna === 'accent',
                    ])></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium">{{ $notif->judul }}</span>
                        <span class="mt-0.5 block text-sm text-secondary/60 dark:text-white/50">{{ $notif->pesan }}</span>
                        <span class="mt-1 block text-xs text-secondary/40 dark:text-white/30">{{ $notif->created_at->diffForHumans() }}</span>
                    </span>
                </button>
            </form>
        @empty
            <p class="px-5 py-12 text-center text-sm text-secondary/40">Belum ada notifikasi.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifikasi->links() }}</div>
</div>
@endsection
