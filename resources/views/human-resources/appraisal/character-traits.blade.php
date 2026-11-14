{{-- How the officer carries themselves in the work: whether they can be left
     to get on with it, how they present themselves, whether they are there
     to begin with, and what they make of what the service gives them. Each
     is graded on its own, and the reporting officer's own words beneath them
     say why — covering all four together rather than one apiece. --}}

<form method="POST" action="{{ route('hr-appraisal.character-traits') }}" class="py-3"
      data-character-traits>
    @csrf
    <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
    <input type="hidden" name="section" value="{{ $section }}" />

    @foreach (\App\Models\CharacterTraitAssessment::TRAITS as $column => $trait)
        @php $chosen = old($column, $characterTraits?->{$column}); @endphp

        <div class="mb-3 w-full">
            <span class="font-sans text-sm font-medium text-base-content">
                {{ $trait['label'] }}<span class="text-error">*</span>
            </span>
            <p class="mt-0.5 mb-1 text-xs text-base-content/50">
                {{ $trait['description'] }}
            </p>

            <div class="flex flex-wrap items-center gap-x-6 gap-y-2" data-grade-group>
                @foreach (\App\Models\CharacterTraitAssessment::GRADES as $option)
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-base-content/80">
                        <input type="checkbox" name="{{ $column }}" value="{{ $option }}"
                               @checked($chosen === $option)
                               class="h-4 w-4 rounded border-base-300 text-[#2E7D32] focus:ring-[#2E7D32]" />
                        {{ $option }}
                    </label>
                @endforeach
            </div>

            @error($column)
                <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>
    @endforeach

    <div class="mb-3 w-full">
        <label for="justification" class="font-sans text-sm font-medium text-base-content">
            Justification<span class="text-error">*</span>
        </label>
        <p class="mt-0.5 mb-1 text-xs text-base-content/50">
            Why these grades were given, for the four traits together.
        </p>
        <textarea id="justification" name="justification" rows="4" required
                  placeholder="Why these grades were given"
                  class="w-full resize-none rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 transition duration-300 ease focus:border-primary focus:outline-none hover:border-primary/50">{{ old('justification', $characterTraits?->justification) }}</textarea>
        @error('justification')
            <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
            {{ $continueLabel ?? 'Save Character Traits' }}
        </button>
    </div>
</form>

<script>
    (function () {
        const form = document.querySelector('[data-character-traits]');

        if (! form) {
            return;
        }

        // One grade per trait: checking a box in a group unchecks the rest
        // of that same group, so each behaves as a single choice while
        // reading as checkboxes.
        form.querySelectorAll('[data-grade-group]').forEach(function (group) {
            const boxes = group.querySelectorAll('input[type="checkbox"]');

            boxes.forEach(function (box) {
                box.addEventListener('change', function () {
                    if (box.checked) {
                        boxes.forEach(function (other) {
                            if (other !== box) {
                                other.checked = false;
                            }
                        });
                    }
                });
            });
        });
    })();
</script>
