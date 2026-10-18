@props(['title', 'description' => '', 'crumb' => null])

<div class="w-full flex flex-col bg-transparent">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <nav aria-label="breadcrumb" class="w-full sm:w-1/3 space-y-2">
            <ol class="flex w-full flex-wrap items-center gap-1.5 py-2 text-xs">
                <li class="flex cursor-pointer items-center gap-1.5 text-base-content/60 transition-colors duration-300 hover:text-[#2E7D32]">
                    <a href="{{ url('dashboard') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                    </a>
                    <span class="pointer-events-none text-base-content/30">/</span>
                </li>
                <li class="flex items-center text-base-content/60 transition-colors duration-300 cursor-not-allowed">
                    <a href="#">{{ $crumb ?? $title }}</a>
                </li>
            </ol>
        </nav>
        <div class="w-full sm:w-2/3">
            <h5 class="font-alkalami text-sm font-semibold uppercase tracking-wide flex justify-end m-0">{{ $title }}</h5>
        </div>
    </div>
    <div class="py-4">
        <div class="w-full px-4 py-2 bg-white border border-base-300 rounded-lg">
            <div class="flex flex-col items-start border-b border-base-300">
                <h2 class="font-alkalami text-base font-semibold mb-0 text-base-content/80">{{ $title }}</h2>
                <p class="font-sans text-sm font-normal text-base-content/60 m-0">{{ $description }}</p>
            </div>
            <div class="py-12 text-center">
                <p class="text-sm text-base-content/60 m-0">This section is routed and ready — its content hasn't been built yet.</p>
            </div>
        </div>
    </div>
</div>
