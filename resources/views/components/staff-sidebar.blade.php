<?php

    $svg_classes = 'rounded-full w-6 h-6 bg-brown-300 text-white p-1 flex items-center justify-center';
    $default_page_classes = ' bg-transparent py-2 px-4 text-center text-sm hover:text-slate-700 transition-all disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none';
    $active_page_classes = 'rounded-lg bg-gradient-to-tr from-slate-600 to-slate-700 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md  focus:bg-slate-600 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 hover:border-slate-800 active:border-slate-800 focus:border-slate-800 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none';
    ?>
<div class="mt-5 flex flex-col gap-y-3">
    <a href="{{ route('basic-info') }}" class="font-sans flex items-center justify-start space-x-3 {{ $activePage == 'index' ? $active_page_classes : $default_page_classes }}">
        <span >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="{{ $activePage == 'index' ? $svg_classes : '' }}">
                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                <path d="M16 19h6"></path>
                <path d="M19 16v6"></path>
                <path d="M6 21v-2a4 4 0 0 1 4 -4h4"></path>
            </svg>
        </span>
        <span class="">Personal Information</span>
    </a>
    <a href="{{ route('edu-progress') }}" class="font-sans flex items-center justify-start space-x-3 {{ $activePage == 'edu-progress' ? $active_page_classes : $default_page_classes }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="{{ $activePage == 'edu-progress' ? $svg_classes : '' }}">
            <path d="M5 4m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z"></path>
            <path d="M9 4m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z"></path>
            <path d="M5 8h4"></path>
            <path d="M9 16h4"></path>
            <path d="M13.803 4.56l2.184 -.53c.562 -.135 1.133 .19 1.282 .732l3.695 13.418a1.02 1.02 0 0 1 -.634 1.219l-.133 .041l-2.184 .53c-.562 .135 -1.133 -.19 -1.282 -.732l-3.695 -13.418a1.02 1.02 0 0 1 .634 -1.219l.133 -.041z"></path>
            <path d="M14 9l4 -1"></path>
            <path d="M16 16l3.923 -.98"></path>
        </svg>
        <span class="">Educational Progression</span>
    </a>
    <a href="{{ route('promotion') }}" class="font-sans flex items-center justify-start space-x-3 {{ $activePage == 'promotion' ? $active_page_classes : $default_page_classes }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="{{ $activePage == 'promotion' ? $svg_classes : '' }}">
            <path d="M6 4h11a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-11a1 1 0 0 1 -1 -1v-14a1 1 0 0 1 1 -1m3 0v18"></path>
            <path d="M13 8l2 0"></path>
            <path d="M13 12l2 0"></path>
        </svg>
        <span class="">Promotion</span>
    </a>
    <a href="{{ route('career') }}" class="font-sans flex items-center justify-start space-x-3 {{ $activePage == 'career' ? $active_page_classes : $default_page_classes }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="{{ $activePage == 'career' ? $svg_classes : '' }}">
            <path d="M9 5m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"></path>
            <path d="M4 7l1 0"></path>
            <path d="M4 11l1 0"></path>
            <path d="M19 7l1 0"></path>
            <path d="M19 11l1 0"></path>
            <path d="M4 15l16 0"></path>
            <path d="M4 19l16 0"></path>
        </svg>
        <span class="">Working Career</span>
    </a>
    <a href="{{ route('leave-record') }}" class="font-sans flex items-center justify-start space-x-3 {{ $activePage == 'leave-record' ? $active_page_classes : $default_page_classes }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="{{ $activePage == 'leave-record' ? $svg_classes : '' }}">
            <path d="M13 12v.01"></path>
            <path d="M3 21h18"></path>
            <path d="M5 21v-16a2 2 0 0 1 2 -2h7.5m2.5 10.5v7.5"></path>
            <path d="M14 7h7m-3 -3l3 3l-3 3"></path>
        </svg>
        <span class="">Leave Record</span>
    </a>
    <a href="{{route('referee') }}" class="font-sans flex items-center justify-start space-x-3 {{ $activePage == 'referee' ? $active_page_classes : $default_page_classes }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="{{ $activePage == 'referee' ? $svg_classes : '' }}">
            <path d="M4 6l5.5 0"></path>
            <path d="M4 10l5.5 0"></path>
            <path d="M4 14l5.5 0"></path>
            <path d="M4 18l5.5 0"></path>
            <path d="M14.5 6l5.5 0"></path>
            <path d="M14.5 10l5.5 0"></path>
            <path d="M14.5 14l5.5 0"></path>
            <path d="M14.5 18l5.5 0"></path>
        </svg>
        <span class="">Referee(s)</span>
    </a>
    <a href="{{ route('next-of-kin') }}" class="font-sans flex items-center justify-start space-x-3 {{ $activePage == 'next-of-kin' ? $active_page_classes : $default_page_classes }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="{{ $activePage == 'next-of-kin' ? $svg_classes : '' }}">
            <path d="M6 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-14a1 1 0 0 1 1 -1z"></path>
            <path d="M15 12l4 0"></path>
            <path d="M17 10l0 4"></path>
        </svg>
        <span class="">Next of Kin</span>
    </a>
</div>
