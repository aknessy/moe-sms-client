{{-- How the officer carries themselves in the work: whether they can be left
     to get on with it, how they present themselves, whether they are there
     to begin with, and what they make of what the service gives them. Each
     is graded A to F on its own, as a matrix — the same shape the paper form
     uses, and the same shape Leadership Traits reads in — and the reporting
     officer's own words beneath them say why, covering all four together
     rather than one apiece.

     The grading is the reporting officer's to give — the Electoral Officer
     of the LGA the officer is deployed to, or the head of the department
     they belong to — so anyone else reads what is on record rather than
     filling the form in. --}}

@if ($mayComment)
    <form method="POST" action="{{ route('hr-appraisal.character-traits') }}" class="py-3"
          data-character-traits>
        @csrf
        <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
        <input type="hidden" name="section" value="{{ $section }}" />

        <div class="mb-4 w-full overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#1B5E20]">
                        <th class="px-3 py-2 font-sans text-xs font-semibold uppercase tracking-wide text-white">
                            Character Trait / Work Habit
                        </th>
                        @foreach (\App\Models\CharacterTraitAssessment::GRADES as $option)
                            <th class="px-3 py-2 text-center font-sans text-xs font-semibold uppercase tracking-wide text-white">
                                {{ $option }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-300/70">
                    @foreach (\App\Models\CharacterTraitAssessment::TRAITS as $column => $trait)
                        @php $chosen = old($column, $characterTraits?->{$column}); @endphp
                        <tr>
                            <td class="px-3 py-2 align-top text-base-content/80">
                                <span class="font-medium text-base-content">{{ $trait['label'] }}</span>
                                {{ $trait['description'] }}
                            </td>
                            <td colspan="{{ count(\App\Models\CharacterTraitAssessment::GRADES) }}" class="p-0">
                                <div class="flex h-full" data-grade-group>
                                    @foreach (\App\Models\CharacterTraitAssessment::GRADES as $option)
                                        <label class="flex flex-1 cursor-pointer items-center justify-center border-l border-base-300/70 py-2">
                                            <input type="checkbox" name="{{ $column }}" value="{{ $option }}"
                                                   @checked($chosen === $option)
                                                   class="h-4 w-4 rounded border-base-300 text-[#2E7D32] focus:ring-[#2E7D32]" />
                                        </label>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @error($column)
                            <tr>
                                <td colspan="{{ count(\App\Models\CharacterTraitAssessment::GRADES) + 1 }}"
                                    class="px-3 pb-2 text-xs text-error">{{ $message }}</td>
                            </tr>
                        @enderror
                    @endforeach
                </tbody>
            </table>
        </div>

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
@else
    <div class="py-3">
        <div class="mb-4 w-full overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#1B5E20]">
                        <th class="px-3 py-2 font-sans text-xs font-semibold uppercase tracking-wide text-white">
                            Character Trait / Work Habit
                        </th>
                        <th class="px-3 py-2 text-center font-sans text-xs font-semibold uppercase tracking-wide text-white">
                            Grade
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-300/70">
                    @foreach (\App\Models\CharacterTraitAssessment::TRAITS as $column => $trait)
                        <tr>
                            <td class="px-3 py-2 align-top text-base-content/80">
                                <span class="font-medium text-base-content">{{ $trait['label'] }}</span>
                                {{ $trait['description'] }}
                            </td>
                            <td class="px-3 py-2 text-center font-sans text-sm text-base-content/80">
                                {{ $characterTraits?->{$column} ?? 'Not yet graded' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mb-3 w-full">
            <span class="font-sans text-sm font-medium text-base-content">Justification</span>
            <p class="mt-0.5 mb-0 font-sans text-sm text-base-content/80">
                {{ $characterTraits?->justification ?? 'Nothing has been written here yet.' }}
            </p>
        </div>

        <p class="mt-0.5 mb-0 text-xs text-base-content/40">
            Character Traits is graded by the Electoral Officer of the local
            government the officer is deployed to, the head of the officer's
            department, or an administrator.
        </p>

        @if ($continueLabel)
            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
                <a href="{{ route('hr-appraisal', ['section' => $nextSection, 'staff' => $staff->ulid]) }}"
                   class="btn btn-sm btn-cta px-4 shadow-md">{{ $continueLabel }}</a>
            </div>
        @endif
    </div>
@endif
