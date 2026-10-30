<?php
    use App\Support\Menu;

    // Tab carries the content area's background so the two merge, lifted on every edge but the right.
    $currentPageClasses = 'text-sm font-medium bg-base-200 text-base-content rounded-l-lg shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.18),0_-4px_6px_-2px_rgba(0,0,0,0.18),0_4px_6px_-2px_rgba(0,0,0,0.18)]';
    $defaultPageClasses = 'text-sm font-medium text-white/80 hover:bg-white/10 hover:text-white';
    // Active sub-menu item: a darker shade of the sidebar green, so it stays
    // subordinate to the parent's content-coloured tab.
    $currentSubPageClasses = 'text-sm font-medium bg-[#1B5E20]/30 text-white rounded-l-lg';

    // What this account may see. An officer on the `User` role gets their own
    // corner of the system; everyone else still gets the whole menu.
    $menu = Menu::forUser(auth()->user());

    // Which entry is current is read off the route being served, not off the
    // url: the self-service pages all sit under one prefix, so segments can no
    // longer tell them apart.
    $currentRoute = request()->route()?->getName();

    // An entry is current when the route being served is its own, or one of the
    // sibling routes that belong to the same page.
    $isCurrent = fn (array $item) => $currentRoute !== null
        && in_array($currentRoute, array_merge(
            isset($item['route']) ? [$item['route']] : [],
            $item['also'] ?? [],
        ), true);
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
                    {{-- A group is open when the page being served is its own or one of its children's. --}}
                    @php
                        $groupOpen = $isCurrent($group)
                            || collect($group['children'] ?? [])->contains(fn ($child) => $isCurrent($child));
                    @endphp

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
