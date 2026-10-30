<!-- h-16 + shrink-0 keeps this band the same height as the sidebar's logo strip. -->
<div class="flex shrink-0 items-center justify-between h-16 bg-base-100 border-b border-base-300">
	<div class="flex items-center pl-4 pt-2 pb-2 gap-x-4">
		<label for="menu-toggle"
			class="md:hidden mr-4 bg-[#2E7D32] text-white p-2 rounded focus:outline-none cursor-pointer">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
				viewBox="0 0 24 24" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
					d="M4 6h16M4 12h16M4 18h16" />
			</svg>
		</label>
		<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
			<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
		</svg>
    	<span class="text-base font-semibold text-base-content">Welcome, {{ $name ?? auth()->user()?->firstname ?? 'Guest' }}!</span>
	</div>
	<div class="flex items-center gap-3 pr-4 pt-2 pb-2">
		{{-- What is waiting for this account. The count is of unread notices,
		     so it clears itself as they are dealt with. --}}
		@php $waiting = auth()->check() ? \App\Models\Notification::for(auth()->user())->unread()->count() : 0; @endphp

		<a href="{{ route('notifications') }}" title="Notifications"
		   class="relative inline-flex items-center rounded-md p-2 text-base-content/70 transition hover:bg-base-200 hover:text-base-content">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
			     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"></path>
				<path d="M9 17v1a3 3 0 0 0 6 0v-1"></path>
			</svg>
			@if ($waiting > 0)
				<span class="absolute -right-0.5 -top-0.5 inline-flex min-w-4 items-center justify-center rounded-full bg-error px-1 text-[10px] font-semibold leading-4 text-white">
					{{ $waiting > 9 ? '9+' : $waiting }}
				</span>
			@endif
		</a>

		<div class="relative inline-block text-left">
			<div>
			  	<button onclick="toggleDropDownMenu('.dropdown-menu')" type="button" class="btn btn-sm btn-cta-outline gap-x-1.5" id="menu-button" aria-expanded="false" aria-haspopup="true">
					<span>Options</span>
					<img src="{{ Vite::asset('resources/images/avatars/avatar-1.png') }}" alt="Avatar" class="rounded-full size-5 border bordr-white" />
					<svg class="-mr-1 size-5 text-base-content/40" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
				  		<path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
					</svg>
			  	</button>
			</div>
			<div id="dropdown-menu" class="dropdown-menu hidden absolute right-0 z-10 divide-y divide-neutral-content/20 mt-2 w-56 origin-top-right rounded-md bg-[#2E7D32] ring-1 shadow-lg ring-base-content/10 focus:outline-hidden" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
			  	<div class="py-1" role="none">
					<!-- Active: "bg-base-200 text-base-content outline-hidden", Not Active: "text-neutral-content/80" -->
					<a href="#" class="block items-centerpx-4 py-2 px-3 text-sm text-neutral-content hover:opacity-40 active:bg-opacity-40 focus:bg-opacity-40 hover:text-neutral-content focus:text-neutral-content active:text-neutral-content" role="menuitem" tabindex="-1" id="menu-item-0">
						<div class="flex items-center gap-x-2">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2"> <path d="M19.875 6.27a2.225 2.225 0 0 1 1.125 1.948v7.284c0 .809 -.443 1.555 -1.158 1.948l-6.75 4.27a2.269 2.269 0 0 1 -2.184 0l-6.75 -4.27a2.225 2.225 0 0 1 -1.158 -1.948v-7.285c0 -.809 .443 -1.554 1.158 -1.947l6.75 -3.98a2.33 2.33 0 0 1 2.25 0l6.75 3.98h-.033z"></path> <path d="M15.5 9.422c.312 .18 .503 .515 .5 .876v3.277c0 .364 -.197 .7 -.515 .877l-3 1.922a1 1 0 0 1 -.97 0l-3 -1.922a1 1 0 0 1 -.515 -.876v-3.278c0 -.364 .197 -.7 .514 -.877l3 -1.79c.311 -.174 .69 -.174 1 0l3 1.79h-.014z"></path> </svg>
							<span>Profile settings</span>
						</div>
					</a>
					<a href="#" class="block px-3 py-2 text-sm text-neutral-content hover:opacity-40 active:bg-opacity-40 focus:bg-opacity-40 hover:text-neutral-content focus:text-neutral-content active:text-neutral-content" role="menuitem" tabindex="-1" id="menu-item-1">
						<div class="flex items-center gap-x-2">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
								<path d="M20.993 11.63a9 9 0 0 1 -9.362 9.362l9.362 -9.362z"></path>
								<path d="M12 3a9.001 9.001 0 0 1 8.166 5.211l-11.955 11.955a9 9 0 0 1 3.789 -17.166z"></path>
								<path d="M12 12l-6 6"></path>
							  </svg>
							<span>Analysis</span>
						</div>
					</a>
			  	</div>
				<div class="py-1" role="none">
					<form method="POST" action="{{ route('logout') }}" role="none">
						@csrf
						<button type="submit" class="block w-full px-4 py-2 text-left text-sm text-neutral-content hover:opacity-25 active:bg-opacity-35 focus:bg-opacity-40 hover:text-neutral-content focus:text-neutral-content active:text-neutral-content" role="menuitem" tabindex="-1" id="menu-item-3">Sign out</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

