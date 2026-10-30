<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Training" crumb="My Records">
            <x-registry.card title="Available Trainings" :count="$trainings->count()"
                             description="Putting your name down says you would like to attend — it is not a nomination">

                @if ($trainings->isEmpty())
                    <p class="py-8 text-center text-sm text-base-content/50 m-0">
                        No trainings have been announced yet.
                    </p>
                @else
                    <div class="flex flex-col gap-3 py-3">
                        @foreach ($trainings as $training)
                            @php $wanted = $training->wanted($staff->ulid); @endphp

                            <div class="flex flex-col gap-3 rounded-md border border-base-300 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <h4 class="m-0 font-sans text-sm font-semibold text-base-content">
                                        {{ $training->title }}
                                        @unless ($training->is_open)
                                            <span class="ml-1 text-xs font-normal text-base-content/40">(closed)</span>
                                        @endunless
                                    </h4>
                                    @if (filled($training->description))
                                        <p class="mt-1 mb-0 text-xs text-base-content/60">{{ $training->description }}</p>
                                    @endif
                                    <p class="mt-1 mb-0 text-xs text-base-content/50">
                                        {{ $training->when }}
                                        @if (filled($training->venue)) &middot; {{ $training->venue }} @endif
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-3">
                                    @if ($wanted)
                                        <span class="text-xs text-[#1B5E20]">Your name is down</span>
                                    @endif

                                    {{-- The same button both ways: it is a toggle,
                                         so a change of mind needs no second form. --}}
                                    <form method="POST" action="{{ route('my-training.interest') }}">
                                        @csrf
                                        <input type="hidden" name="training_ulid" value="{{ $training->ulid }}" />
                                        <button type="submit"
                                                @disabled(! $training->is_open && ! $wanted)
                                                class="btn btn-sm px-4 {{ $wanted ? 'btn-cta-outline' : 'btn-cta shadow-md' }}">
                                            {{ $wanted ? 'Withdraw Interest' : 'Indicate Interest' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
