{{-- Where the officer has a caution or query already on file, that is what
     answers this section — there is nothing to ask that the record does not
     already say. The Yes/No question is only offered where there is none. --}}

@php
    $taken = old('taken', $sanction === null ? null : ($sanction->taken ? 'yes' : 'no'));
@endphp

@if ($staffQueries->isNotEmpty())
    <div class="py-3">
        <p class="mb-3 font-sans text-sm font-medium text-base-content">
            Queries and cautions on file for the officer:
        </p>

        <div class="space-y-3">
            @foreach ($staffQueries as $query)
                <div class="rounded-md border border-base-200 bg-base-100 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="m-0 text-sm font-medium text-base-content">{{ $query->offence }}</p>
                        <p class="m-0 text-xs text-base-content/60">
                            {{ \Carbon\Carbon::parse($query->query_date)->format('j F Y') }}
                        </p>
                    </div>

                    @if ($query->sanction && $query->sanction !== 'none')
                        <p class="mt-1 mb-0 text-xs text-base-content/70">
                            Sanction: <span class="font-medium capitalize">{{ str_replace('-', ' ', $query->sanction) }}</span>
                        </p>
                    @endif

                    @if ($query->details)
                        <p class="mt-1 mb-0 text-xs text-base-content/70">{{ $query->details }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
        @if ($continueLabel)
            <a href="{{ route('hr-appraisal', ['section' => $nextSection, 'staff' => $staff->ulid]) }}"
               class="btn btn-sm btn-cta px-4 shadow-md">
                {{ $continueLabel }}
            </a>
        @endif
    </div>
@else
    <form method="POST" action="{{ route('hr-appraisal.sanctions') }}" class="py-3"
          data-sanctions>
        @csrf
        <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
        <input type="hidden" name="section" value="{{ $section }}" />

        <div class="mb-3 w-full">
            <span class="font-sans text-sm font-medium text-base-content">
                Has any disciplinary action been taken against the officer during
                the period covered by this report?<span class="text-error">*</span>
            </span>

            <div class="mt-2 flex items-center gap-x-6">
                @foreach (['yes' => 'Yes', 'no' => 'No'] as $value => $label)
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-base-content/80">
                        <input type="radio" name="taken" value="{{ $value }}" data-taken-option
                               @checked($taken === $value)
                               class="h-4 w-4 border-base-300 text-[#2E7D32] focus:ring-[#2E7D32]" />
                        {{ $label }}
                    </label>
                @endforeach
            </div>

            @error('taken')
                <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-3 w-full" data-sanction-details
             style="display: {{ $taken === 'yes' ? 'block' : 'none' }}">
            <label for="details" class="font-sans text-sm font-medium text-base-content">
                Detail of the disciplinary action<span class="text-error">*</span>
            </label>
            <p class="mt-0.5 mb-1 text-xs text-base-content/50">
                What the action was, and what gave rise to it.
            </p>
            <textarea id="details" name="details" rows="4"
                      placeholder="Detail of the disciplinary action"
                      class="w-full resize-none rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50">{{ old('details', $sanction?->details) }}</textarea>
            @error('details')
                <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
                {{ $continueLabel ?? 'Save Sanctions' }}
            </button>
        </div>
    </form>

    <script>
        (function () {
            const form = document.querySelector('[data-sanctions]');

            if (! form) {
                return;
            }

            const details = form.querySelector('[data-sanction-details]');

            form.querySelectorAll('[data-taken-option]').forEach(function (option) {
                option.addEventListener('change', function () {
                    details.style.display = option.value === 'yes' && option.checked ? 'block' : 'none';
                });
            });
        })();
    </script>
@endif
