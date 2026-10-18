@props(['name', 'class' => 'h-5 w-5 mr-2'])

{{-- Tabler outline icons, drawn at stroke-width 2 to match the rest of the sidebar. --}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="{{ $class }} shrink-0">
    @switch($name)
        @case('dashboard')
            {{-- layout-dashboard: panel grid, rather than the hamburger this used to borrow --}}
            <path d="M4 4h6v8h-6z"></path>
            <path d="M4 16h6v4h-6z"></path>
            <path d="M14 12h6v8h-6z"></path>
            <path d="M14 4h6v4h-6z"></path>
            @break

        @case('users')
            <path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path>
            <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            <path d="M21 21v-2a4 4 0 0 0 -3 -3.85"></path>
            @break

        @case('archive')
            <path d="M3 4m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v1a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"></path>
            <path d="M5 9v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-9"></path>
            <path d="M10 13h4"></path>
            @break

        @case('wallet')
            <path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12"></path>
            <path d="M20 12v4h-4a2 2 0 0 1 0 -4h4"></path>
            @break

        @case('fingerprint')
            <path d="M18.9 7a8 8 0 0 1 1.1 5v1a6 6 0 0 0 .8 3"></path>
            <path d="M8 11a4 4 0 0 1 8 0v1a10 10 0 0 0 2 6"></path>
            <path d="M12 11v2a14 14 0 0 0 2.5 8"></path>
            <path d="M8 15a18 18 0 0 0 1.8 6"></path>
            <path d="M4.9 19a22 22 0 0 1 -.9 -7v-1a8 8 0 0 1 12 -6.95"></path>
            @break

        @case('chart')
            <path d="M3 3v18h18"></path>
            <path d="M9 17v-5"></path>
            <path d="M13 17v-9"></path>
            <path d="M17 17v-3"></path>
            @break

        @case('settings')
            <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"></path>
            <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
            @break

        @case('chevron')
            <path d="M6 9l6 6l6 -6"></path>
            @break
    @endswitch
</svg>
