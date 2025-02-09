<?php

$svg_classes = 'rounded-full w-6 h-6 bg-brown-300 text-white p-1 flex items-center justify-center';
$default_page_classes = ' bg-transparent py-2 px-4 text-center text-sm hover:text-slate-700 transition-all disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none';
$active_page_classes = 'rounded-lg bg-gradient-to-tr from-slate-600 to-slate-700 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md  focus:bg-slate-600 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 hover:border-slate-800 active:border-slate-800 focus:border-slate-800 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none';
?>
<div class="mt-5 flex flex-col gap-y-3">
    <a href="{{ route('docs') }}" class="font-sans flex items-center justify-start space-x-3 {{ $activePage == 'index' ? $active_page_classes : $default_page_classes }}">
        <span >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="{{ $activePage == 'index' ? $svg_classes : '' }}">
                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                <path d="M16 19h6"></path>
                <path d="M19 16v6"></path>
                <path d="M6 21v-2a4 4 0 0 1 4 -4h4"></path>
            </svg>
        </span>
        <span class="">Document Files</span>
    </a>
    <a href="{{ route('passport') }}" class="font-sans flex items-center justify-start space-x-3 {{ $activePage == 'passport' ? $active_page_classes : $default_page_classes }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="{{ $activePage == 'edu-progress' ? $svg_classes : '' }}">
            <path d="M5 4m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z"></path>
            <path d="M9 4m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z"></path>
            <path d="M5 8h4"></path>
            <path d="M9 16h4"></path>
            <path d="M13.803 4.56l2.184 -.53c.562 -.135 1.133 .19 1.282 .732l3.695 13.418a1.02 1.02 0 0 1 -.634 1.219l-.133 .041l-2.184 .53c-.562 .135 -1.133 -.19 -1.282 -.732l-3.695 -13.418a1.02 1.02 0 0 1 .634 -1.219l.133 -.041z"></path>
            <path d="M14 9l4 -1"></path>
            <path d="M16 16l3.923 -.98"></path>
        </svg>
        <span class="">Staff Passport</span>
    </a>
</div>
<div>
    <!-- Life is available only in the present moment. - Thich Nhat Hanh -->
</div>
