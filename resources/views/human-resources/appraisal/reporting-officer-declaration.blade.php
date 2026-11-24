{{-- The reporting officer's declaration: that they assessed the officer with
     the highest sense of responsibility and to the best of their judgement,
     with the post and grade they hold, the date and their signature.

     It is theirs to make — an administrator, the head of the department the
     officer is posted to, or the Electoral Officer of the LGA they are
     deployed to. Anyone else only reads what is on record. --}}

@php
    $declared = $reportingOfficerDeclaration;
    $inputClass = 'w-full rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50';
    $declarantName = $declarant ? trim($declarant->surname . ' ' . trim($declarant->firstname . ' ' . $declarant->othernames)) : '';
@endphp

@if ($mayComment)
    <form method="POST" action="{{ route('hr-appraisal.reporting-officer-declaration') }}" class="py-3"
          data-reporting-officer-declaration>
        @csrf
        <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
        <input type="hidden" name="section" value="{{ $section }}" />

        <p class="mb-3 font-sans text-sm text-base-content/80">
            I,
            <span class="inline-block w-full sm:w-80 align-middle">
                <label for="officer_name" class="sr-only">Your name</label>
                <input id="officer_name" name="officer_name" type="text" required
                       value="{{ old('officer_name', $declared?->officer_name ?? $declarantName) }}"
                       placeholder="Your full name" class="{{ $inputClass }}" />
            </span>
            assessed the above named officer and declare with the highest sense of
            responsibility and to the best of my judgement and with due regard to
            my conscience:
        </p>
        @error('officer_name')
            <p class="mb-2 mt-0 text-xs text-error">{{ $message }}</p>
        @enderror

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="w-full">
                <label for="post_held" class="mb-1 block text-xs font-semibold text-base-content/70">Post Held*</label>
                <input id="post_held" name="post_held" type="text" required
                       value="{{ old('post_held', $declared?->post_held ?? $declarant?->designation) }}"
                       class="{{ $inputClass }}" />
                @error('post_held')
                    <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label for="grade_level" class="mb-1 block text-xs font-semibold text-base-content/70">Grade Level*</label>
                <input id="grade_level" name="grade_level" type="text" required
                       value="{{ old('grade_level', $declared?->grade_level ?? $declarant?->grade_level) }}"
                       class="{{ $inputClass }}" />
                @error('grade_level')
                    <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label for="declared_on" class="mb-1 block text-xs font-semibold text-base-content/70">Date*</label>
                <input id="declared_on" name="declared_on" type="date" required
                       value="{{ old('declared_on', $declared?->declared_on?->toDateString() ?? now()->toDateString()) }}"
                       class="{{ $inputClass }}" />
                @error('declared_on')
                    <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label for="signature" class="mb-1 block text-xs font-semibold text-base-content/70">Signature*</label>
                <input id="signature" name="signature" type="text" required
                       value="{{ old('signature', $declared?->signature) }}"
                       placeholder="Type your name to sign" class="{{ $inputClass }}" />
                @error('signature')
                    <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        @if ($declared)
            <p class="mt-3 mb-0 text-xs text-base-content/40">
                Last declared on {{ $declared->updated_at->format('d M Y') }}.
            </p>
        @endif

        <div class="mt-3 flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
                {{ $continueLabel ?? 'Save Reporting Officer Declaration' }}
            </button>
        </div>
    </form>
@else
    <div class="py-3">
        <p class="mb-3 font-sans text-sm text-base-content/80">
            I, <strong>{{ $declared?->officer_name ?? '…………………' }}</strong>, assessed the
            above named officer and declare with the highest sense of responsibility
            and to the best of my judgement and with due regard to my conscience:
        </p>

        @if ($declared)
            <dl class="mb-3 grid grid-cols-1 gap-2 text-sm text-base-content/80 md:grid-cols-2">
                <div><dt class="text-xs font-semibold text-base-content/60">Post Held</dt><dd class="m-0">{{ $declared->post_held }}</dd></div>
                <div><dt class="text-xs font-semibold text-base-content/60">Grade Level</dt><dd class="m-0">{{ $declared->grade_level }}</dd></div>
                <div><dt class="text-xs font-semibold text-base-content/60">Date</dt><dd class="m-0">{{ $declared->declared_on->format('d M Y') }}</dd></div>
                <div><dt class="text-xs font-semibold text-base-content/60">Signature</dt><dd class="m-0">{{ $declared->signature }}</dd></div>
            </dl>
        @else
            <p class="mt-0.5 mb-3 font-sans text-sm text-base-content/80">Nothing has been declared here yet.</p>
        @endif

        <p class="mt-0.5 mb-0 text-xs text-base-content/40">
            The Reporting Officer Declaration is made by an administrator, the head
            of the officer's department, or the Electoral Officer of their LGA.
        </p>

        @if ($continueLabel)
            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
                <a href="{{ route('hr-appraisal', ['section' => $nextSection, 'staff' => $staff->ulid]) }}"
                   class="btn btn-sm btn-cta px-4 shadow-md">{{ $continueLabel }}</a>
            </div>
        @endif
    </div>
@endif
