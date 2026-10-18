<nav aria-label="breadcrumb" class="w-full">
    <ol class="flex w-full flex-wrap items-center gap-1.5 py-2 text-xs">
        <li class="flex cursor-pointer items-center gap-1.5 text-base-content/60 transition-colors duration-300 hover:text-[#2E7D32]">
            <a href="#">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                </svg>
            </a>
            <span class="pointer-events-none text-base-content/30">/</span>
        </li>
        <li class="flex cursor-pointer items-center gap-1.5 {{ $page == 'dashboard' ? 'text-base-content/40' : 'text-base-content/60' }} transition-colors duration-300 hover:text-[#2E7D32]">
            <a href="#">Dashboard</a>
            <span class="pointer-events-none text-base-content/30">/</span>
        </li>
        <li class="flex cursor-pointer items-center gap-1.5 {{ $page == 'profile-settings' ? 'text-base-content/40' : 'text-base-content/60' }} transition-colors duration-300 hover:text-[#2E7D32]">
            <a href="#">Profile Settings</a>
        </li>
    </ol>
</nav>
