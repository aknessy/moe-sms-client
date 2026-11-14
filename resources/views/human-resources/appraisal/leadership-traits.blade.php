{{-- How the officer leads those under them: whether standards are agreed
     rather than imposed, whether goals are met on time, whether they lead by
     example, and what their own suggestions have been worth. Each of the
     five is graded A to F on its own, and the five add up to one overall
     assessment — not an average taken automatically, but a judgement the
     reporting officer makes of them together.

     The grading is the reporting officer's to give — the Electoral Officer
     of the LGA the officer is deployed to, or the head of the department
     they belong to — so anyone else reads what is on record rather than
     filling the form in. --}}

@if ($mayComment)
    <form method="POST" action="{{ route('hr-appraisal.leadership-traits') }}" class="py-3"
          data-leadership-traits>
        @csrf
        <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
        <input type="hidden" name="section" value="{{ $section }}" />

        <div class="mb-4 w-full overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#1B5E20]">
                        <th class="px-3 py-2 font-sans text-xs font-semibold uppercase tracking-wide text-white">
                            Leadership Trait
                        </th>
                        @foreach (\App\Models\LeadershipTraitAssessment::GRADES as $option)
                            <th class="px-3 py-2 text-center font-sans text-xs font-semibold uppercase tracking-wide text-white">
                                {{ $option }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-300/70">
                    @foreach (\App\Models\LeadershipTraitAssessment::TRAITS as $column => $trait)
                        @php $chosen = old($column, $leadershipTraits?->{$column}); @endphp
                        <tr>
                            <td class="px-3 py-2 align-top text-base-content/80">
                                <span class="font-medium text-base-content">{{ $trait['label'] }}</span>
                                {{ $trait['description'] }}
                            </td>
                            <td colspan="{{ count(\App\Models\LeadershipTraitAssessment::GRADES) }}" class="p-0">
                                <div class="flex h-full" data-grade-group>
                                    @foreach (\App\Models\LeadershipTraitAssessment::GRADES as $option)
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
                                <td colspan="{{ count(\App\Models\LeadershipTraitAssessment::GRADES) + 1 }}"
                                    class="px-3 pb-2 text-xs text-error">{{ $message }}</td>
                            </tr>
                        @enderror
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mb-3 w-full">
            <span class="font-sans text-sm font-medium text-base-content">
                Overall Assessment<span class="text-error">*</span>
            </span>
            <p class="mt-0.5 mb-1 text-xs text-base-content/50">
                From the above, the overall performance of the officer's duties.
            </p>

            @php $overallChosen = old('overall_grade', $leadershipTraits?->overall_grade); @endphp

            <div class="divide-y divide-base-300/70 rounded-md border border-base-300" data-grade-group>
                @foreach (\App\Models\LeadershipTraitAssessment::OVERALL_GRADES as $option => $meaning)
                    <label class="flex cursor-pointer items-start gap-3 px-3 py-2 text-sm text-base-content/80 hover:bg-base-200/40">
                        <input type="checkbox" name="overall_grade" value="{{ $option }}"
                               @checked($overallChosen === $option)
                               class="mt-0.5 h-4 w-4 shrink-0 rounded border-base-300 text-[#2E7D32] focus:ring-[#2E7D32]" />
                        <span>
                            <span class="font-medium text-base-content">{{ $option }} &middot; {{ $meaning['label'] }}</span>
                            <span class="block text-xs text-base-content/60">{{ $meaning['description'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            @error('overall_grade')
                <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
                {{ $continueLabel ?? 'Save Leadership Traits' }}
            </button>
        </div>
    </form>

    <script>
        (function () {
            const form = document.querySelector('[data-leadership-traits]');

            if (! form) {
                return;
            }

            // One grade per trait, and one for the overall assessment:
            // checking a box in a group unchecks the rest of that same group,
            // so each behaves as a single choice while reading as checkboxes.
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
                            Leadership Trait
                        </th>
                        <th class="px-3 py-2 text-center font-sans text-xs font-semibold uppercase tracking-wide text-white">
                            Grade
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-300/70">
                    @foreach (\App\Models\LeadershipTraitAssessment::TRAITS as $column => $trait)
                        <tr>
                            <td class="px-3 py-2 align-top text-base-content/80">
                                <span class="font-medium text-base-content">{{ $trait['label'] }}</span>
                                {{ $trait['description'] }}
                            </td>
                            <td class="px-3 py-2 text-center font-sans text-sm text-base-content/80">
                                {{ $leadershipTraits?->{$column} ?? 'Not yet graded' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mb-3 w-full">
            <span class="font-sans text-sm font-medium text-base-content">Overall Assessment</span>
            <p class="m-0 font-sans text-sm text-base-content/80">
                @if ($leadershipTraits?->overall_grade)
                    {{ $leadershipTraits->overall_grade }} &middot;
                    {{ \App\Models\LeadershipTraitAssessment::OVERALL_GRADES[$leadershipTraits->overall_grade]['label'] }}
                @else
                    Not yet graded
                @endif
            </p>
        </div>

        <p class="mt-0.5 mb-0 text-xs text-base-content/40">
            Leadership Traits is graded by the Electoral Officer of the local
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
