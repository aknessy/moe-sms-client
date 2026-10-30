<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Notifications" crumb="Notifications">

            <x-registry.card title="What is waiting for you" :count="$notifications->total()"
                             description="Newest first">

                @if ($notifications->isNotEmpty() && $notifications->contains(fn ($n) => ! $n->is_read))
                    <form method="POST" action="{{ route('notifications.read-all') }}" class="pt-3">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-cta-outline px-4">Mark all as read</button>
                    </form>
                @endif

                @if ($notifications->isEmpty())
                    <p class="py-10 text-center text-sm text-base-content/50 m-0">
                        Nothing is waiting for you.
                    </p>
                @else
                    <div class="flex flex-col gap-2 py-3">
                        @foreach ($notifications as $notification)
                            {{-- Unread sits on white and carries a mark; read
                                 recedes, so a glance finds what is new. --}}
                            <a href="{{ route('notifications.open', $notification->ulid) }}"
                               class="flex items-start gap-3 rounded-md border px-4 py-3 transition hover:border-primary/50
                                      {{ $notification->is_read ? 'border-base-200 bg-base-200/40' : 'border-base-300 bg-white' }}">

                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $notification->is_read ? 'bg-transparent' : 'bg-[#2E7D32]' }}"></span>

                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm {{ $notification->is_read ? 'text-base-content/70' : 'font-semibold text-base-content' }}">
                                        {{ $notification->title }}
                                    </span>
                                    @if (filled($notification->body))
                                        <span class="mt-0.5 block text-xs text-base-content/60">{{ $notification->body }}</span>
                                    @endif
                                </span>

                                <span class="shrink-0 text-xs text-base-content/40">
                                    {{ $notification->created_at?->diffForHumans() }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif

                <x-registry.paginator :items="$notifications" />
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
