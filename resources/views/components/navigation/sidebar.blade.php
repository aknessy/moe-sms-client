<?php
    $currentPageClasses = 'bg-brown-50 text-slate-800 rounded-l-lg';
    $defaultPageClasses = 'text-gray-100 hover:bg-brown-300';
?>

<div>
    <!-- Mobile menu toggle button -->
    <input type="checkbox" id="menu-toggle" class="hidden peer">

    <!-- Sidebar -->
    <div class="h-screen min-h-full hidden peer-checked:flex md:flex flex-col w-64 bg-brown-400 transition-all duration-300 ease-in-out">
        <div class="flex items-center justify-between h-16 bg-brown-500 px-4">
            <img class="mx-auto w-14 h-auto rounded-full" src="{{ Vite::asset('resources/images/app_favicon.png') }}" alt="App State Logo">
            <label for="menu-toggle" class="text-white cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 lg:hidden" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </label>
            <!-- <span class="text-white font-bold uppercase">Sidebar</span> -->
        </div>
        <div class="flex flex-col flex-1 overflow-y-auto">
            <nav class="flex-1 pl-3 px-0 py-4 bg-brown-400 gap-4">
                <a href="{{ url('dashboard') }}" class="flex items-center px-4 py-2 group mb-3 {{ ($segments[1] == 'dashboard' && null == $segments[2]) ? $currentPageClasses : $defaultPageClasses }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 group-hover:transform group-hover:rotate-90" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    Dashboard
                </a>
                <!-- Favourites -->
                <a href="{{ url('dashboard/staff') }}" class="flex items-center px-4 py-2 group mb-3 {{ ($segments[1] == 'dashboard' && $segments[2] == 'staff') ? $currentPageClasses : $defaultPageClasses }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="h-6 w-6 mr-2 group-hover:hidden">
                        <path d="M4 19v-10.5a3.5 3.5 0 1 1 7 0v10.5"></path>
                        <path d="M4 13h7"></path>
                        <path d="M18 9v6"></path>
                        <path d="M21 12h-6"></path>
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="hidden group-hover:block h-6 w-6 mr-2">
                        <path d="M4 19v-10.5a3.5 3.5 0 1 1 7 0v10.5"></path>
                        <path d="M4 13h7"></path>
                        <path d="M21 12h-6"></path>
                    </svg>
                    <span>Add Staff Record</span>
                </a>
                <a href="{{ url('config') }}" class="flex items-center px-4 py-2 group {{ ($segments[1] == 'dashboard' && $segments[2] == 'config') ? $currentPageClasses : $defaultPageClasses }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                         class="h-6 w-6 mr-2 group-hover:hidden">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                         class="hidden group-hover:block h-6 w-6 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 0 1-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 1 1-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 0 1 6.336-4.486l-3.276 3.276a3.004 3.004 0 0 0 2.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.867 19.125h.008v.008h-.008v-.008Z" />
                    </svg>
                    <span>Config</span>
                </a>
            </nav>
        </div>
    </div>
</div>
