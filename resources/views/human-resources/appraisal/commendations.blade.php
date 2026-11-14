{{-- Whether the officer was commended in writing during the year for
     outstanding performance. The detail is only worth asking where the
     answer is yes. --}}

@php
    $received = old('received', $commendation === null ? null : ($commendation->received ? 'yes' : 'no'));
@endphp

<form method="POST" action="{{ route('hr-appraisal.commendations') }}" class="py-3"
      data-commendations>
    @csrf
    <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
    <input type="hidden" name="section" value="{{ $section }}" />

    <div class="mb-3 w-full">
        <span class="font-sans text-sm font-medium text-base-content">
            Has the officer received any special commendations (written) during
            the year for outstanding performance?<span class="text-error">*</span>
        </span>

        <div class="mt-2 flex items-center gap-x-6">
            @foreach (['yes' => 'Yes', 'no' => 'No'] as $value => $label)
                <label class="flex cursor-pointer items-center gap-2 text-sm text-base-content/80">
                    <input type="radio" name="received" value="{{ $value }}" data-received-option
                           @checked($received === $value)
                           class="h-4 w-4 border-base-300 text-[#2E7D32] focus:ring-[#2E7D32]" />
                    {{ $label }}
                </label>
            @endforeach
        </div>

        @error('received')
            <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-3 w-full" data-commendation-details
         style="display: {{ $received === 'yes' ? 'block' : 'none' }}">
        <label for="details" class="font-sans text-sm font-medium text-base-content">
            Detail of the commendation<span class="text-error">*</span>
        </label>
        <p class="mt-0.5 mb-1 text-xs text-base-content/50">
            What the commendation was for, and who it came from.
        </p>
        <textarea id="details" name="details" rows="4"
                  placeholder="Detail of the commendation"
                  class="w-full resize-none rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50">{{ old('details', $commendation?->details) }}</textarea>
        @error('details')
            <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
            {{ $continueLabel ?? 'Save Commendations' }}
        </button>
    </div>
</form>

<script>
    (function () {
        const form = document.querySelector('[data-commendations]');

        if (! form) {
            return;
        }

        const details = form.querySelector('[data-commendation-details]');

        form.querySelectorAll('[data-received-option]').forEach(function (option) {
            option.addEventListener('change', function () {
                details.style.display = option.value === 'yes' && option.checked ? 'block' : 'none';
            });
        });
    })();
</script>
