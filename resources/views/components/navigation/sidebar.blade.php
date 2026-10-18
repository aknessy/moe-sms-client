<?php
    // Tab carries the content area's background so the two merge, lifted on every edge but the right.
    $currentPageClasses = 'text-sm font-medium bg-base-200 text-base-content rounded-l-lg shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.18),0_-4px_6px_-2px_rgba(0,0,0,0.18),0_4px_6px_-2px_rgba(0,0,0,0.18)]';
    $defaultPageClasses = 'text-sm font-medium text-white/80 hover:bg-white/10 hover:text-white';
    // Active sub-menu item: a darker shade of the sidebar green, so it stays
    // subordinate to the parent's content-coloured tab.
    $currentSubPageClasses = 'text-sm font-medium bg-[#1B5E20]/30 text-white rounded-l-lg';

    // Not every page passes :segments down from the dashboard component, so fall back to the request.
    $segments = $segments ?? explode('/', request()->getRequestUri());
    $section = $segments[1] ?? null;
    $subSection = $segments[2] ?? null;
    $leafSection = $segments[3] ?? null;
    $inDashboard = $section == 'dashboard';

    // The whole menu lives here: one entry per item, so adding a page is a one-line change.
    // A group is open (and tab-highlighted) whenever the url sits inside one of its `sections`.
    $menu = [
        [
            'label' => 'Dashboard',
            'icon' => 'dashboard',
            'route' => 'dashboard',
            'sections' => [null],
        ],
        [
            'label' => 'Human Resources',
            'icon' => 'users',
            'sections' => ['human-resources'],
            'children' => [
                ['label' => 'Leave Management', 'route' => 'hr-leave'],
                ['label' => 'Promotion Exercises', 'route' => 'hr-promotions'],
                ['label' => 'Performance Appraisal', 'route' => 'hr-appraisal'],
                ['label' => 'Recruitment', 'route' => 'hr-recruitment'],
                ['label' => 'Training & Capacity', 'route' => 'hr-training'],
                ['label' => 'Establishment Strength', 'route' => 'hr-establishment'],
            ],
        ],
        [
            'label' => 'Registry',
            'icon' => 'archive',
            'sections' => ['staff', 'registry'],
            'children' => [
                ['label' => 'Add Staff Record', 'route' => 'basic-info', 'section' => 'staff'],
                ['label' => 'Staff Bio-data', 'route' => 'bio-data'],
                ['label' => 'Nominall Roll', 'route' => 'nominal-roll'],
                ['label' => 'Posting & Deployment', 'route' => 'posting-deployment'],
                ['label' => 'Confirmation of Appointment', 'route' => 'confirmation'],
                ['label' => 'Record of Service', 'route' => 'record-of-service'],
                ['label' => 'Query & Discipline', 'route' => 'query-discipline'],
                ['label' => 'Retirement & Exit', 'route' => 'retirement-exit'],
                ['label' => 'Record Amendments', 'route' => 'record-amendments'],
            ],
        ],
        [
            'label' => 'Finance/Account',
            'icon' => 'wallet',
            'sections' => ['finance-account'],
            'children' => [
                ['label' => 'Salary Structure', 'route' => 'salary-structure'],
                ['label' => 'Allowances', 'route' => 'allowances'],
                ['label' => 'Staff Deductions', 'route' => 'staff-deductions'],
                ['label' => 'Loans & Advances', 'route' => 'loans-advances'],
                ['label' => 'Pay Roll', 'route' => 'payroll'],
                ['label' => 'Pay Slip', 'route' => 'payslip'],
                ['label' => 'Arrears & Variations', 'route' => 'arrears-variations'],
                ['label' => 'Remittances', 'route' => 'remittances'],
            ],
        ],
        [
            'label' => 'ICT/Voter Registry',
            'icon' => 'fingerprint',
            'sections' => ['ict'],
            'children' => [
                ['label' => 'Overview', 'route' => 'voter-registry', 'section' => 'ict', 'leaf' => null],
                ['label' => 'Ad-hoc Staff', 'route' => 'adhoc-staff'],
                ['label' => 'Voter Registration', 'route' => 'voter-registration'],
                ['label' => 'Polling Units', 'route' => 'polling-units'],
                ['label' => 'Device & Asset Register', 'route' => 'device-register'],
            ],
        ],
        [
            'label' => 'Reports & Analytics',
            'icon' => 'chart',
            'sections' => ['reports'],
            'children' => [
                ['label' => 'Nominal Roll Report', 'route' => 'nominal-roll-report'],
                ['label' => 'Retirement Projection', 'route' => 'retirement-projection'],
                ['label' => 'Payroll Report', 'route' => 'payroll-report'],
                ['label' => 'Headcount & Audit', 'route' => 'headcount-report'],
            ],
        ],
        [
            'label' => 'Administration',
            'icon' => 'settings',
            'sections' => ['config'],
            'children' => [
                ['label' => 'Settings', 'route' => 'config', 'leaf' => null],
                ['label' => 'Users, Roles & Permissions', 'route' => 'users-roles'],
                ['label' => 'Audit Trail', 'route' => 'audit-trail'],
                ['label' => 'Reference Data', 'route' => 'reference-data'],
                ['label' => 'Notification Templates', 'route' => 'notification-templates'],
                ['label' => 'Backup & Data', 'route' => 'backup-data'],
            ],
        ],
    ];

    // A child is current when its own url matches the one being viewed.
    $isCurrent = function (array $child) use ($inDashboard, $subSection, $leafSection) {
        if (! $inDashboard) {
            return false;
        }

        if (array_key_exists('section', $child)) {
            return $subSection === $child['section']
                && (! array_key_exists('leaf', $child) || $leafSection === $child['leaf']);
        }

        return $leafSection === last(explode('.', $child['route']))
            || request()->url() === route($child['route']);
    };
