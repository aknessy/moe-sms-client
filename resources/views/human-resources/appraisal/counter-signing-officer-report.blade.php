{{-- The countersigning officer's report: normally the immediate superior of
     the reporting officer confirms they agree with the assessment (or notes
     any disagreement), how often they have seen the officer's work, any adverse
     comment, and whether it has been brought to the officer's attention —
     with how long the officer has served under them, their post and grade,
     name, date and signature.

     Written by the desks above the reporting officers: an administrator, the
     Commissioner or the Administrative Secretary. Anyone else only reads. --}}

@php
    $report = $counterSigningReport;
    $inputClass = 'w-full rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50';
    $declarantName = $declarant ? trim($declarant->surname . ' ' . trim($declarant->firstname . ' ' . $declarant->othernames)) : '';
    $instructions = 'You should confirm that you agree with the reporting officer\'s assessment, or indicate in the foregoing sections any disagreements which may remain after discussing them with him/her. You should also indicate how frequently you have seen the work of the person being reported upon. Add any adverse comment, including whether any aspect of the assessments in the report have been brought to the attention of the person reported upon.';
@endphp

@if ($mayCounterSign)
    <form method="POST" action="{{ route('hr-appraisal.counter-signing-officer-report') }}" class="py-3"
          data-counter-signing-report>
        @csrf
        <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
        <input type="hidden" name="section" value="{{ $section }}" />

        <p class="mb-1 font-sans text-xs font-semibold text-base-content/60">
            (The Countersigning officer will normally be the immediate superior of the reporting officer)
        </p>
        <p class="mb-3 font-sans text-sm text-base-content/80">{{ $instructions }}</p>

        <div class="mb-3 w-full">
            <label for="comments" class="sr-only">Comments</label>
            <textarea id="comments" name="comments" rows="6" required
                      class="{{ $inputClass }} resize-none">{{ old('comments', $report?->comments) }}</textarea>
            @error('comments')
                <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-3 flex flex-wrap items-center gap-2 text-sm text-base-content/80">
            <span>The officer has served under me for</span>
            <label for="served_years" class="sr-only">Years</label>
            <input id="served_years" name="served_years" type="number" min="0" max="60" required
                   value="{{ old('served_years', $report?->served_years) }}" class="{{ $inputClass }} !w-20" />
            <span>Years and</span>
            <label for="served_months" class="sr-only">Months</label>
            <input id="served_months" name="served_months" type="number" min="0" max="11" required
                   value="{{ old('served_months', $report?->served_months) }}" class="{{ $inputClass }} !w-20" />
            <span>Months</span>
        </div>
        @error('served_years')
            <p class="mb-2 mt-0 text-xs text-error">{{ $message }}</p>
        @enderror
        @error('served_months')
            <p class="mb-2 mt-0 text-xs text-error">{{ $message }}</p>
        @enderror

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="w-full">
                <label for="post_held" class="mb-1 block text-xs font-semibold text-base-content/70">Post Held*</label>
                <input id="post_held" name="post_held" type="text" required
                       value="{{ old('post_held', $report?->post_held ?? $declarant?->designation) }}"
                       class="{{ $inputClass }}" />
                @error('post_held')
                    <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label for="grade_level" class="mb-1 block text-xs font-semibold text-base-content/70">Grade Level*</label>
                <input id="grade_level" name="grade_level" type="text" required
                       value="{{ old('grade_level', $report?->grade_level ?? $declarant?->grade_level) }}"
                       class="{{ $inputClass }}" />
                @error('grade_level')
                    <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full md:col-span-2">
                <label for="officer_name" class="mb-1 block text-xs font-semibold text-base-content/70">Name of Countersigning Officer*</label>
                <input id="officer_name" name="officer_name" type="text" required
                       value="{{ old('officer_name', $report?->officer_name ?? $declarantName) }}"
                       class="{{ $inputClass }}" />
                @error('officer_name')
                    <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label for="declared_on" class="mb-1 block text-xs font-semibold text-base-content/70">Date*</label>
                <input id="declared_on" name="declared_on" type="date" required
                       value="{{ old('declared_on', $report?->declared_on?->toDateString() ?? now()->toDateString()) }}"
                       class="{{ $inputClass }}" />
                @error('declared_on')
                    <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label for="signature" class="mb-1 block text-xs font-semibold text-base-content/70">Signature*</label>
                <input id="signature" name="signature" type="text" required
                       value="{{ old('signature', $report?->signature) }}"
                       placeholder="Type your name to sign" class="{{ $inputClass }}" />
                @error('signature')
                    <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        @if ($report)
            <p class="mt-3 mb-0 text-xs text-base-content/40">
                Last saved on {{ $report->updated_at->format('d M Y') }}.
            </p>
        @endif

        <div class="mt-3 flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
                Save Countersigning Officer's Report
            </button>
        </div>
    </form>
@else
    <div class="py-3">
        <p class="mb-1 font-sans text-xs font-semibold text-base-content/60">
            (The Countersigning officer will normally be the immediate superior of the reporting officer)
        </p>

        @if ($report)
            <p class="mb-3 whitespace-pre-line font-sans text-sm text-base-content/80">{{ $report->comments }}</p>

            <dl class="mb-3 grid grid-cols-1 gap-2 text-sm text-base-content/80 md:grid-cols-2">
                <div class="md:col-span-2"><dt class="text-xs font-semibold text-base-content/60">Served under the countersigning officer for</dt><dd class="m-0">{{ $report->served_years }} years and {{ $report->served_months }} months</dd></div>
                <div><dt class="text-xs font-semibold text-base-content/60">Post Held</dt><dd class="m-0">{{ $report->post_held }}</dd></div>
                <div><dt class="text-xs font-semibold text-base-content/60">Grade Level</dt><dd class="m-0">{{ $report->grade_level }}</dd></div>
                <div class="md:col-span-2"><dt class="text-xs font-semibold text-base-content/60">Name of Countersigning Officer</dt><dd class="m-0">{{ $report->officer_name }}</dd></div>
                <div><dt class="text-xs font-semibold text-base-content/60">Date</dt><dd class="m-0">{{ $report->declared_on->format('d M Y') }}</dd></div>
                <div><dt class="text-xs font-semibold text-base-content/60">Signature</dt><dd class="m-0">{{ $report->signature }}</dd></div>
            </dl>
        @else
            <p class="mt-0.5 mb-3 font-sans text-sm text-base-content/80">Nothing has been reported here yet.</p>
        @endif

        <p class="mt-0.5 mb-0 text-xs text-base-content/40">
            The Countersigning Officer's Report is written by an administrator, the
            Commissioner or the Administrative Secretary.
        </p>
    </div>
@endif
