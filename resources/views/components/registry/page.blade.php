@props(['title', 'description' => ''])

<div class="w-full flex flex-col bg-transparent">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <nav aria-label="breadcrumb" class="w-full sm:w-1/3">
            <ol class="flex w-full flex-wrap items-center gap-1.5 py-2 text-xs">
                <li class="flex cursor-pointer items-center gap-1.5 text-base-content/60 transition-colors duration-300 hover:text-[#2E7D32]">
                    <a href="{{ route('dashboard') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                    </a>
                    <span class="pointer-events-none text-base-content/30">/</span>
                </li>
                <li class="flex items-center text-base-content/60 cursor-not-allowed">Registry</li>
            </ol>
        </nav>
        <div class="w-full sm:w-2/3">
            <h5 class="font-alkalami text-sm font-semibold uppercase tracking-wide flex justify-end m-0">{{ $title }}</h5>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-3 rounded-md border border-[#2E7D32]/40 bg-[#2E7D32]/10 px-4 py-3 text-sm text-[#1B5E20]">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-3 rounded-md border border-error/40 bg-error/10 px-4 py-3 text-sm text-error">
            <p class="m-0 font-semibold">Please correct the following:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{ $slot }}
</div>