?>

<div>
    <!-- Mobile menu toggle button -->
    <input type="checkbox" id="menu-toggle" class="hidden peer">

    <!-- Sidebar -->
    <div class="h-screen min-h-full hidden peer-checked:flex md:flex flex-col w-64 bg-[#2E7D32] transition-all duration-300 ease-in-out">
        <!-- Logo strip lines up with the navbar, so it carries the navbar's own background. -->
        <div class="flex shrink-0 items-center justify-between h-16 bg-base-100 border-b border-base-300 px-4">
            <img class="mx-auto w-28 h-auto rounded-full" src="{{ Vite::asset('resources/images/inec_nig_logo.png') }}" alt="App State Logo">
            <label for="menu-toggle" class="text-base-content cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 lg:hidden" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </label>
        </div>
        <div class="flex flex-col flex-1 overflow-y-auto">
            <nav class="flex-1 pl-3 px-0 py-4 bg-[#2E7D32]">
                @foreach ($menu as $group)
                    @php $groupOpen = $inDashboard && in_array($subSection, $group['sections'], true); @endphp

                    @if (empty($group['children']))
                        <a href="{{ route($group['route']) }}" class="flex items-center px-4 py-2 group mb-3 active:outline-none focus:outline-none {{ $groupOpen ? $currentPageClasses : $defaultPageClasses }}">
                            <x-navigation.icon :name="$group['icon']" />
                            <span>{{ $group['label'] }}</span>
                        </a>
                    @else
                        <details class="group mb-3" @if($groupOpen) open @endif>
                            <summary class="flex cursor-pointer list-none items-center px-4 py-2 [&::-webkit-details-marker]:hidden {{ $groupOpen ? $currentPageClasses : $defaultPageClasses }}">
                                <x-navigation.icon :name="$group['icon']" />
                                <span>{{ $group['label'] }}</span>
                                <x-navigation.icon name="chevron" class="ml-auto h-4 w-4 transition-transform duration-200 group-open:rotate-180" />
                            </summary>
                            {{-- Author-level `display` beats the UA rule that hides a closed <details>, so drive it off group-open instead. --}}
                            <div class="mt-3 ml-7 hidden flex-col group-open:flex">
                                @foreach ($group['children'] as $child)
                                    <a href="{{ route($child['route']) }}" class="flex items-center py-2 pl-4 pr-4 active:outline-none focus:outline-none {{ $isCurrent($child) ? $currentSubPageClasses : $defaultPageClasses }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 mr-2 shrink-0">
                                            <circle cx="12" cy="12" r="5"></circle>
                                        </svg>
                                        <span>{{ $child['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                @endforeach
            </nav>
        </div>
    </div>
</div>
