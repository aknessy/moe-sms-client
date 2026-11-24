{{-- Whether the reporting officer suggests the officer for a different job
     in the same grade, and the kind of job and reasons where they do.

     The suggestion is the reporting officer's to make — the Electoral
     Officer of the LGA the officer is deployed to, or the head of the
     department they belong to — so anyone else reads what is on record
     rather than filling the form in. --}}

@php
    $suggested = old('different_job_suggested',
        $suggestion === null ? null : ($suggestion->different_job_suggested ? 'yes' : 'no'));
@endphp

@if ($maySuggest)
    <form method="POST" action="{{ route('hr-appraisal.suggestions') }}" class="py-3"
          data-suggestions>
        @csrf
        <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
        <input type="hidden" name="section" value="{{ $section }}" />

        <div class="mb-3 w-full">
            <span class="font-sans text-sm font-medium text-base-content">
                Do you suggest the officer for a different job in the same
                grade?<span class="text-error">*</span>
            </span>

            <div class="mt-2 flex items-center gap-x-6">
                @foreach (['yes' => 'Yes', 'no' => 'No'] as $value => $label)
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-base-content/80">
                        <input type="radio" name="different_job_suggested" value="{{ $value }}"
                               data-different-job-option
                               @checked($suggested === $value)
                               class="h-4 w-4 border-base-300 text-[#2E7D32] focus:ring-[#2E7D32]" />
                        {{ $label }}
                    </label>
                @endforeach
            </div>

            @error('different_job_suggested')
                <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-3 w-full" data-different-job-details
             style="display: {{ $suggested === 'yes' ? 'block' : 'none' }}">
            <label for="different_job_details" class="font-sans text-sm font-medium text-base-content">
                Kind of job, and reasons<span class="text-error">*</span>
            </label>
            <p class="mt-0.5 mb-1 text-xs text-base-content/50">
                Indicate the kind of job suggested and give reasons.
            </p>
            <textarea id="different_job_details" name="different_job_details" rows="4"
                      placeholder="Kind of job and reasons"
                      class="w-full resize-none rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50">{{ old('different_job_details', $suggestion?->different_job_details) }}</textarea>
            @error('different_job_details')
                <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Whoever acts on the appraisal signs it and says the day. --}}
        <div class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="w-full">
                <label for="acted_on" class="mb-1 block text-xs font-semibold text-base-content/70">Date Acted*</label>
                <input id="acted_on" name="acted_on" type="date" required
                       value="{{ old('acted_on', $suggestion?->acted_on?->toDateString() ?? now()->toDateString()) }}"
                       class="w-full rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50" />
                @error('acted_on')
                    <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label for="signature" class="mb-1 block text-xs font-semibold text-base-content/70">Signature*</label>
                <input id="signature" name="signature" type="text" required
                       value="{{ old('signature', $suggestion?->signature) }}"
                       placeholder="Type your name to sign"
                       class="w-full rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50" />
                @error('signature')
                    <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
                {{ $continueLabel ?? 'Save Suggestions' }}
            </button>
        </div>
    </form>

    <script>
        (function () {
            const form = document.querySelector('[data-suggestions]');

            if (! form) {
                return;
            }

            const details = form.querySelector('[data-different-job-details]');

            form.querySelectorAll('[data-different-job-option]').forEach(function (option) {
                option.addEventListener('change', function () {
                    details.style.display = option.value === 'yes' && option.checked ? 'block' : 'none';
                });
            });
        })();
    </script>
@else
    <div class="py-3">
        <div class="mb-3 w-full">
            <span class="font-sans text-sm font-medium text-base-content">
                Do you suggest the officer for a different job in the same grade?
            </span>
            <p class="mt-0.5 mb-0 font-sans text-sm text-base-content/80">
                @if ($suggestion === null)
                    Nothing has been recorded here yet.
                @else
                    {{ $suggestion->different_job_suggested ? 'Yes' : 'No' }}
                @endif
            </p>
        </div>

        @if ($suggestion?->different_job_suggested)
            <div class="mb-3 w-full">
                <span class="font-sans text-sm font-medium text-base-content">Kind of job, and reasons</span>
                <p class="mt-0.5 mb-0 font-sans text-sm text-base-content/80">
                    {{ $suggestion->different_job_details }}
                </p>
            </div>
        @endif

        @if ($suggestion?->signature)
            <dl class="mb-3 grid grid-cols-1 gap-2 text-sm text-base-content/80 md:grid-cols-2">
                <div><dt class="text-xs font-semibold text-base-content/60">Date Acted</dt><dd class="m-0">{{ $suggestion->acted_on?->format('d M Y') }}</dd></div>
                <div><dt class="text-xs font-semibold text-base-content/60">Signature</dt><dd class="m-0">{{ $suggestion->signature }}</dd></div>
            </dl>
        @endif

        <p class="mt-0.5 mb-0 text-xs text-base-content/40">
            Suggestions is given by the Electoral Officer of the local
            government the officer is deployed to, the head of the officer's
            department, HOD GAP, or an administrator.
        </p>

        @if ($continueLabel)
            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
                <a href="{{ route('hr-appraisal', ['section' => $nextSection, 'staff' => $staff->ulid]) }}"
                   class="btn btn-sm btn-cta px-4 shadow-md">{{ $continueLabel }}</a>
            </div>
        @endif
    </div>
@endif
